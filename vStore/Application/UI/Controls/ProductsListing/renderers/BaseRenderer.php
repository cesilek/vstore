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
	 vBuilder;

/**
 * Shop page control
 *
 * @author JirkaVebr
 */
abstract class BaseRenderer extends Nette\Object implements IRenderer {
	
	protected $control;
	
	protected $defaultFile;
	
	public function render(ProductsListing $control, $what = null) {
		$this->control = $control;
		
		if ($what === null) {
			// regular render
			$template = $this->createTemplate();
			
			$template->paginator = $this->renderPaginator();
			$template->adjustRender = $this->renderAdjustRender();
			$template->data = $this->renderData();
			$this->defaultFile && $template->setFile($this->defaultFile);
		} else {
			// manual render
			if (method_exists($this, $method = 'render'. ucfirst($what))) {
				$template = $this->{$method}();
			} else {
				throw new Nette\InvalidArgumentException("Can't render '$what'.");
			}
		}
		echo $template;
	}
	
	public function renderPaginator() {
		$template = $this->createTemplate('paginator');
		$control = $this->control;
		$template->all = ($control->perPage === $control::LIST_ALL) ?: false;
		return $template;
	}
	
	public function renderAdjustRender() {
		return $this->createTemplate('adjustRender');
	}

	protected function createTemplate($file = 'default') {
		$template = $this->control->createTemplate();
		$template->setFile(__DIR__.'/../templates/'.$file.'.latte');
		return $template;
	}
	
	public function setFile($file) {
		$this->defaultFile = $file;
	}
}
