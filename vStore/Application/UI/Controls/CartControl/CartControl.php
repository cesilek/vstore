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
		
		foreach($this->order->items as $item) {
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
			foreach ($this->order->items as $item) {
				if($values['check'.$item->uniqueId] === true) {
					$item->delete();
				}
			}
		} else if ($form['reCount']->isSubmittedBy()) {
			foreach ($this->order->items as $item) {
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
	
	// </editor-fold>	
	
	// <editor-fold defaultstate="collapsed" desc="Customer page">
	
	public function actionCustomerPage() {
		if(!$this->checkDeliveryPage()) $this->redirect('default');
		if(!$this->checkCustomerPage()) $this->redirect('deliveryPage');
	}
	
	protected function checkCustomerPage() {
		return $this->order->delivery != null && $this->order->payment != null;
	}
	
	// </editor-fold>	
	
	
}
