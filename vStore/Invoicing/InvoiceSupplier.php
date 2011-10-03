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
 * Basic invoice supplier implementation
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
class InvoiceSupplier extends InvoiceParticipant {
		
	/** @var IInvoiceBankAccount bank account info */
	protected $bankAccount;
	
	/** @var string e-mail address */
	protected $email;
	
	/** @var string phone number */
	protected $phone;
	
	/** @var string web page */
	protected $webpage;
	
	/** @var string logo image URL */
	protected $logo;
	
	function __construct($in, $tin, IInvoiceAddress $invoiceAddress, IInvoiceBankAccount $bankAccount, $email = null, $phone = null, $webpage = null, $logo = null) {	
		parent::__construct($in, $tin, $invoiceAddress);
		
		$this->bankAccount = $bankAccount;
		$this->email = $email;
		$this->phone = $phone;
		$this->webpage = $webpage;
		$this->logo = $logo;
	}
			
	/**
	 * @return IInvoiceBankAccount
	 */
	function getBankAccount() {
		return $this->bankAccount;
	}
	
	/**
	 * @return string URL to logo image
	 */
	function getLogoUrl() {
		return $this->logo;
	}
	
	/**
	 * @return string
	 */
	function getEmail() {
		return $this->email;
	}
	
	/**
	 * @return string
	 */
	function getPhone() {
		return $this->phone;
	}
	
	/**
	 * @return string
	 */
	function getWebpage() {
		return $this->webpage;
	}
	
}
