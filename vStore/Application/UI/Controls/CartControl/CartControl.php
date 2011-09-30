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
	Nette\Application\UI\Form;

/**
 * Shop products listing
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class CartControl extends BaseCartControl {
	
	/**
	 * @var array
	 */
	protected $data;
	
	public function __construct($parent = null, $name = null) {
		parent::__construct($parent, $name);
		$this->data = $this->getContext()->cart->loadAll();
	}	
	
	public function render() {
		$template = $this->createTemplate();
		$template->data = (array) $this->data;
		$template->setFile(__DIR__.'/templates/default.latte');
		echo $template;
	}
	
	public function handleDelete($id) {
		$this->cart->delete(intval($id));
		$this->redirect('this');
	}
	
	public function createComponentCartForm() {
		$form = new Form;
		$form->onSuccess[] = callback($this, 'cartFormSubmitted');
		
		foreach ($this->data as $product) {
			$form->addCheckbox('check'.$product['pageId']);
			$form->addText('range'.$product['pageId'])
					->setDefaultValue($product['quantity'])
					->addRule(Form::INTEGER, 'The number must be an integer!')
					->addRule(function ($control) {
						return $control->value < 1 ? !((bool) ($control->value = 1)) : true;
					}, 'The amount must be greater than zero...')				
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
					$this->getContext()->cart->delete($product['pageId']);
				}
			}
		} else if ($form['reCount']->isSubmittedBy()) {
			foreach ($this->data as $product) {
				if ($values['range'.$product['pageId']] !== $product['quantity']) {
					$item = $this->branch->get($product['pageId']);
					$this->getContext()->cart->save($item, $values['range'.$product['pageId']]);
				}
			}
		} else if ($form['buy']->isSubmittedBy()) {
			dd('and now whatever will be next...');
		} else {
			
		}
		$this->redirect('this');
	}
}
