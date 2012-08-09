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

use vBuilder,
		Nette;

/**
 * Interface of shop delivery method data class
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
interface IDeliveryMethod {
	
	static function fromConfig($id, vBuilder\Config\ConfigDAO $config, Nette\DI\IContainer $context);
	
	/**
	 * @return string
	 */
	function getId();
		
	/**
	 * @return string
	 */
	function getName();
	
	/**
	 * @return string
	 */
	function getDescription();
		
	/**
	 * @return bool
	 */
	function isSuitableWith($payment);
	
	/**
	 * @return OrderItem|null
	 */
	function createOrderItem(Order $order);
	
	/**
	 * @return IDeliveryMethod
	 */
	function createParametrizedMethod(array $parameters);
	
	/**
	 * @return string|null
	 */
	function getControlClass();
	
}
