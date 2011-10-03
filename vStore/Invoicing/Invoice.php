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
 * Base invoice class
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
abstract class Invoice extends vBuilder\Object implements IInvoice {
	
	/**
	 * @return string
	 */
	function getAuthor() {
		return null;
	}
	
	/**
	 * @return int
	 */
	function getVarSymbol() {
		return (int) preg_replace('/[^0-9]+/', '', $this->id);
	}
	
	/**
	 * @return int
	 */
	function getConstSymbol() {
		return 308;
	}
	
	/**
	 * @return int
	 */
	function getSpecificSymbol() {
		return (int) $this->customer->in;
	}
	
	/**
	 * @return \DateTime
	 */
	function getDueDate() {
		return $this->getIssuanceDate()->modify('+ 21 day');
	}
	
	/**
	 * @return \DateTime
	 */
	function getRevenueRecognitionDate() {
		return $this->getIssuanceDate();
	}
	
	/**
	 * @return float 
	 */
	function getRounding() {
		return ceil($this->getTotalBeforeRounding()) - $this->getTotalBeforeRounding();
	}
	
	/**
	 * @return float
	 */
	function getTotalBeforeRounding() {
		$total = 0;
		foreach($this->getItems() as $item)
			$total += $item->total;
		
		return $total;
	}
	
	/**
	 * @return int 
	 */
	function getTotal() {
		return $this->getTotalBeforeRounding() + $this->getRounding();
	}
	
}
