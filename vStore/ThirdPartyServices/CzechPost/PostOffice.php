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

namespace vStore\ThirdPartyServices\CzechPost;

use vStore,
	vBuilder,
	vBuilder\Utils\Http,
	Nette;

/**
 * Post office data representation
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 20, 2012
 */
class PostOffice extends Nette\Object {

	const MONDAY	= 'mon';
	const TUESDAY	= 'tue';
	const WEDNESDAY	= 'wed';
	const THURSDAY	= 'thu';
	const FRIDAY	= 'fri';
	const SATURDAY	= 'sat';
	const SUNDAY	= 'sun';

	private $postalCode;
	private $name;
	private $street;
	private $city;
	private $available;
	private $maxPkgValue;
	private $parkingLot;
	private $atm;
	private $openingHours;

	public function __construct($postalCode, $name, $street, $city, $availability = true, $maxPkgValue = null, $hasATM = false, $hasParkingLot = false, array $openingHours = null) {
		$this->postalCode = (int) $postalCode;
		$this->name = $name;
		$this->street = $street;
		$this->city = $city;
		$this->available = (bool) $availability;
		$this->maxPkgValue = $maxPkgValue;
		$this->atm = $hasATM;
		$this->parkingLot = $hasParkingLot;
		$this->openingHours = $openingHours;
	}
	
	/**
	 * Returns street and house number
	 *
	 * @return string
	 */
	public function getStreet() {
		return $this->street;
	}
	
	/**
	 * Returns city
	 *
	 * @return string
	 */
	public function getCity() {
		return $this->city;
	}
	
	/**
	 * Returns postal code
	 *
	 * @return int
	 */
	public function getPostalCode() {
		return $this->postalCode;
	}
	
	/**
	 * Returns formated postal code:
	 * Ex. 193 00
	 *
	 * @return string
	 */
	public function getFormatedPostalCode() {
		$psc = (string) $this->postalCode;
		return mb_substr($psc, 0, 3) . ' ' . mb_substr($psc, 3, 2);
	}
	
	/**
	 * Returns country name
	 *
	 * @return string
	 */
	public function getCountry() {
		return 'Česká republika';
	}
	
	
	/**
	 * Returns official name of this PO
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Returns comma formated address:
	 * <street> <house number>, <postal code> <city>
	 *
	 * @return string
	 */
	public function getFormatedAddress() {
		return $this->street . ', ' . $this->getFormatedPostalCode() . ' ' . $this->city;
	}
	
	/**
	 * Returns true if PO is available
	 *
	 * @return bool
	 */
	public function isAvailable() {
		return $this->available;
	}
	
	/**
	 * Returns true if PO has ATM
	 *
	 * @return bool
	 */
	public function hasATM() {
		return $this->atm;
	}
	
	/**
	 * Returns true if PO has it's own parking lot
	 *
	 * @return bool
	 */
	public function hasParkingLot() {
		return $this->parkingLot;
	}
	
	/**
	 * Returns maximum package value which can be delivered to this PO
	 *
	 * @return null|float
	 */
	public function getMaximumPackageValue() {
		return $this->maxPkgValue;
	}

}