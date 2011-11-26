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
 * Add to cart process control
 * 
 * TODO: Pripsat podporu parametrizovanych produktu, ukladat je spolu s ID 
 * do session pod unikatnim IDckem procesu. Nezapomenout na nejakou rozumnou expiraci
 * aby to tam nehnilo.
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class AddToCartProcess extends vStore\Application\UI\Control {
	
	/** 
	 * @var array of vStore\Shop\IProduct
	 */
	protected $_products;
	
	protected function createRenderer() {		
		return new AddToCartProcessRenderer($this);
	}
	
	/**
	 * Sets products to add
	 * 
	 * @param array|vStore\Shop\IProduct product or array of products 
	 */
	public function setProducts($product) {
		$products = array();
		if($product instanceof vStore\Shop\IProduct) {
			$products[] = $product;
		} elseif(is_array($product)) {
			foreach($product as $curr) {
				if(!($curr instanceof vStore\Shop\IProduct)) {
					throw new Nette\InvalidArgumentException(get_called_class() . "::setProduct() takes only classes implementing vStore\Shop\IProduct or array of them. " . get_class($curr) . " given.");
				}
				
				$products[] = $curr;
			}
		}
		
		$this->_products = $products;
	}
	
	/**
	 * Returns products to add
	 * 
	 * @return array of vStore\Shop\IProduct 
	 */
	public function getProducts() {
		if(!isset($this->_products) || count($this->_products) == 0) {
			if(isset($this->renderParams['products']))
					$this->setProducts($this->renderParams['products']);
		}
		
		if(!isset($this->_products) || count($this->_products) == 0)
				throw new Nette\InvalidStateException("Missing products for AddToCartProcess. Forget to pass parameter?");
		
		return $this->_products;
	}
	
	/**
	 * Returns product IDs
	 * 
	 * @return array of integer 
	 */
	public function getProductIds() {
		$ids = array();
		foreach($this->products as $curr) $ids[] = $curr->pageId;
		return $ids;
	}
	
	/**
	 * Sets product IDs
	 * 
	 * @param array of integer 
	 */
	public function setProductIds($ids) {
		$products = array();
		foreach($ids as $id) $products[] = $this->context->redaction->get($id);
		$this->setProducts($products);
	}
	
	public function actionDefault() {
		$order = $this->context->shop->order;
		$this->template->productSum = $order->getAmount();
		$this->template->totalPrice = $order->getTotal();
	}
	
	public function createComponentAdjustOrderForm($name) {
		vBuilder\Application\UI\Form\IntegerPicker::register();
		
		$form = new Form($this, $name);
		$form->addHidden('ids');
		
		if($form->isSubmitted()) {
			$this->setProductIds(explode(',', $form->values->ids));
		} else {
			$form['ids']->setDefaultValue(implode(',', $this->getProductIds()));
		}
		
		foreach($this->products as $product) {
			$form->addIntegerPicker('amount' . $product->pageId)
				->setDefaultValue(1)
				->addRule(function ($control) {
					return ctype_digit($control->value);
				}, 'The amount must be a number!');
		}
		
		$form->onSuccess[] = callback($this, $name.'Submitted');
		$form->onError[] = callback($this, $name.'Error');
		$form->addProtection();
		$form->addSubmit('s', 'Přidat do košíku');
	
		return $form;
	}
	
	public function adjustOrderFormError(Form $form) {
		if ($form->hasErrors() && $this->presenter->isAjax()) {
			$errors = $form->getErrors();
			$error = array_shift($errors);
			$this->presenter->payload->error = true;
			$this->presenter->payload->message = $error;
			$this->presenter->sendPayload();
		}
	}
	
	public function adjustOrderFormSubmitted(Form $form) {
		$values = $form->values;
		$totalAmount = 0;
		
		foreach($this->products as $product) {
			$amountKey = 'amount' . $product->getPageId();
			$amount = (int) $values->{$amountKey};
			
			if($amount > 0) {
				$totalAmount += $amount;
				$this->shop->order->addProduct($product, $amount);
			}
		}
		$amountFail = $totalAmount === 0;
		$amountFailMsg = 'Vyberte prosím alespoň jednu položku';
		
		if($this->presenter->isAjax()) {
			if ($amountFail) {
				$this->presenter->payload->error = true;
				$this->presenter->payload->message = $amountFailMsg;
			} else {
				$this->presenter->payload->success = true;
			}
			$this->presenter->sendPayload();
		}
		if ($amountFail) {
			$form->addError($amountFailMsg);
			return;
		}
		
		$this->redirect('success');
	}
	
}
