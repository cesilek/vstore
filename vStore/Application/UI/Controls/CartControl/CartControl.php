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

use vStore, Nette,
	vBuilder,
	vBuilder\Application\UI\Form\IntegerPicker,
	Nette\Application\UI\Form,
	vBuilder\Utils\Strings,
	vStore\Shop\CouponException,
	vStore\Shop\DeliveryMethods\ParametrizedDeliveryMethod;

/**
 * Cart control
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class CartControl extends vStore\Application\UI\Control {
	
	/** @persistent */
	public $orderId;
	
	// <editor-fold defaultstate="collapsed" desc="General">
	
	/**
	 * Returns current order
	 * 
	 * @return vStore\Shop\Order
	 */
	public function getOrder() {
		return $this->context->shop->order;
	}
	
	protected function createRenderer() {
		return new CartRenderer($this);
	}
	
	/**
	 * Returns session namespace provided to pass temporary data between control actions
	 *
	 * @return Nette\Http\SessionSection
	 */
	protected function getControlSession() {
		return $this->context->session->getSection('vStore.Application.UI.Controls.CartControl');
	}
	
	// </editor-fold>	
	
	// <editor-fold defaultstate="collapsed" desc="Shopping cart (default)">
	
	public function actionDefault() {
		
	}
	
	public function handleDelete($id) {
		$this->cart->delete(intval($id));
		$this->redirect('this');
	}
	
	public function createComponentCartForm($name) {
		// Nevolat v konstruktoru, protoze, pokud se jedna o handling akce (?do=cartControl-default), tak je konstruktor
		// zavolan jeste pred sablonou a nema tudiz nastavene potrebne vazby (resilo by to attached()?)
		IntegerPicker::register();
	
		$form = new Form($this, $name);
		$form->onSuccess[] = callback($this, 'cartFormSubmitted');
		
		foreach($this->order->getItems(true) as $item) {
			$form->addCheckbox('check'.$item->uniqueId);
			$form->addIntegerPicker('range'.$item->uniqueId)
					->setDefaultValue($item->amount)
					->addRule(function ($control) {
						return $control->value >= 0;
					}, 'Zadané číslo musí byt nula či vyšší')
					->addRule(Form::FILLED, 'Vyplňte prosím počet produktů');
		}

		$form->addSubmit('delete', 'Delete selected');
		$form->addSubmit('reCount', 'Recount');
		$form->addSubmit('buy', 'Buy');
		
		return $form;
	}
	
	
	public function cartFormSubmitted(Form $form) {
		$values = $form->values;
		foreach ($this->order->getItems(true) as $item) {
			$amount = $values['range'.$item->uniqueId];
			if ($amount == 0) {
				$item->delete();
				continue;
			}
			if($amount !== $item->amount) {
				$item->amount = $values['range'.$item->uniqueId];
			}
		}		
		if ($form['delete']->isSubmittedBy()) {
			foreach ($this->order->getItems(true) as $item) {
				if($values['check'.$item->uniqueId] === true) {
					$item->delete();
				}
			}
		} else if ($form['buy']->isSubmittedBy()) {
			$this->redirect('deliveryPage');
		} else {
			
		}
		$this->redirect('this');
	}
	
	public function createComponentCartDiscountCouponForm($name) {
		$form = new Form($this, $name);
		$form->getElementPrototype()->novalidate = 'novalidate';
		$form->onSuccess[] = callback($this, 'cartDiscountCouponFormSubmitted');
		
		$form->addText('discountCode', 'Máte slevový kupón? Vložte svůj kód:')
			->setRequired('Zadaný kód není platný')
			->addRule(Form::PATTERN, 'Zadaný kód není platný', '[A-Z0-9]{8}')
			->addFilter(function ($input) {
				return mb_strtoupper(trim($input));
			});

		$form->addSubmit('s', 'Uplatnit slevu');
		
		return $form;
	}
	
	public function cartDiscountCouponFormSubmitted(Form $form) {
		$values = $form->values;
		
		try {
			$this->order->setDiscountCode($values->discountCode);
			
			$this->presenter->flashMessage('Slevový kupón "' . $values->discountCode . '" byl úspěšně uplatněn na objednávku');
		} catch(CouponException $e) {
			switch($e->getCode()) {
				case CouponException::NOT_FOUND:
					$this->presenter->flashMessage('Slevový kupón s kódem "' . $values->discountCode . '" neexistuje', 'warn');
					break;
				
				case CouponException::EXPIRED:
					$this->presenter->flashMessage('Zadaný slevový kupón propadl :-(', 'warn');
					break;
					
				case CouponException::NOT_ACTIVE_YET:
					$this->presenter->flashMessage('Tento slevový kupón lze použít nejdříve ' . $e->getCoupon()->validSince->format('j.m.Y'), 'warn');
					break;
					
				case CouponException::USED:
					$this->presenter->flashMessage('Zadaný slevový kupón již byl jednou uplatněn.', 'warn');
					break;
				
				case CouponException::CONDITION_NOT_MET:
					$this->presenter->flashMessage('Tento slevový kupón lze uplatnit pouze při zakoupení produktu "' . $this->context->redaction->pageTitle($e->getCoupon()->requiredProductId) . '"', 'warn');
					break;				
					
				default:
					$this->presenter->flashMessage($e->getMessage(), 'error');		
			
			}
		}
		
		$this->redirect('this');
	}
	
	// </editor-fold>	
	
	// <editor-fold defaultstate="collapsed" desc="Delivery page">
	
	public function actionDeliveryPage() {
		if(!$this->checkDeliveryPage()) $this->redirect('default');
	}
	
	protected function checkDeliveryPage() {
		return $this->order->amount > 0;
	}
	
	public function createComponentDeliveryPaymentForm() {
		$form = new Form;
		$form->onSuccess[] = callback($this, 'deliveryPaymentFormSubmitted');
		
		$delivery = array();
		$defaultDelivery = null;
		foreach($this->shop->availableDeliveryMethods as $m) {
			if(!$m->isEnabled()) continue;
		
			$delivery[$m->id] = $m->name;
			if(!isset($defaultDelivery))
				$defaultDelivery = $m->id;
		}
		$form->addRadioList('delivery')->setItems($delivery)
					->addRule(Form::FILLED, 'Způsob platby musí být vybrán.');
		
		$form->addHidden('deliveryAttr');
		if($this->order->delivery && ($this->order->delivery instanceof ParametrizedDeliveryMethod)) {
			$params = $this->order->delivery->getParams();
			$form['deliveryAttr']->setDefaultValue($params[0]);
		}
		
		$defaultDelivery = $this->order->delivery
				? ($this->order->delivery instanceof ParametrizedDeliveryMethod ? $this->order->delivery->method->id : $this->order->delivery->id)
				: $defaultDelivery;
				
		$form['delivery']->setDefaultValue($defaultDelivery);
		
		$payments = array();
		$defaultPayment = null;
		foreach($this->shop->availablePaymentMethods as $m) {
			if(!$m->isEnabled()) continue;
			
			$payments[$m->id] = $m->name;
			if(!isset($defaultPayment) && $this->shop->getDeliveryMethod($defaultDelivery)->isSuitableWith($m))
				$defaultPayment = $m->id;
		}
		
		$form->addRadioList('payment')->setItems($payments)
					->addRule(Form::FILLED, 'Způsob doručení musí být vybrán.');
		
		$defaultPayment = $this->order->payment ? $this->order->payment->id : $defaultPayment;
		if(isset($defaultPayment))
				$form['payment']->setDefaultValue($defaultPayment);
		
		
		$form->addSubmit('back', 'Zpět do košíku')->setValidationScope(false);
		$form->addSubmit('next', 'Pokračovat v objednávce');
		
		return $form;
	}
	
	public function deliveryPaymentFormSubmitted(Form $form) {
		$values = $form->values;
		
		if($form['back']->isSubmittedBy()) {
			$this->redirect('default');
			
		// Pozor na AJAX submit (pripadna review page)
		} else /* if($form['next']->isSubmittedBy()) */ {
			
			try {
				$delivery = $this->shop->getDeliveryMethod($values->delivery);
				if($values->deliveryAttr) {
					$delivery = $delivery->createParametrizedMethod(array($values->deliveryAttr));
				}
			
				$this->shop->order->setDeliveryAndPayment(
								$delivery,
								$this->shop->getPaymentMethod($values->payment)
				);
				
				$this->redirect('customerPage');
			
			} catch(vStore\Shop\OrderException $e) {
				$this->presenter->flashMessage('Zadaný neplatný způsob dobírky či platby.', 'warn');
				$this->redirect('deliveryPage');
			}
			
		} 
	}
	
	/**
	 * Component factory. Creates delivery controls or fallback to parent handling.
	 * @param  string      component name
	 * @return IComponent  the created component (optionally)
	 */
	protected function createComponent($name) {
		if(Strings::startsWith($name, 'deliveryControl')) {
		
			foreach($this->shop->availableDeliveryMethods as $m) {
				if(mb_substr($name, 15) == $m->id) {
				
					if($m->getControlClass() != NULL) {
						$class = $m->getControlClass();
						$control = new $class($this, $name);
						
						return $control;
					}
									
					break;
				}
			}
			
			return null;
		}
		
		return parent::createComponent($name);
	}
	
	// </editor-fold>	
	
	// <editor-fold defaultstate="collapsed" desc="Customer page">
	
	public function actionCustomerPage() {
		if(!$this->checkDeliveryPage()) $this->redirect('default');
		if(!$this->checkCustomerPage()) $this->redirect('deliveryPage');
	}
	
	protected function checkCustomerPage() {
		return $this->order->delivery != null && $this->order->payment != null;
	}
	
	public function createComponentCustomerForm() {
		$allowCompanyOrders = true; // TODO: config
	
		$form = new Form;
		$form->onSuccess[] = callback($this, 'customerFormSubmitted');
		$control = $this;
		$form->onError[] = function () use ($control){
			$control->changeView('customerPage');
		};
				
		$form->addText('name', 'Jméno')
					->addRule(Form::FILLED, 'Je nutné vyplnit Vaše jméno');
		$form->addText('surname', 'Příjmení')
					->addRule(Form::FILLED, 'Je nutné vyplnit Vaše příjmení');
		
		$form->addText('phone', 'Telefon')
					->addRule(Form::FILLED);
		
		$form->addText('email', 'E-mail')
					->addRule(Form::FILLED, 'Je nutné vyplnit e-mailovou adresu')
					->addRule(Form::EMAIL, 'Prosím vyplňte platnou e-mailovou adresu');
		
		// Firemní objednávky -----------------------------------------------------
		if($allowCompanyOrders) {
			$form->addCheckbox('businessCustomer', 'Přeji si nakoupit jako firemní zákazník');
				
			$form->addText('companyIn', 'IČ')
				->addConditionOn($form['businessCustomer'], Form::EQUAL, TRUE)
					->addRule(Form::FILLED, 'Pokud si přejete objednat zboží jako firemní zákazník, je třeba vyplnit IČ společnosti')
					->addRule(function ($formControl) {
						return vBuilder\Utils\Validators::isCzechSubjectIn($formControl->getValue());
					}, 'Zadané IČ společnosti není platné');
					
			$form->addText('companyTin', 'DIČ');
			
			$form->addText('companyName', 'Název společnosti')
				->addConditionOn($form['businessCustomer'], Form::EQUAL, TRUE)
					->addRule(Form::FILLED, 'Pokud si přejete objednat zboží jako firemní zákazník, je třeba vyplnit název Vaší společnosti');	
		}
					
		// Adresa dodání -----------------------------------------------------------
					
		if($this->isCustomerAddressNeeded()) {
			$form->addText('street', 'Ulice')
						->addRule(Form::FILLED, 'Je nutné vyplnit adresu (Ulice).');
			
			$form->addText('houseNumber', 'Číslo popisné')
						->addRule(Form::FILLED, 'Je nutné vyplnit adresu (Č.P.).');

			$form->addText('city', 'Město')
						->addRule(Form::FILLED, 'Je nutné vyplnit adresu (Město).');

			$form->addText('zip', 'PSČ')
						->addRule(Form::FILLED, 'Je nutné vyplnit adresu (PSČ).');

			$form->addSelect('country', 'Země', $this->order->delivery->availableCountries);
			
			$address = null;
			if($this->order->address) {
				$address = $this->order->address;
			} elseif($this->context->user->isLoggedIn()) {
				$lastUserOrder = $this->shop->getUserOrders()->where('[address] IS NOT NULL')->orderBy('[timestamp] DESC')->fetch();
				if($lastUserOrder && $lastUserOrder->address)
					$address = $lastUserOrder->address;
			}
			
			if($address) {
				$form['street']->setDefaultValue($address->street);
				$form['city']->setDefaultValue($address->city);
				$form['houseNumber']->setDefaultValue($address->houseNumber);
				$form['zip']->setDefaultValue($address->zip);
				$form['country']->setDefaultValue($address->country);
			}
			
			// Fakturační adresa?
			if($allowCompanyOrders) {
				$form->addCheckbox('differentInvoiceAddress', 'Má fakturační adresa je odlišná od adresy dodání');
			}

		}
		
		// Fakturační adresa ---------------------------------------------------------
		if($allowCompanyOrders) {
		
			$c = $form->addText('invoiceStreet', 'Ulice')->addConditionOn($form['businessCustomer'], Form::EQUAL, TRUE);
			if(isset($form['differentInvoiceAddress'])) $c = $c->addConditionOn($form['differentInvoiceAddress'], Form::EQUAL, TRUE);
			$c->addRule(Form::FILLED, 'Je nutné vyplnit fakturační adresu (Ulice).');
		
			$c = $form->addText('invoiceHouseNumber', 'Číslo popisné')->addConditionOn($form['businessCustomer'], Form::EQUAL, TRUE);
			if(isset($form['differentInvoiceAddress'])) $c = $c->addConditionOn($form['differentInvoiceAddress'], Form::EQUAL, TRUE);
			$c->addRule(Form::FILLED, 'Je nutné vyplnit fakturační adresu (Č.P.).');

			$form->addText('invoiceCity', 'Město')->addConditionOn($form['businessCustomer'], Form::EQUAL, TRUE);
			if(isset($form['differentInvoiceAddress'])) $c = $c->addConditionOn($form['differentInvoiceAddress'], Form::EQUAL, TRUE);
			$c->addRule(Form::FILLED, 'Je nutné vyplnit fakturační adresu (Město).');

			$c = $form->addText('invoiceZip', 'PSČ')->addConditionOn($form['businessCustomer'], Form::EQUAL, TRUE);
			if(isset($form['differentInvoiceAddress'])) $c = $c->addConditionOn($form['differentInvoiceAddress'], Form::EQUAL, TRUE);
			$c->addRule(Form::FILLED, 'Je nutné vyplnit fakturační adresu (PSČ).');

			$form->addSelect('invoiceCountry', 'Země', $this->shop->getAvailableCountries());
		}
		
		
		$form->addTextArea('note', 'Poznámka');		
		$form->addSubmit('back', 'Zpět k výběru dopravy')->setValidationScope(false);
		$form->addSubmit('next', 'Pokračovat v objednávce');
		
		// Načtení dat z poslední objednávky ---------------------------------------------
		
		$customer = $this->order->customer ?: NULL;
		$company = $this->order->company ?: NULL;
		$justLoaded = false;
		
		if($this->context->user->isLoggedIn()) {
			// Predtim hledame jen s adresou, je mozne, ze dosud zadna objednavka nebyla poslana postou
			if(!isset($lastUserOrder) || $lastUserOrder == FALSE)
				$lastUserOrder = $this->shop->getUserOrders()->orderBy('[timestamp] DESC')->fetch();
			
			if($lastUserOrder) {
				if(!$customer && $lastUserOrder->customer) {
					$customer = $lastUserOrder->customer;

					// Dosud nebyly vyplneny informace o zakaznikovi => zrovna jsem je nacetl
					$justLoaded = true;	
				}

				// Nacitame data o firme jen jednou
				if(!$company && $lastUserOrder->company) $company = $lastUserOrder->company;				
			}	
		}
		
		
		
		if($customer) {
			$form['name']->setDefaultValue($customer->name);
			$form['surname']->setDefaultValue($customer->surname);
			$form['email']->setDefaultValue($customer->email);
			$form['phone']->setDefaultValue($customer->phone);
		}

		if($allowCompanyOrders && $company) {
			if($justLoaded || $this->order->company) $form['businessCustomer']->setDefaultValue(TRUE);
			$form['companyIn']->setDefaultValue($company->in);
			$form['companyTin']->setDefaultValue($company->tin);
			$form['companyName']->setDefaultValue($company->name);

			if($company->address) {
				if(isset($form['differentInvoiceAddress'])) $form['differentInvoiceAddress']->setDefaultValue(TRUE);

				$form['invoiceStreet']->setDefaultValue($company->address->street);
				$form['invoiceHouseNumber']->setDefaultValue($company->address->houseNumber);
				$form['invoiceCity']->setDefaultValue($company->address->city);
				$form['invoiceZip']->setDefaultValue($company->address->zip);
				$form['invoiceCountry']->setDefaultValue($company->address->country);
			}
		}
		
		$form['note']->setDefaultValue($this->order->note);
		
		
		
		return $form;
	}
	
	public function customerFormSubmitted(Form $form) {
		$values = $form->values;
		
		if($form['back']->isSubmittedBy()) {
			$this->redirect('deliveryPage');
			
		} elseif($form['next']->isSubmittedBy()) {
			
			// TODO: Nemel by tvorit entitu pokazdy, mel by se podivat, pokud tam takova uz neni
			if($this->order->customer == null)
				$this->order->customer = $this->order->repository->create('vStore\\Shop\\CustomerInfo');
			
			$this->order->customer->name = $values->name;
			$this->order->customer->surname = $values->surname;
			$this->order->customer->email = $values->email;
			$this->order->customer->phone = $values->phone;
			
			$invoiceAddress = null;
			
			// Firemni zakaznici
			if(isset($form['businessCustomer']) && $values->businessCustomer) {
				if($this->order->company == null)
					$this->order->company = $this->order->repository->create('vStore\\Shop\\Company');
				
				$this->order->company->in = $values->companyIn;	
				$this->order->company->tin = $values->companyTin;
				$this->order->company->name = $values->companyName;
								
				if(!isset($form['differentInvoiceAddress']) || $values->differentInvoiceAddress) {
					if($this->order->company->address == null)
						$this->order->company->address = $this->order->repository->create('vStore\\Shop\\CompanyAddress');
				
					$this->order->company->address->street = $values->invoiceStreet;
					$this->order->company->address->houseNumber = $values->invoiceHouseNumber;
					$this->order->company->address->city = $values->invoiceCity;
					$this->order->company->address->zip = $values->invoiceZip;
					$this->order->company->address->country = $values->invoiceCountry;
				} else {
					if($this->order->address == null)
						$this->order->address = $this->order->repository->create('vStore\\Shop\\ShippingAddress');
										
					// Pokud nejde o doruceni, tak si musim adresu nastavit sam, jinak se to nastavi
					// o par radku nize
					if(!$this->isCustomerAddressNeeded()) {
						$this->order->address->street = $values->street;
						$this->order->address->houseNumber = $values->houseNumber;
						$this->order->address->city = $values->city;
						$this->order->address->zip = $values->zip;
						$this->order->address->country = $values->country;
					}
					
					$this->order->company->address = $this->order->repository->create('vStore\\Shop\\CompanyAddress');
					$this->order->company->address->street = $values->street;
					$this->order->company->address->houseNumber = $values->houseNumber;
					$this->order->company->address->city = $values->city;
					$this->order->company->address->zip = $values->zip;
					$this->order->company->address->country = $values->country;					
				}
			} else
				$this->order->company = null;
			
			$this->order->note = $values->note;
			
			if($this->isCustomerAddressNeeded()) {
				
				// TODO: Nemel by tvorit entitu pokazdy, mel by se podivat, pokud tam takova uz neni
				if($this->order->address == null)
					$this->order->address = $this->order->repository->create('vStore\\Shop\\ShippingAddress');
				
				$this->order->address->street = $values->street;
				$this->order->address->houseNumber = $values->houseNumber;
				$this->order->address->city = $values->city;
				$this->order->address->zip = $values->zip;
				$this->order->address->country = $values->country;
			} else {
				$this->order->address = null;
			}
			
			$this->redirect('reviewPage');
		} 
	}
	
	// </editor-fold>	
	
	// <editor-fold defaultstate="collapsed" desc="Review page">
	
	public function actionReviewPage() {
		if(!$this->checkDeliveryPage()) $this->redirect('default');
		if(!$this->checkCustomerPage()) $this->redirect('deliveryPage');
		if(!$this->checkReviewPage()) $this->redirect('customerPage');
	}
	
	protected function checkReviewPage() {
		// TODO: adresa u parceldeliverymethod
		return $this->order->customer != null;
	}
	
	public function createComponentReviewForm() {
		$form = new Form;
		$form->onSuccess[] = callback($this, 'reviewFormSubmitted');
		
		$form->addSubmit('back', 'Zpět k zadání osobních údajů')->setValidationScope(false);
		$form->addSubmit('next', 'Dokončit objednávku');
		
		return $form;
	}
	
	public function reviewFormSubmitted(Form $form) {

		if($form['back']->isSubmittedBy()) {
			$this->redirect('customerPage');
			
		} elseif($form['next']->isSubmittedBy()) {
			$this->order->send();
			$this->getControlSession()->justSent = true;
			
			$this->presenter->flashMessage('Vaše objednávka byla úspěšně odeslána.');
			$this->redirect('lastPage', array('orderId' => $this->order->id));
		} 
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Order confirmation (lastPage)">

	public function actionLastPage() {
		if(isset($this->getControlSession()->justSent)) {
			$this->template->justSent = true;
			unset($this->getControlSession()->justSent);
		} else
			$this->template->justSent = false;
	}

	public function createComponentPayment() {
		$order = $this->shop->getOrder($this->getParam('orderId'));
	
		if($order->payment instanceOf vStore\Shop\DirectPaymentMethod) {
			$control = $order->payment->createComponent(
					$order,
					callback($this, 'onSuccessfulPayment'),
					callback($this, 'onFailedPayment')
			);
			
			return $control;
		}
			
		throw new Nette\InvalidStateException("Trying to create payment component on order without direct payment method.");
	}
	
	public function onSuccessfulPayment(vStore\Shop\Order $order) {
		$this->presenter->flashMessage('Platba proběhla úspěšně.');
		$this->redirect('lastPage', array('orderId' => $this->getParam('orderId')));
	}
	
	public function onFailedPayment(vStore\Shop\Order $order, $message) {
		$this->presenter->flashMessage('Při platbě došlo k chybě. ' . $message, 'error');
		$this->redirect('lastPage', array('orderId' => $this->getParam('orderId')));
	}
	
	// </editor-fold>
	
	/**
	 * Returns true if customer has to fill in his address
	 *
	 * @return bool
	 */
	protected function isCustomerAddressNeeded() {
		return $this->order->delivery instanceof vStore\Shop\DeliveryMethods\ParcelDeliveryMethod
					|| (
						$this->order->delivery instanceof vStore\Shop\DeliveryMethods\ParametrizedDeliveryMethod
						&& $this->order->delivery->getMethod() instanceof vStore\Shop\DeliveryMethods\ParcelDeliveryMethod
					);
	}
	
}
