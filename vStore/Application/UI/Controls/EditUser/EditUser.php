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

use vStore, Nette,
	vBuilder,
	Nette\Application\UI\Form;

/**
 * Mini cart
 *
 * @author Jirka Vebr
 */
class EditUser extends vStore\Application\UI\Control {
	
	public function createRenderer() {
		return new EditUserRenderer($this);
	}
	
	public function createComponentChangePasswordForm($name) {
		$form = new Form($this, $name);
		$form->onSuccess[] = callback($this, $name.'Submitted');
		
		$form->addPassword('current', 'Zadejte prosím své současné heslo')
				->setRequired('Vyplnťe prosím své stávající heslo');
		$form->addPassword('password', 'Zvolte si prosím své nové heslo')
				->setRequired('Prosím vyplňte své nové heslo')
				->addRule(Form::MIN_LENGTH, 'Vaše nové heslo musí být dlouhé nejméně %d znaků.', $this->context->parameters['security']['password']['minLength']);
		$form->addPassword('passwordCheck', 'Heslo znovu pro kontrolu:')
				->setRequired('Prosím vyplňte své druhé helso pro kontrolu.')
				->addRule(Form::EQUAL, 'Vyplněná hesla se neshodují', $form['password']);
		$form->addSubmit('s', 'Změnit!');
		$form->addProtection();
	}
	
	public function changePasswordFormSubmitted(Form $form) {
		$values = $form->values;
		$entity = $this->getContext()->user->identity;
		$username = $entity->username;
		if (!$entity->checkPassword($values->current)) {
			$form->addError('Chybné stávající heslo');
			return;
		}
		$entity->setPassword($values->password);
		$entity->setBypassSecurityCheck(true);
		$entity->save();
		$user = $this->getContext()->user;
		$user->logOut();
		$user->logIn($username, $values->password);
		$this->presenter->flashMessage('Vaše heslo bylo úspěšně změněno.');
		$this->redirect('this');
	}
	
	public function actionDefault() {
		$this['updateProfileForm']->loadFromEntity($this->getContext()->user->identity);
	}


	public function createComponentUpdateProfileForm($name) {
		$form = new Form($this, $name);
		$form->onSuccess[] = callback($this, $name.'Submitted');
		
		$form->addText('name', 'Jméno:')
			->setRequired('Zadejte prosím své jméno.');
		$form->addText('surname', 'Přijímení:')
			->setRequired('Zadejte prosím své přijímení.');
		
		// Přezdívky (Optional)
		if($this->getContext()->user->identity->getMetadata()->hasField('nickname')) {
			$form->addText('nickname', 'Přezdívka do diskuze:')
				->setRequired('Vaše přezdívka nemůže být prázdná.');
		}
			
		$form->addText('email', 'E-mail:')
			->setRequired('Vyplňte prosím svůj e-mail.')
			->addRule(Form::EMAIL, 'Vyplňte prosím validní e-mailovou adresu.');
		$form->addCheckbox('newsletter', 'Mám zájem o novinky');
		$form->addProtection();
		$form->addSubmit('s', 'Změnit!');
	}
	
	public function updateProfileFormSubmitted(Form $form) {
		$user = $this->getContext()->user->identity;
		$form->fillInEntity($user);
		$user->setBypassSecurityCheck(true);
		$user->save();
		$this->presenter->flashMessage('Vaše změny byly úspěšně uloženy.');
		$this->presenter->redirect('this');
	}
}