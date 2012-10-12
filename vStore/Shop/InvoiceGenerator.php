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
 * Invoice generator for shop orders
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 22, 2011
 */
class InvoiceGenerator extends vBuilder\Object {
	
	/**
	 * @var Nette\DI\IContainer DI context
	 * @nodump
	 */
	protected $context;
	
	/** @var vStore\Invoicing\InvoiceRenderer */
	private $_renderer;
		
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI context 
	 */
	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;	
	}
	
	/**
	 * Returns invoice renderer
	 * 
	 * @return vStore\Invoicing\InvoiceRenderer
	 */
	public function getRenderer() {
		if(!isset($this->_renderer)) {
			$this->_renderer = new vStore\Invoicing\InvoicePdfRenderer($this->context);
		}
		
		return $this->_renderer;
	}
	
	/**
	 * Creates file path for order invoice file
	 * @param Order $order 
	 */
	public function getFilePath(Order $order) {
		return FILES_DIR . '/shop/invoices/' . $order->id . '.pdf';
	}
	
	/**
	 * Generates proforma invoice for order and returns it's file path
	 * 
	 * @param Order $order
	 * 
	 * @return string absolute filepath 
	 */
	public function generate(Order $order) {
		$outputFile = $this->getFilePath($order);
		vBuilder\Utils\FileSystem::createFilePath($outputFile);
		
		$orderInvoice = new vStore\Invoicing\ShopOrderInvoice($order);
		
		if(!($order->payment instanceof InvoicePaymentMethod)) {
			$ok = false;
			foreach($this->context->shop->getAvailablePaymentMethods() as $m) {
				if($m instanceof InvoicePaymentMethod) {
									
					$orderInvoice->setSupplier($m->invoiceSupplier);
					$orderInvoice->setAuthor($m->invoiceIssuer);
					$ok = true;
					
					break;
				}
			}
			
			if(!$ok) throw new Nette\InvalidStateException("No supplier information could have been received");
		}

		$this->renderer->renderToFile($orderInvoice, $outputFile);
		
		return $outputFile;
	}
	
}

