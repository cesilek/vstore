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

namespace vStore\ThirdPartyServices\CzechPost;

use vStore,
	vBuilder,
	vBuilder\Utils\Http,
	Nette,
	Nette\Utils\Strings;

/**
 * Service for gathering info about post offices
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 17, 2012
 */
class PostOfficeProvider extends vBuilder\Object {
	
	/**
	 * @var string URL of XML data file
	 */
	private $_dataUrl = 'http://napostu.cpost.cz/vystupy/napostu.xml';
	
	/** @var Nette\DI\IContainer DI context container */
	private $_context;
	
	/** @var Nette\Caching\Cache cache instance */
	protected $cache;
	
	/** @var array of PostOffice */
	protected $postOffices;
	
	/**
	 * Constructor
	 *
	 * @param Nette\DI\IContainer DI context container
	 */
	public function __construct(Nette\DI\IContainer $context) {
		$this->_context = $context;
		$this->cache = new Nette\Caching\Cache($context->cacheStorage, \str_replace('\\', '.', __CLASS__));
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
	 * Returns URL of XML data file
	 *
	 * @return string
	 */
	public function getDataUrl() {
		return $this->_dataUrl;
	}
	
	/**
	 * Sets URL for XML data file
	 *
	 * @param string URL
	 * @return CzechPostOfficeProvider Fluent
	 */
	public function setDataUrl($url) {
		$this->_dataUrl = $url;
		return $this;
	}
	
	/**
	 * Returns date/time of cache creation or NULL if
	 * data has not been cached yet.
	 *
	 * @return DateTime|null
	 */
	public function getCacheDateTime() {
		return isset($this->cache['created']) ? $this->cache['created'] : null;
	}
	
	/**
	 * Returns post office with postal code (exact match)
	 *
	 * @return PostOffice|NULL
	 */
	public function getByPostalCode($postalCode) {
		if(!isset($this->postOffices)) $this->noDataHandler();
	
		return isset($this->postOffices[$postalCode]) ? $this->postOffices[$postalCode] : null;
	}
	
	/**
	 * Returns all post offices with postal code starting with $postalCodePrefix.
	 * Result set is sorted by postal code.
	 *
	 * @param string postal code
	 * @return array of PostOffice
	 * 
	 */
	public function findByPostalCode($postalCodePrefix = '') {
		if(!isset($this->postOffices)) $this->noDataHandler();
			
		$postalCodePrefix = preg_replace('#\s+#', '', (string) $postalCodePrefix);
		
		if($postalCodePrefix == '')
			return array_values($this->postOffices);
		elseif(!is_numeric($postalCodePrefix))
			return array();
		elseif(mb_strlen($postalCodePrefix) == 5)
			return isset($this->postOffices[$postalCodePrefix]) ? array($this->postOffices[$postalCodePrefix]) : array();
		else {
			$matched = array();

			// Next possible value
			$low = (int) $postalCodePrefix - 1;
			$high = (int) $postalCodePrefix + 1; 
			while($high/10000 < 1) {
				$low *= 10;
				$high *= 10;
			}
		
			foreach($this->postOffices as $k => $po) {
				 // Sorted array optimization
				if($low >= $k) continue;
				if($k >= $high) break;
			
				if(Strings::startsWith($k, $postalCodePrefix))
					$matched[] = $po;
			}
			
			return $matched;
		}
	}
	
	/**
	 * Loads data from set up URL
	 *
	 * @return bool true, if data was loaded or false if cached data was used
	 */
	public function load() {
		return $this->loadFromUrl($this->dataUrl);
	}
	
	/**
	 * Loads data from cache
	 *
	 * @return bool if cache exists or false if does not
	 */
	protected function loadFromCache() {
		if(isset($this->cache['postOffices'])) {
			$this->postOffices = $this->cache['postOffices'];
			return true;
		}
		
		return false;
	}
	
	/**
	 * Loads data from given URL. HTTP and local files supported.
	 * If given URL refers to older file than cached content, cached data are used.
	 *
	 * @param string URL
	 * @param bool force load (no cache)
	 *
	 * @return bool true, if data was loaded or false if cached data was used
	 * @throws Nette\IOException if URL can't be loaded
	 */	
	public function loadFromUrl($url, $force = false) {
		if(!$fp = fopen($url, 'r'))
			throw new Nette\IOException("Cannot download post office info. Unable to open URL ($url).");

		$meta = stream_get_meta_data($fp);
		
		// HTTP DOWNLOAD
		if($meta['wrapper_type'] == 'http') {
			$httpCode = Http::parseStatusCode(array_shift($meta['wrapper_data']));
			if($httpCode != 200)
				throw new Nette\IOException("Cannot download post office info. HTTP $httpCode returned.");
			
			foreach($meta['wrapper_data'] as $headerStr) {
				$parsedHeaders = Http::parseHeaders($headerStr, true);
				
				if(isset($parsedHeaders['last-modified'])) {
					$lastModified = Http::parseDateTime($parsedHeaders['last-modified']);
					break;
				}
			}
		}

		// LOCAL FILE
		elseif($meta['wrapper_type'] == 'plainfile') {
			$lastModified = \DateTime::createFromFormat('U', filemtime($url));			
		}

		$cachedDT = $this->getCacheDateTime();
		if(!isset($lastModified) || $cachedDT == null || $lastModified > $cachedDT || $force) {
			$data = stream_get_contents($fp);
			fclose($fp);
			
			$this->cache['created'] = isset($lastModified) ? $lastModified : new \DateTime('now');
			$this->cache['postOffices'] = $this->postOffices = $this->parseData($data);			
			
			return true;
			
		} else {
			fclose($fp);
			
			$this->loadFromCache();
			return false;
		}
	}
	
	/**
	 * Helper function for parsing XML string into array of post offices.
	 * Array is associative by PO postal code (integer) and with sorted
	 * key for faster lookup.
	 *
	 * @return array of PostOffice
	 */
	protected function parseData(&$xmlStr) {
		$parsed = array();
		$root = simplexml_load_string($xmlStr);
		if($root === FALSE) throw new Nette\UnexpectedValueException("Cannot parse post office info. XML is not valid.");
		
		foreach($root->row as $curr) {
			$openingHours = array();
			if(isset($curr->OTV_DOBA)) {
				foreach($curr->OTV_DOBA->den as $d) {
				
					$thisDayOpening = array();
					foreach($d->od_do as $t) $thisDayOpening[] = array((string) $t->od, (string) $t->do);
					if(count($thisDayOpening) == 0) continue;
				
					switch((string) $d['name']) {
						case "Pondělí": $day = PostOffice::MONDAY; break;
						case "Úterý": $day = PostOffice::TUESDAY; break;
						case "Středa": $day = PostOffice::WEDNESDAY; break;
						case "Čtvrtek": $day = PostOffice::THURSDAY; break;
						case "Pátek": $day = PostOffice::FRIDAY; break;
						case "Sobota": $day = PostOffice::SATURDAY; break;
						case "Neděle": $day = PostOffice::SUNDAY; break;
					}

					$openingHours[$day] = $thisDayOpening;
				}
			}
		
			$parsed[(int) $curr->PSC] = new PostOffice(
				(int) $curr->PSC,					// Postal code
				(string) $curr->NAZ_PROV,			// Name
				(string) $curr->ADRESA,				// Street
				(string) $curr->OKRES,				// City
				(string) $curr->V_PROVOZU == 'N',	// Availability: V_PROVOZU <=> pokud neni provoz omezeny... kokoti...
				(string) $curr->UKL_NP_LIMIT == 'N' ? 20000 : null, // Maximum package value: UKL_NP_LIMIT <=> pokud lze na postu ukladat zasilky nad 20000 Kc
				(string) $curr->BANKOMAT == 'A',	// Has ATM?
				(string) $curr->PARKOVISTE == 'A',	// Has parking lot?
				count($openingHours) ? $openingHours : null	// Opening hours
			);
		}
		
		ksort($parsed, SORT_NUMERIC);
		return $parsed;
	}
	
	/**
	 * Helper function for state when data are required but nothing is cached
	 *
	 * @return void
	 */
	protected function noDataHandler() {
		if(!$this->loadFromCache())
			$this->load();
	}
	
}