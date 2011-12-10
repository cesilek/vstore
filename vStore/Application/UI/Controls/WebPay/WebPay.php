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

use Nette,
	Nette\FileNotFoundException,
	Nette\UnexpectedValueException,
	Nette\InvalidArgumentException,
	Nette\InvalidStateException,
	Nette\NotImplementedException,
	Nette\Caching\Cache,
	Nette\Forms\Container as FormContainer,
	vStore\Application\UI\Controls\WebPay\Object as WebPayObject,
	vStore\Application\UI\Controls\WebPay\Request as WebPayRequest,
	vStore\Application\UI\Controls\WebPay\Response as WebPayResponse;

/**
 * Control pro platbu přes WebPay bránu.
 * 
 * Převzato z http://addons.nette.org/cs/webpay a upraveno, aby fungovalo s novým Nette.
 *
 * Autor úpravy: Adam Staněk adam.stanek@v3net.cz 
 *
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.3 (opraveno pro nove Nette)
 */
class WebPay extends Nette\Application\UI\Control
{
	
	/**
	* Událost při vytváření platby.
	* @var array
	* @param WebPay $webPay
	* @param WebPayRequest $request Požadavek na banku které je možné/potřeba upravit dle potřeb.
	*/
	public $onCreate;
	
	/**
	* Událost při obdržení kladné odpovědi od banky.
	* @var array
	* @param WebPay $webPay
	* @param WebPayResponse $response Odpověd od banky.
	*/
	public $onResponse;
	
	/**
	* Událost při chybové odpovědi od banky.
	* Defaultně nastaveno na metodu, která přidá chybu jako flashMessage na presenter.
	* @see self::defaultError()
	* @var array
	* @param WebPay $webPay
	* @param WebPayException $exception Chyba od banky.
	*/
	public $onError = array(
		array(__CLASS__,'defaultError'),
	);
	
	/** @var string Cesta k privátnímu klíči obchodníka. */
	private $privateKeyFile;
	
	/** @var string Heslo privátního klíče obchodníka. */
	private $privateKeyPassphrase;
	
	/** @var string Cesta k veřejnému klíči (certifikátu) platební brány. */
	private $publicKeyFile;
	
	/** @var string Platební brána. Url adresa specifikovaná ve smlouvě. Zasílá se na ní požadavek. */
	private $requestUrl;
	
	/** @var int Přidělené číslo obchodníka. */
	private $merchantNumber;
	
	/**
	* Uloží parametry do perzistentního uložiště, dle názvu componenty a pořadového čísla objednávky.
	* @see WebPayRequest::saveParamsSessionAndCache() Defaultně se ukládá do session a cache.
	* @var callback
	* @param WebPay $webPay
	* @param int $orderNumber
	* @param array $params
	*/
	public static $saveParams = array(__CLASS__,'saveParamsSessionAndCache');
	
	/**
	* Vytáhne parametry z perzistentního uložiště, dle názvu componenty a pořadového čísla objednávky.
	* @see WebPayRequest::loadParamsSessionAndCache() Defaultně se ukládá do session a cache.
	* @var callback
	* @param WebPay $webPay
	* @param int $orderNumber
	*/
	public static $loadParams = array(__CLASS__,'loadParamsSessionAndCache');
	
	
	/**
	 * Returns control's context
	 *
	 * @return Nette\DI\IContainer
	 */
	public function getContext() {
		return $this->getPresenter()->getContext();
	}
	
	/**
	* Cesta k privátnímu klíči obchodníka.
	* @return string
	*/
	public function getPrivateKey()
	{
		return $this->privateKeyFile;
	}
	
	/**
	* Heslo privátního klíče obchodníka.
	* @return string
	*/
	public function getPrivateKeyPassphrasse()
	{
		return $this->privateKeyPassphrase;
	}
	
	/**
	* Privátní klíč obchodníka.
	* @param string Cesta k privátnímu klíči obchodníka.
	* @param string Heslo privátního klíče obchodníka.
	* @return WebPay
	*/
	public function setPrivateKey($file, $passphrase)
	{
		if (!extension_loaded('openssl'))
			throw new InvalidStateException("PHP extension OpenSSL is not loaded.");
			
		if (!is_readable($file)) throw new FileNotFoundException("File '$file' not found.");
		$fp = fopen($file, "r");
		$key = fread($fp, filesize($file));
		fclose($fp);
		// TODO php <= 5.2.5 nema druhej parametr, ale prvni je pole! (V documentaci to ale neni napsane.)
		if (!openssl_pkey_get_private($key, $passphrase))
		{
			throw new InvalidStateException("'$file' is not valid PEM formatted public key or passphrase is incorrect.");
		}
		
		$this->privateKeyFile = $file;
		$this->privateKeyPassphrase = $passphrase;
		
		return $this;
	}
	
