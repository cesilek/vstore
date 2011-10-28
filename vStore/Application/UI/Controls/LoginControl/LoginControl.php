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
			$form->addText('username','Přihlašovací jméno:')
				  ->setRequired('Prosím zadejte Vaše přihlašovací jméno');
		} elseif ($login === 'mail') {
			$form->addText('username','E-mail:')
				  ->addRule(Form::EMAIL, 'Prosím zadejte Váš e-mail')
				  ->setRequired('Prosím zadejte Váš e-mail');
		}

		$form->addPassword('password', 'Heslo:')
				  ->setRequired('Prosím zadejte heslo');

		//$form->addCheckbox('remember', 'Auto-login in future.');

		$form->addSubmit('send', 'Přihlásit se');

		$form->onSuccess[] = callback($this, $name.'Submitted');
		$form->onError[] = callback($this, 'ajaxFormErrors');
		

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
			if(isset($values->remember) && $values->remember) {
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
			$form->addError('Zadané jméno nebo heslo není platné' /* $e->getMessage() */ );
		}
		$this->ajaxFormErrors($form);
	}
	
	
	public function createComponentRetrievePasswordForm($name) {
		$form = new Form;

		$form->addProtection();
		$form->addText('email', 'E-mail:')
				->setEmptyValue('@')
				->addRule(Form::EMAIL, 'E-mail is not valid')
				->setRequired('You have to fill in a valid email');


		\PavelMaca\Captcha\CaptchaControl::register();
		$form['captcha'] = new \PavelMaca\Captcha\CaptchaControl;
		$form['captcha']->caption = ('Security code:');
		$form['captcha']->setTextColor(Nette\Image::rgb(48, 48, 48));
		$form['captcha']->setBackgroundColor(Nette\Image::rgb(232, 234, 236));
		$form['captcha']->addRule(Form::FILLED, 'Rewrite text from image.');
		$form['captcha']->addRule($form["captcha"]->getValidator(), 'Security code is incorrect. Read it carefuly from the image above.');

			
		$form->addSubmit('send', 'Continue');

		$form->onSuccess[] = callback($this, $name.'Submitted');
		$form->onError[] = callback($this, 'ajaxFormErrors');
		return $form;
	}
	
	public function retrievePasswordFormSubmitted(Form $form) {
		$values = $form->getValues();
		$email = $values->email;

		$user = $this->getContext()->repository->findAll('vBuilder\Security\User')->where('[email] = %s', $email)->fetch();
		
		if ($user) {
			$this->newSecurityToken($user);

			$this->flashMessage('Na Váš email byl zaslán bezpečnostní kód.'); // TODO - nezobrzuje se
			$this->presenter->redirect('Redaction:resetPassword');
		} else {
			$form->addError('User not found.');
		}
		$this->ajaxFormErrors($form);
	}
	
	public function createComponentResetPasswordForm($name) {
		$form = new Form;
		
		$form->addProtection();
		$form->addText('token', 'Bezpečnostní kód:')
				->setRequired('Prosím vyplňte bezpečnostní kód, který jste obdrželi.');
		$form->addPassword('password', 'Zvolte si své nové heslo')
				->setRequired('Prosím vyplňte heslo')
				->addRule(Form::MIN_LENGTH, 'Heslo musí být dlouhé nejméně %d znaků', 6);
		$form->addPassword('passwordCheck', 'Opište své heslo znovu')
				->setRequired('Prosím vyplňte své heslo znovu pro kontrolu.')
				->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password']);
		
		$form->addSubmit('send', 'Odeslat!');
		
		$form->onSuccess[] = callback($this, $name.'Submitted');
		$form->onError[] = callback($this, 'ajaxFormErrors');
		return $form;
	}
	
	public function resetPasswordFormSubmitted(Form $form) {
		$values = $form->values;
		$section = $this->context->session->getSection('retrievePassword');
		if ($section->attempts >= 2) {
			$user = $this->getContext()->repository->findAll('vBuilder\Security\User')->where('[email] = %s', $section->email)->fetch();
			$this->newSecurityToken($user);
			$form->addError('Počet pokusů vypršel. Do Vaší emailové schránky byl zaslán nový bezpečnostní kód.');
		} else {
			try {
				if ($values->token === $section->token) {
					/*$user = $this->getContext()->repository->findAll('vBuilder\Security\User')->where('[email] = %s', $section->email)->fetch();
					$user->setPassword($values->password);
					$user->save();
					$this->presenter->getUser()->login($values->username, $values->password);*/
					// you totally just logged in...

					if ($this->presenter->isAjax()) {
						$this->presenter->payload->success = true;
						$this->presenter->sendPayload();
					}

					$this->presenter->redirect('Redaction', array(
						'id' => 2
					));
				} else {
					$section->attempts++;
					$form->addError('Bezpečnostní kód se neshoduje.');
				}
			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError($e->getMessage());
			}	
		}
		$this->ajaxFormErrors($form);
	}


	public function ajaxFormErrors(Form $form) {
		if ($form->hasErrors() && $this->presenter->isAjax()) {
			$errors = $form->getErrors();
			$error = array_shift($errors);
			$this->presenter->payload->error = true;
			$this->presenter->payload->message = $error; //$e->getMessage();
			$this->presenter->sendPayload();
		}
	}


	public function actionDefault() { }
	public function actionLogin() { }
	public function actionRetrievePassword() { }
	
	
	public function actionResetPassword() {
		$section = $this->context->session->getSection('retrievePassword');
	}

	public function createRenderer() {
		return new LoginControlRenderer($this);
	}
	
	public function handleLogout() {
		$this->context->user->logOut();
		$this->presenter->redirect('this');
	}
	
	
	/**
	 * @author Pavel Maca
	 * @param int $length
	 * @return string
	 * 
	 * To utils...?
	 */
	protected function getRandomToken($length = 8)
	{
		$numbers = '123456789';
		$vowels = 'aeiuy';
		$consonants = 'bcdfghjkmnpqrstvwxz';
		$s = '';
		for ($i = 0; $i < $length; $i++) {
			if(mt_rand(0, 10) % 3 === 0){
				$group = $numbers;
				$s .= $group{mt_rand(0, strlen($group) - 1)};
				continue;
			}
			$group = $i % 2 === 0 ? $consonants : $vowels;
			$s .= $group{mt_rand(0, strlen($group) - 1)};
		}
		return $s;
	}
	
	
	public function newSecurityToken($user) {
		$randomToken = $this->getRandomToken();

		$section = $this->context->session->getSection('retrievePassword');
		$section->attempts = 0;
		$section->token = $randomToken;
		$section->email = $user->email;

		$mail = new vBuilder\Mail\MailNotificator($this->context);
		$template = $mail->getTemplate();
		$message = $mail->getMessage();

		$message->setSubject('Shop - reset your password');
		$message->addTo($user->email);

		$template->setFile(__DIR__.'/Templates/_mail.latte');
		$template->token = $randomToken;
		$template->username = $user->username;

		$message->setHtmlBody($template);
		$message->send();
	}
}
