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
 * Order item for payment methods
 * 
 * @author Adam Staněk (velbloud)
 * @since Jan 7, 2011
 */
class PaymentOrderItem extends OrderItem {
	
	public function __construct() {
		call_user_func_array(array('parent', '__construct'), func_get_args()); 
		
		$this->name = 'Dobírečné';
		$this->productId = Order::PAYMENT_ITEM_ID;
		$this->amount = 1;
	}	

	/**
	 * Returns true if item should be displayed in order item tables
	 * 
	 * @param bool true, if we are quering for cart table, false for general order table
	 *
	 * @return bool 
	 */
	public function isVisible($cartMode = false) {
		return !$cartMode;
	}
		
}