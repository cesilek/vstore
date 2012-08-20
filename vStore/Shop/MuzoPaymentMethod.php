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
	Nette,
	vStore\Application\UI\Controls\WebPay,
	vStore\Application\UI\Controls\WebPayException,
	vStore\Application\UI\Controls\WebPay\Request as WebPayRequest,
	vStore\Application\UI\Controls\WebPay\Response as WebPayResponse;


/**
 * Shop payment method for direct payment by credit card.
 * Payment is realized through GP WebPay (formerly PayMUZO).
 *
 * @see http://www.globalpaymentsinc.com/Europe/Czech/productsServices/eCommerce.html
 * @see http://addons.nette.org/cs/webpay
 *
 * @author Adam Staněk (velbloud)
 * @since Dec 10, 2011
 */
class MuzoPaymentMethod extends DirectPaymentMethod {
	
	private $_muzoGatewayUrl;
	private $_muzoGatewayPublicKey;
	private $_muzoMerchantNumber;
	private $_muzoMerchantPrivateKey;
	private $_muzoMerchantPasspharse;
	
	/**
	 * Creates method from app configuration
	 * 
	 * @param string id
	 * @param vBuilder\Config\ConfigDAO config
	 * @param Nette\DI\IContainer DI context
	 */
	static function fromConfig($id, vBuilder\Config\ConfigDAO $config, Nette\DI\IContainer $context) {
		$method = parent::fromConfig($id, $config, $context);
		
		$method->_muzoGatewayUrl = $config->get('muzo.gateway.url');
		if(!isset($method->_muzoGatewayUrl) && $method->isEnabled()) throw new vBuilder\InvalidConfigurationException("Muzo: Missing '$id.muzo.gateway.url' option");
		
		$method->_muzoGatewayPublicKey = $config->get('muzo.gateway.publicKey');
		if(!isset($method->_muzoGatewayPublicKey) && $method->isEnabled()) throw new vBuilder\InvalidConfigurationException("Muzo: Missing '$id.muzo.gateway.publicKey' option");
		
		$method->_muzoGatewayPublicKey = APP_DIR . '/../' . $method->_muzoGatewayPublicKey;
		if(!file_exists($method->_muzoGatewayPublicKey) && $method->isEnabled()) throw new Nette\InvalidStateException("Muzo: Missing gateway public key '".$method->_muzoGatewayPublicKey."'");
		
		$method->_muzoMerchantNumber = $config->get('muzo.merchant.number');
		if(!isset($method->_muzoMerchantNumber) && $method->isEnabled()) throw new vBuilder\InvalidConfigurationException("Muzo: Missing '$id.muzo.merchant.number' option");
		
		$method->_muzoMerchantPrivateKey = $config->get('muzo.merchant.privateKey');
		if(!isset($method->_muzoMerchantPrivateKey) && $method->isEnabled()) throw new vBuilder\InvalidConfigurationException("Muzo: Missing '$id.muzo.merchant.privateKey' option");
		$method->_muzoMerchantPrivateKey = APP_DIR . '/../' . $method->_muzoMerchantPrivateKey;
		if(!file_exists($method->_muzoMerchantPrivateKey) && $method->isEnabled()) throw new Nette\InvalidStateException("Muzo: Missing merchant private key '".$method->_muzoMerchantPrivateKey."'");
		
		$method->_muzoMerchantPasspharse = $config->get('muzo.merchant.passpharse');
		if(!isset($method->_muzoMerchantPasspharse) && $method->isEnabled()) throw new vBuilder\InvalidConfigurationException("Muzo: Missing '$id.muzo.merchant.passpharse' option");
		
		return $method;
	}

