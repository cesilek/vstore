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
class RetrievePassword extends BaseForm {
	
	protected $hash;
	
	public function createComponentRetrievePasswordForm($name) {
		$form = new Form;

		$form->addHidden('backlink', $this->presenter->getParam('backlink'));

		$login = $this->getContext()->config->get('user.login');
		if ($login === 'username') {
			$form->addText('username', 'Username:');
			if(($username = $this->getContext()->httpRequest->getCookie('vStoreLastLoggedUser')) !== NULL)
				$form['username']->setValue($username);
		}
		$form->addText('email', 'E-mail:')
				  ->setEmptyValue('@')
				  ->addCondition(Form::FILLED)
				  ->addRule(Form::EMAIL, 'E-mail is not valid');


		\PavelMaca\Captcha\CaptchaControl::register();
		$form['captcha'] = new \PavelMaca\Captcha\CaptchaControl;
		$form['captcha']->caption = ('Security code:');
		$form['captcha']->setTextColor(Nette\Image::rgb(48, 48, 48));
		$form['captcha']->setBackgroundColor(Nette\Image::rgb(232, 234, 236));
		$form['captcha']->addRule(Form::FILLED, 'Rewrite text from image.');
		$form['captcha']->addRule($form["captcha"]->getValidator(), 'Security code is incorrect. Read it carefuly from the image above.');

		if ($login === 'username') {
			$form['username']
				  ->addConditionOn($form['email'], Form::EQUAL, '')
				  ->addRule(Form::FILLED, 'Please provide your username or e-mail.');
			$form['email']
					  ->addConditionOn($form['username'], Form::EQUAL, '')
					  ->addRule(Form::FILLED, 'Please provide your username or e-mail.');
		}
		
		$form->addSubmit('send', 'Send new password');

		$form->onSuccess[] = callback($this, $name.'Submitted');
		return $form;
	}
	
	public function retrievePasswordFormSubmitted($form) {
		try {
			$values = $form->getValues();
			$username = $values->username;
			$email = $values->email;

			if(!isset($email) || $email == '') {
				$user = $this->getContext()->repository->findAll('vBuilder\Security\User')->where('[username] = %s', $username)->fetch();
			} else if(!isset($username) || $username == '') {
				$user = $this->getContext()->repository->findAll('vBuilder\Security\User')->where('[email] = %s', $email)->fetch();
			} else {
				$form->addError('Please provide your username or e-mail.');
				return;
			}
			
			if($user != false && $user->email != '') {
				
				//$user->setPassword($newPassword);
				$randomHash = Nette\Utils\Strings::random(48);
				$section = $this->context->session->getSection('retrievePassword');
				$section->passwordHash = $randomHash;

				$template = $this->template;
				$template->setFile(__DIR__.'/Templates/_mail.latte');
				$template->hash = $randomHash;
				$template->username = $user->username;
		
				$mail = new Nette\Mail\Message;
				$mail->setFrom('Admin <mirek@mladej.com>');
				$mail->setSubject($_SERVER['SERVER_NAME'].' - reset your password');
				$mail->addTo($user->email);
				echo($template->__toString());die;
				$mail->setHtmlBody($template);
				$mail->send();


				$this->flashMessage('A link has been sent to your e-mail address. Pleas click on it.');
				$this->redirect('this');
			} else {
				$form->addError('User not found.');
			}
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	public function createComponentNewPasswordForm($name) {
		$form = new Form;
		$form->onSuccess[] = callback($this, $name.'Submitted');
		$form->addProtection();
		$form->addHidden('hash')
				->setValue($this->getHash());
		$form->addPassword('pass', 'Fill in your new password:')
				->setRequired('You have to fill in your password')
				->addRule(Form::MIN_LENGTH, 'Your password has to be at least %d characters long', 9);
		$form->addPassword('pass2', 'And now again:')
				->addRule(Form::EQUAL, 'Your passwords have to match', $form['pass']);
		$form->addSubmit('s', 'Change!');
		return $form;
	}
	
	public function newPasswordFormSubmitted(Form $form) {
		dd('?.)');
	}


	public function getHash() {
		return $this->hash;
	}
	
	public function actionNewPassword($hash) {
		$section = $this->context->session->getSection('retrievePassword');
		if (isset($hash) && $hash === $section->passwordHash) {
			$this->hash = $hash;
		} else {
			$this->flashMessage('Invalid hash');
			$this->redirect('default');
		}
	}


	public function createRenderer() {
		return new RetrievePasswordRenderer($this);
	}
}
