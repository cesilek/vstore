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

use vStore,
		Nette,
		vBuilder,
		Nette\Application\UI\Form,
		vBuilder\Orm\Repository,
		vBuilder\Application\UI\Link;

/**
 * Shop products listing
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class Register extends BaseForm {

	public $onUserRegistred = array();

	/** @var null|Link */
	private $_targetLink;
	
	/**
	 * Returns target link if set (or null)
	 * 
	 * @return null|Link
	 */
	public function getTargetLink() {
		return $this->_targetLink;
	}
	
	public function getLoginField() {
		// return $this->getContext()->config->get('user.login');
		return 'username';
	}

	/**
	 * Sets link of target page
	 * (page to which will user be redirected after succesful registration)
	 * 
	 * @param Link|string link
	 * @return Register fluent
	 */
	public function setTargetLink($link) {
		if (is_string($link)) {
			$this->_targetLink = new Link($this->getParent(), $link, array ());
		} else if (is_object ($link) && $link instanceof Link) {
			$this->_targetLink = $link;
		} else {
			throw new Nette\InvalidArgumentException('A link must be a string or an instance of vBuilder\Application\UI\Link! "'.  gettype($link).'" given.');
		}
		return $this;
	}
	
	public function createComponentRegisterForm($name) {
		$form = new Form($this, $name);
		
		$form->onSuccess[] = callback($this, $name.'Submitted');
				
		$form->addText('name', 'Jméno')
				->setRequired('Prosím vyplňte své jméno');
		$form->addText('surname', 'Přijímení')
				->setRequired('Prosím vyplňte své přijímení');
		
		
		if ($this->getLoginField() === 'username') {
			$form->addText('username','Uživatelské jméno:')
				  ->setRequired('Prosím vyplňte své uživatelské jméno.');
		}
		// we want the email anyway...
		$form->addText('email','E-mail:')
			  ->addRule(Form::EMAIL, 'Prosím vyplňte validní emailovou adresu.')
			  ->setRequired('Prosím vyplňte svou emailovou adresu.');

		$form->addPassword('password', 'Heslo:')
				->setRequired('Prosím vyplňte své heslo')
				->addRule(Form::MIN_LENGTH, 'Vaše heslo musí být dlouhé nejméně %d znaků.', $this->context->parameters['security']['password']['minLength']);
		$form->addPassword('passwordCheck', 'Heslo znovu pro kontrolu:')
				->setRequired('Prosím vyplňte své druhé helso pro kontrolu.')
				->addRule(Form::EQUAL, 'Vyplněná hesla se neshodují', $form['password']);
		
		$form->addCheckbox('newsletter', 'Mám zájem o pravidelné zasílání novinek')
			->setDefaultValue(true);
		
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

		if($this->context->authenticator instanceof vBuilder\Security\Authenticator) {
			$entityName = $this->context->authenticator->getUserClass();
		} else
			$entityName = "vBuilder\\Security\\User";
		
		$potentialUser = $this->getContext()->repository->findAll($entityName)->where('[email] = %s', $values->email)->fetch();
		if($potentialUser) {
			$form->addError('Uživatel se zadanou emailovou adresou je již registrován. Prosím zvolte nabídku "Přihlašte se" (uprostřed nahoře).');
			return;
		}
		
		if ($this->getLoginField() === 'username') {
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

		if ($this->getLoginField() === 'username') {
			$user->setUsername($values->username);
		}
		
		$user->newsletter = (int) $values->newsletter;
		
		$user->setBypassSecurityCheck(true);
		$user->save();
		
		$this->onUserRegistred($user, $values->password);
		
		$this->context->user->login($user->username, $values->password);
		
		$this->presenter->flashMessage(sprintf('Registrace proběhla úspěšně. Byl/a jste přihlášen jako uživatel %s. Nyní můžete naplno využívat výhod registrovaných uživatelů.', $user->username));
				
		if(isset($this->targetLink))
			$this->redirect($this->targetLink);
		else
			$this->redirect('this');
	}

	public function createRenderer() {
		return new RegisterRenderer($this);
	}
}
