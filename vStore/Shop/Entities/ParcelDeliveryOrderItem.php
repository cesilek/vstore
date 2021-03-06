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
 * Order item for auto-computing of postal charge
 *
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 14, 2011
 */
class ParcelDeliveryOrderItem extends DynamicOrderItem {
	
	/** @var vStore\Shop\IDeliveryMethod */
	protected $_method;
	
	/**
	 * Returns product id (delivery items have fixed ID)
	 * 
	 * @return int 
	 */
	public function getProductId() {
		return Order::DELIVERY_ITEM_ID;
	}
	
	/**
	 * Returns item name
	 * 
	 * @return type 
	 */
	public function getName() {
		return "Poštovné";
	}
	
	/**
	 * Returns bound delivery method
	 *
	 * @return vStore\Shop\IDeliveryMethod
	 * @throws Nette\InvalidStateException if delivery method is not set
	 */
	public function getMethod() {
		if(!isset($this->_method))
			throw new Nette\InvalidStateException("Delivery method not set");
			
		return $this->_method;
	}
	
	/**
	 * Sets the delivery method
	 *
	 * @param vStore\Shop\IDeliveryMethod
	 */
	public function setMethod(IDeliveryMethod $method) {
		$this->_method = $method;
	}
	
	/**
	 * Computes price for this item
	 * 
	 * @return float
	 */
	protected function gatherPrice() {		
		/* if($this->order->delivery == null) throw new Nette\InvalidStateException("Given order does not have delivery method set");
		$delivery = $this->order->delivery;
		
		if(!($this->order->delivery instanceof vStore\Shop\ParcelDeliveryMethod))
			throw new Nette\InvalidStateException(get_called_class() . " can be only used with ParcelDeliveryMethod"); */
		
		
		
		// $delivery = $this->context->shop->getDeliveryMethod('byPost');
		$delivery = $this->method;
		
		// Kvuli zobrazovani prubezneho postovneho jeste pred zadanim dorucovaci adresy
		//if($this->order->address == null) throw new Nette\InvalidStateException("Given order does not have delivery address set");
	
		if($this->order->address != null && array_key_exists($this->order->address->country, $delivery->getAvailableCountries())) {
			$countryCode = $this->order->address->country;
		} else {
			list($countryCode) = array_keys($delivery->availableCountries);
		}
		
		$freeOfChargeLimit = $delivery->getFreeOfChargeLimit($countryCode);
		$charge = $delivery->getCharge($countryCode);
		
		$orderProductTotal = $this->order->getTotal(true);
		
		if($freeOfChargeLimit === null || $freeOfChargeLimit > $orderProductTotal) {
			return $charge;
		}
		
		return 0;
	}
		
}