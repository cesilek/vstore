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
 * Invoice payment method
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class InvoicePaymentMethod extends PaymentMethod {
	
	/** @var string issuer name */
	private $_invoiceIssuer;
	
	/** @var vStore\Invoicing\InvoiceSupplier invoice supplier */
	private $_invoiceSupplier;
	
	/** @var vBuilder\Config\ConfigDAO config */
	private $_invoicingConfig;
	
	/**
	 * Creates method from app configuration
	 * 
	 * @param string id
	 * @param vBuilder\Config\ConfigDAO config
	 * @param Nette\DI\IContainer DI context
	 */
	static function fromConfig($id, vBuilder\Config\ConfigDAO $config, Nette\DI\IContainer $context) {
		$method = parent::fromConfig($id, $config, $context);
		
		$method->_invoiceIssuer = $config->get('invoicing.issuer');
		$method->_invoicingConfig = $config->get('invoicing');
		
		return $method;
	}
	
	/**
	 * Returns invoice issuer name (author)
	 * 
	 * @return string 
	 */
	public function getInvoiceIssuer() {
		return $this->_invoiceIssuer;
	}
	
	/**
	 * Returns invoice supplier object
	 * 
	 * @return vStore\Invoicing\InvoiceSupplier
	 */
	public function getInvoiceSupplier() {
		if(!isset($this->_invoiceSupplier)) {
			if(!isset($this->_invoicingConfig)) throw new Nette\InvalidStateException("Missing invoicing configuration");
			
				$addr = new vStore\Invoicing\InvoiceAddress(
					$this->_invoicingConfig->get('address.name'),
					$this->_invoicingConfig->get('address.street'),
					$this->_invoicingConfig->get('address.city'),
					$this->_invoicingConfig->get('address.zip'),
					$this->_invoicingConfig->get('address.country')
				);

				$this->_invoiceSupplier = new vStore\Invoicing\InvoiceSupplier(
						$this->_invoicingConfig->get('in'),
						$this->_invoicingConfig->get('tin'),
						$addr,
						new vStore\Invoicing\InvoiceBankAccount(
								$this->_invoicingConfig->get('bankInfo.accountNumber'),
								$this->_invoicingConfig->get('bankInfo.bankCode'),
								$this->_invoicingConfig->get('bankInfo.bankName'),
								$this->_invoicingConfig->get('bankInfo.swift'),
								$this->_invoicingConfig->get('bankInfo.iban')
						),
						$this->_invoicingConfig->get('email'),
						$this->_invoicingConfig->get('phone'),
						$this->_invoicingConfig->get('web'),
						FILES_DIR . '/..' . $this->_invoicingConfig->get('logo')
				);
		}
		
		return $this->_invoiceSupplier;
	}
	
}