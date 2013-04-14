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
	
	/** @var array config */
	private $_invoicingConfig;
	
	/**
	 * Creates method from app configuration
	 * 
	 * @param string id
	 * @param array config
	 * @param Nette\DI\IContainer DI context
	 */
	static function fromConfig($id, array $config, Nette\DI\IContainer $context) {
		$method = parent::fromConfig($id, $config, $context);
		
		$method->_invoicingConfig = isset($config['invoicing']) ? $config['invoicing'] : NULL;
		$method->_invoiceIssuer = isset($method->_invoicingConfig['issuer'])  ? $method->_invoicingConfig['issuer'] : NULL;		
		
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
					$this->_invoicingConfig['address']['name'],
					$this->_invoicingConfig['address']['street'],
					$this->_invoicingConfig['address']['city'],
					$this->_invoicingConfig['address']['zip'],
					$this->_invoicingConfig['address']['country']
				);

				$this->_invoiceSupplier = new vStore\Invoicing\InvoiceSupplier(
						$this->_invoicingConfig['in'],
						$this->_invoicingConfig['tin'],
						$addr,
						new vStore\Invoicing\InvoiceBankAccount(
							$this->_invoicingConfig['bankInfo']['accountNumber'],
							$this->_invoicingConfig['bankInfo']['bankCode'],
							$this->_invoicingConfig['bankInfo']['bankName'],
							$this->_invoicingConfig['bankInfo']['swift'],
							$this->_invoicingConfig['bankInfo']['iban']
						),
						$this->_invoicingConfig['email'],
						$this->_invoicingConfig['phone'],
						$this->_invoicingConfig['web'],
						FILES_DIR . '/..' . $this->_invoicingConfig['logo']
				);
		}
		
		return $this->_invoiceSupplier;
	}
	
}