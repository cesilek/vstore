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


/**
 * Interface of all invoices
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
interface IInvoice {
	
	/**
	 * @return string 
	 */
	function getId();
	
	/**
	 * @return int
	 */
	function getVarSymbol();
	
	/**
	 * @return int
	 */
	function getConstSymbol();
	
	/**
	 * @return int
	 */
	function getSpecificSymbol();
	
	/**
	 * @return IInvoiceSupplier
	 */
	function getSupplier();
	
	/**
	 * @return IInvoiceParticipant
	 */
	function getCustomer();
	
	/**
	 * @return array of IInvoiceItem
	 */
	function getItems();
	
	/**
	 * @return float
	 */
	function getRounding();
	
	/**
	 * @return float
	 */
	function getTotalBeforeRounding();
	
	/**
	 * @return int
	 */
	function getTotal();
	
	/**
	 * @return string
	 */
	function getAuthor();
	
	/**
	 * @return \DateTime
	 */
	function getIssuanceDate();
	
	/**
	 * @return \DateTime
	 */
	function getRevenueRecognitionDate();
	
	/**
	 * @return \DateTime
	 */
	function getDueDate();
	
	
	
}
