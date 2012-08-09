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
	Nette\Application\UI\Form,
	Nette\Application\Responses\JsonResponse;

/**
 * Delivery control for parcel delivery to post office (Czech Post)
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 23, 2011
 */
class CzechPostParcelDeliveryToPostOffice extends vStore\Application\UI\Control {

	
	// <editor-fold defaultstate="collapsed" desc="General">
		
	/**
	 * Renderer factory
	 *
	 * @return CzechPostParcelDeliveryToPostOfficeRenderer
	 */
	protected function createRenderer() {
		return new CzechPostParcelDeliveryToPostOfficeRenderer($this);
	}
	
	/**
	 * Renders AJAX response for auto-complete
	 *
	 * @return void
	 */
	public function actionFindPostOfficesByCode() {
		$data = array();
		$codePrefix = $this->getParam('codePrefix');
		
		if($codePrefix != "") {
			$postOffices = $this->context->postOfficeProvider->findByPostalCode($codePrefix);
			foreach($postOffices as $po) {
				$struct = new \StdClass;
				$struct->code = $po->formatedPostalCode;
				$struct->name = $po->name;
				$struct->address = $po->formatedAddress;
				
				$struct->isAvailable = $po->isAvailable() && (
						$po->getMaximumPackageValue() === NULL
						|| $this->context->shop->order->getTotal(true) < $po->getMaximumPackageValue()
				);
				
				$data[] = $struct;
			}
		}
	
		$this->getPresenter(true)->sendResponse(new JsonResponse($data));
	}
	
	// </editor-fold>
	
}
