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
	vBuilder\Utils\Strings,
	Nette;

/**
 * Magic proxy for delivery method with bound parameters
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class ParametrizedDeliveryMethod extends vBuilder\Object implements IDeliveryMethod {
	
	/** @var IDeliveryMethod */	
	protected $_refInstance;
	
	/** @var array */
	protected $_params;
	
	/**
	 * Constructor
	 */
	public function __construct(IDeliveryMethod $method, array $params = array()) {
		$this->_refInstance = $method;
		$this->_params = $params;
	}
		
	/**
	 * Returns method ID
	 * 
	 * @return string
	 */
	function getId() {
		return Strings::intoParameterizedString($this->_refInstance->getId(), $this->_params);
	}
	
	/**
	 * Returns given parameters
	 *
	 * @return array
	 */
	function getParams() {
		return $this->_params;
	}
	
	/**
	 * Returns actual method
	 *
	 * @return IDeliveryMethod
	 */
	function getMethod() {
		return $this->_refInstance;
	}
	
	// --------------------------------------------------------------------------------------------------
	// Proxy methods ------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------------------
	
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->_refInstance, $name), $arguments);
	}
	
	public static function __callStatic($name, $arguments) {
		return call_user_func_array(get_class($this->_refInstance) . '::' . $name, $arguments);
	}
	
	public function __set($name, $value) {
		$this->_refInstance->{$name} = $value;
	}
	
	public function & __get($name) {
		try {
			$t = Nette\ObjectMixin::get($this, $name);
			return $t;
		} catch(Nette\MemberAccessException $e) {
			$t = $this->_refInstance->{$name};
			return $t;
		}
	}
	
	public function __isset($name) {
		return isset($this->_refInstance->{$name});
	}
	
	public function __unset($name) {
		unset($this->_refInstance->{$name});
	}
	
	// --------------------------------------------------------------------------------------------------
	// IDeliveryMethody compatibility methods -----------------------------------------------------------
	// --------------------------------------------------------------------------------------------------
	
	/**
	 * Creates method from app configuration
	 * 
	 * @param string id
	 * @param vBuilder\Config\ConfigDAO config
	 * @param Nette\DI\IContainer DI context
	 */
	static function fromConfig($id, vBuilder\Config\ConfigDAO $config, Nette\DI\IContainer $context) {
		throw new \LogicException("Parametrized delivery method is not meant to be created from config");
	}	
	
	/**
	 * Returns true if this method is available for new orders
	 *
	 * @return bool
	 */
	function isEnabled() {
		return $this->_refInstance->isEnabled();
	}
	
	/**
	 * Returns method name
	 * 
	 * @return string
	 */
	function getName() {
		return $this->_refInstance->getName();
	}	
	
	/**
	 * Returns method description
	 * 
	 * @return string
	 */
	function getDescription() {
		return $this->_refInstance->getDescription();
	}
		
	/**
	 * Returns true if this method is suitable with given payment
	 * 
	 * @param string|IPaymentMethod payment method
	 * 
	 * @return bool
	 */
	function isSuitableWith($payment) {
		return $this->_refInstance->isSuitableWith($payment);
	}
	
	/**
	 * Creates order item for this delivery method
	 * 
	 * @param Order order instance
	 * 
	 * @return OrderItem|null
	 */
	function createOrderItem(Order $order) {
		return $this->_refInstance->createOrderItem($order);
	}
	
	/**
	 * Creates parametrized instance of this delivery method
	 *
	 * @return ParametrizedDeliveryMethod
	 */
	function createParametrizedMethod(array $parameters) {
		throw new \LogicException("This method is already parametrized");
	}
	
	/**
 	 * Returns class name of control for advanced rendering or null
 	 *
	 * @return string|null
	 */
	function getControlClass() {
		return $this->_refInstance->getControlClass();
	}
	
	/**
	 * Returns URL of page with more information about this method
	 * 
	 * @return string|null
	 */
	function getMoreInfoUrl() {
		return $this->_refInstance->getMoreInfoUrl();
	}
	
}