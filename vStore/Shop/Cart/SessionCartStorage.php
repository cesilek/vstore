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
		if (!is_int($id)) {
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
	 * @return ICartItem 
	 */
	public function save(ICartItem $item) {
		$this->session->cart[$item->getId()] = array (
			'id'	=> $item->getId(),
			'price' => $item->getPrice(),
			'title' => $item->getTitle()
		);
		return $item;
	}

	/**
	 * @param int $id
	 * @return bool 
	 */
	public function delete($id) {
		$id = intval($id);
		unset($this->session->cart[$id]);
		return true;
	}
}