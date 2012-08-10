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
	}
	
	public function __destruct() {
		
		// Pokud mam rozpracovanou nejakou objednavku a nebyla prave ulozena do DB,
		// tak si ji ulozim do session
		if(isset($this->_order) && !$this->_order->orderSent()) {
			
			// Musim zachytavat vyjimky a logovat je, protoze pri destructu uz je stranka
			// vyrenderovana a nelze zobrazit chybove hlaseni
			try {
				$this->_order->save();
			} catch(\Exception $e) {
				Nette\Diagnostics\Debugger::log($e);
				Nette\Diagnostics\Debugger::log('Error saving shop order into session because of ' . get_class($e) .': ' . md5($e), Nette\Diagnostics\Debugger::CRITICAL);
			}
		}
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
			$methods = $this->context->config->shop->deliveryMethods;
			$methodIds = $methods->getKeys();
			foreach($methodIds as $id) {
				$m = $methods->$id;
				
				if(($class = $m->get('type')) != null) {
					$class = 'vStore\\Shop\\DeliveryMethods\\' . ucfirst($class);
				} else
					$class = 'vStore\\Shop\\DeliveryMethods\\GeneralDeliveryMethod';
				
				$this->_availableDeliveryMethods[$id] = $class::fromConfig($id, $m, $this->context);
			}
		}
		
		return $this->_availableDeliveryMethods;
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
			$methods = $this->context->config->shop->paymentMethods;
			$methodIds = $methods->getKeys();
			foreach($methodIds as $id) {
				$m = $methods->$id;
				
				if(($class = $m->get('type')) != null) {
					$class = 'vStore\\Shop\\' . ucfirst($class) . 'PaymentMethod';
				} else
					$class = 'vStore\\Shop\\PaymentMethod';
				
				$this->_availablePaymentMethods[$id] = $class::fromConfig($id, $m, $this->context);
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