	/**
	* Cesta k veřejnému klíči (certifikátu) platební brány.
	* @return string
	*/
	public function getPublicKey()
	{
		return $this->publicKeyFile;
	}
	
	/**
	* Cesta k veřejnému klíči (certifikátu) platební brány.
	* @param string
	* @return WebPay
	*/
	public function setPublicKey($file)
	{
		if (!extension_loaded('openssl'))
			throw new InvalidStateException("PHP extension OpenSSL is not loaded.");
			
		if (!is_readable($file)) throw new FileNotFoundException("File '$file' not found.");
		$fp = fopen($file, "r");
		$key = fread($fp, filesize($file));
		fclose($fp);
		if (!openssl_pkey_get_public($key))
		{
			throw new InvalidStateException("'$file' is not valid PEM formatted public key.");
		}
		
		$this->publicKeyFile = $file;
		
		return $this;
	}
	
	/**
	* Platební brána. Url adresa specifikovaná ve smlouvě. Zasílá se na ní požadavek.
	* @return string
	*/
	public function getRequestUrl()
	{
		return $this->requestUrl;
	}
	
	/**
	* Platební brána. Url adresa specifikovaná ve smlouvě. Zasílá se na ní požadavek.
	* @param string
	* @return WebPay
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
	* Přidělené číslo obchodníka.
	* @return int
	*/
	public function getMerchantNumber()
	{
		return $this->merchantNumber;
	}
	
	/**
	* Přidělené číslo obchodníka.
	* @param int
	* @return WebPay
	*/
	public function setMerchantNumber($merchantNumber)
	{
		$this->merchantNumber = $merchantNumber;
		return $this;
	}

	/**
	* Signál na vytvoření/odeslání požadavku do banky.
	* @see self::$onCreate Událost.
	* @see self::payLink() Tímto se vytváří odkaz na tento signál.
	* @return void
	*/
	public function handleCreate($params = NULL)
	{
		$this->pay();
	}
	
	/**
	* Signál pro obdržení odpovědi od banky.
	* @see self::$onResponse Událost, v případě úspěšné odpovědi.
	* @see self::$onError Událost, při chybové odpovědi.
	* @return void
	*/
	public function handleResponse()
	{
		try {
			$response = $this->createWebPayResponse();
			
			if ($params = call_user_func(self::$loadParams, $this, $response->getOrderNumber()))
				$this->setParams($params);
				
			$response->verify();
		}
		catch (WebPayException $e)
		{
			$this->onError($this, $e);
			$this->getPresenter()->redirect('this');
			return;
		}
		
		$this->onResponse($this, $response);
		$this->getPresenter()->redirect('this');
	}
	
	/**
	* Vytvoření/odeslání požadavku do banky.
	* @see self::$onCreate Událost.
	* @param array Tyto parametry budou k dispozici ve všech událostech (onCreate, onResponse, onError). WebPay::getParam()
	* @return void
	*/
	public function pay($params = NULL)
	{
		if ($params instanceof FormContainer) $params = $params->getValues();
		if ($params) $this->setParams($params);
		
		$this->check();
		
		$request = $this->createWebPayRequest();
		
		$this->onCreate($this,$request);
		
		$link = $request->getLink();
		
		call_user_func(self::$saveParams, $this, $request->getOrderNumber(), $this->getParam());
		
		$this->getPresenter()->redirectUrl($link);
	}
	
	/**
	* Vytvoří odkaz na signál na vytvoření/odeslání požadavku do banky.
	* @see self::handleCreate()
	* @param array Tyto parametry budou k dispozici ve všech událostech (onCreate, onResponse, onError). WebPay::getParam()
	* @return string
	*/
	public function payLink($params = NULL)
	{
		$this->check(); // TODO neni zbytecne kontrolovat pokazde kdyz se generuje jen odkaz? nekontrolovat az pri signalu, bylo by ale dobre aby se certifikaty nekontrolovali taky hned (kvuli vykonu)
		return $this->link('create!',$params);
	}
	
