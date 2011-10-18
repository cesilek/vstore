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
 * Model for ordered items
 *
 * @Table(name="shop_orderItems")
 * 
 * @Column(orderId, pk, type="integer")
 * @Column(productId, pk, type="integer")
 * @Column(name)
 * @Column(amount, type="integer")
 * @Column(price, type="float")
 * @Column(params, type="Json")
 * 
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class OrderItem extends vBuilder\Orm\ActiveEntity {
	
	/**
	 * Returns unique id in the cart
	 * 
	 * @return string up to 32 char log 
	 */
	public function getUniqueId() {
		if($this->params == null || count($this->params->toArray()) == 0) {
			return $this->productId;
		} else {
			return md5($this->productId . $this->data->params);
		}
	}
	
	/**
	 * Returns effective price of item
	 * 
	 * @return float 
	 */
	public function getPrice() {
		// Pokud data jeste nejsou ulozena v databazi, nacitame VZDY aktualni cenu
		if($this->repository instanceof vBuilder\Orm\SessionRepository && $this->productId > 0) {
			$price = $this->context->redaction->get($this->productId)->getEffectivePrice();
			if($this->defaultGetter('price') != $price) $this->data->price = $price;
			return $price;
		}
		
		return $this->defaultGetter('price');
	}
	

		
}