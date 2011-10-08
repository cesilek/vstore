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
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
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
	 * AJAX refresh
	 */
	public function handleReload() {
		$this->presenter->payload->count = $this->amount;
		$this->presenter->payload->totalPrice = $this->template->currency($this->total);
		$this->presenter->sendPayload();
	}
	
	// ***************************************************************************
	
	/**
	 * Returns current total price of all items (products only)
	 * 
	 * @return float
	 */
	public function getTotal() {
		return $this->shop->order->getTotal(true);
	}
	
	/**
	 * Returns number of items in the cart
	 * 
	 * @return int
	 */
	public function getAmount() {
		return $this->shop->order->getAmount();
	}

}
