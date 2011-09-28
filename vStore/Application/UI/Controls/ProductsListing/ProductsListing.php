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
	vBuilder\Application\UI\Controls\Paging;

/**
 * Shop products listing
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class ProductsListing extends vBuilder\Application\UI\Controls\RedactionControl {
	
	const LIST_ALL = -1;
	
	/**
	 * @var IRenderer
	 */
	protected $rendererClass;
	
	/**
	 * @var vBuilder\Redaction\Document
	 */
	protected $entity;

	/**
	 * @persistent
	 */
	public $perPage = 10;
	
	/**
	 * @persistent
	 */
	public $sorting = 'price';
	
	/**
	 * @persistent
	 */
	public $page = 1;
	
	/**
	 * @persistent
	 */
	public $renderer = 'table';
	

	/**
	 * @param string $name
	 * @return Paging
	 */
	public function createComponentPaging($name) {
		$paging = new Paging($this, $name);
		$paging->setPage($this->page, 'page');

		$paging->setItemsPerPage(max(5, intval($this->perPage)));
		
		$paging->setFluent($this->getFluent());
		
		$paging->setLink('default!');
		
		return $paging;
	}
	
	/**
	 * @param int $page 
	 */
	public function handleDefault($page = 1) {
		$this->page = max(1, intval($page));
		$this['adjustRenderForm']->setDefaults(array(
			'perPage' => $this->perPage,
			'sorting' => $this->sorting
		));
	}
	
	/**
	 * TODO: this method really shouldn't be in this class
	 * @param int $id 
	 */
	public function handleAddToCart($id) {
		$this->getContext()->cart->setStorage(new vStore\Shop\SessionCartStorage($this->getContext()))
				->save($this->branch->findAll('vStore\Redaction\Documents\Product')
					->where('[contentId] = %i', intval($id))->fetch());
		$this->redirect('this');
	}


	/**
	 * @param string $entity
	 * @return ProductsListing 
	 */
	public function setEntityClass($entity) {
		if (!is_subclass_of($entity, $this->branch->getBaseDocument())) {
			throw new Nette\InvalidArgumentException("Entity class must be an descendant of '{$this->branch->getBaseDocument()}'");
		}
		$this->entity = is_string($entity) ? new $entity($this->getContext()) : $entity;
		
		return $this;
	}
	
	/**
	 * @param string $name
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentAdjustRenderForm($name) {	
		$form = new Form;
		$form->onSuccess[] = callback($this, $name.'Submitted');
		
		$form->addSelect('perPage', 'Number of items listed on one page:', $this->getPerPageOptions());
		$form->addSelect('sorting', 'Sort based on:', $this->getSortingOptions());
		
		$form->addSubmit('s', 'Change');		
		return $form;
	}
	
	/**
	 * @param Nette\Application\UI\Form $form 
	 */
	public function adjustRenderFormSubmitted(Form $form) {
		$values = $form->values;
		$this->sorting = $values['sorting'];
		$this->perPage = $values['perPage'];
		$this->redirect('default!');
	}

	/**
	 * Need this public...
	 * @param string|bool $file
	 * @param string|bool $class
	 * @return Nette\Templating\FileTemplate
	 */
	public function createTemplate($file = null, $class = null) {
		return parent::createTemplate($file, $class);
	}

	/**
	 * @param string $what 
	 */
	public function render($what = null) {
		echo $this->getRenderer()->render($this, $what);
	}
	
	/**
	 * @return IRenderer 
	 */
	public function getRenderer() {
		if (!$this->rendererClass) {
			if (in_array($this->renderer, array ('table', 'catalogue'))) {
				$renderer = __NAMESPACE__ . '\\' . ucfirst($this->renderer).'Renderer';
				$this->setRenderer(new $renderer);
			} else {
				$this->setRenderer(new TableRenderer);
			}
		}
		return $this->rendererClass;
	}
	
	/**
	 * @param IRenderer $renderer
	 * @return ProductsListing 
	 */
	public function setRenderer(IRenderer $renderer) {
		$this->rendererClass = $renderer;
		return $this;
	}
	
	/**
	 * @return array
	 */
	protected function getPerPageOptions() {
		// TODO: config or manual setting?
		return array (
			5	=> '5', 
			10	=> '10',
			20	=> '20',
			30	=> '30',
			self::LIST_ALL => 'All'
		);
	}
	
	/**
	 * @return array
	 */
	protected function getSortingOptions() {
		// TODO: config or manual setting?
		return array (
			'alphabet'	=> 'Alphabet',
			'cheapest'	=> 'Cheapest first',
			'mostExpensive'	=> 'Most expensive first',
		);
	}
	
	/**
	 * @return vBuilder\Orm\Fluent 
	 */
	protected function getFluent() {
		$rev = 'ASC';
		$order = "price";
		switch ($this->sorting) {
			case 'alphabet':
				$order = "title";
				break;
			case 'cheapest':
				break;
			case 'mostExpensive':
				$rev = 'DESC';
				break;
			default:
				$order = 'title';
				break;
		}
		return $this->branch
			->findAll($this->entity)
			->orderBy("[$order] $rev");
	}
	
	/**
	 * @return array
	 */
	public function getData() {
		if ($this->perPage === static::LIST_ALL) {
			return $this->getFluent()->fetchAll();
		}
		return $this['paging']->getData();
	}
}
