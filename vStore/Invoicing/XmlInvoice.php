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


/**
 * Class for reading of XML invoices from old CMS
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
class XmlInvoice extends Invoice {
	
	/** @var \SimpleXMLElement xml root element */
	protected $xml;
	
	/**
	 * Constructor
	 * 
	 * @param SimpleXMLElement XML root element
	 */
	function __construct(\SimpleXMLElement $xml) {
		$this->xml = $xml;
	}
	
	/**
	 * Creates invoice from XML file
	 * 
	 * @param string absolute file path
	 * @return XmlInvoice
	 */
	static function fromFile($filepath) {
		if(!file_exists($filepath))
			throw new \InvalidArgumentException("File '$filepath' does not exist.");
		
		$xml = simplexml_load_file($filepath);
		if($xml === false) 
			throw new \InvalidArgumentException("File '$filepath' does not have well-formed data.");
		
		return new self($xml);
	}
	
	/**
	 * @return string 
	 */
	function getId() {
		return (String) $this->xml['number'];
	}
	
	/**
	 * @return \DateTime
	 */
	function getIssuanceDate() {
		return \DateTime::createFromFormat('j.m.Y', preg_replace('/\s+/', '', (String) $this->xml['created']));
	}
	
	/**
	 * @return \DateTime
	 */
	function getDueDate() {
		return \DateTime::createFromFormat('j.m.Y', preg_replace('/\s+/', '', (String) $this->xml->payment[0]->deadline[0]));
	}
		
	/**
	 * @return \DateTime
	 */
	function getAuthor() {
		return (String) $this->xml['author'];
	}
	
	/**
	 * @return int
	 */
	function getVarSymbol() {
		return (int) $this->xml->payment[0]->varsymbol[0];
	}
	
	/**
	 * @return int
	 */
	function getConstSymbol() {
		return (String) $this->xml->payment[0]->constsymbol[0];
	}
	
	/**
	 * @return int
	 */
	function getSpecificSymbol() {
		return (String) $this->xml->payment[0]->specsymbol[0];
	}
	
	/**
	 * @return IInvoiceSupplier
	 */
	function getSupplier() {
		$addr = new InvoiceAddress(
			(String) $this->xml->supplier[0]->name[0],
			(String) $this->xml->supplier[0]->address[0]->street[0],
			(String) $this->xml->supplier[0]->address[0]->city[0],
			(String) $this->xml->supplier[0]->address[0]->postal[0],
			(String) $this->xml->supplier[0]->address[0]->country[0]
		);
		
		list($accountNumber, $bankCode) = explode('/', (String) $this->xml->payment[0]->account[0]);
		
		return new InvoiceSupplier(
				(int) $this->xml->supplier[0]['ic'],
				isset($this->xml->supplier[0]['dic']) ? (String) $this->xml->supplier[0]['dic'] : null,
				$addr,
				new InvoiceBankAccount(
						$accountNumber,
						$bankCode,
						isset($this->xml->payment[0]->bank[0]) ? $this->xml->payment[0]->bank[0] : null,
						isset($this->xml->payment[0]->swift[0]) ? $this->xml->payment[0]->swift[0] : null,
						isset($this->xml->payment[0]->iban[0]) ? $this->xml->payment[0]->iban[0] : null
				),
				$this->xml->supplier[0]->email[0],
				$this->xml->supplier[0]->tel[0],
				$this->xml->supplier[0]->web[0],
				isset($this->xml->logo[0]) ? (String) $this->xml->logo[0]['src'] : null
		);
	}
	
	/**
	 * @return IInvoiceParticipant
	 */
	function getCustomer() {
		$invoiceName = isset($this->xml->customer[0]->invoicename[0]) ? (String) $this->xml->customer[0]->invoicename[0] : (String) $this->xml->customer[0]->name[0];
		$name = isset($this->xml->customer[0]->name[0]) ? (String) $this->xml->customer[0]->name[0] : (String) $this->xml->customer[0]->invoicename[0];
		
		$invoiceAddr = !isset($this->xml->customer[0]->invoiceaddress[0]) ? null :
				new InvoiceAddress(
					$invoiceName,
					(String) $this->xml->customer[0]->invoiceaddress[0]->street[0],
					(String) $this->xml->customer[0]->invoiceaddress[0]->city[0],
					(String) $this->xml->customer[0]->invoiceaddress[0]->postal[0],
					(String) $this->xml->customer[0]->invoiceaddress[0]->country[0]
				);
		
		$contactAddr = !isset($this->xml->customer[0]->address[0]) ? $invoiceAddr :
				new InvoiceAddress(
					$name,
					(String) $this->xml->customer[0]->address[0]->street[0],
					(String) $this->xml->customer[0]->address[0]->city[0],
					(String) $this->xml->customer[0]->address[0]->postal[0],
					(String) $this->xml->customer[0]->address[0]->country[0]
				);
		
		return new InvoiceParticipant(
				(int) $this->xml->customer[0]['ic'],
				isset($this->xml->customer[0]['dic']) ? (String) $this->xml->customer[0]['dic'] : null,
				$invoiceAddr ? $invoiceAddr : $contactAddr,
				$contactAddr
		);
	}
	
	/**
	 * @return array of IInvoiceItem
	 */
	function getItems() {
		$items = array();
		
		foreach($this->xml->items[0]->item as $item) {
			$items[] = new InvoiceItem(
							(String) $item,
							(float) $item['price'],
							isset($item['amount']) ? (int) $item['amount'] : 1,
							isset($item['unit']) ? (String) $item['unit'] : 'ks'
			);
		}
		
		return $items;
	}
	
}
