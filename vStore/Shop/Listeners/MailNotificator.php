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

namespace vStore\Shop\Listeners;

use vStore,
		vBuilder,
		Nette;

/**
 * E-mail notificator for new orders
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 22, 2011
 */
class MailNotificator extends vBuilder\Mail\MailNotificator {
		
	public function onOrderCreated(vStore\Shop\Order $order) {
		$this->template->order = $order;
		
		
		if($this->template->getFile() == "")
			$this->template->setFile(__DIR__ . '/Templates/email.orderConfirmation.latte');		

		$this->message->addTo($order->customer->email, $order->customer->displayName);
		$this->message->setSubject('Potvrzeni objednavky c. ' . vStore\Latte\Helpers\Shop::formatOrderId($order->id));
		$this->message->setHtmlBody($this->template);
		$this->message->send();
	}
	
	// --------------------
	
	public function createTemplate($class = NULL) {
		$template = parent::createTemplate($class);
		
		$template->redaction = $this->context->redaction;
		
		$template->registerHelper('currency', 'vStore\Latte\Helpers\Shop::currency');
		$template->registerHelper('formatOrderId', 'vStore\Latte\Helpers\Shop::formatOrderId');
		
		return $template;
	}
	
	public function templatePrepareFilters($template, &$engine = null) {
		parent::templatePrepareFilters($template, $engine);		
	
		vBuilder\Latte\Macros\RedactionMacros::install($engine->parser);
	}
	
}

