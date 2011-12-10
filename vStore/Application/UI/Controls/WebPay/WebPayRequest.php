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

use Nette\FileNotFoundException,
	Nette\UnexpectedValueException,
	Nette\InvalidArgumentException,
	Nette\InvalidStateException;

/**
 * Požadavek na platební bránu.
 * 
 * Převzato z http://addons.nette.org/cs/webpay a upraveno, aby fungovalo s novým Nette.
 *
 * Autor úpravy: Adam Staněk adam.stanek@v3net.cz 
 *
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.3 (opraveno pro nove Nette)
 */
class Request extends Object
{
	
	/** @var resource OpenSSL key	*/
	protected $privateKey;
	
	/** @var string */
	protected $requestUrl;
	
	/** @var array  PARAM_NAME => [required? , validation, outputFilter] */
	protected $paramTypes = array(
		self::MERCHANTNUMBER => array(true, '#^[0-9]+$#'),
		self::OPERATION => array(true, array(NULL,'in_array', array(self::CREATE_ORDER),true)),
		self::ORDERNUMBER => array(true, '#^[0-9]{0,15}$#'),
		self::AMOUNT => array(true, '#^[0-9]{0,12}$#'),
		self::CURRENCY => array(true, array(__CLASS__,'validCurrency'), array(__CLASS__,'validCurrency')),
		self::DEPOSITFLAG => array(true, '#^(:?0|1)$#'),
		self::MERORDERNUM => array(false, '#^[0-9]{0,16}$#'),
		self::URL => array(true, '#^.+\.[a-z]{2,6}(\\/.*)?$#i'),
		self::DESCRIPTION => array(false, '#^.{0,125}$#'),
		self::MD => array(false, '#^.{0,125}$#'),
	);
	
	/**
	* @param string Platební brána - URL adresa specifikovaná ve smlouvě. Zasílá se na ní požadavek. 
	* @param int Přidělené číslo obchodníka.
	*/
	public function __construct($requestUrl = NULL, $merchantNumber = NULL)
	{
		parent::__construct();
		if ($requestUrl) $this->setRequestUrl($requestUrl);
		if ($merchantNumber) $this->setMerchantNumber($merchantNumber);
		
		// default params
		$this->setOperation(self::CREATE_ORDER);
	}
	
	/**
	* Přidělené číslo obchodníka.
	* povinné
	* @return int
	*/
	public function getMerchantNumber()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Přidělené číslo obchodníka.
	* povinné
	* @param int
	* @return WebPayRequest
	*/
	public function setMerchantNumber($merchantNumber)
	{
		return $this->processParam(__FUNCTION__, $merchantNumber);
	}
	
	/**
	* Jaká operace se má provést.
	* povinné
	* @see self::CREATE_ORDER
	* @return string self::CREATE_ORDER
	*/
	public function getOperation()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Jaká operace se má provést.
	* povinné
	* @see self::CREATE_ORDER
	* @param string self::CREATE_ORDER
	* @return WebPayRequest
	*/
	public function setOperation($operation)
	{
		return $this->processParam(__FUNCTION__, $operation);
	}
	
	/**
	* Pořadové číslo objednávky, číslo musí být v každém požadavku od obchodníka unikátní.
	* povinné
	* @return string
	*/
	public function getOrderNumber()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Pořadové číslo objednávky, číslo musí být v každém požadavku od obchodníka unikátní.
	* povinné
	* @param string
	* @return WebPayRequest
	* @todo Neexistuje moznost jak automaticky generovat orderNumber?
	*/
	public function setOrderNumber($orderNumber)
	{
		return $this->processParam(__FUNCTION__, $orderNumber);
	}
	
	/**
	* Částka v nejmenších jednotkách dané měny (pro Kč = v haléřích)
	* povinné
	* @return string
	*/
	public function getAmount()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Částka v nejmenších jednotkách dané měny (pro Kč = v haléřích)
	* Dále lze nastavit CURRENCY a DEPOSITFLAG
	* povinné
	* @param int Částka v nejmenších jednotkách dané měny.
	* @param int|string Měna dle ISO 4217.
	* @param bool Udává, zda má být objednávka uhrazena automaticky.
	* @return WebPayRequest
	*/
	public function setAmount($amount, $currency = NULL, $depositFlag = NULL)
	{
		if ($currency) $this->setCurrency($currency);
		if ($depositFlag !== NULL) $this->setDepositFlag($depositFlag);
		return $this->processParam(__FUNCTION__, $amount);
	}
	
	/**
	* Identifikátor měny dle ISO 4217.
	* povinné
	* @see self::validCurrency()
	* @return int|string 
	*/
	public function getCurrency()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Identifikátor měny dle ISO 4217.
	* povinné
	* @see self::validCurrency()
	* @param int|string např: 203 nebo CZK
	* @return WebPayRequest
	*/
	public function setCurrency($currency)
	{
		return $this->processParam(__FUNCTION__, $currency);
	}
	
	/**
	* Udává, zda má být objednávka uhrazena automaticky.
	* 0 = není požadována úhrada
	* 1 = je požadována úhrada
	* povinné
	* @return bool
	*/
	public function getDepositFlag()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Udává, zda má být objednávka uhrazena automaticky.
	* 0 = není požadována úhrada
	* 1 = je požadována úhrada
	* povinné
	* @param bool
	* @return WebPayRequest
	*/
	public function setDepositFlag($depositFlag)
	{
		return $this->processParam(__FUNCTION__, is_bool($depositFlag)?($depositFlag?'1':'0'):$depositFlag);
	}
	
