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
			$form->addText('username','Username:')
				  ->setRequired('Please provide a username.');
		}
		// we want the email anyway...
		$form->addText('email','E-mail:')
			  ->addRule(Form::EMAIL, 'Please provide a valid e-mail.')
			  ->setRequired('Please provide a valid e-mail.');
		$form->addPassword('password', 'Fill in your new password:')
				->setRequired('You have to fill in your password')
				->addRule(Form::MIN_LENGTH, 'Your password has to be at least %d characters long', 9);
		$form->addPassword('passwordCheck', 'And now again:')
				->setRequired('Please fill in the password again.')
				->addRule(Form::EQUAL, 'Your passwords have to match', $form['password']);
		$form->addSubmit('s', 'Register!');
	}
	
	public function registerFormSubmitted(Form $form) {
		$values = $form->values;
	}

	public function createRenderer() {
		return new RegisterRenderer($this);
	}
}
