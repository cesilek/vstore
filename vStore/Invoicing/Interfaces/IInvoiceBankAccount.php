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
 * Interface for participant's bank account info
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
interface IInvoiceBankAccount {
	
	/**
	 * @return string
	 */
	function getAccountNumber();
	
	/**
	 * @return int
	 */
	function getBankCode();
	
	/**
	 * @return string
	 */
	function getBankName();
	
}
