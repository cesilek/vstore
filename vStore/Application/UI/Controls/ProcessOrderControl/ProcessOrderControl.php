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
class ProcessOrderControl extends BaseCartControl {
	
	/**
	 * @var int product id
	 */
	protected $id;
	
	/**
	 * @param int $id 
	 */
	public function render($id) {	
		$this->id = $id;
		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/default.latte');
		$template->product = $this->branch->get($id);
		
		echo $template;
	}
	
	/**
	 * Form factory
	 * @return Form 
	 */
	public function createComponentAdjustOrderForm() {
		$form = new Form;
		$form->onSuccess[] = callback($this, 'adjustOrderFormSubmitted');
		
		$form->addHidden('id')
				->setDefaultValue($this->id);		
		$form->addText('amount', 'How many?')
				->setDefaultValue(1)
				->addRule(Form::INTEGER, 'The amount must be a number!');
		
		$form->addProtection();
		$form->addSubmit('s', 'Add to my cart');
	
		return $form;
	}
	
	public function adjustOrderFormSubmitted(Form $form) {
		$values = $form->values;
		
		if (!ctype_digit($values['id'])) {
			$form->addError('Something went wrong. Please try again.');
		}
		
		$cart = $this->getContext()->cart;
		$cart->setStorage(new vStore\Shop\SessionCartStorage($this->getContext()));
		if ($cart->add($this->branch->get($values['id']), $values->amount)) {
			$this->getPresenter()->flashMessage('The items were successfully added!');
			$this->getPresenter()->redirect('redaction', array('id' => 7));
		} else {
			$form->addError('An error occured while adding the items to the cart. Please try again.');
		}
	}
}
