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

namespace vStore\ThirdPartyServices\Ppl;

use vStore,
	vBuilder,
	vBuilder\Utils\Http,
	Nette,
	Nette\Utils\Strings;

/**
 * Service for providing info about availability of PPL delivery
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 9, 2012
 */
class InfoProvider extends vBuilder\Object {

	
	/** @var Nette\DI\IContainer DI context container */
	private $_context;
	
	/**
	 * Constructor
	 *
	 * @param Nette\DI\IContainer DI context container
	 */
	public function __construct(Nette\DI\IContainer $context) {
		$this->_context = $context;
	}
	
	/**
	 * Returns DI context container
	 *
	 * @return Nette\DI\IContainer
	 */
	public function getContext() {
		return $this->_context;
	}	
	
	/**
	 * Checks if evening delivery is available for parcel delivery to given postal code
	 *
	 * @param string postal code
	 */
	public function isEveningDeliveryAvailableForCode($postalCode) {
		$postalCode = (int) preg_replace('#\s+#', '', $postalCode);
	
		// Brno
		if(($postalCode >= 60010 && $postalCode <= 64700) || $postalCode == 65502 || $postalCode == 65691 || $postalCode == 65805 || $postalCode == 65856)
			return true;
			
		// České Budějovice
		if($postalCode >= 37001 && $postalCode <= 37021)
			return true;
			
		// Hradec Králové
		if(($postalCode >= 50001 && $postalCode <= 50012) || $postalCode == 50101 || $postalCode == 50301 || $postalCode == 50302 || $postalCode == 50311 || $postalCode == 50321 || $postalCode == 50332 || $postalCode == 50341)
			return true;
			
		// Liberec
		if(($postalCode >= 46001 && $postalCode <= 46020) || $postalCode == 46177 || $postalCode == 46302 || $postalCode == 46303 || $postalCode == 46311 || $postalCode == 46312)
			return true;
			
		// Olomouc
		if(($postalCode >= 77002 && $postalCode <= 77900) || $postalCode == 78301 || $postalCode == 78302 || $postalCode == 78371)
			return true;
	
		// Ostrava
		if($postalCode >= 70002 && $postalCode <= 73001)
			return true;
			
		// Pardubice
		if(($postalCode >= 53001 && $postalCode <= 53020) || ($postalCode >= 53351 && $postalCode <= 53354) || $postalCode == 53078 || $postalCode == 53141 || $postalCode == 53215 || $postalCode == 53231 || $postalCode == 53301 || $postalCode == 53331 || $postalCode == 53333)
			return true;
			
		// Plzeň
		if($postalCode >= 30100 && $postalCode <= 32800)
			return true;
			
		// Praha
		if($postalCode >= 10000 && $postalCode <= 19999)
			return true;
			
		// Ústí nad Labem
		if(($postalCode >= 40003 && $postalCode <= 40020) || $postalCode == 40001)
			return true;
			
		// Zlín
		if(($postalCode >= 76001 && $postalCode <= 76007) || $postalCode == 76302 || $postalCode == 76311 || $postalCode == 76314)
			return true;
			
		return false;
	}
	
}