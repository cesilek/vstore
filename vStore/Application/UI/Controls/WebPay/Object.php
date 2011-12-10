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
 
namespace vStore\Application\UI\Controls\WebPay;

use Nette,
	Nette\UnexpectedValueException,
	Nette\InvalidArgumentException,
	Nette\InvalidStateException;

/**
 * Společný předek Requestu i Response.
 * 
 * Převzato z http://addons.nette.org/cs/webpay a upraveno, aby fungovalo s novým Nette.
 *
 * Autor úpravy: Adam Staněk adam.stanek@v3net.cz
 *
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.3 (opraveno pro nove Nette)
 */
abstract class Object extends Nette\Object
{
	
	/** @var string Možný stav parametru OPERATION. */
	const CREATE_ORDER = 'CREATE_ORDER';
	
	/**#@+ param name */
	// request
	const MERCHANTNUMBER = 'MERCHANTNUMBER';
	const AMOUNT = 'AMOUNT';
	const CURRENCY = 'CURRENCY';
	const DEPOSITFLAG = 'DEPOSITFLAG';
	const URL = 'URL';
	const DESCRIPTION = 'DESCRIPTION';
	// response
	const PRCODE = 'PRCODE';
	const SRCODE = 'SRCODE';
	const RESULTTEXT = 'RESULTTEXT';
	// both
	const OPERATION = 'OPERATION';
	const ORDERNUMBER = 'ORDERNUMBER';
	const MERORDERNUM = 'MERORDERNUM';
	const MD = 'MD';
	const DIGEST = 'DIGEST';
	/**#@-*/
	
	/** @var array */
	protected $params = array();
	
	/** Kontrola rozšíření.	*/
	public function __construct()
	{
		if (!extension_loaded('openssl'))
			throw new InvalidStateException("PHP extension OpenSSL is not loaded.");
	}
	
	/**
	* @param string param name
	* @return string|NULL
	*/
	final protected function getParam($name)
	{
		if (!isset($this->paramTypes[$name])) throw new InvalidArgumentException("Unknown param type '$name'");
		
		if (isset($this->params[$name])) return $this->params[$name];
		else return NULL;
	}
	
	/**
	* @param mixed param name
	* @param mixed string|int|NULL
	* @return WebPayObject
	*/
	final protected function setParam($name,$value)
	{
		if (!isset($this->paramTypes[$name])) throw new InvalidArgumentException("Unknown param type '$name'");
		
		$validation = $this->paramTypes[$name][1];
		$outputFilter = NULL;
		if (isset($this->paramTypes[$name][2]))
			$outputFilter = $this->paramTypes[$name][2];
		
		do {
			if ($validation === NULL OR $value === NULL) break;
			else if (is_string($validation) AND $validation{0} === '#')
			{
				if (preg_match($validation, $value)) break;
			}
			else if (is_array($validation) AND $validation[0] === NULL)
			{
				if (call_user_func_array($validation[1], array_merge(array($value),array_slice($validation,2)))) break;
			}
			else if (is_callable($validation))
			{
				if (call_user_func($validation, $value)) break;
			}
			else throw new InvalidStateException("Invalid validation in '$name'.");
			throw new UnexpectedValueException("'$name' is not valid.");
		} while (false);
		
		if (isset($outputFilter)) $value = call_user_func($outputFilter,$value);
		
		$this->params[$name] = $value;
		
		return $this;
	}
	
	/**
	* @param string param name
	* @param string|int|NULL
	* @return WebPayObject|string|NULL
	*/
	final protected function processParam($name, $value = NULL)
	{
		$mode = strtolower(substr($name ,0, 3));
		$name = strtoupper(substr($name, 3));
		if ($mode === 'get')
		{
			return $this->getParam($name);
		}
		else if ($mode === 'set')
		{
			return $this->setParam($name, $value);
		}
		throw new InvalidStateException("Only `get` and `set` operation is allowed.");
	}
	
}