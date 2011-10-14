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
 * @Column(id, pk, type="integer")
 * @Column(delivery)
 * @Column(payment)
 * @Column(items, type="OneToMany", entity="vStore\Shop\OrderItem", joinOn="id=order")
 * @Column(customer, type="OneToOne", entity="vStore\Shop\CustomerInfo", joinOn="customer=id")
 * @Column(address, type="OneToOne", entity="vStore\Shop\ShippingAddress", joinOn="address=id")
 * @Column(note)
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class Order extends vBuilder\Orm\ActiveEntity {
	
	private $_total;
	private $_totalProducts;
	private $_amountProducts;
	
	/**
	 * Constructor
	 * 
	 * @param array of data 
	 */
	public function __construct($data = array()) {
		call_user_func_array(array('parent', '__construct'), func_get_args()); 
		
		//$this->context->sessionRepository->clear();
		
		$this->defaultGetter('items')->onItemAdded[] = array($this, 'invalidateCartInfo');
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
		
		$item = new OrderItem($this->context);
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
		$var = $onlyProducts ? '_totalProducts' : '_total';
		
		if(!isset($this->{$var})) $this->calculateCartInfo();
		return $this->{$var};
	}
	
	/**
	 * Returns ordered items
	 * 
	 * @param bool true if only product items should be returned
	 * @return array|EntityCollection depending on parameter 
	 */
	public function getItems($onlyProducts = false) {
		$items = $this->defaultGetter('items');
				
		if($onlyProducts == false) return $items;
		
		return array_filter($items->toArray(), function($item) {
			if($item->productId > 0) return true;
			
			return false;
		});
	}
	
	/**
	 * Returns number of products in the cart
	 * 
	 * @return int
	 */
	public function getAmount() {
		if(!isset($this->_amountProducts)) $this->calculateCartInfo();
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
	 * Calculates items in the cart
	 */
	protected function calculateCartInfo() {
		$this->_total = 0.0;
		$this->_totalProducts = 0.0;
		$this->_amountProducts = 0;
		
		foreach($this->items as $curr) {
			//dd($curr->productId, $curr->name, $curr->amount, $curr->price);
			
			if($curr->productId > 0) {
				$this->_amountProducts += $curr->amount;
				$this->_totalProducts += $curr->amount * $curr->price;
			}
			
			$this->_total += $curr->amount * $curr->price;
		}
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