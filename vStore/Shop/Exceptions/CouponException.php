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

/**
 * Exception for invalid discount coupon handling
 *
 * @author Adam Staněk (velbloud)
 * @since Dec 31, 2011
 */
class CouponException extends \Exception {

	const NOT_FOUND = 1;
	
	const USED = 2;
	const NOT_ACTIVE_YET = 3;
	const EXPIRED = 4;
	
	const CONDITION_NOT_MET = 5;
	
	/** @var Coupon coupon instance */
	protected $_coupon;	
	
	public function __construct($msg, $code = null, $coupon = null) {
		$this->_coupon = $coupon;
		
		parent::__construct($msg, $code);
	}
	
	public function getCoupon() {
		return $this->_coupon;
	}
	
}