	/**
	* Nastaví paremetry.
	* @param array
	* @return WebPay
	*/
	private function setParams(array $params)
	{
		$this->loadState($this->params + $params);
		return $this;
	}
	
	/**
	* Vytvoření požadavku na banku.
	* @return WebPayRequest
	*/
	private function createWebPayRequest()
	{
		$request = new WebPayRequest($this->getRequestUrl(),$this->getMerchantNumber());

		$request->setPrivateKey($this->getPrivateKey(),$this->getPrivateKeyPassphrasse());

		$request->setResponseUrl($this->link('//response!'));
		
		return $request;
	}
	
	/**
	* Vytvoření odpovědi od banku.
	* @return WebPayResponse
	*/
	private function createWebPayResponse()
	{
		$response = new WebPayResponse($this->context->httpRequest->getQuery());
		
		$response->setPublicKey($this->getPublicKey());
		
		return $response;
	}
	
	/**
	* Zkontroluje jestli jsou dodány všechny potřebné náležitosti pro vytvoření požadavku a přijmutí odpovědi.
	* @throws Exception
	* @return void
	*/
	private function check()
	{
		if (!$this->getPresenter(false)) throw new InvalidStateException("WebPay '$this->name' is not attached to 'Presenter'.");
		if (!$this->getRequestUrl()) throw new UnexpectedValueException("Request url is required.");
		if (!$this->getMerchantNumber()) throw new UnexpectedValueException("Merchant number is required.");
		if (!$this->getPrivateKey()) throw new UnexpectedValueException("Private key is required.");
		if (!$this->getPublicKey()) throw new UnexpectedValueException("Public key is required.");
		if (!$this->onCreate) throw new InvalidStateException("You must specify one or more 'onCreate' events.");
		if (!$this->onResponse) throw new InvalidStateException("You must specify one or more 'onResponse' events.");
		if (!$this->onError) throw new InvalidStateException("You must specify one or more 'onError' events.");
		if (!is_callable(self::$saveParams)) throw new InvalidStateException("There is no valid handler for save params into persistent device.");
		if (!is_callable(self::$loadParams)) throw new InvalidStateException("There is no valid handler for load params out of persistent device.");
	}
	
	/**
	* Uloží parametry do perzistentního uložiště, dle názvu componenty a pořadového čísla objednávky.
	* Ukládá do session a cache.
	* @see WebPayRequest::getOrderNumber()
	* @see WebPayRequest::$saveParams Lze změnit způsob ukládání.
	* @param WebPay
	* @param int
	* @param array
	* @return void
	*/
	private static function saveParamsSessionAndCache(WebPay $webPay, $orderNumber, $params)
	{
		if (!$params) return;
		$orderNumber = (string) $orderNumber;
		
		$cache = new Cache($webPay->context->cacheStorage, 'WebPay/'.$webPay->name);
		$session = $webPay->context->session->getSection('WebPay/'.$webPay->name);

		$cache->save($orderNumber, $params, array(
			Cache::EXPIRE => '+ 2 day',
		));
		
		$session->{$orderNumber} = $params;
	}
	
	/**
	* Vytáhne parametry z perzistentního uložiště, dle názvu componenty a pořadového čísla objednávky.
	* Ukládá do session a cache.
	* @see WebPayRequest::getOrderNumber()
	* @see WebPayRequest::$loadParams Lze změnit způsob ukládání.
	* @param WebPay
	* @param int
	* @return array
	* @todo Je dobrý napat ukládat to jak do session tak do cache?
	*/
	private static function loadParamsSessionAndCache(WebPay $webPay, $orderNumber)
	{
		$orderNumber = (string) $orderNumber;
		
		$session = $webPay->context->session->getSection('WebPay/'.$webPay->name);
		if (isset($session->{$orderNumber}))
			return $session->{$orderNumber};
		else
		{
			$cache = new Cache($webPay->context->cacheStorage, 'WebPay/'.$webPay->name);
			if (isset($cache[$orderNumber]))
				return $cache[$orderNumber];
		}
		return NULL;
	}
	
	/**
	* Defaultní událost při chybové odpovědi od banky.
	* Přidá chybu jako flashMessage na presenter.
	* @see self::$onError
	* @param WebPay
	* @param WebPayException Chyba od banky.
	* @return void
	*/
	public static function defaultError(WebPay $webPay, WebPayException $exception)
	{
		$webPay->getParent()->flashMessage($exception->getMessage(), 'error');
	}
	
}