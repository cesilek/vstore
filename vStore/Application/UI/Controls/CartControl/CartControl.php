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
	vStore\Shop\CouponException;

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
			$delivery[$m->id] = $m->name;
			if(!isset($defaultDelivery))
				$defaultDelivery = $m->id;
		}
		$form->addRadioList('delivery')->setItems($delivery)
					->addRule(Form::FILLED, 'Způsob platby musí být vybrán.');
		
		$defaultDelivery = $this->order->delivery ? $this->order->delivery->id : $defaultDelivery;
		$form['delivery']->setDefaultValue($defaultDelivery);
		
		$payments = array();
		$defaultPayment = null;
		foreach($this->shop->availablePaymentMethods as $m) {
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
			
		} elseif($form['next']->isSubmittedBy()) {
			
			try {
				$this->shop->order->setDeliveryAndPayment(
								$this->shop->getDeliveryMethod($values->delivery),
								$this->shop->getPaymentMethod($values->payment)
				);
				
				$this->redirect('customerPage');
			
			} catch(vStore\Shop\OrderException $e) {
				$this->presenter->flashMessage('Zadaný neplatný způsob dobírky či platby.', 'warn');
				$this->redirect('deliveryPage');
			}
			
		} 
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
		
		if($this->order->delivery instanceof vStore\Shop\ParcelDeliveryMethod) {
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
				$lastUserOrder = $this->shop->getUserOrders()->where('[address] IS NOT NULL')->orderBy('[timestamp]')->fetch();
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
			
		}
		
		$form->addTextArea('note', 'Poznámka');		
		
		$customer = null;
		if($this->order->customer) {
			$customer = $this->order->customer;
		} elseif($this->context->user->isLoggedIn()) {
			if(!isset($lastUserOrder))
				$lastUserOrder = $this->shop->getUserOrders()->orderBy('[timestamp]')->fetch();
			
			if($lastUserOrder && $lastUserOrder->customer)
				$customer = $lastUserOrder->customer;
		}
		
		if($customer) {
			$form['name']->setDefaultValue($customer->name);
			$form['surname']->setDefaultValue($customer->surname);
			$form['email']->setDefaultValue($customer->email);
			$form['phone']->setDefaultValue($customer->phone);
		}
		
		$form['note']->setDefaultValue($this->order->note);
		
		$form->addSubmit('back', 'Zpět k výběru dopravy')->setValidationScope(false);
		$form->addSubmit('next', 'Pokračovat v objednávce');
		
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
			
			$this->order->note = $values->note;
			
			if($this->order->delivery instanceof vStore\Shop\ParcelDeliveryMethod) {
				
				// TODO: Nemel by tvorit entitu pokazdy, mel by se podivat, pokud tam takova uz neni
				if($this->order->address == null)
					$this->order->address = $this->order->repository->create('vStore\\Shop\\ShippingAddress');
				
				$this->order->address->street = $values->street;
				$this->order->address->houseNumber = $values->houseNumber;
				$this->order->address->city = $values->city;
				$this->order->address->zip = $values->zip;
				$this->order->address->country = $values->country;
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
			
			$this->presenter->flashMessage('Vaše objednávka byla úspěšně odeslána.');
			$this->redirect('lastPage', array('orderId' => $this->order->id));
		} 
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Order confirmation (lastPage)">

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
	
}
