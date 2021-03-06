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

namespace vStore\Latte\Helpers;

/**
 * Latte template helpers for e-shop platform
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 2, 2011
 */
class Shop {

	public static function currency($value, $decimals = false) {
		return str_replace(" ", "\xc2\xa0", number_format($value, $decimals ? 2 : 0, ",", " "))."\xc2\xa0Kč";
	}

	public static function formatOrderId($value, $spaced = false) {
		$spacing = $spaced ? '  ' : '';
	
		return mb_substr($value, 0, 6) . $spacing . '/' . $spacing . mb_substr($value, 6);
	}
	
}