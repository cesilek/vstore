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

use vStore,
	 vBuilder;

/**
 * Shop page control
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 16, 2011
 */
class ShopPage extends vBuilder\Application\UI\Controls\RedactionPage {

	public function createComponentProductsListing($name) {
		$listing = new ProductsListing($this, $name);
		
		$listing->setEntityClass('vStore\Redaction\Documents\Product');
		return $listing;
	}
	
	/*public function actionProduct($contentId) {
		dd($contentId);
	}*/
	
	public function actionDefault() {
		/*$items = $this->branch->findAll('vStore\Redaction\Documents\Product')
				->fetchAll(null, 10);
		$storage = new vStore\Shop\SessionCartStorage($this->getPresenter()->context);*/
		
		/*foreach ($items as $item) {
			$storage->save($item);
		}*/
		//dd($storage->load(1), $storage->loadAll());
	}
	
	public function createComponentCartPreview($name) {
		return new CartPreview($this, $name);
	}
	
	public function createComponentCartControl($name) {
		return new CartControl($this, $name);
	}
	
}
