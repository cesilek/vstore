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
	Nette\Application\UI\Form,
	vBuilder\Orm\Repository;

/**
 * Shop products listing
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class Register extends BaseForm {

	public function createComponentRegisterForm($name) {
		$form = new Form($this, $name);
		
		$form->onSuccess[] = callback($this, $name.'Submitted');
		
		$login = $this->getContext()->config->get('user.login');
		if ($login === 'username') {
			$form->addText('username','Uživatelské jméno:')
				  ->setRequired('Prosím vyplňte své uživatelské jméno.');
		}
		// we want the email anyway...
		$form->addText('email','E-mail:')
			  ->addRule(Form::EMAIL, 'Prosím vyplňte validní emailovou adresu.')
			  ->setRequired('Prosím vyplňte svou emailovou adresu.');
		$form->addPassword('password', 'Heslo:')
				->setRequired('Prosím vyplňte své heslo')
				->addRule(Form::MIN_LENGTH, 'Vaše heslo musí být dlouhé nejméně %d znaků.', 9);
		$form->addPassword('passwordCheck', 'Heslo znovu pro kontrolu:')
				->setRequired('Prosím vyplňte své druhé helso pro kontrolu.')
				->addRule(Form::EQUAL, 'Vyplněná hesla se neshodují', $form['password']);
		
		$form->addText('name', 'Jméno')
				->setRequired('Prosím vyplňte své jméno');
		$form->addText('surname', 'Přijímení')
				->setRequired('Prosím vyplňte své přijímení');
		
		$form->addCheckbox('newsletter', 'Mám zájem o pravidelné zasílání novinek');
		
		\PavelMaca\Captcha\CaptchaControl::register();
		$form['captcha'] = new \PavelMaca\Captcha\CaptchaControl;
		$form['captcha']->caption = ('Security code:');
		$form['captcha']->setTextColor(Nette\Image::rgb(48, 48, 48));
		$form['captcha']->setBackgroundColor(Nette\Image::rgb(232, 234, 236));
		$form['captcha']->setRequired('Prosím opište bezpečnostní kód z obrázku');
		$form['captcha']->addRule($form["captcha"]->getValidator(), 'Bezpečnostní kód se neshoduje. Prosím zkuste to znovu.');

		
		
		$form->addSubmit('s', 'Registrovat!');
	}
	
	public function registerFormSubmitted(Form $form) {
		$values = $form->values;
		$entityName = vBuilder\Security::getUserClassName();
		
		$potentialUser = $this->getContext()->repository->findAll($entityName)->where('[email] = %s', $values->email)->fetch();
		if($potentialUser) {
			$form->addError('Uživatel se zadanou emailovou adresou je již registrován.');
			return;
		}
		
		$login = $this->getContext()->config->get('user.login');
		
		if ($login === 'username') {
			$potentialUser = $this->getContext()->repository->findAll($entityName)->where('[username] = %s', $values->username)->fetch();
			if($potentialUser) {
				$form->addError('Uživatel se zadaným uživatelským jménem je již registrován.');
				return;
			}
		}
		
		$user = new $entityName($this->context);
		
		$user->setEmail($values->email);
		$user->setPassword($values->password);
		$user->setName($values->name);
		$user->setSurname($values->surname);

		if ($login === 'username') {
			$user->setUsername($values->username);
		}
		
		$user->setNewsletter((int) $values->newsletter);
		
		$user->setBypassSecurityCheck(true);
		$user->save();
		$this->presenter->flashMessage('Registrace proběhla úspěšně. Nyní se můžete přihlásit');
		$this->presenter->redirect('this', array ('id' => 2));
	}

	public function createRenderer() {
		return new RegisterRenderer($this);
	}
}
