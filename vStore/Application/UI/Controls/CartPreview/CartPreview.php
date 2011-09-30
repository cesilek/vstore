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
class CartPreview extends CartControl {
	public function render() {
		$template = $this->createTemplate();
		$result = $this->processCartData();
		$template->count = $result['count'];
		$template->totalPrice = $result['totalPrice'];
		$template->setFile($this->file ?: __DIR__.'/templates/default.latte');
		echo $template;
	}
	
	protected function processCartData() {
		$result = array (
			'count' => 0,
			'totalPrice' => 0
		);
		foreach ($this->cart->loadAll() as $product) {
			$result['count'] += $product['quantity'];
			$result['totalPrice'] += $product['price'] * $product['quantity'];
		}
		return $result;
	}


	public function handleReload() {
		$result = $this->processCartData();
		$this->presenter->payload->count = $result['count'];
		$this->presenter->payload->totalPrice = $this->template->currency($result['totalPrice']);
		$this->presenter->sendPayload();
	}
}
