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

namespace vStore\Redaction\Documents;

use vBuilder,
		vBuilder\Redaction\Document,
		vStore;

/**
 * Basic redaction data type
 *
 * @Table(name="redaction_doc_product")
 * 
 * @Column(perex, type="RedactionTemplate", cmsName="Perex", cmsType="ShortHTML")
 * @Column(content, type="RedactionTemplate", cmsName="Obsah", cmsType="HTML")
 * @Column(price, type="Float", cmsName="Cena")
 * @Column(usualPrice, type="Float", cmsName="Běžná cena")
 * @Column(image, type="RedactionImage", cmsName="Foto produktu", cmsType="Image")
 * 
 * @author Adam Staněk (velbloud)
 * @since Aug 16, 2011
 */
class Product extends Document implements vStore\Shop\IProduct {
		
	public function getProductId() {
		return $this->pageId;
	}
		
	public function getTitle() {
		return parent::getTitle();
	}
	
	/**
	 * For optional dual pricing
	 * 
	 * @return float 
	 */
	public function getEffectivePrice() {
		return $this->price;
	}
	
}
