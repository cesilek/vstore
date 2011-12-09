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
 * Control for viewing shop orders
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class OrderControl extends vStore\Application\UI\Control {

	/** @var vStore\Shop\Order order */
	private $_order;
	
	// <editor-fold defaultstate="collapsed" desc="General">
	
	/**
	 * Returns current order
	 * 
	 * @return vStore\Shop\Order
	 */
	public function getOrder() {
		if(!isset($this->_order)) {
			if($this->getParam('orderId') == null)
				throw new Nette\InvalidArgumentException("Missing 'orderId' parameter");
		
			$this->_order = $this->context->shop->getOrder($this->getParam('orderId'));
			
			if(!$this->_order->exists())
				throw new Nette\InvalidArgumentException("Order with id '".$this->_order->id."' does not exists");
				
			if(!$this->_order->user || !$this->context->user->isLoggedIn() || $this->context->user->getId() != $this->_order->user->id)
				throw new Nette\Application\ForbiddenRequestException("Access denied for reading sho order with id '$this->_order->id'");
		}
	
		return $this->_order;
	}
	
	/**
	 * Returns fluent interface to user orders query
	 * 
	 * @return vBuilder\Orm\Fluent
	 */
	public function getOrders() {
		return $this->context->shop->userOrders->orderBy('timestamp DESC');
	}
	
	protected function createRenderer() {
		return new OrderControlRenderer($this);
	}
	
	// </editor-fold>	
	
	// <editor-fold defaultstate="collapsed" desc="Order listing (default)">
	
	public function actionDefault() {
		
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Order detail display (detail)">
	
	public function actionDetail() {
		
	}
	
	// </editor-fold>
	
}
