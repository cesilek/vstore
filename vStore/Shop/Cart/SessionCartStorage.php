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
	Nette,
	 vBuilder;

/**
 * @author Jirka Vebr
 */
class SessionCartStorage extends BaseStorage implements ICartStorage {
	
	/**
	 * @var Nette\Http\SessionSection
	 */
	protected $session;
	
	protected $last;
	
	const SESSION_NAMESPACE = 'SessionCart';
	
	/**
	 * @param Nette\DI\IContainer $container 
	 */
	public function __construct(Nette\DI\IContainer $container) {
		parent::__construct($container);
		
		$container->session->start();
		$this->session = $container->session->getSection(static::SESSION_NAMESPACE);
	}

	/**
	 * @param int $id
	 * @return array 
	 */
	public function load($id) {
		if (!is_int($id) || $id < 1) {
			throw new Nette\InvalidArgumentException("Id must be an integer. '".gettype($var)."' given.");
		}
		return $this->session->cart[$id];
	}
	
	/**
	 * @return array 
	 */
	public function loadAll() {
		return $this->session->cart;
	}
	
	/**
	 * @param ICartItem $item
	 * @param int $quantity How many times to add $item
	 * @return ICartItem 
	 */
	public function save(ICartItem $item, $quantity) {
		if (!ctype_digit($quantity) || $quantity < 1) {
			throw new Nette\InvalidArgumentException("The second argument passed to save() must be an integer greater than zero. '".gettype($quantity)."' given.");
		}
		$quantity = (int) $quantity;
		if (isset($this->session->cart[$item->getPageId()]) && is_array($this->session->cart[$item->getPageId()])) {
			$this->session->cart[$item->getPageId()]['quantity'] = $quantity;
		} else {
			$this->session->cart[$item->getPageId()] = array (
				'contentId'	=> $item->getId(),
				'pageId'	=> $item->getPageId(),
				'price' => $item->getPrice(),
				'title' => $item->getTitle(),
				
				'quantity' => $quantity
			);
			
		}
		return $item;
	}
	
	/**
	 * Adds a number of items
	 * @param ICartItem $item
	 * @param type $quantity
	 * @return ICartItem 
	 */
	public function add(ICartItem $item, $quantity = 1) {
		if (!ctype_digit($quantity) || $quantity < 1) {
			throw new Nette\InvalidArgumentException("The second argument passed to add() must be an integer greater than zero. '".gettype($quantity)."' given.");
		}
		if (isset($this->session->cart[$item->getPageId()]) && is_array($this->session->cart[$item->getPageId()])) {
			$toSave = $this->session->cart[$item->getPageId()]['quantity'] += $quantity;
		} else {
			$this->save($item, $quantity);
		}
		return $item;
	}

	/**
	 * @param int $id
	 * @param int $number How many items should be left. A negative value means to delete only some.
					-2 will delete two regardles of the initial value. 0 will delete evverything.
	 * @return bool 
	 */
	public function delete($id, $number = 0) {
		$id = intval($id);
		if (!is_int($number)) {
			throw new Nette\InvalidArgumentException("Second argument of delete() must be an integer. '".gettype($number)."'given.");
		}
		if (isset($this->session->cart[$id])) {
			if ($number > 0) {
				$this->session->cart[$id]['quantity'] = $number;
			} else {
				$this->session->cart[$id]['quantity'] += $number;
				if ($this->session->cart[$id]['quantity'] <= 0 || $number === 0) {
					unset($this->session->cart[$id]);
				}
			}
		}
		return true;
	}
}