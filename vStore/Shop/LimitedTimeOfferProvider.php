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
 *
 * vStore is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vStore bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vStore\Shop;

use vStore,
	vBuilder,
	Nette;

/**
 * Invoice generator for shop orders
 *
 * DB Structure (shop_timeLimitedOffers):
 * 		Disable autofill <==> productId == 0
 *   	Disable product with id <==> productId = 0 - productId
 * 
 * @author Adam Staněk (velbloud)
 * @since Jan 21, 2013
 */
class LimitedTimeOfferProvider extends vBuilder\Object {
	
	const FLAG_NAME		= 'onLtSale';
	

	/**
	 * @var Nette\DI\IContainer DI context
	 * @nodump
	 */
	private $_context;

	/** @var \DibiConnection */
	protected $db;
	
	/** @var int number of products up to which use the autofill (default: 1) */
	private $_autofillCount = 1;

	/** @var string DateTime period */
	private $_autofillInterval = vBuilder\Utils\DateTime::DAY;

	/** @var array|null */
	private $_data;

	/** @var array */
	private $_productPageTypes = array();
	
	/**
	 * Initializes services for usage of this class.
	 * Takes care of creating necessary redaction structure flag.
	 * 
	 * @return void
	 */
	public static function initialize(Nette\DI\IContainer $context) {
		static $initialized = false;

		if(!$initialized) {
			$initialized = true;

			$context->redaction->getStructure()->addCustomFlag(
				self::FLAG_NAME,
				'Nabízet produkt ve slevě',
				array('vStore\\Shop\\IProduct')
			);
		}
	}

	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI context 
	 */
	public function __construct(Nette\DI\IContainer $context) {
		static::initialize($context);

		$this->_context = $context;
		$this->_productPageTypes = $this->context->classInfo->getAllClassesImplementing('vStore\\Shop\\IProduct');

		// $this->db = &$context->database->connection;
		$this->db = &$this->context->connection;
	}
	
	/**
	 * Returns DI context container
	 *
	 * @return Nette\DI\IContainer
	 */
	public function getContext() {
		return $this->_context;
	}

	/**
	 * Returns DB table name
	 * 
	 * @return string
	 */
	public function getTableName() {
		return 'shop_timeLimitedOffers';
	}
		
	/**
	 * Returns array of product candidates (their page IDs) for
	 * new sale offer
	 *
	 * @return array
	 */
	protected function getCandidates() {
	
		$structure = $this->context->redaction->getStructure();
		$pageIds = array_filter($structure->getAllPageIds(), \callback($this, 'canBeCandidate'));

		return array_values($pageIds);
	}

	/**
	 * Returns true if product with given ID can be offer candidate
	 * @return bool
	 */
	public function canBeCandidate($pageId) {
		$classes = $this->_productPageTypes;
		$structure = $this->context->redaction->getStructure();

		if(!$structure->pageExists($pageId))
			return false;

		if(!in_array($structure->pageType($pageId), $classes))
			return false;

		if(!$structure->pageVisibility($pageId))
			return false;

		return $structure->pageFlagIsSet($pageId, LimitedTimeOfferProvider::FLAG_NAME);
	}
	
	/**
	 * Returns true if products will be automatically autofilled
	 * when necessary
	 *
	 * @return bool
	 */
	public function isAutofillEnabled() {
		$data = $this->getData();

		return $data['isAutofillEnabled'];
	}
	
	/**
	 * Enable / disable autofill functionality
	 *
	 * @param bool
	 */
	public function setAutofillEnabled($enabled) {

		$now = new \DateTime;

		if($enabled) {
			
			$this->db->query(
				"UPDATE [$this->tableName] SET [until] = %t", $now,
				"WHERE [productId] = 0 AND [until] > %t", $now
			);

			if(isset($this->_data))
				$this->_data['isAutofillEnabled'] = true;

		} else {
			$iData = array(
				'productId' => 0,
				'since' => $now->format('Y-m-d H:i:s'),
				'until' => '9999-12-31 23:59:59'
			);

			$this->db->query(
				"INSERT INTO [$this->tableName]", $iData
			);

			if(isset($this->_data))
				$this->_data['isAutofillEnabled'] = false;
		}
	}

	/**
	 * Returns number of products which are in sale
	 *
	 * @return int
	 */
	public function getAutofillCount() {
		return $this->_autofillCount;
	}
	
	/**
	 * Returns autofill interval
	 * 
	 * @return string
	 */
	public function getAutofillInterval() {
		return $this->_autofillInterval;
	}

	/**
	 * Sets autofill interval
	 * 
	 * @param string vBuilder\Utils\DateTime constant
	 */
	public function setAutofillInterval($interval) {
		$this->_autofillInterval = $interval;
	}

	/**
	 * Returns array of products currently in the sale
	 *
	 * @return array of product ids
	 */
	public function getProducts() {

		$data = &$this->getData();

		// Autofill
		if($this->isAutofillEnabled() && $this->getAutofillCount() > count($data['activeDeals']))
			$this->autofill($this->getAutofillCount() - count($data['activeDeals']));

		return array_keys($data['activeDeals']);
	}

	/**
	 * Returns date until the given offer remains active
	 *
	 * @param  int product id
	 * @return \DateTime
	 */
	public function getOfferExpiration($id) {
		$data = $this->getData();

		if(!isset($data['activeDeals'][$id]))
			throw new Nette\InvalidArgumentException("Invalid offer given");

		return $data['activeDeals'][$id]['until'];
	}

