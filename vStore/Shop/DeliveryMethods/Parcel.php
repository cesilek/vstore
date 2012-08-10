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

namespace vStore\Shop\DeliveryMethods;

use vStore,
	vStore\Shop\IDeliveryMethod,
	vStore\Shop\IPaymentMethod,
	vStore\Shop\Order,
	vBuilder,
	Nette;

/**
 * Implementation of parce delivery method
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 8, 2011
 */
class ParcelDeliveryMethod extends GeneralDeliveryMethod {
	
	protected $_countries;
	protected $_freeOfChargeLimit;
	protected $_charge;
		
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
		$method->_freeOfChargeLimit = array();
		$method->_charge = array();
		foreach($config->get('countries')->getKeys() as $key) {
			$country = $config->get('countries')->{$key};
			$method->_countries[$key] = $country->get('name', $key);
			$method->_freeOfChargeLimit[$key] = $country->get('freeOfChargeLimit');
			$method->_charge[$key] = (float) $country->get('charge', 0);
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
	
	/**
	 * Returns postal charge for a country
	 * 
	 * @param string country code
	 * @return float
	 */
	function getCharge($countryCode) {
		if(!array_key_exists($countryCode, $this->_charge))
				throw new Nette\InvalidArgumentException("Country with code '$countryCode' is not defined");
		
		return $this->_charge[$countryCode];
	}
	
	/**
	 * Returns free of charge limit for a country
	 * 
	 * @param string country code
	 * @return null|float
	 */
	function getFreeOfChargeLimit($countryCode) {
		if(!array_key_exists($countryCode, $this->_freeOfChargeLimit))
				throw new Nette\InvalidArgumentException("Country with code '$countryCode' is not defined");
		
		return $this->_freeOfChargeLimit[$countryCode];
	}
	
	/**
	 * Creates order item for this delivery method
	 * 
	 * @param Order order instance
	 * 
	 * @return OrderItem|null
	 */
	function createOrderItem(Order $order) {
		$item = $order->repository->create('vStore\\Shop\\ParcelDeliveryOrderItem');
		
		return $item;
	}
	
}