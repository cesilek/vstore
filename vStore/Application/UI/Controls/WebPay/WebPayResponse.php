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
 
namespace vStore\Application\UI\Controls\WebPay;

use vStore\Application\UI\Controls\WebPayException,
	Nette\FileNotFoundException,
	Nette\UnexpectedValueException,
	Nette\InvalidArgumentException,
	Nette\InvalidStateException,
	Nette\NotImplementedException;

/**
 * Odpověd od platební brány.
 * 
 * Převzato z http://addons.nette.org/cs/webpay a upraveno, aby fungovalo s novým Nette.
 *
 * Autor úpravy: Adam Staněk adam.stanek@v3net.cz 
 *
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.3 (opraveno pro nove Nette)
 */
class Response extends Object
{
	
	/** @var resource OpenSSL key	*/
	protected $publicKey;
	
	/** @var string	*/
	protected $digest;
	
	/** @var array  PARAM_NAME => [required? , validation], outputFilter */
	protected $paramTypes = array(
		self::OPERATION => array(true, array(NULL,'in_array', array(self::CREATE_ORDER),true)),
		self::ORDERNUMBER => array(true, '#^[0-9]{0,15}$#'),
		self::MERORDERNUM => array(false, '#^[0-9]{0,16}$#'),
		self::MD => array(false, '#^.{0,125}$#'),
		self::PRCODE => array(true, '#^[0-9]+$#'),
		self::SRCODE => array(true, '#^[0-9]+$#'),
		self::RESULTTEXT => array(false, '#^.{0,255}$#'),
	);
	
	/**
	* @param array Parametry odpovědi.
	*/
	public function __construct($params = NULL)
	{
		parent::__construct();
		if ($params) $this->setParams($params);
	}
	
	/**
	* Parametry odpovědi.
	* povinné
	* @return array
	*/
	public function getParams()
	{
		// TODO
		throw new NotImplementedException;
	}
	
	/**
	* Parametry odpovědi.
	* např z $_GET
	* povinné
	* @param array
	* @return WebPayResponse
	*/
	public function setParams(array $params)
	{
		$this->params = array();
		$this->digest = NULL;
		foreach ($this->paramTypes as $paramName => $paramValidation)
		{
			$paramValue = NULL;
			if (isset($params[$paramName])) $paramValue = $params[$paramName];
			if ($paramValue === NULL AND $paramValidation[0])
			{
				throw new InvalidStateException("'$paramName' is required.");
			}
			$this->setParam($paramName,$paramValue);
		}
		
		if (isset($params[self::DIGEST]))
			$this->digest = $params[self::DIGEST];
		
		return $this;
	}
	
	/**
	* Jaká operace se má provést.
	* @see self::CREATE_ORDER
	* @return string self::CREATE_ORDER
	*/
	public function getOperation()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Pořadové číslo objednávky, číslo musí být v každém požadavku od obchodníka unikátní.
	* Obsah pole z požadavku.
	* @return string
	*/
	public function getOrderNumber()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Identifikace objednávky pro obchodníka.
	* Zobrazí se na výpisu z banky. 
	* Obsah pole z požadavku, pokud bylo uvedeno.
	* @return string
	*/
	public function getMerOrderNum()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Libovolná data obchodníka, která jsou vrácena obchodníkovi v odpovědi v nezměněné podobě.
	* Pole se používá pro uspokojení rozdílných požadavků jednotlivých e-shopů.
	* Pole musí obsahovat pouze ASCII znaky v rozsahu 0x20 – 0x7E.
	* Pokud je nezbytné přenášet jiná data, potom je zapotřebí použít BASE64 kódování.
	* Pole nesmí obsahovat osobní údaje.
	* Výsledná délka dat může být maximálně 30 B.
	* Obsah pole z požadavku, pokud bylo uvedeno.
	* @return string
	*/
	public function getMd()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Udává primární návratový kód.
	* @return int
	* @see WebPayException
	*/
	public function getPrimaryCode()
	{
		return $this->getParam(self::PRCODE);
	}
	

	/**
	* Udává sekundární kód.
	* @return int
	* @see WebPayException
	*/
	public function getSecondaryCode()
	{
		return $this->getParam(self::SRCODE);
	}
	
	/**
	* Slovní popis chyby, který je jednoznačně dán kombinací PRCODE a SRCODE.
	* @todo prevest toto pole na utf8 ?
	* @return string
	*/
	public function getResultText()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Veřejný klíč (certifikát) platební brány.
	* povinné
	* @return resource OpenSSL key
	*/
	public function getPublicKey()
	{
		return $this->publicKey;
	}
	
	/**
	* Veřejný klíč (certifikát) platební brány.
	* povinné
	* @param resource OpenSSL key
	* @return WebPayResponse
	*/
	public function setPublicKey($file)
	{
		if (!is_readable($file)) throw new FileNotFoundException("File '$file' not found.");
		$fp = fopen($file, "r");
		$key = fread($fp, filesize($file));
		fclose($fp);
		if (!($this->publicKey = openssl_pkey_get_public($key)))
		{
			throw new InvalidStateException("'$file' is not valid PEM formatted public key.");
		}
		return $this;
	}
	
	/**
	* Kontroluje jestli je vše v pořádku.
	* Případně vyhazuje WebPayException.
	* @throws WebPayException
	* @return bool
	*/
	public function verify()
	{
		if (!$this->publicKey) throw new InvalidStateException("Public key is required.");
		
		$params = array();
		
		foreach ($this->paramTypes as $paramName => $paramValidation)
		{
			$paramValue = $this->getParam($paramName);
			if ($paramValue === NULL AND $paramValidation[0])
			{
				throw new InvalidStateException("'$paramName' is required.");
			}
			if ($paramValue !== NULL)
				$params[$paramName] = $paramValue;
		}
		
		$digestText = implode('|',$params);
		$signature = base64_decode($this->digest);
		if (openssl_verify($digestText, $signature, $this->publicKey) !== 1)
		{
			throw new InvalidStateException('Response is not verified. The signature is incorrect.');
		}
		
		if ($this->getPrimaryCode() != '0' OR $this->getSecondaryCode() != '0')
		{
			throw new WebPayException(
				$this->getResultText(),
				$this->getPrimaryCode(),
				$this->getSecondaryCode(),
				$this
			);
		}
		
		return true;
	}
}
