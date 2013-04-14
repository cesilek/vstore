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

use vBuilder,
		vStore,
		Nette;

/**
 * Generic implementation of invoice routines
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
abstract class InvoiceRenderer extends vBuilder\Object {
	
	/** @var string file with invoice template */
	protected $templateFile;
	
	/** @var Nette\Templating\Template template */
	protected $template;
	
	/**
	 * @var Nette\DI\IContainer DI container
	 * @nodump
	 */
	protected $context;
	
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI container 
	 */
	function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
	}
	
	/**
	 * Sets file path to invoice template
	 * 
	 * @param string absolute file path
	 */
	function setTemplateFile($filePath) {
		$this->templateFile = $filePath;
	}
		
	/**
	 * Renders invoice into output buffer
	 * 
	 * @param IInvoice invoice 
	 */
	abstract function render(IInvoice $invoice);
	
	/**
	 * Renders invoice into file
	 * 
	 * @param IInvoice invoice 
	 */
	abstract function renderToFile(IInvoice $invoice, $filepath);
			
	/**
	 * Creates template instance
	 * 
	 * @return Nette\Templating\FileTemplate
	 * 
	 * @throws Nette\InvalidStateException if template file was not set
	 * @throws Nette\InvalidArgumentException if template file does not exists
	 */
	protected function createTemplate() {
		if(empty($this->templateFile))
			throw new Nette\InvalidStateException("Template file was not set. Forget to call " . get_called_class() . "::setTempplate()?");		
		
		if(!file_exists($this->templateFile))
			throw new Nette\InvalidArgumentException("Invoice template file '$this->templateFile' does not exist.");
		
		$template = new Nette\Templating\FileTemplate($this->templateFile);
		
		$template->registerFilter(new Nette\Latte\Engine);
		$template->registerHelperLoader('Nette\Templating\Helpers::loader');
		$template->registerHelper('currency', 'vStore\Latte\Helpers\Shop::currency');
		
		$template->baseUrl = rtrim($this->context->httpRequest->getUrl()->getBaseUrl(), '/');
		$template->renderer = $this;
		
		return $template;
	}
	
	/**
	 * Returns template
	 * 
	 * @return Nette\Templating\Template
	 */
	protected function getTemplate() {
		return isset($this->template)
						? $this->template
						: $this->template = $this->createTemplate();
	}
	
}