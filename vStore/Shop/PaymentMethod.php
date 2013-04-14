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
 * Basic implementation of order payment method
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class PaymentMethod extends vBuilder\Object implements IPaymentMethod {
	
	private $_enabled = true;
	private $_id;
	private $_name;
	private $_description;
	private $_charge;
		
	/**
	 * Protected constructor
	 */
	protected function __construct() {
	
	}
	
	/**
	 * Creates method from app configuration
	 * 
	 * @param string id
	 * @param array config
	 * @param Nette\DI\IContainer DI context
	 */
	static function fromConfig($id, array $config, Nette\DI\IContainer $context) {
		$method = new static;
		
		$method->_id = $id;
		$method->_name = isset($config['name']) ? $config['name'] : $id;
		$method->_description = isset($config['description']) ? $config['description'] : NULL;
		$method->_charge = isset($config['charge']) ? $config['charge'] : NULL;
		if(array_key_exists('enabled', $config)) $method->_enabled = (bool) $config['enabled'];
		
		return $method;
	}
	
	/**
	 * Returns true if this method is available for new orders
	 *
	 * @return bool
	 */
	function isEnabled() {
		return $this->_enabled;
	}
	
	/**
	 * Returns method ID
	 * 
	 * @return string
	 */
	function getId() {
		return $this->_id;
	}
	
	/**
	 * Returns charge for this method
	 * 
	 * @return float 
	 */
	function getCharge() {
		return $this->_charge;
	}
	
	/**
	 * Returns method name
	 * 
	 * @return string
	 */
	function getName() {
		return $this->_name;
	}
		
	/**
	 * Returns method description
	 * 
	 * @return string
	 */
	function getDescription() {
		return $this->_description;
	}
	
	/**
	 * @return OrderItem|null
	 */
	function createOrderItem(Order $order) {
		if($this->charge) {
			
			$item = $order->repository->create('vStore\\Shop\\PaymentOrderItem');
			$item->price = $this->charge;
			return $item;
		}
		
		return null;
	}
	
}