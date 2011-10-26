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
		Nette,
		vBuilder,
		Nette\Application\UI\Form,
		vBuilder\Orm\Repository;

/**
 *
 * @author Jirka
 */
class Search extends vStore\Application\UI\Control {

	protected $query;


	protected function createRenderer() {
		return new SearchRenderer($this);
	}
		
	public function createComponentSearchForm($name) {
		$form = new Form($this, $name);
		$form->onSuccess[] = callback($this, $name.'Submitted');
		$form->addText('query')
			->setDefaultValue($this->query ?: 'Hledat...');
		$form->addSubmit('s', 'Search!');
	}
	
	public function searchFormSubmitted(Form $form) {
		$this->presenter->redirect('search', array(
			'id'=>2,
			'query'=>$form->values->query
		));
	}
	
	public function handlePrompt($query) {
		//sleep(3); // 'loading' simulation
		$search = $this->search($query)->fetchAll();
		if (empty ($search)) {
			$this->presenter->payload->emptyResult = true;
		} else {
			$result = array ();
			foreach ($search as $page) {
				$temp = (object) null;
				$temp->title = $page->title;
				$temp->imageUrl = $page->image ? $page->image->getUrl(32, 32) : null;
				$temp->link = $this->presenter->link('//Redaction:', array (
					'id'=>$page->pageId
				));
				$result[] = $temp;
			}
			$this->presenter->payload->prompt = $result;
		}
		$this->presenter->sendPayload();
	}
	
	protected function search($query) {
		$query = mb_strlen($query) > 2 ? "%$query%" : "$query%";
		
				return $this->presenter->getContext()->redaction->branch->findAll('vStore\Redaction\Documents\Product')
				->where('([perex] LIKE %s',$query, ') OR ([content] LIKE %s', $query, ') OR ([title] LIKE %s', $query, ')')
				->limit(10);
	}
	
	public function setQuery($query) {
		$this->query = (string) $query;
		return $this;
	}
	
	public function createComponentResultListing($name) {
		$listing = new vStore\Application\UI\Controls\ProductsListing($this, $name);

		$fluent = $this->search($this->query);
		$listing->setFluent($fluent->orderByStructure());
		
		$listing->setDefaultRenderer(new CatalogueRenderer);
		$listing->getRenderer()->setFile(APP_DIR . '/vBuilderFrontModule/templates/ProductListing/catalogueRenderer.latte');
		
		return $listing;
	}
	
	
	public function actionFull() {
		
	}
}