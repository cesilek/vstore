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
 *
 * vStore is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vStore bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vStore;

use vBuilder,
	vBuilder\Utils\Strings,
	Nette;

/**
 * Shop super-class
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class Shop extends vBuilder\Object {
	
	/**
	 * @var Nette\DI\IContainer DI context
	 * @nodump
	 */
	protected $context;
	
	private $_order;
	private $_availableDeliveryMethods;
	private $_availablePaymentMethods;
	
	/** @var array of order listeners */
	public $onOrderCreated = array();
	
	/**
	  * @var array of order listeners
	  *
	  * @warning called from connector not model!
	  */
	public $onOrderDone = array();
	
	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;	
		
		$shop = $this;
		$this->context->application->onResponse[] = function ($sender, $response) use ($shop) {
			$shop->getOrder()->save();
		};
	}
	
	/**
	 * Returns name of order entity class
	 * 
	 * @return string class name 
	 */
	public function getOrderEntityClass() {
		return 'vStore\\Shop\\Order';
	}
	
	/**
	 * Returns order. If ID is null, then current (session) order is returned.
	 * 
	 * @param int id
	 * 
	 * @return Order
	 */
	public function getOrder($id = null) {
		if($id !== null) {
			return $this->context->repository->get($this->getOrderEntityClass(), $id);
		} else {
			if(!isset($this->_order)) {
				$this->_order = $this->context->sessionRepository->get($this->getOrderEntityClass());
			}

			return $this->_order;
		}
	}
	
	/**
	 * Returns fluent to user's order query
	 * 
	 * @param vBuilder\Security\User|int|null user id (or user instance), if null current logged user is used
	 * 
	 * @throws Nette\InvalidStateException if no user is logged but current user is requested
	 */
	public function getUserOrders($user = null) {
		$userId = $this->getUserId($user);
		
		return $this->context->repository->findAll($this->getOrderEntityClass())->where('[user] = %i', $userId);
	}
	
	
	/**
	 * Returns delivery method object
	 * 
	 * @param string id of method
	 * @return IDeliveryMethod 
	 */
	public function getDeliveryMethod($id) {
		if($id === null) return null;
		
		list($methodId, $parameters) = Strings::parseParametrizedString($id);
		
		if(!isset($this->availableDeliveryMethods[$methodId]))
			throw new Nette\InvalidArgumentException("Delivery method '$methodId' not defined");
				
		return count($parameters) > 0
					? $this->availableDeliveryMethods[$methodId]->createParametrizedMethod($parameters)
					: $this->availableDeliveryMethods[$methodId];
	}
	
	/**
	 * Returns available delivery methods
	 * 
	 * @return array of IDeliveryMethod
	 */
	public function getAvailableDeliveryMethods() {
		if(!isset($this->_availableDeliveryMethods)) {

			$this->_availableDeliveryMethods = array();

			if(isset($this->context->parameters['shop']['deliveryMethods']) && is_array($this->context->parameters['shop']['deliveryMethods'])) {
				foreach($this->context->parameters['shop']['deliveryMethods'] as $key => $config) {

					$class = isset($config['type'])
						? 'vStore\\Shop\\DeliveryMethods\\' . ucfirst($config['type'])
						: 'vStore\\Shop\\DeliveryMethods\\GeneralDeliveryMethod';

					$this->_availableDeliveryMethods[$key] = $class::fromConfig($key, $config, $this->context);
				}
			}
		}
		
		return $this->_availableDeliveryMethods;
	}
	
	/**
	 * Returns array of all defined countries by shop delivery methods
	 *
	 * @return array of string
	 */
	public function getAvailableCountries() {
		$countries = array();
		foreach($this->getAvailableDeliveryMethods() as $method) {
			if($method instanceof Shop\DeliveryMethods\ParcelDeliveryMethod) {
				foreach($method->getAvailableCountries() as $code=>$name) {
					if(!isset($countries[$code]))
						$countries[$code] = $name;
				}
			}
		}

		return $countries;
	}

	/**
	 * Returns payment method object
	 * 
	 * @param string id of method
	 * @return IPaymentMethod 
	 */
	public function getPaymentMethod($id) {
		if($id === null) return null;
		
		if(!isset($this->availablePaymentMethods[$id]))
			throw new Nette\InvalidArgumentException("Payment method '$id' not defined");
		
		return $this->availablePaymentMethods[$id];
	}
	
	/**
	 * Returns available payment methods
	 * 
	 * @return array of IPaymentMethod
	 */
	public function getAvailablePaymentMethods() {
		if(!isset($this->_availablePaymentMethods)) {

			$this->_availablePaymentMethods = array();

			if(isset($this->context->parameters['shop']['paymentMethods']) && is_array($this->context->parameters['shop']['paymentMethods'])) {
				foreach($this->context->parameters['shop']['paymentMethods'] as $key => $config) {

					$class = isset($config['type'])
						? 'vStore\\Shop\\' . ucfirst($config['type']) . 'PaymentMethod'
						: 'vStore\\Shop\\PaymentMethod';

					$this->_availablePaymentMethods[$key] = $class::fromConfig($key, $config, $this->context);
				}
			}

		}
		
		return $this->_availablePaymentMethods;
	}

	/**
	 * Helper for getting user id from parameter
	 * 
	 * @param vBuilder\Security\User|int|null user id (or user instance), if null current logged user is used
	 * 
	 * @throws Nette\InvalidStateException if no user is logged but current user is requested
	 */
	protected function getUserId($user = null) {
		if($user instanceof vBuilder\Security\User) $user = $user->id;
		elseif($user === null) {
			if(!$this->context->user->isLoggedIn())
				throw new Nette\InvalidStateException("Current user requested but no user is logged in");
			
			$user = $this->context->user->getId();
		}
		
		return $user;
	}
	
}
