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
 
namespace vStore\Application\UI\Controls;

/**
 * Chyba od platební brány.
 * 
 * Převzato z http://addons.nette.org/cs/webpay a upraveno, aby fungovalo s novým Nette.
 *
 * Autor úpravy: Adam Staněk adam.stanek@v3net.cz 
 *
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.3 (opraveno pro nove Nette)
 */
class WebPayException extends \RuntimeException
{
	
	/** @var string Slovní popis chyby, který je jednoznačně dán kombinací PRCODE a SRCODE. */
	private $resultText;
	
	/** @var int Udává primární návratový kód. */
	private $primaryCode;
	
	/** @var int Udává sekundární kód. */
	private $secondaryCode;
	
	/** @var WebPayResponse */
	private $response;
	
	/**
	* @param string Slovní popis chyby, který je jednoznačně dán kombinací PRCODE a SRCODE.
	* @param int Udává primární návratový kód.
	* @param int Udává sekundární kód.
	* @param WebPayResponse
	*/
	public function __construct($resultText, $primaryCode, $secondaryCode, WebPay\Response $response)
	{
		parent::__construct($resultText, $primaryCode);
		
		$this->resultText = $resultText;
		$this->primaryCode = $primaryCode;
		$this->secondaryCode = $secondaryCode;
		$this->response = $response;
	}
	
	public function getPrimaryCode() {
		return $this->primaryCode;
	}
	
	public function getSecondaryCode() {
		return $this->secondaryCode;
	}
	
}