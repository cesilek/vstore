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
 *
 * vStore is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vStore bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vStore\ThirdPartyServices\Heureka;

use vStore,
	vBuilder,
	Nette,
	HeurekaOvereno,
	vStore\Shop\Order;

/**
 * Shop order -> Heureka connector
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 1, 2013
 */
class OrderReporter extends Nette\Object {

	/**
	 * Reports order to Heureka "Ověřeno" service.
	 * Should be called from vStore\Shop::onOrderCreated event;
	 *
	 * @param  Nette\DI\IContainer DI context
	 * @param  Order  order to report
	 * @param  string API secret key
	 * @return void
	 */
	public static function report($context, Order $order, $apiSecret) {

		// $context->parameters['productionMode'] == true

		if(Nette\Diagnostics\Debugger::$productionMode == Nette\Diagnostics\Debugger::PRODUCTION) {

			// Grab products only
			$items = $order->getItems(true);

			if($order->customer->email && count($items)) {

				try {
					$h = new HeurekaOvereno($apiSecret);
					$h->setEmail($order->customer->email);

					foreach($items as $item) {
						$h->addProduct($item->name);
					}

					$h->addOrderId($order->id);
			        $h->send();

			    } catch (Exception $e) {
			    	// Neresim, Heureka neni fatalni chyba
			    	// TODO: log
			        /* print $e->getMessage();
			        exit; */
			    }
			}
		}		
	}

}