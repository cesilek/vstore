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
 * Order item for discount coupons
 * 
 * @author Adam Staněk (velbloud)
 * @since Dec 30, 2011
 */
class CouponDiscountOrderItem extends DynamicOrderItem {

	/** @var Coupon coupon */
	protected $_coupon;

	/**
	 * Sets discount code for a coupon
	 *
	 * @param string coupon code
	 */
	public function setDiscountCode($code) {
		$this->params = array('discountCode' => $code);
	}

	/**
	 * Returns assigned coupon
	 *
	 * @return Coupon
	 */
	public function getCoupon() {
		if(!isset($this->_coupon)) {
			$params = $this->params->toArray();
			if(!isset($params['discountCode'])) throw new Nette\InvalidStateException('Discount code parameter is not set for CouponDiscountOrderItem');
			
			$coupon = $this->context->persistentRepository->findAll(Order::COUPON_ENTITY)->where('[id] = %s', $params['discountCode'])->fetch();
			if($coupon === false) throw new Nette\InvalidStateException('Discount coupon with code '.var_export($params['discountCode'], true).' does not exist although it is bound to CuponDiscountOrderItem');
			
			$this->_coupon = $coupon;
		}
		
		return $this->_coupon;
	}
	
	/**
	 * Returns product id (coupon item has fixed ID)
	 * 
	 * @return int 
	 */
	public function getProductId() {
		return Order::COUPON_ITEM_ID;
	}
	
	/**
	 * Returns item name
	 * 
	 * @return type 
	 */
	public function getName() {
		if($this->coupon->type == Coupon::TYPE_PERCENTAGE)
			return "Slevový kupón (" . $this->coupon->value . "%)";
		elseif($this->coupon->type == Coupon::TYPE_REBATE)
			return "Slevový kupón (" . $this->coupon->value . " Kč)";
	
		return "Slevový kupón";
	}
	
	/**
	 * Computes price for this item
	 * 
	 * @return float
	 */
	protected function gatherPrice() {
		$discount = 0;
		$orderProductTotal = $this->order->getTotal(true);
	
		if($this->coupon->type == Coupon::TYPE_PERCENTAGE) {
			$discount = ($this->coupon->value / 100) * $orderProductTotal;
			
		} elseif($this->coupon->type == Coupon::TYPE_REBATE) {
			$discount = $this->coupon->value;
		}
		
		return 0 - min($discount, $orderProductTotal);
	}
	
	/**
	 * Discount item has to come up even in cart listing
	 * 
	 * @param bool true, if we are quering for cart table, false for general order table
	 *
	 * @return bool 
	 */
	public function isVisible($cartMode = false) {
		return true;
	}
		
}