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

namespace vStore\Invoicing;

use vStore,
		Nette;

/**
 * Class for creating invoices from vStore shop orders
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
class ShopOrderInvoice extends Invoice {
	
	/** @var vStore\Shop\Order order */
	protected $order;
	
	/** @var InvoiceParticipant */
	private $_customer;
	
	/** @var array of rder items */
	private $_items;
	
	/**
	 * Constructor
	 * 
	 * @param vStore\Shop\Order order
	 */
	function __construct(vStore\Shop\Order $order) {
		$this->order = $order;
		
		if(!isset($order->customer))
			throw new Nette\InvalidStateException("Order customer ahs to be set");			
		
		if(!($order->payment instanceof vStore\Shop\InvoicePaymentMethod))
			throw new Nette\InvalidStateException("Order with proforma invoice payment expected");
	}
		
	/**
	 * @return string 
	 */
	function getId() {
		return vStore\Latte\Helpers\Shop::formatOrderId($this->order->id);
	}
	
	/**
	 * @return \DateTime
	 */
	function getIssuanceDate() {
		return $this->order->timestamp;
	}
	
	/**
	 * @return \DateTime
	 */
	function getDueDate() {
		return $this->getIssuanceDate()->add(\DateInterval::createFromDateString('14 day'));
	}
		
	/**
	 * @return \DateTime
	 */
	function getAuthor() {
		return $this->order->payment->invoiceIssuer;
	}
	
	/**
	 * @return int
	 */
	function getVarSymbol() {
		return (int) $this->order->id;
	}
	
	/**
	 * @return int
	 */
	function getConstSymbol() {
		return "0308";
	}
	
	/**
	 * @return int
	 */
	function getSpecificSymbol() {
		return null;
	}
	
	/**
	 * @return IInvoiceSupplier
	 */
	function getSupplier() {
		return $this->order->payment->invoiceSupplier;
	}
	
	/**
	 * @return IInvoiceParticipant
	 */
	function getCustomer() {
		$realDeliveryMethod = $this->order->delivery instanceof vStore\Shop\DeliveryMethods\ParametrizedDeliveryMethod
				? $this->order->delivery->getMethod() : $this->order->delivery;


		if(!isset($this->_customer)) {
			$in = NULL;
			$tin = NULL;
			$invoiceAddress = NULL;

			if($this->order->company) {
				
				$invoiceAddress = new InvoiceAddress(
					$this->order->company->name,
					$this->order->company->address->street . ' ' . $this->order->company->address->houseNumber,
					$this->order->company->address->city,
					$this->order->company->address->zip,
					$this->order->company->address->countryName
				);
			} 

			if($this->order->address && $realDeliveryMethod instanceof vStore\Shop\DeliveryMethods\ParcelDeliveryMethod) {
				$contactAddress = new InvoiceAddress(
					$this->order->customer->displayName,
					$this->order->address->street . ' ' . $this->order->address->houseNumber,
					$this->order->address->city,
					$this->order->address->zip,
					isset($this->order->delivery->availableCountries[$this->order->address->country])
								? $this->order->delivery->availableCountries[$this->order->address->country]
								: $this->order->address->country
				);

			} else {
				$contactAddress = new InvoiceAddress(
						$this->order->customer->displayName
				);
			}

			$this->_customer = new InvoiceParticipant(
				$in,
				$tin,
				isset($invoiceAddress) ? $invoiceAddress : $contactAddress,
				$contactAddress
			);
		}
		
		return $this->_customer;
	}
	
	/**
	 * @return array of IInvoiceItem
	 */
	function getItems() {
		if(!isset($this->_items)) {
			$this->_items = array();

			foreach($this->order->items as $item) {
				$this->_items[] = new InvoiceItem(
								$item->name,
								$item->price,
								$item->amount
				);
			}
		}
		
		return $this->_items;
	}
	
}
