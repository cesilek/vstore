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
 * Cart renderer
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class CartRenderer extends vStore\Application\UI\ControlRenderer {
		
	public function renderDefault() {
		$this->template->order = $this->control->order;
	}
	
	public function renderDeliveryPage() {
		$enabledFilterCb = function ($method) {
			return $method->isEnabled();
		};
	
		$this->template->deliveryMethods = array_filter($this->shop->availableDeliveryMethods, $enabledFilterCb);
		$this->template->paymentMethods = array_filter($this->shop->availablePaymentMethods, $enabledFilterCb);
		$this->template->order = $this->shop->order;
	}
	
	public function renderReviewPage() {
		$this->template->order = $this->shop->order;
	}
	
	public function renderLastPage() {
		$this->template->order = $this->shop->getOrder($this->control->getParam('orderId'));
	}
		
}
