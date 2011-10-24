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
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class LoginControl extends BaseForm {
	
	/** @var array */
	public $onRedirect = array ();
	
	public function createComponentLoginForm($name) {
		$form = new Form;

		$form->addHidden('backlink', $this->presenter->getParam('backlink'));
		
		$login = $this->getContext()->config->get('user.login');
		if ($login === 'username') {
			$form->addText('username','Username:')
				  ->setRequired('Please provide a username.');
		} elseif ($login === 'mail') {
			$form->addText('username','E-mail:')
				  ->addRule(Form::EMAIL, 'Please provide a valid e-mail.')
				  ->setRequired('Please provide a valid e-mail.');
		}

		$form->addPassword('password', 'Password:')
				  ->setRequired('Please provide a password.');

		$form->addCheckbox('remember', 'Auto-login in future.');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = callback($this, $name.'Submitted');

		$request = $this->getContext()->httpRequest;
		$value = $request->getCookie('vStoreLastLoggedUser');
		if($value !== NULL) {
			$form['username']->setValue($value);
			/*$form['password']->setAttribute('class', 'focus');
			$form['username']->setAttribute('class', '');*/
		}/* else {
			$form['password']->setAttribute('class', '');
			$form['username']->setAttribute('class', 'focus');
		}*/

		return $form;
	}
	
	public function loginFormSubmitted($form) {
		try {
			$values = $form->getValues();
			if($values->remember) {
				$this->presenter->getUser()->setExpiration('+ 14 days', FALSE);
			} else {
				$this->presenter->getUser()->setExpiration('+ 20 minutes', TRUE);
			}
			$this->presenter->getUser()->login($values->username, $values->password);

			$this->getContext()->httpResponse->setCookie('vStoreLastLoggedUser', $values->username, time() + 365 * 24 * 60 * 60);

			if(isset($values->backlink) && !empty($values->backlink)) {
				$this->getPresenter()->getApplication()->restoreRequest($values->backlink);
			} else {
				if ($this->presenter->isAjax()) {
					$this->presenter->payload->success = true;
					$this->presenter->sendPayload();
				}
				$this->onRedirect($this);
				$this->presenter->redirectUrl($this->context->httpRequest->headers['referer']);
			}
			
		} catch(Nette\Security\AuthenticationException $e) {
			if ($this->presenter->isAjax()) {
				$this->presenter->payload->error = true;
				$this->presenter->payload->message = $e->getMessage();
				$this->presenter->sendPayload();
			}
			$form->addError($e->getMessage());
		}
	}
	
	public function actionDefault() { }
	public function actionLogin() { }

	public function createRenderer() {
		return new LoginControlRenderer($this);
	}
	
	public function handleLogout() {
		$this->context->user->logOut();
		$this->presenter->redirect('this');
	}
}
