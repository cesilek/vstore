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
	Nette\Application\UI\Form;

/**
 * Cart control
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class CartControl extends vStore\Application\UI\Control {
	
	// <editor-fold defaultstate="collapsed" desc="General">
	
	public function __construct($parent = null, $name = null) {
		parent::__construct($parent, $name);

		IntegerPicker::register();
	}
	
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
	
	public function createComponentCartForm() {
		$form = new Form;
		$form->onSuccess[] = callback($this, 'cartFormSubmitted');
		
		foreach($this->order->getItems(true) as $item) {
			$form->addCheckbox('check'.$item->uniqueId);
			$form->addIntegerPicker('range'.$item->uniqueId)
					->setDefaultValue($item->amount)
					->addRule(IntegerPicker::POSITIVE, 'The amount must be greater than zero...')
					->addRule(Form::FILLED, 'The number must be filled!');
		}

		$form->addSubmit('delete', 'Delete selected');
		$form->addSubmit('reCount', 'Recount');
		$form->addSubmit('buy', 'Buy');
		
		return $form;
	}
	
	
	public function cartFormSubmitted(Form $form) {
		$values = $form->values;
		if ($form['delete']->isSubmittedBy()) {
			foreach ($this->order->getItems(true) as $item) {
				if($values['check'.$item->uniqueId] === true) {
					$item->delete();
				}
			}
		} else if ($form['reCount']->isSubmittedBy()) {
			foreach ($this->order->getItems(true) as $item) {
				if($values['range'.$item->uniqueId] !== $item->amount) {
					$item->amount = $values['range'.$item->uniqueId];
				}
			}
		} else if ($form['buy']->isSubmittedBy()) {
			$this->redirect('deliveryPage');
		} else {
			
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
			
			if($this->order->address) {
				$form['street']->setDefaultValue($this->order->address->street);
				$form['city']->setDefaultValue($this->order->address->city);
				$form['houseNumber']->setDefaultValue($this->order->address->houseNumber);
				$form['zip']->setDefaultValue($this->order->address->zip);
				$form['country']->setDefaultValue($this->order->address->country);
			}
			
		}
		
		$form->addTextArea('note', 'Poznámka');		
		
		if($this->order->customer) {
			$form['name']->setDefaultValue($this->order->customer->name);
			$form['surname']->setDefaultValue($this->order->customer->surname);
			$form['email']->setDefaultValue($this->order->customer->email);
			$form['phone']->setDefaultValue($this->order->customer->phone);
			$form['note']->setDefaultValue($this->order->note);
		}
		
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
	
	// <editor-fold defaultstate="collapsed" desc="Review page (last)">
	
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
	
}
