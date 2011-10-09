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
	 * @var IRenderer
	 */
	protected $defaultRenderer;
	
	
	/**
	 * @var vBuilder\Redaction\Fluent
	 */
	protected $fluent;
	private $appliedFluent;

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
	public $renderer;
	
	protected $_data;
	

	/**
	 * Sets data source for product listing
	 * 
	 * @param vBuilder\Redaction\Fluent $fluent
	 * @param type $raw 
	 */
	public function setFluent(vBuilder\Redaction\Fluent $fluent) {
		if(!is_subclass_of($fluent->getRowClass(), $this->branch->getBaseDocument()))
			throw new Nette\InvalidArgumentException("Fluent has to return entities descendant of '{$this->branch->getBaseDocument()}'");
		
		$this->fluent = $fluent;
		$this->appliedFluent = null;
		return $this->fluent;
	}
	
	/**
	 * Gets internal data source
	 * 
	 * @param bool true if internal filters should be reapplied
	 * 
	 * @return vBuilder\Orm\Fluent 
	 */
	protected function getFluent($refresh = false) {
		if(isset($this->appliedFluent) && !$refresh) return $this->appliedFluent;
		if(!isset($this->fluent))
				throw new Nette\InvalidStateException("Missing data source " . get_called_class() . "::setFluent not called?");		
		
				
		$this->appliedFluent = new vBuilder\Orm\Fluent($this->fluent->getRowClass(), $this->getContext());
		$this->appliedFluent->select('*')->from('('.(string) $this->fluent.')')->as('pl');


		switch ($this->sorting) {

			case 'cheapest':
				$order = "price";
				$method = 'ASC';
				break;

			case 'mostExpensive':
				$order = "price";
				$method = 'DESC';
				break;				

			case 'alphabet':				
			default:
				$order = 'title';
				$method = 'ASC';
				break;

		}
		
		$this->appliedFluent->orderBy("[$order] $method");

		return $this->appliedFluent;
	}
	
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
	
	/** WTF? */
	public function createComponentAddToCart($name) {
		return new CartControl();
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
	 * @param string $name
	 * @return Form 
	 */
	public function createComponentQuickPickForm($name) {
		$form = new Form;
		$form->onSuccess[] = callback($this, $name.'Submitted');
		
		foreach ($this->getData() as $product) {
			$form->addCheckbox('product'.$product->pageId);
		}
		$form->addSubmit('s', 'Add to cart!');
		return $form;
	}
	
	public function quickPickFormSubmitted(Form $form) {
		$values = array();
		foreach ($form->values as $name => $val) {
			if ($val == true) {
				$values[] = (int) substr($name, 7); // strip the 'product' prefix
			}
		}
		if($this->presenter->isAjax()) {
			$this->presenter->payload->success = true;
			$this->presenter->payload->values = $values;
			$this->presenter->sendPayload();
		}
		
		$this->presenter->redirect('addToCart', array ('product' => $values));
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
				$this->setRenderer($this->getDefaultRenderer());
				$this->renderer = 'catalogue';
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
	 * @return IRenderer 
	 */
	public function getDefaultRenderer() {
		return $this->defaultRenderer ?: new TableRenderer();
	}
	
	/**
	 * @param IRenderer $renderer
	 * @return ProductsListing 
	 */
	public function setDefaultRenderer(IRenderer $renderer) {
		$this->defaultRenderer = $renderer;
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
	 * @return array
	 */
	public function getData($refresh = false) {
		if (!$this->_data || $refresh) {
			if ($this->perPage === static::LIST_ALL) {
				$this->_data = $this->getFluent()->fetchAll();
			} else {
				$this->_data = $this->getFluent()->fetchAll(
					$this['paging']->getPaginator()->getOffset(),
					$this['paging']->getPaginator()->itemsPerPage
				);
			}
		}
		return $this->_data;
	}	
}
