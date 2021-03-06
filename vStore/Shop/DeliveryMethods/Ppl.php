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

namespace vStore\Shop\DeliveryMethods;

use vStore,
	vBuilder,
	Nette;

/**
 * PPL delivery
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 10, 2011
 */
class Ppl extends ParcelDeliveryMethod {
		
	/**
	 * Protected constructor
	 */
	protected function __construct() {
	
		// Default control (if not overriden by config)
		$this->_controlClass = 'vStore\\Application\\UI\\Controls\\PplAvailabilityChecker';
	}
	
	/**
	 * Creates method from app configuration
	 * 
	 * @param string id
	 * @param vBuilder\Config\ConfigDAO config
	 * @param Nette\DI\IContainer DI context
	 */
	static function fromConfig($id, vBuilder\Config\ConfigDAO $config, Nette\DI\IContainer $context) {
		$method = parent::fromConfig($id, $config, $context);
		
		if($config->get('allowEveningDelivery') === FALSE)
			$method->_controlClass = NULL;
		
		return $method;
	}
	
}