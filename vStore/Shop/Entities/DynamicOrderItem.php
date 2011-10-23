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

namespace vStore\Shop;

use vStore,
		vBuilder,
		Nette;

/**
 * Base class for dynamic order items (delivery charges, etc...)
 *
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 14, 2011
 */
abstract class DynamicOrderItem extends OrderItem {
	
	public function __construct($data = array()) {
		call_user_func_array(array('parent', '__construct'), func_get_args()); 
		
		// Merge z getteru pri ukladani
		$this->onPreSave[] = function($e) {
			$e->data->productId = $e->getProductId();
			$e->data->price = $e->getPrice();
			$e->data->amount = $e->getAmount();
			$e->data->name = $e->getName();
		};
		
	}
	
	/**
	 * Returns unique id in the cart
	 * 
	 * @return string up to 32 char log 
	 */
	public function getUniqueId() {
		if($this->productId >= 0) return parent::getUniqueId();
		
		return md5($this->productId . $this->data->params);
	}
	
	/**
	 * Returns bound order
	 * 
	 * @return Order
	 * @throws Nette\InvalidStateException if order was not set 
	 */
	public function getOrder() {
		
		if($this->repository instanceof vBuilder\Orm\SessionRepository) {			
			return $this->context->shop->order;
		}
		
		throw new \LogicException("Order can't be loaded from DibiRepository. Saved OrderItems can't depend on it!");
	}
	

	
	/**
	 * Returns product id (delivery items have fixed ID)
	 * 
	 * @return int 
	 */
	public function getProductId() {
		throw new Nette\NotImplementedException(get_called_class() . '::getProductId() should be implemented to return static unique ID');
	}
	
	/**
	 * Setting of product id of delivery item does not make any sense.
	 * 
	 * @param int $id 
	 */
	public function setProductId($id) {
		throw new \LogicException('Product ID is not supported by delivery items');
	}
	
	/**
	 * Computes price for this item
	 * 
	 * @return float
	 */
	abstract protected function gatherPrice();
	
	/**
	 * Returns effective price of item
	 * 
	 * @return float 
	 */
	final public function getPrice() {
		// Pokud data jeste nejsou ulozena v databazi, nacitame VZDY aktualni cenu
		if($this->repository instanceof vBuilder\Orm\SessionRepository) {			
			return $this->gatherPrice();
		}
		
		return $this->defaultGetter('price');
	}
	
	/**
	 * Default amount
	 * 
	 * @return type 
	 */
	public function getAmount() {
		return 1;
	}
		
}