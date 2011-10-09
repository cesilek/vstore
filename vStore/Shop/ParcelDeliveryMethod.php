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

namespace vStore\Shop;

use vStore,
		vBuilder,
		Nette;

/**
 * Implementation of parce delivery method
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 8, 2011
 */
class ParcelDeliveryMethod extends DeliveryMethod {
	
	protected $_countries;
	
	/**
	 * Creates method from app configuration
	 * 
	 * @param string id
	 * @param vBuilder\Config\ConfigDAO config
	 * @param Nette\DI\IContainer DI context
	 */
	static function fromConfig($id, vBuilder\Config\ConfigDAO $config, Nette\DI\IContainer $context) {
		$method = parent::fromConfig($id, $config, $context);
		
		$method->_countries = array();
		foreach($config->get('countries')->getKeys() as $key) {
			$country = $config->get('countries')->{$key};
			$method->_countries[$key] = $country->get('name', $key);
		}
		
		return $method;
	}
		
	/**
	 * Returns array of all available countries
	 * 
	 * @return array of countries (code => name)
	 */
	function getAvailableCountries() {
		return $this->_countries;
	}	
	
}