<?php

/**
 * This file is part of vStore
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vstore.cz
 * 
 * vStore is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vStore is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vStore bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vStore\Shop;

use vStore,
		vBuilder,
		Nette;

/**
 * Basic order implementation
 *
 * @warning When adding more OrderItem implementations flushing the cache is necessary!
 *
 * @Table(name="shop_orders")
 * 
 * @Column(id, pk, type="integer")
 * @Column(delivery)
 * @Column(payment)
 * @Column(items, type="OneToMany", entity="vStore\Shop\OrderItem", joinOn="id=orderId", processSubclasses="true")
 * @Column(user, type="OneToOne", entity="vBuilder\Security\User", joinOn="user=id")
 * @Column(customer, type="OneToOne", entity="vStore\Shop\CustomerInfo", joinOn="customer=id")
 * @Column(company, type="OneToOne", entity="vStore\Shop\Company", joinOn="company=id")
 * @Column(address, type="OneToOne", entity="vStore\Shop\ShippingAddress", joinOn="address=id")
 * @Column(note)
 * @Column(timestamp, type="CreatedDateTime")
 * @Column(state)
 * @Column(lastStateAuthor, type="OneToOne", entity="vBuilder\Security\User", joinOn="lastStateAuthor=id")
 * @Column(lastStateTime, type="DateTime")
 * @Column(isPaid, type="boolean")
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class Order extends vBuilder\Orm\ActiveEntity {
	
	const COUPON_ENTITY = 'vStore\\Shop\\Coupon';
	
	const DELIVERY_ITEM_ID = -1;
	const PAYMENT_ITEM_ID = -2;
	const DISCOUNT_ITEM_ID = -3;
	const COUPON_ITEM_ID = -4;
	
	const STATE_NEW = 0;
	const STATE_DONE = 1;
	const STATE_CANCELED = 2;
	const STATE_MARKED = 3;
	
	private $_total;
	private $_totalProducts;
	private $_amountProducts;
	
	private $_calculateCartInfoLock = false;
	private $_calculateProductInfoLock = false;
	
	protected $_orderSent = false;
	protected $_itemsAltered = false;
	
	/**
	 * Constructor
	 * 
	 * @param array of data 
	 */
	public function __construct($data = array()) {
		call_user_func_array(array('parent', '__construct'), func_get_args()); 
		
		// TODO: Remove? Zmena mnozstvi? Je to vubec potreba (redirect)?
		$this->defaultGetter('items')->onItemAdded[] = array($this, 'invalidateCartInfo');
	}
	
	/**
	 * Stores this order in DB
	 */
	public function send() {
		if($this->orderSent())
				throw new Nette\InvalidStateException('Order has been already stored in DB');
		
		$this->_orderSent = true;
		
		$this->load();		
		
		$db = $this->context->connection;
		$persistentRepo = Nette\Environment::getContext()->repository;
		$user = $this->context->user;
		
		$this->onPreSave[] = function ($e) use ($db, $persistentRepo, $user) {
			$table = $e->getMetadata()->getTableName();
			$db->query("LOCK TABLES [" . $table . "] WRITE");
			$db->begin();
			
			$date = new \DateTime;
			$monthPrefix = $date->format('Ym');
			$e->data->timestamp = $date->format('Y-m-d H:i:s');
			
			// Pred ulozenim odstranim schovane nulove polozky (nulove slevy atd.)
			// Zaroven to vyvola load => musim nacist polozky,
			// protoze jakmile se zmeni ID, nemam je podle ceho svazat
			foreach($e->items as $curr) {
				if(!$curr->isVisible() && $curr->getPrice() == 0) {
					$e->items->remove($curr);
				}
			}
			
			$id = $db->select('MAX(id)')->from($table)->where('SUBSTRING(id, 1, 6) = %s', $monthPrefix)->fetchSingle();			
			$e->data->id = $monthPrefix . ($id == null ? 1 : substr($id, 6) + 1);
			
			$e->user = $user->isLoggedIn()
				? $user->getIdentity()
				: null;

		};
		
		$this->onPostSave[] = function ($e) use ($db) {			
			$db->query("UNLOCK TABLES");
		};
		
		$persistentRepo->save($this);		
		
		// Odstranim polozky ze session objednavky
		$orderedProducts = $this->context->sessionRepository->findAll('vStore\\Shop\\OrderItem', true);
		foreach($orderedProducts as $curr) $curr->delete();
		
		// Vyresetuju objednavku (Ulozi to destruktor)
		list($sessionOrder) = $this->context->sessionRepository->findAll('vStore\\Shop\\Order', true)->fetchAll();
		$sessionOrder->isPaid = false;
		$sessionOrder->save();

		
		// Zavolam vsechny listenery (pouziju pro jistotu jiz ulozenou objednavku)
		$this->context->shop->onOrderCreated($this->context->shop->getOrder($this->id));
	}
	
	/**
	 * Returns true, if order has been sent already
	 * 
	 * @return bool
	 */
	public function orderSent() {
		if($this->repository instanceof vBuilder\Orm\SessionRepository)
			return $this->_orderSent;
		
		return true;
	}
	
	/**
	 * Add discount coupon to order
	 *
	 * @param string coupon code
	 * @throws CouponException if coupon is not valid
	 */
	public function setDiscountCode($code) {
		$coupon = $this->context->persistentRepository->findAll(self::COUPON_ENTITY)->where('[id] = %s', $code)->fetch();
		
		// Kontrola existence kodu
		if($coupon === false) throw new CouponException("Coupon with code '$code' does not exist", CouponException::NOT_FOUND);

		// Kontrola, jestli kod uz nebyl pouzit
		if($coupon->isUsed()) throw new CouponException("Coupon with code '$code' has already been used", CouponException::USED, $coupon);
		
		// Kontrola, jestli kod uz neni po expiraci
		if($coupon->isExpired()) throw new CouponException("Coupon with code '$code' has expired", CouponException::EXPIRED, $coupon);
		
		// Kontrola, jestli kod uz je aktivni
		if(!$coupon->isActive()) throw new CouponException("Coupon with code '$code' is not active yet", CouponException::NOT_ACTIVE_YET, $coupon);

		// Kontrola podminky na produkt		
		if($coupon->requiredProductId) {
			// Musi se osetrit odebirani produktu po aplikaci slevy
			throw new CouponException("Slevové kupóny vázané na konkrétní produkt v současné době nejsou podporovány. Kontaktujte prosím obchodníka.");
		
			/* 
			if($this->getItemWithId($coupon->requiredProductId) === null)
				throw new CouponException("Coupon with code '$code' is only appliable in orders containing product id " . var_export($coupon->requiredProductId, true), CouponException::CONDITION_NOT_MET, $coupon); */
		}
		
		$item = $this->repository->create('vStore\\Shop\\CouponDiscountOrderItem');
		$item->setDiscountCode($code);
		$this->replaceItem($item);
		
		// Pokud mame kupon nesmime nastavovat slevovy program
		$this->removeItemWithId(self::DISCOUNT_ITEM_ID);
	}
	
	/**
	 * Returns true if order has any discount coupon applied
	 *
	 * @return bool
	 */
	public function hasDiscountCode() {
		return $this->getItemWithId(self::COUPON_ITEM_ID) !== null;
	}
	
	/**
	 * Adds product to cart
	 * 
	 * @param IProduct product
	 * @param int amount
	 * @param array of product params (associative - color, size, etc.)
	 */
	public function addProduct(IProduct $product, $amount = 1, array $params = array()) {
		if(!is_int($amount) || $amount < 1) {
			throw new Nette\InvalidArgumentException("The second argument passed to addProduct() must be an integer greater than zero. '".gettype($amount)."' given.");
		}
		
		foreach($this->items as $item) {
			if($item->productId == $product->getProductId() && (($item->params === null && count($params) == 0) || ($item->params && $item->params->toArray() == $params))) {
				$item->amount += $amount;
				$this->invalidateCartInfo();
				return ;
			}
		}
		
		$item = $this->repository->create('vStore\\Shop\\OrderItem');
		$item->name = $product->getTitle();
		$item->price = $product->getEffectivePrice();
		$item->productId = $product->getProductId();
		$item->amount = $amount;
		$item->params = $params;
		
		$this->items->add($item);
	}
		
	/**
	 * Returns total value of items in the cart
	 * 
	 * @param bool only products (without postal charges, etc..)? 
	 * @return float 
	 */
	public function getTotal($onlyProducts = false) {
		if($onlyProducts) {
			$var = '_totalProducts';
			if(!isset($this->{$var})) $this->calculateProductInfo();
		} else {
			$var = '_total';
			if(!isset($this->{$var})) $this->calculateCartInfo();
		}

		return $this->{$var};
	}
	
	/**
	 * Returns order total ceiling
	 *
	 * @return float
	 */
	public function getCeiling() {
		$total = $this->getTotal();
		
		return ceil($total) - $total;
	}
	
	/**
	 * Returns ordered items
	 * 
	 * @param bool true if only product items should be returned
	 * @return array|EntityCollection depending on parameter 
	 */
	public function getItems($onlyProducts = false) {
		$items = $this->defaultGetter('items');
		
		// Nesmim polozku pridat vice jak jednou, kvuli pripadnym deletum
		if(!$this->_itemsAltered && $this->repository instanceof vBuilder\Orm\SessionRepository) {
			$this->_itemsAltered = true;
			
			if($this->context->config->get('shop.scheduledDiscounts.enabled', false) && !$this->hasDiscountCode()) {
				if($this->getItemWithId(self::DISCOUNT_ITEM_ID) === null) {
					$items->add($this->repository->create('vStore\\Shop\\ScheduledDiscountOrderItem'));
				}
			}
		}
		
		if($onlyProducts == false) return $items;
		
		return array_filter($items->toArray(), function($item) {
			if($item->productId > 0) return true;
			
			return false;
		});
	}
	
	/**
	 * Inserts order item on product id position, if such item exists it is replaced.
	 * 
	 * @param OrderItem $newItem 
	 */
	protected function replaceItem(OrderItem $newItem) {
		$items = $this->defaultGetter('items');
		
		foreach($items as $item) {
			if($item->productId == $newItem->productId) {
				if($newItem !== $item) {
					$items->remove($item);
					$items->add($newItem);
					$this->invalidateCartInfo();
				}
				
				return ;
			}
		}
		
		$items->add($newItem);
		$this->invalidateCartInfo();
	}
	
	/**
	 * Removes order item with product id
	 * 
	 * @param int product id
	 */
	protected function removeItemWithId($productId) {
		$item = $this->getItemWithId($productId);
		if($item) {
			$item->delete();
			$this->invalidateCartInfo();
		}
	}
	
	/**
	 * Finds order item with product id
	 * 
	 * @param int product id
	 * 
	 * @return OrderItem|null
	 */
	public function getItemWithId($productId) {
		$items = $this->defaultGetter('items');
		
		foreach($items as $item) {
			if($item->productId == $productId) {
				return $item;
			}
		}
		
		return null;
	}
	
	/**
	 * Returns number of products in the cart
	 * 
	 * @return int
	 */
	public function getAmount() {
		if(!isset($this->_amountProducts)) $this->calculateProductInfo();
		return $this->_amountProducts;
	}
	
	/**
	 * Invalidates current info about cart items
	 * so next time any info is reqested, new calculation is done.
	 */
	public function invalidateCartInfo() {
		$this->_total = null;
		$this->_totalProducts = null;
		$this->_amountProducts = null;
	}
	
	/**
	 * Calculates PRODUCT items in the cart
	 * 
	 * Calculation is separated from total cart info because postal charges and etc. are
	 * often dynamic items calculated using product total.
	 */
	protected function calculateProductInfo() {
		
		// Check pro dynamicke polozky, aby nemohly zaviset na vlastnim vypoctu
		if($this->_calculateProductInfoLock)
			throw new Nette\InvalidStateException(get_called_class() . "::calculateProductInfo() can't be called while calculation is already in progress. Infinite loop detected.");
		
		$this->_calculateProductInfoLock = true;
		
		// Aby pripadne volani externich metod nemohlo pristupovat
		// k mezisouctum, musim si to ukladat vedle a az potom prenest do tridni promenne		
		$totalProducts = 0.0;
		$amountProducts = 0;
		
		foreach($this->items as $curr) {
			
			if($curr->productId > 0) {
				$amountProducts += $curr->getAmount();
				$totalProducts += $curr->getAmount() * $curr->getPrice();
			}
			
		}
		
		$this->_totalProducts = $totalProducts;
		$this->_amountProducts = $amountProducts;
		$this->_calculateProductInfoLock = false;
	}
	
	/**
	 * Calculates items in the cart
	 */
	protected function calculateCartInfo() {
		
		// Check pro dynamicke polozky, aby nemohly zaviset na vlastnim vypoctu
		if($this->_calculateCartInfoLock)
			throw new Nette\InvalidStateException(get_called_class() . "::calculateCartInfo() can't be called while calculation is already in progress. Infinite loop detected.");
		
		$this->_calculateCartInfoLock = true;
		
		
		$total = $this->getTotal(true);
		
		// Spocitam ne-produkty
		foreach($this->items as $curr) {
			
			if($curr->productId < 1) {
				$total += $curr->getAmount() * $curr->getPrice();
			}
						
		}
		
		$this->_total = $total;
		$this->_calculateCartInfoLock = false;
	}
	
	// <editor-fold defaultstate="collapsed" desc="Payment / Delivery">
	
	/**
	 * Returns delivery method
	 * 
	 * @return IDeliveryMethod
	 */
	function getDelivery() {
		if(($cached = $this->fieldCache("delivery")) !== null) return $cached;
 
		$value = $this->context->shop->getDeliveryMethod($this->data->delivery);
		
		return $this->fieldCache("delivery", $value);
	}
	
	/**
	 * Sets delivery method
	 * 
	 * @param IDeliveryMethod method
	 * 
	 * @throws OrderException if methods are not meant to be used together
	 */
	function setDelivery(IDeliveryMethod $method) {
		$this->checkDeliveryPayment($method, $this->payment);
		
		$this->data->delivery = $method->getId();
		
		$deliveryItem = $method->createOrderItem($this);
		if($deliveryItem) $this->replaceItem($deliveryItem);
		else $this->removeItemWithId(self::DELIVERY_ITEM_ID);
	}
	
	/**
	 * Returns delivery method
	 * 
	 * @return IPaymentMethod
	 */
	function getPayment() {
		if(($cached = $this->fieldCache("payment")) !== null) return $cached;
 
		$value = $this->context->shop->getPaymentMethod($this->data->payment);
 
		return $this->fieldCache("payment", $value);
	}
	
	/**
	 * Sets payment method
	 * 
	 * @param IPaymentMethod method
	 * 
	 * @throws OrderException if methods are not meant to be used together
	 */
	function setPayment(IPaymentMethod $method) {
		$this->checkDeliveryPayment($this->delivery, $method);
		
		$this->data->payment = $method->getId();
		
		$paymentItem = $method->createOrderItem($this);
		if($paymentItem) $this->replaceItem($paymentItem);
		else $this->removeItemWithId(self::PAYMENT_ITEM_ID);
	}
	
	/**
	 * Sets both delivery and payment to avoid dependency hell
	 * 
	 * @param IDeliveryMethod delivery
	 * @param IPaymentMethod payment
	 * 
	 * @throws OrderException if methods are not meant to be used together
	 */
	function setDeliveryAndPayment(IDeliveryMethod $delivery, IPaymentMethod $payment) {
		$this->checkDeliveryPayment($delivery, $payment);
		
		$this->data->delivery = $delivery->getId();
		$this->data->payment = $payment->getId();
		
		$deliveryItem = $delivery->createOrderItem($this);
		if($deliveryItem) $this->replaceItem($deliveryItem);
		else $this->removeItemWithId(self::DELIVERY_ITEM_ID);
		
		$paymentItem = $payment->createOrderItem($this);
		if($paymentItem) $this->replaceItem($paymentItem);
		else $this->removeItemWithId(self::PAYMENT_ITEM_ID);
	}
	
	/**
	 * Checks if payment is suitable for selected delivery
	 * 
	 * @param IDeliveryMethod delivery method
	 * @param IPaymentMethod payment method
	 * @param bool throw exception?
	 * @return bool
	 * @throws OrderException 
	 */
	protected function checkDeliveryPayment($delivery, $payment, $throw = true) {
		if(!isset($delivery) || !isset($payment)) return true;
		if($delivery->isSuitableWith($payment)) return true;
		
		if($throw)
			throw new OrderException("Delivery '".$delivery->getId()."' is unsuitable with payment '".$payment->getId()."'", OrderException::UNSUITABLE_DELIVERY_PAYMENT);
		
		return false;
	}
	
	// </editor-fold>	
	

	
}