	/**
	 * Performs autofill 
	 * 
	 * @param  int how many
	 * @return array of newly autofilled ids
	 */
	protected function autofill($n) {

		// Musim zamknout tabulku, aby nelosovalo vice sezeni zaroven
		// MySQL neumi zamykani s posranymi aliasy...
		$this->db->query("LOCK TABLES [$this->tableName] WRITE, [$this->tableName] AS [tlo1] WRITE, [$this->tableName] AS [tlo2] WRITE");

		// No-cache kvuli zamku
		// Data sice nacteme znovu, ale je to lepsi, je nacitat 2x jednou za cas,
		// nez pri kazdem cteni zamykat tabulku pro pripad, ze bychom potrebovali
		// provest autofill.
		$data = &$this->getData(true);

		$candidates = array_diff($this->getCandidates(), $data['blacklist']);

		// Date/Time interval
		$now = new \DateTime;
		$dt1 = vBuilder\Utils\DateTime::startOfPeriod($now, $this->getAutofillInterval());
		$dt2 = vBuilder\Utils\DateTime::endOfPeriod($now, $this->getAutofillInterval());

		// Creates array of never used products
		$freshProducts = array();
		foreach($candidates as $id) {
			$found = false;
			foreach($data['autofillPriorityQueue'] as $p => $ids) {
				if(in_array($id, $ids)) {
					$found = true;
					break;
				}
			}

			if(!$found) {
				$freshProducts[] = $id;
			}
		}

		// Preffering fresh products in auto-fill
		if(count($freshProducts) > 0)
			array_unshift($data['autofillPriorityQueue'], $freshProducts);

		$chosen = array();
		foreach($data['autofillPriorityQueue'] as $priority => $ids) {
			shuffle($ids);
			foreach($ids as $k => $id) {
				if($n-- <= 0) break 2;
				$chosen[] = $id;

				$data['activeDeals'][$id] = array(
					'until' => $dt2
				);

				unset($data['autofillPriorityQueue'][$priority][$k]);
			}

			unset($data['autofillPriorityQueue'][$priority]);
		}



		$iData = array();
		foreach($chosen as $id) {
			$iData[] = array(
				'productId' => $id,
				'since' => $dt1->format('Y-m-d H:i:s'),
				'until' => $dt2->format('Y-m-d H:i:s'),
			);
		}

		$this->db->query("INSERT INTO [$this->tableName] %ex", $iData);

		$this->db->query("UNLOCK TABLES");

		return $chosen;
	}

	/**
	 * Loads data from DB
	 *
	 * isAutofillEnabled: TRUE, if there is a productId == 0 row within active date range
	 * autofillPriorityQueue: array of arrays (product ids) sorted by ranking based on
	 * 		was this product not used
	 * blacklist: array of product ids which are temporary disables
	 * activeDeals: array of active deals (their product ids)
	 * 
	 * Function cashes it's results within an class instance
	 * 
	 * @return array
	 */
	protected function & getData($nocache = false) {

		if(!isset($this->_data) || $nocache) {

			$lastOffers = $this->db->query(
				"SELECT * FROM [$this->tableName] AS [tlo1]",
				"WHERE [until] = (",
				"	SELECT MAX([until]) FROM [$this->tableName] AS [tlo2]",
				"	WHERE ABS([tlo1.productId]) = ABS([tlo2.productId])",
				")",
				"GROUP BY ABS([productId])"
			)->setType('since', \dibi::DATETIME)
				->setType('until', \dibi::DATETIME)
				->setType('productId', \dibi::INTEGER);

			$now = new \DateTime;
			$data = array(
				'isAutofillEnabled' => true,
				'autofillPriorityQueue' => array(),
				'blacklist' => array(),
				'activeDeals' => array()
			);

			foreach($lastOffers as $record) {

				// Active deals
				if($record->since <= $now && $record->until >= $now) {

					if($record->productId == 0)
						$data['isAutofillEnabled'] = false;
					else {

						if($this->canBeCandidate(abs($record->productId))) {
							if($record->productId < 0)
								$data['blacklist'][] = abs($record->productId);
							else
								$data['activeDeals'][$record->productId] = array(
									'until' => $record->until
								);
						}

						// Pokud produkt uz nemuze byt pouzit (byl smazat, byl mu odstranen flag, ...)
						// Je treba upravit stavajici zaznamy v DB aby prestaly platit
						else {
							// Nepotrebuje zamknout tabulku, protoze sam o sobe nic neovlivnuje
							// (je v oifovane vetvi, jen updatuje tabulku pro ostatni session,
							// kterym to nevadi - bud bude proveden pred tim a vubec se sem nedostanou
							// a nebo se jen posune cas na nejaky jiny until, coz nam nevadi)		
							$this->db->query(
								"UPDATE [$this->tableName] SET",
								"	[until] = %t", $now,
								"WHERE",
								"	[id] = %i", $record->id
							);
						}

					}

				// Inactive deals for priority queue
				} elseif($record->productId != 0 && $this->canBeCandidate(abs($record->productId))) {
					$validForDays = (int) $record->until->diff($record->since)->format('%a');
					$beforeDays = (int) $now->diff($record->until)->format('%a');
					
					// Adjusts priority for blacklist records
					// 	- it's not exact because we don't take date of actual usage
					// 	  but only the factor how long has been this product disabled,
					// 	  => let's just say that disabled products deserve some more propagation :-)
					$priority = $record->productId < 0
						? 0 - ($validForDays + $beforeDays)
						: $validForDays - $beforeDays;

					if(isset($data['autofillPriorityQueue'][$priority]))
						$data['autofillPriorityQueue'][$priority][] = abs($record->productId);
					else
						$data['autofillPriorityQueue'][$priority] = array(abs($record->productId));
				}
			}

			ksort($data['autofillPriorityQueue']);
			$data['autofillPriorityQueue'] = array_values($data['autofillPriorityQueue']);
			$this->_data = $data;
		}

		return $this->_data;
	}

	
}

