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
 * Basic invoice address implementation
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
class InvoiceAddress extends vBuilder\Object implements IInvoiceAddress {
	
	/** @var string contact name */
	protected $name;
	
	/** @var string street */
	protected $street;
	
	/** @var string city */
	protected $city;
	
	/** @var string zip */
	protected $zip;
	
	/** @var string country */
	protected $country;
	
	function __construct($name, $street, $city, $zip, $country = 'Česká republika') {
		$this->name = $name;	
		$this->street = $street;
		$this->city = $city;
		$this->zip = $zip;
		$this->country = $country;
	}
	
	/**
	 * @return string
	 */
	function getName() {
		return $this->name;
	}
	
	/**
	 * @return string
	 */
	function getStreet() {
		return $this->street;
	}
	
	/**
	 * @return string
	 */
	function getCity() {
		return $this->city;
	}
	
	/**
	 * @return string
	 */
	function getZip() {
		return $this->zip;
	}
	
	/**
	 * @return string
	 */
	function getCountry() {
		return $this->country;
	}
	
}
