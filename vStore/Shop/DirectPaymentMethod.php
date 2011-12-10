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
 * Direct payment method
 *
 * @author Adam Staněk (velbloud)
 * @since Dec 10, 2011
 */
abstract class DirectPaymentMethod extends PaymentMethod {
	
	/**
	 * Creates control for handling payment requests/responses.
	 * Control needs to have method payLink() which generates link to action handling request.
	 *
	 * @param vStore\Shop\Order
	 * @param Nette\Callback callback for successful payment handling (on success response)
	 * @param Nette\Callback callback for unsuccessful payment handling (on error response)
	 *
	 * @return Nette\Application\UI\Control
	 */
	abstract function createComponent(Order $order, Nette\Callback $onSuccessCallback, Nette\Callback $onErrorCallback);
		
}