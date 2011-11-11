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
	Nette\Application\UI\Form,
	vBuilder\Orm\Repository;

/**
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class QuickPick extends BaseForm {
	
	protected $data;
	
	/**
	 * @param string $name
	 * @return Form 
	 */
	public function createComponentQuickPickForm($name) {
		$form = new Form;
		$form->onSuccess[] = callback($this, $name.'Submitted');
		$form->onError[] = callback($this, $name.'Error');
		
		$form->addHidden('foo')->setValue('bar');
		foreach ($this->getProductIds() as $id) {
			$form->addCheckbox('product'.$id);
		}
		$form->addSubmit('s', 'Přidat do košíku');
		return $form;
	}
	
	public function quickPickFormSubmitted(Form $form) {
		$values = array();
		$formValues = $form->values;
		unset($formValues['foo']);
		foreach ($formValues as $name => $val) {
			if ($val == true) {
				$values[] = (int) substr($name, 7); // strip the 'product' prefix
			}
		}
		if (empty($values)) {
			$form->addError('Vyberte prosím alespoň jednu položku');
			$this->quickPickFormError($form);
			return;
		}
		if($this->presenter->isAjax()) {
			$this->presenter->payload->success = true;
			$this->presenter->payload->values = $values;
			$this->presenter->sendPayload();
		}
		
		$this->presenter->redirect('addToCart', array ('product' => $values));
	}
	
	public function quickPickFormError(Form $form) {
		if ($form->hasErrors() && $this->presenter->isAjax()) {
			$errors = $form->getErrors();
			$error = array_shift($errors);
			$this->presenter->payload->error = true;
			$this->presenter->payload->message = $error;
			$this->presenter->sendPayload();
		}
	}
	

	public function createRenderer() {
		return new QuickPickRenderer($this);
	}
	
	public function getProductIds() {
		if (!$this->data) {
			$result = array ();
		
			$docs = $this->context->redaction->findAll('vStore\Redaction\Documents\Product')
				->where('[showOnQuickPick] = 1');
				//->orderByStructure();
				
			foreach($docs as $doc) {
				$result[] = $doc->pageId;
			}
		
			/*
			$structure = $this->context->redaction->getStructure();
			foreach ($structure->topLevelItemIds() as $item) {
				foreach ($structure->getChildrenIds($item) as $id) {
					
					if($structure->pageType($id) === 'vStore\Redaction\Documents\Product' || is_subclass_of($structure->pageType($id), 'vStore\Redaction\Documents\Product')) {
						
						$result[] = $id;
					}
				}
			} */
			
			$this->data = $result;
		}
		
		return $this->data;
	}
}
