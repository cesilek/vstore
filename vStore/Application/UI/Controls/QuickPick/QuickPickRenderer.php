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
	vBuilder\Orm\Repository;

/**
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class QuickPickRenderer extends vStore\Application\UI\ControlRenderer {
	
	public function renderDefault() {
		/* $this->template->documentTitles = $titles = $this->context->redaction->getDocumentTitles();
		$this->template->structure =  */
		
		$structure = $this->redaction->getStructure();
		
		$productIds = $this->control->getProductIds();
		$byParent = array();
		
		// Roztridim produkty podle kategorie (rodicovske stranky)
		foreach($productIds as $id)
			$byParent[$structure->pageParent($id)][$id] = $this->redaction->pageMenuTitle($id);
		
		// Seradim kategorie podle struktury
		$byParent2 = array();
		$categories = $structure->pagesOrder(array_keys($byParent));
		foreach($categories as $curr) $byParent2[$curr] = $byParent[$curr];
		
		// Seradim produkty podle jmena v ramci kategorie
		foreach($byParent2 as &$array)	asort($array, SORT_LOCALE_STRING);		
		
		$this->template->data = $byParent2;
	}
}
