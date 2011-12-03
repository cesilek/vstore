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
 * Dynamic discount item based on time schedule from shop_scheduledDiscounts
 * table
 *  
 * @author Adam Staněk (velbloud)
 * @since Oct 22, 2011
 */
class ScheduledDiscountOrderItem extends DynamicOrderItem {
	
	const TABLE_NAME = 'shop_scheduledDiscounts';
	
	private $isLoaded = false;
	
	/** @var int percentage discount value */
	private $_percentageDiscount;
	
	/**
	 * Returns true if item should be displayed in order item tables
	 * 
	 * @return bool 
	 */
	public function isVisible() {
		return $this->getPrice() != 0;
	}
	
	/**
	 * Returns product id (delivery items have fixed ID)
	 * 
	 * @return int 
	 */
	public function getProductId() {
		return Order::DISCOUNT_ITEM_ID;
	}
	
	/**
	 * Returns item name
	 * 
	 * @return type 
	 */
	public function getName() {
		if(!$this->isLoaded) $this->internalLoad();
		
		if($this->_percentageDiscount > 0)
			return "Sleva (" .$this->_percentageDiscount. "%)";
		
		return "Sleva";
	}
	
	
	/**
	 * Computes price for this item
	 * 
	 * @return float
	 */
	protected function gatherPrice() {
		if(!$this->isLoaded) $this->internalLoad();
		
		if($this->_percentageDiscount > 0) {
			$orderProductTotal = $this->order->getTotal(true);
			return 0 - ($this->_percentageDiscount / 100) * $orderProductTotal;
		}
		
		return 0;
	}
	
	/**
	 * Loads discount from db
	 */
	protected function internalLoad() {
		$this->isLoaded = true;
		$db = $this->context->connection;
		
		$ds = $db->select('[percentageDiscount]')->from(self::TABLE_NAME)->where('[until] >= NOW() OR [until] IS NULL');
		
		if($this->context->user->isLoggedIn()) {
			$ds->where('([user] = 0 OR [user] = %i)', $this->context->user->getId());
		} else
			$ds->where('[user] = 0');
		
		$ds->orderBy('[percentageDiscount] DESC');
		
		$this->_percentageDiscount = (int) $ds->fetchSingle();
	}
		
}