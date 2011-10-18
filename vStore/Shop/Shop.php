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
	
	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;	
	}
	
	public function __destruct() {
		// Pokud mam rozpracovanou nejakou objednavku a nebyla prave ulozena do DB,
		// tak si ji ulozim do session
		if(isset($this->_order) && !$this->_order->orderSent()) {
			$this->_order->save();
		}
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
			return $this->context->repository->get('vStore\\Shop\\Order', $id);
		} else {
			if(!isset($this->_order)) {
				$this->_order = $this->context->sessionRepository->get('vStore\\Shop\\Order');
			}

			return $this->_order;
		}
	}
	
	/**
	 * Returns delivery method object
	 * 
	 * @param string id of method
	 * @return IDeliveryMethod 
	 */
	public function getDeliveryMethod($id) {
		if($id === null) return null;
		
		if(!isset($this->availableDeliveryMethods[$id]))
			throw new Nette\InvalidArgumentException("Delivery method '$id' not defined");
		
		return $this->availableDeliveryMethods[$id];
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
					$class = 'vStore\\Shop\\' . ucfirst($class) . 'DeliveryMethod';
				} else
					$class = 'vStore\\Shop\\DeliveryMethod';
				
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
				
				$this->_availablePaymentMethods[$id] = new Shop\PaymentMethod($id, $m->get('name', $id), $m->get('description'), $m->get('charge', 0));
			}
		}
		
		return $this->_availablePaymentMethods;
	}
	
	
}
