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
 * Shop coupon
 *
 * @Table(name="shop_coupons")
 * 
 * @Column(id, pk)
 * @Column(validSince, type="DateTime")
 * @Column(validUntil, type="DateTime")
 * @Column(type)
 * @Column(value, type="float")
 * @Column(requiredProductId, type="integer")
 * 
 * @author Adam Staněk (velbloud)
 * @since Dec 9, 2011
 */
class Coupon extends vBuilder\Orm\ActiveEntity {
	
	const TYPE_PERCENTAGE = 'percentage';
	const TYPE_RABATE = 'rabate';
	
}
