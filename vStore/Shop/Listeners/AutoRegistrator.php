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
	Nette,
	Nette\Utils\Strings;

/**
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 22, 2011
 */
class AutoRegistrator extends vBuilder\Mail\MailNotificator {
		
	public function onOrderCreated(vStore\Shop\Order $order) {
		$customer = $order->customer;
		$entityName = vBuilder\Security::getUserClassName();
		
		if($this->context->user->isLoggedIn()) return ;
		
		$potentialUser = $this->context->repository->findAll($entityName)->where('[email] = %s', $customer->email)->fetch();
		
		if ($potentialUser) {
			// we shall not replace the existing user with another one...
			return;
		}
		
		$login = $this->context->config->get('user.login');
		$i = '';
		do {
			$newUsername = Strings::webalize($customer->name).'.'.Strings::webalize($customer->surname).$i;
			$i++;
			$usernameTaken = $this->context->repository->findAll($entityName)->where('[username] = %s', $newUsername)->fetch();
		} while ($usernameTaken);
		
		$user = new $entityName($this->context);
			
		$password = Nette\Utils\Strings::random(8);
		
		$user->setEmail($customer->email);
		$user->setPassword($password);
		$user->setName($customer->name);
		$user->setSurname($customer->surname);

		
		if ($login === 'username') {
			$user->setUsername($newUsername);
		}
		
		$user->setNewsletter(0); // No newsletter, by default
		$user->setBypassSecurityCheck(true);
		$user->save();
		
		// Ulozi to znovu i produkty :-(
		//$order->user = $user;
		//$order->save();
		
		// Docasny fix
		$orderEntity = $this->context->shop->getOrderEntityClass();
		$this->context->connection->update($orderEntity::getMetadata()->getTableName(), array(
			'user' => $user->id
		))->where('[id] = %i', $order->id)->execute();
		
		$this->template->user = $user;
		$this->template->password = $password;
		
		
		if($this->template->getFile() == "")
			$this->template->setFile(__DIR__ . '/Templates/email.autoRegistration.latte');		

		$this->message->addTo($customer->email, $customer->displayName);
		$this->message->setSubject('Vase registrace');
		$this->message->setHtmlBody($this->template);
		$this->message->send();
	}
	
}

