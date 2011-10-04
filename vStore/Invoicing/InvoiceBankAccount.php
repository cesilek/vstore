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
		vStore;

/**
 * Bank account info for invocing usage
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 3, 2011
 */
class InvoiceBankAccount extends vBuilder\Object implements IInvoiceBankAccount {
	
	/** 
	 * @var array dictionary of bank code => bank name associations 
	 * @url http://www.bankovni-kody.cz/
	 */
	protected static $bankCodes = array(
			'0100' => 'Komerční banka a.s.',
			'0300' => 'Československá obchodní banka, a.s.',
			'0600' => 'GE Money Bank, a.s.',
			'0710' => 'Česká národni banka',
			'0800' => 'Česká spořitelna, a.s.',
			
			'2010' => 'Fio banka, a.s.',
			'2020' => 'Bank of Tokyo - Mitsubishi N.V. pobočka Praha',
			'2030' => 'AKCENTA, spořitelní a úvěrní družstvo',
			'2040' => 'UNIBON - spořitelní a úvěrní družstvo',
			'2050' => 'WPB Capital spořitelní družstvo',
			'2060' => 'Citfin, spořitelní družstvo',
			'2070' => 'Moravský peněžní ústav, s.d.',
			'2100' => 'Peněžní dům, spořitelní družstvo',
			'2200' => 'Evropsko-ruská banka, a.s.',
			'2210' => 'Citibank Europe plc, organizační složka',
			'2600' => 'Hypoteční banka, a.s.',
			'2700' => 'UniCredit Bank Czech Republic, a.s.',
			
			'3500' => 'ING Bank N.V.',
			
			'4000' => 'LBBW Bank CZ a.s.',
			'4300' => 'Českomoravská záruční a rozvojová banka, a.s.',
			
			'5000' => 'CALYON BANK CZECH REPUBLIC, a.s.',
			'5400' => 'The Royal Bank of Scotland N.V. (RBS)',
			'5500' => 'Raiffeisenbank a.s.',
			'5800' => 'J & T Banka, a.s.',
			
			'6000' => 'PPF banka, a.s.',
			'6100' => 'Banco Popolare Česká republika, a.s.',
			'6200' => 'COMMERZBANK AG, pobočka Praha',
			'6210' => 'BRE Bank S.A., o.s.p. (mBank)',
			'6300' => 'Fortis Bank SA/NV, pobočka ČR',
			'6700' => 'Všeobecná úverová banka, a.s., pobočka Praha',
			'6800' => 'VOLKSBANK CZ, a.s.',
			
			'7910' => 'Deutsche Bank A.G. Filiale Prag',
			'7940' => 'Waldviertler Sparkasse von 1842',
			'7950' => 'Raiffeisen stavební spořitelna a.s.',
			'7960' => 'Českomoravská stavební spořitelna a.s.',
			'7970' => 'Wüstenrot-stavební spořitelna a.s.',
			'7980' => 'Wüstenrot hypoteční banka,a.s.',
			'7990' => 'Modrá pyramida stavební spořitelna, a.s.',
			
			'8030' => 'Raiffeisenbank im Stiftland Waldsassen eG, odštěpný závod Cheb',
			'8040' => 'Oberbank AG pobočka Česká republika',
			'8060' => 'ČS stavební spořitelna a.s.',
			'8090' => 'Česká exportní banka, a.s.',
			'8150' => 'HSBC Bank plc - pobočka Praha',
	);
	
	/** @var string account number (if including prefix then string) */
	protected $accountNumber;
	
	/** @var int code of the bank */
	protected $bankCode;
	
	/** @var string name of the bank */
	protected $bankName;
	
	/** @var string swift code - http://cs.wikipedia.org/wiki/SWIFT */
	protected $swift;
	
	/** @var string IBAN - http://cs.wikipedia.org/wiki/IBAN */
	protected $iban;
	
	/**
	 * Constructor
	 * 
	 * @param string account number
	 * @param string bank code
	 * @param string bank name (if null then it will be determined automaticly based on bank code) 
	 * @param string swift code
	 * @param string iban code
	 * 
	 * @throws \InvalidArgumentException if bank code is unknown (and bank name not defined)
	 */
	function __construct($accountNumber, $bankCode, $bankName = null, $swift = null, $iban = null) {
		$this->accountNumber = $accountNumber;
		$this->bankCode = $bankCode;
		$this->swift = $swift;
		$this->iban = $iban;
		
		if($bankName)
			$this->bankName = $bankName;
		elseif(array_key_exists((String) $bankCode, static::$bankCodes))
			$this->bankName = static::$bankCodes[(String) $bankCode];
		else
			throw new \InvalidArgumentException("Unknown bank with code '$bankCode'");
	}
	
	/**
	 * @return string
	 */
	function getAccountNumber() {
		return $this->accountNumber;
	}
	
	/**
	 * @return int
	 */
	function getBankCode() {
		return $this->bankCode;
	}
	
	/**
	 * @return string
	 */
	function getBankName() {
		return $this->bankName;
	}
	
	/**
	 * @return null|string
	 */
	function getSwift() {
		return $this->swift;
	}
	
	/**
	 * @return null|string
	 */
	function getIban() {
		return $this->iban;
	}
	
}
