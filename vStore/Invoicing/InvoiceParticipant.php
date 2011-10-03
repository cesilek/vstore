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
 * Basic invoice participant implementation
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
class InvoiceParticipant extends vBuilder\Object implements IInvoiceParticipant {
	
	/** @var string identification number (IC) */
	protected $in;
	
	/** @var string tax identification number (DIC) */
	protected $tin;
	
	/** @var IInvoiceAddress invoice address */
	protected $invoiceAddress;
	
	/** @var IInvoiceAddress contact address */
	protected $contactAddress;

	
	function __construct($in, $tin, IInvoiceAddress $invoiceAddress, IInvoiceAddress $contactAddress = null) {	
		$this->in = $in;
		$this->tin = $tin;
		$this->invoiceAddress = $invoiceAddress;
		$this->contactAddress = $contactAddress === null ? $invoiceAddress : $contactAddress;
	}
		
	/**
	 * @return string
	 */
	function getTin() {
		return $this->tin;
	}
	
	/**
	 * @return string
	 */
	function getIn() {
		return $this->in;
	}
	
	/**
	 * @return IInvoiceAddress
	 */
	function getContactAddress() {
		return $this->contactAddress;
	}
	
	/**
	 * @return IInvoiceAddress
	 */
	function getInvoiceAddress() {
		return $this->invoiceAddress;
	}
	
}
