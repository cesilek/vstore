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
 * Generic implementation of order delivery method
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class GeneralDeliveryMethod extends vBuilder\Object implements IDeliveryMethod {
		
	protected $_enabled = true;
	protected $_id;
	protected $_name;
	protected $_description;
	protected $_suitablePayments;
	protected $_controlClass;
	
	/**
	 * Protected constructor
	 */
	protected function __construct() {
	
	}
	
	/**
	 * Creates method from app configuration
	 * 
	 * @param string id
	 * @param vBuilder\Config\ConfigDAO config
	 * @param Nette\DI\IContainer DI context
	 */
	static function fromConfig($id, vBuilder\Config\ConfigDAO $config, Nette\DI\IContainer $context) {
		$method = new static;
		
		$method->_id = $id;
		$method->_name = $config->get('name', $method->_name ? $method->_name : $id);
		if($config->get('description'))  $method->_description = $config->get('description');
		if($config->get('suitablePayments')) $method->_suitablePayments = $config->get('suitablePayments')->toArray();
		if($config->get('control')) $method->_controlClass = $config->get('control');		
		if($config->get('enabled') !== NULL) $method->_enabled = (bool) $config->get('enabled');
		
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
	 * Returns true if this method is suitable with given payment
	 * 
	 * @param string|IPaymentMethod payment method
	 * 
	 * @return bool
	 */
	function isSuitableWith($payment) {
		if($this->_suitablePayments === null) return true;
		if($payment instanceof IPaymentMethod) $payment = $payment->getId();
		
		foreach($this->_suitablePayments as $curr) {
			if($curr == $payment) return true;
		}
		
		return false;
	}
	
	/**
	 * Creates order item for this delivery method
	 * 
	 * @param Order order instance
	 * 
	 * @return OrderItem|null
	 */
	function createOrderItem(Order $order) {
		return null;
	}
	
	/**
	 * Creates parametrized instance of this delivery method
	 *
	 * @return ParametrizedDeliveryMethod
	 */
	function createParametrizedMethod(array $parameters) {
		return new ParametrizedDeliveryMethod($this, $parameters);
	}
	
	/**
 	 * Returns class name of control for advanced rendering or null
 	 *
	 * @return string|null
	 */
	function getControlClass() {
		return $this->_controlClass;
	}
	
}