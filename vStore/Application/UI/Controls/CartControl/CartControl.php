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
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class CartControl extends vStore\Application\UI\Control {
	
	/**
	 * @var array
	 */
	protected $data;
	
	public function __construct($parent = null, $name = null) {
		parent::__construct($parent, $name);

		$this->data = $this->context->cart->loadAll();
		IntegerPicker::register();
	}
	
	public function getCartData() {
		return $this->data;
	}
	
	protected function createRenderer() {
		return new CartRenderer($this);
	}
	
	// ***************************************************************************
	
	public function handleDelete($id) {
		$this->cart->delete(intval($id));
		$this->redirect('this');
	}
	
	public function createComponentCartForm() {
		$form = new Form;
		$form->onSuccess[] = callback($this, 'cartFormSubmitted');
		
		foreach ($this->data as $product) {
			$form->addCheckbox('check'.$product['pageId']);
			$form->addIntegerPicker('range'.$product['pageId'])
					->setDefaultValue($product['quantity'])
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
			foreach ($this->data as $product) {
				if ($values['check'.$product['pageId']] === true) {
					$this->context->cart->delete($product['pageId']);
				}
			}
		} else if ($form['reCount']->isSubmittedBy()) {
			foreach ($this->data as $product) {
				if ($values['range'.$product['pageId']] !== $product['quantity']) {
					$item = $this->redaction->get($product['pageId']);
					$this->context->cart->save($item, $values['range'.$product['pageId']]);
				}
			}
		} else if ($form['buy']->isSubmittedBy()) {
			$this->redirect('deliveryPage');
		} else {
			
		}
		$this->redirect('this');
	}
	
}
