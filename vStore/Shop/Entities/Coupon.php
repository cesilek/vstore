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
 * Shop coupon
 *
 * @Table(name="shop_coupons")
 * 
 * @Column(id, pk)
 * @Column(validSince, type="DateTime")
 * @Column(validUntil, type="DateTime")
 * @Column(type)
 * @Column(value, type="float")
 * @Column(requiredProductId, type="integer")
 * 
 * @author Adam Staněk (velbloud)
 * @since Dec 9, 2011
 */
class Coupon extends vBuilder\Orm\ActiveEntity {
	
	const TYPE_PERCENTAGE = 'percentage';
	const TYPE_REBATE = 'rebate';
	
	private $_boundOrderId = null;
	
	/**
	 * Returns true if coupon has been used already
	 *
	 * @return bool
	 */
	function isUsed() {
		return $this->boundOrderId !== null;
	}
	
	/**
	 * Return ID of order in which has been this coupon used or NULL if it 
	 * has not been used yet.
	 *
	 * @return null|int order id or null
	 */
	function getBoundOrderId() {
		if($this->_boundOrderId === null) {
			$item = $this->context->repository->findAll('vStore\\Shop\\CouponDiscountOrderItem')
			->where('[params] = %s', json_encode(array(
				'discountCode' => $this->id
			)))->fetch();
			
			if($item === false) $this->_boundOrderId = false;
			else $this->_boundOrderId = $item->orderId;
		}
		
		return ($this->_boundOrderId !== false) ? $this->_boundOrderId : null;
	}
	
	/**
	 * Returns true if coupon is active (has been activated and is not expired)
	 *
	 * @return bool
	 */
	function isActive() {
		return ($this->validSince->getTimestamp() <= time()) && !$this->isExpired();
	}
	
	/**
	 * Returns true if coupon has expired
	 *
	 * @return bool
	 */
	function isExpired() {
		return $this->validUntil->getTimestamp() < time();
	}
	
}
