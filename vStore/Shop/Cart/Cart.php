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
class Cart extends vBuilder\Object {
	
	/**
	 * @var ICartStorage
	 */
	protected $storage;
	
	/**
	 * @var Nette\DI\IContainer
	 */
	protected $context;
	
	/**
	 * @param string | ICartStorage $storage
	 * @param Nette\DI\IContainer $container 
	 */
	public function __construct(Nette\DI\IContainer $container, $storage = null) {
		$this->context = $container;
		
		if ($storage) {
			$this->setStorage($storage);
		}
	}
	
	public function getStorage() {
		if (!$this->storage) {
			throw new Nette\InvalidStateException("No storage was set. Use ".get_called_class()."::setStorage().");
		}
		return $this->storage;
	}
	
	/**
	 * @param string | ICartStorage $storage 
	 * @return Cart 
	 */
	public function setStorage($storage) {
		if (is_string($storage)) {
			$storage = new $storage($container);
		}
		if (!($storage instanceof ICartStorage)) {
			throw new Nette\InvalidArgumentException("A storage must implement vStore\\Shop\\ICartStorage!");
		}
		$this->storage = $storage;
		return $this;
	}
	
	/**
	 * @param int $id
	 * @return mixed 
	 */
	public function load($id) {
		return $this->getStorage()->load($id);
	}
	
	/**
	 * @return mixed 
	 */
	public function loadAll() {
		return $this->getStorage()->loadAll();
	}
	
	/**
	 * @param ICartItem $item
	 * @param int $quantity
	 * @return mixed 
	 */
	public function save(ICartItem $item, $quantity) {
		return $this->getStorage()->save($item, $quantity);
	}
	
	/**
	 * @param ICartItem $item
	 * @param int $quantity
	 * @return mixed 
	 */
	public function add(ICartItem $item, $quantity = 1) {
		return $this->getStorage()->add($item, $quantity);
	}
	
	/**
	 * @param int $id
	 * @param int $number How many items should be left. A negative value means to delete only some.
					-2 will delete two regardles of the initial value. 0 will delete evverything.
	 * @return bool 
	 */
	public function delete($id, $number = 0) {
		return $this->getStorage()->delete($id, $number);
	}
}