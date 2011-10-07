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
 * Mini cart
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class CartPreview extends vStore\Application\UI\Control {
	
	private $_total;
	private $_amount;

	/**
	 * Creates renderer instance
	 * 
	 * @return ControlRenderer renderer
	 */
	protected function createRenderer() {
		return new CartPreviewRenderer($this);
	}
	
	/**
	 * Default action handler
	 */
	public function actionDefault() {
		$this->processCartData();
	}
	
	/**
	 * AJAX refresh
	 */
	public function handleReload() {
		// Forced data refresh
		$this->processCartData();
		
		$this->presenter->payload->count = $this->amount;
		$this->presenter->payload->totalPrice = $this->template->currency($this->total);
		$this->presenter->sendPayload();
	}
	
	// ***************************************************************************
	
	/**
	 * Returns current total price of all items
	 * 
	 * @return float
	 */
	public function getTotal() {
		if(!isset($this->_total)) $this->processCartData();
		
		return $this->_total;
	}
	
	/**
	 * Returns number of items in the cart
	 * 
	 * @return int
	 */
	public function getAmount() {
		if(!isset($this->_amount)) $this->processCartData();
		
		return $this->_amount;
	}
	
	/**
	 * Refresh data from current cart
	 */
	protected function processCartData() {
		$this->_amount = 0;
		$this->_total = 0.0;
		
		foreach ((array) $this->context->cart->loadAll() as $product) {
			$this->_amount += $product['quantity'];
			$this->_total += $product['price'] * $product['quantity'];
		}
	}

}