	/**
	* Identifikace objednávky pro obchodníka.
	* Zobrazí se na výpisu z banky. 
	* Každá banka má své řešení. 
	* V případě, že není zadáno, použije se hodnota ORDERNUMBER
	* @return string
	*/
	public function getMerOrderNum()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Identifikace objednávky pro obchodníka.
	* Zobrazí se na výpisu z banky. 
	* Každá banka má své řešení. 
	* V případě, že není zadáno, použije se hodnota ORDERNUMBER
	* @param string
	* @return WebPayRequest
	*/
	public function setMerOrderNum($merOrderNum)
	{
		return $this->processParam(__FUNCTION__, $merOrderNum);
	}
	
	/**
	* Plná URL adresa obchodníka. (včetně specifikace protokolu – např. https://)
	* Na tuto adresu bude odeslán výsledek požadavku.
	* povinné
	* @return string
	*/
	public function getResponseUrl()
	{
		return $this->getParam(self::URL);
	}
	
	/**
	* Plná URL adresa obchodníka. (včetně specifikace protokolu – např. https://)
	* Na tuto adresu bude odeslán výsledek požadavku.
	* povinné
	* @param string
	* @return WebPayRequest
	*/
	public function setResponseUrl($url)
	{
		return $this->setParam(self::URL,$url);
	}
	
	/**
	* Popis nákupu. Obsah pole se přenáší do 3-D systému pro možnost následné kontroly
	* držitelem karty během autentikace u Access Control Serveru vydavatelské banky.
	* Pole musí obsahovat pouze ASCII znaky v rozsahu 0x20 – 0x7E.
	* 
	* @todo Otestovat jestli může obsahovat urlencoded znaky mimo rozsah.
	* 
	* @return string
	*/
	public function getDescription()
	{
		return $this->processParam(__FUNCTION__);
	}
	
	/**
	* Popis nákupu. Obsah pole se přenáší do 3-D systému pro možnost následné kontroly
	* držitelem karty během autentikace u Access Control Serveru vydavatelské banky.
	* Pole musí obsahovat pouze ASCII znaky v rozsahu 0x20 – 0x7E.
	* @param string
	* @return WebPayRequest
	*/
	public function setDescription($description)
	{
		return $this->processParam(__FUNCTION__, $description);
	}
	
	/**
	* Libovolná data obchodníka, která jsou vrácena obchodníkovi v odpovědi v nezměněné podobě.
	* Pole se používá pro uspokojení rozdílných požadavků jednotlivých e-shopů.
	* Pole musí obsahovat pouze ASCII znaky v rozsahu 0x20 – 0x7E.
	* Pokud je nezbytné přenášet jiná data, potom je zapotřebí použít BASE64 kódování.
	* Pole nesmí obsahovat osobní údaje.
	* Výsledná délka dat může být maximálně 30 B.
	* @return string
	*/
	public function getMd()
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
	* @param string
	* @return WebPayRequest
	*/
	public function setMd($md)
	{
		return $this->processParam(__FUNCTION__, $md);
	}

	/**
	* Privátní klíč obchodníka.
	* povinné
	* @return resource OpenSSL key
	*/
	public function getPrivateKey()
	{
		return $this->privateKey;
	}
	
	/**
	* Privátní klíč obchodníka.
	* povinné
	* @param string Cesta ke klíči.
	* @param string Heslo klíče
	* @return WebPayRequest
	*/
	public function setPrivateKey($file, $passphrase)
	{
		if (!is_readable($file)) throw new FileNotFoundException("File '$file' not found.");
		$fp = fopen($file, "r");
		$key = fread($fp, filesize($file));
		fclose($fp);
		// TODO php <= 5.2.5 nema druhej parametr, ale prvni je pole! (V documentaci to ale neni napsane.)
		if (!($this->privateKey = openssl_pkey_get_private($key, $passphrase)))
		{
			throw new InvalidStateException("'$file' is not valid PEM formatted public key or passphrase is incorrect.");
		}
		return $this;
	}
	
	/**
	* Platební brána.
	* Url adresa specifikovaná ve smlouvě.
	* Zasílá se na ní požadavek. 
	* povinné
	* @return string
	*/
	public function getRequestUrl()
	{
		return $this->requestUrl;
	}
	
	/**
	* Platební brána.
	* Url adresa specifikovaná ve smlouvě.
	* Zasílá se na ní požadavek. 
	* povinné
	* @param string
	* @return WebPayRequest
	*/
	public function setRequestUrl($url)
	{
		if (!preg_match('#^https://#i',$url) OR strpos($url,'?') OR strpos($url,'#'))
		{
			throw new UnexpectedValueException("Request url is not valid.");
		}
		$this->requestUrl = $url;
		return $this;
	}
	
	
	/**
	* Vrátí všechny parametry připravené na odeslání požadavku.
	* Obsahuje i podpis (DIGEST)
	* @return array
	*/
	public function getParams()
	{
		$params = array();
		
		if (!$this->privateKey) throw new InvalidStateException("Private key is required.");
		
		if (!$this->getRequestUrl()) throw new InvalidStateException("Request url is required.");
		
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
		openssl_sign($digestText, $signature, $this->privateKey);
		$signature = base64_encode($signature);
		$params[self::DIGEST] = $signature;

		return $params;
	}
	
	/**
	* Vrátí link požadavku se všemy připravenými parametry.
	* @return string
	*/
	public function getLink()
	{
		return $this->getRequestUrl().'?'.http_build_query($this->getParams());
	}
	
	/**
	* Vrací číselný formát měny podle ISO 4217.
	* @param int|string 203 nebo CZK
	* @return int|NULL
	*/
	protected static function validCurrency($code)
	{
		// ISO 4217
		$codes = array(
			'CZK' => 203,
			'EUR' => 978,
			'USD' => 840,
		);
		if (isset($codes[$code])) return $codes[$code];
		if (in_array($code,$codes)) return $codes[array_search($code,$codes)];
		return NULL;
	}
	
	
}