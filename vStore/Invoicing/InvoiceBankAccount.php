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
 * Bank account info for invocing usage
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
class InvoiceBankAccount extends vBuilder\Object implements IInvoiceBankAccount {
	
	/** @var string account number (if including prefix then string) */
	protected $accountNumber;
	
	/** @var int code of the bank */
	protected $bankCode;
	
	/** @var string name of the bank */
	protected $bankName;
	
	/**
	 * Constructor
	 * 
	 * @param string account number
	 * @param int bank code
	 * @param string bank name (if null then it will be determined automaticly based on bank code) 
	 * 
	 * @throws \InvalidArgumentException if bank code is unknown (and bank name not defined)
	 */
	function __construct($accountNumber, $bankCode, $bankName = null) {
		$this->accountNumber = $accountNumber;
		$this->bankCode = $bankCode;
		
		if($bankName) $this->bankName = $bankName;
		else {
			switch($bankCode) {
				case 5500:
					$this->bankName = 'Raiffeisenbank, a.s'; break;
				
				default:
					throw new \InvalidArgumentException("Unknown bank with code '$bankCode'");
			}
		}
	}
	
	/**
	 * @return string
	 */
	function getAccountNumber() {
		return $this->accountNumber;
	}
	
	/**
	 * @return int
	 */
	function getBankCode() {
		return $this->bankCode;
	}
	
	/**
	 * @return string
	 */
	function getBankName() {
		return $this->bankName;
	}
	
}
