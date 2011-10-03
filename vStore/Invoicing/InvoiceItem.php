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

namespace vStore\Invoicing;

use vBuilder,
		vStore;

/**
 * Basic invoice item implementation
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
class InvoiceItem extends vBuilder\Object implements IInvoiceItem {
	
	/** @var string item name */
	protected $name;
	
	/** @var float price per item */
	protected $price;
	
	/** @var float number of items */
	protected $amount;
	
	/** @var string unit name */
	protected $unit;
	
	/** @var int tax percentage */
	protected $tax;
	
	/** @var bool true if price is taxed */
	protected $priceIsTaxed;
	
	function __construct($name, $price, $amount = 1, $unit = 'ks', $tax = null, $priceIsTaxed = true) {
		$this->name = $name;
		$this->price = $price;
		$this->amount = max(1, (float) $amount);
		$this->unit = $unit;
		$this->tax = $tax !== null ? max(0, min($tax, 100)) : null;
		$this->priceIsTaxed = $priceIsTaxed;
	}
	
	/**
	 * @return string
	 */
	function getName() {
		return $this->name;
	}
	
	/**
	 * @return int
	 */
	function getAmount() {
		return $this->amount;
	}
	
	/**
	 * @return string
	 */
	function getUnit() {
		return $this->unit;
	}
	
	/**
	 * @return float
	 */
	function getPrice() {
		if($this->priceIsTaxed) return $this->price;
		
		if($this->tax === null) throw new \LogicException("Cannot compute taxed price without a tax.");
		return $this->price * ($this->tax + 100);
	}
	
	/**
	 * @return float
	 */
	function getPriceUntaxed() {
		if(!$this->priceIsTaxed) return $this->price;
		
		if($this->tax === null) throw new \LogicException("Cannot compute untaxed price without a tax.");
		return $this->price * (100 - $this->tax);
	}
	
	/**
	 * @return float
	 */
	function getTotal() {
		return $this->getAmount() * $this->getPrice();
	}
	
	/**
	 * @return float
	 */
	function getTotalUntaxed() {
		return $this->getAmount() * $this->getPriceUntaxed();
	}
	
	/**
	 * @return int|null percentage
	 */
	function getTaxPercentage() {
		return $this->tax;
	}
	
}