	/**
	 * Creates WebPay control for handling WebPay requests/responses
	 *
	 * @param vStore\Shop\Order
	 * @param Nette\Callback callback for successful payment handling
	 * @param Nette\Callback callback for unsuccessful payment handling
	 *
	 * @return vStore\Application\UI\Controls\WebPay
	 */
	function createComponent(Order $order, Nette\Callback $onSuccessCallback, Nette\Callback $onErrorCallback) {
		if(!$this->isEnabled()) throw new Nette\InvalidStateException('Cannot create payment component. Payment ' . $this->id . ' is disabled by config.');
	
		$wp = new WebPay;
		
		$wp->setRequestUrl($this->_muzoGatewayUrl);
		$wp->setMerchantNumber($this->_muzoMerchantNumber);
		$wp->setPublicKey($this->_muzoGatewayPublicKey);
		$wp->setPrivateKey($this->_muzoMerchantPrivateKey, $this->_muzoMerchantPasspharse);
		
		// Vytvareni pozadavku
		$wp->onCreate[] = function (WebPay $webPay, WebPayRequest $request) use ($order) {
			// Pořadové číslo objednávky. Je potřeba při každém i nepovedeném požadavku změnit.
			// Maximální délka je 15 číslic (YYMM NNNN SSSSSSS)
			
			$orderNumber = $order->timestamp->format('ym') . str_pad(mb_substr($order->id, 6), 4, "0", STR_PAD_LEFT); // 2*Y + 2*M + {1..4}N => 8 cifer
			$timeDiff = abs(time() - $order->timestamp->getTimestamp());
			
			// Pokud je objednavka starsi jak cca 115 dni, tak by doslo k preteceni citace (Mame k dispozici jen 7 cifer)
			if($timeDiff > 9999999) throw new Nette\InvalidStateException("Order is too old for direct payment.");
			
			$request->setOrderNumber($orderNumber . str_pad($timeDiff, 7, "0", STR_PAD_LEFT));

			// Cena objednavky v halirich
			$request->setAmount(($order->total + $order->ceiling) * 100, 'CZK', true);
		};
		
		// Pri uspesne dokoncene objednavce (PRCODE = 0, SRCODE = 0, overeno verejnym certifikatem)
		$wp->onResponse = array();
		$wp->onResponse[] = function (WebPay $webPay, WebPayResponse $response) use($onSuccessCallback, $order) {
			$order->isPaid = true;
			$order->save();
		
			$onSuccessCallback->invoke($order);
		};
		
		// Pri chybe (PRCODE <> 0 || SRCODE <> 0)
		$wp->onError = array();
		$wp->onError[] = function (WebPay $webPay, WebPayException $exception) use($onErrorCallback, $order) {
			$msg = $exception->getMessage();

			// Překlad chybových zpráv
			if($exception->getPrimaryCode() == 30) {
				switch($exception->getSecondaryCode()) {
					case 1001: $msg = "Neúspěšná autorizace – karta blokovaná."; break;
					case 1002: $msg = "Autorizace zamítnuta."; break;
					case 1003: $msg = "Neúspěšná autorizace – problém karty. Kontaktujte vydavatele karty."; break;
					case 1004: $msg = "Neúspěšná autorizace – technický problém v autorizačním centru."; break;
					case 1005: $msg = "Neúspěšná autorizace – problém účtu. Kontaktujte vydavatele karty."; break;
					
					default:
						Nette\Diagnostics\Debugger::log($exception);
				}
			} elseif($exception->getPrimaryCode() == 28) {
				switch($exception->getSecondaryCode()) {
					case 3000: $msg = "Neúspěšné ověření držitele karty. Kontaktujte vydavatele karty."; break;
					case 3001: $msg = "Autorizace zamítnuta."; break;
					case 3002: $msg = "Vydavatel karty nebo karta není zapojena do 3D. Kontaktujte vydavatele karty."; break;
					case 3004: $msg = "Vydavatel karty není zapojen do 3D nebo karta
nebyla aktivována. Kontaktujte vydavatele."; break;
					case 3005: $msg = "Technický problém při ověření držitele karty.
Kontaktujte vydavatele karty."; break;
					case 3006: $msg = "Technický problém při ověření držitele karty."; break;
					case 3007: $msg = "Technický problém v systému zúčtující banky. Kontaktujte obchodníka."; break;
					case 3008: $msg = "Použit nepodporovaný karetní produkt. Kontaktujte vydavatele karty."; break;
					
					default:
						Nette\Diagnostics\Debugger::log($exception);
				}
			
			} elseif($exception->getPrimaryCode() == 17) {
				$msg = "Částka k úhradě překročila autorizovanou částku.";	
				
			} elseif($exception->getPrimaryCode() == 18) {
				$msg = "Součet kreditovaných částek překročil uhrazenou částku.";	
			
			} elseif($exception->getPrimaryCode() == 35) {
				$msg = "Vypršel časový limit pro provedení platby.";
			
			} else
				Nette\Diagnostics\Debugger::log($exception);
			
		
			$onErrorCallback->invoke($order, $msg);
		};
		
		return $wp;
	}
		
}