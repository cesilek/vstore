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
	Nette\Application\Responses\JsonResponse;

/**
 * Delivery control for checking availability of PPL parcel delivery
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 9, 2011
 */
class PplAvailabilityChecker extends vStore\Application\UI\Control {

	
	// <editor-fold defaultstate="collapsed" desc="General">
		
	/**
	 * Renderer factory
	 *
	 * @return CzechPostParcelDeliveryToPostOfficeRenderer
	 */
	protected function createRenderer() {
		return new PplAvailabilityCheckerRenderer($this);
	}
	
	/**
	 * Renders AJAX response for auto-complete
	 *
	 * @return void
	 */
	public function actionCheckAvailabilityByCode() {
		$code = $this->getParam('code');

		$data = array('result' => $this->context->pplInfoProvider->isEveningDeliveryAvailableForCode($code));		
			
		$this->getPresenter(true)->sendResponse(new JsonResponse($data));
	}
	
	// </editor-fold>
	
}
