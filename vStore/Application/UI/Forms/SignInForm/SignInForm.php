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
 * Shop products listing
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class SignInForm extends BaseForm {

	public function render($what = null) {
		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/'.($what ?: 'full').'.latte');
		echo $template;
	}
	
	public function createComponentSignInForm() {
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

		$form->onSuccess[] = callback($this, 'signInFormSubmitted');

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
	
	public function signInFormSubmitted($form) {
		try {
			$values = $form->getValues();
			if($values->remember) {
				$this->presenter->getUser()->setExpiration('+ 14 days', FALSE);
			} else {
				$this->presenter->getUser()->setExpiration('+ 20 minutes', TRUE);
			}
			$this->presenter->getUser()->login($values->username, $values->password);

			$this->getHttpResponse()->setCookie('vStoreLastLoggedUser', $values->username, time() + 365 * 24 * 60 * 60);

			if($values->backlink) {
				$this->getPresenter()->getApplication()->restoreRequest($values->backlink);
			} else {
				/*$u = $this->presenter->getUser()->getIdentity();
				
				//$config = Nette\Environment::getService('vBuilder\Config\IConfig');
				$config = $this->getContext()->config;
				$userLang = $config->get('system.language');
				if($userLang !== null) Nette\Environment::getService('Nette\ITranslator')->setLang($userLang);
				
				/*if($u->getLastLoginInfo()->exists() && $u->getLastLoginInfo()->getTime() !== null) {
					$host = gethostbyaddr($u->getLastLoginInfo()->getIp());
					if($host != $u->getLastLoginInfo()->getIp()) $host .= ' ('.$u->getLastLoginInfo()->getIp().')';
					
					$this->flashMessage(_x('%s. Welcome back. Last time you logged in %s from %s.', array(
						 $u->getSalutation(), 
						 vManager\Application\Helpers::timeAgoInWords($u->getLastLoginInfo()->getTime()),
						 /*$u->getLastLoginInfo()->getTime()->format('d.m.Y'),
						 $u->getLastLoginInfo()->getTime()->format('h:i:s'), * /
						 $host
					)));
				} else {
					$this->flashMessage(_x('%s. Since this is the first time you have logged in it is recommended to change your password.', array(
						 $u->getSalutation()
					)));
				}*/
			}
			
			//$this->presenter->redirect('this', array ('id'=>2));
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

}
