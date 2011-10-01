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
class RetrievePasswordForm extends BaseForm {

	public function render($what = null) {
		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/'.($what ?: 'full').'.latte');
		echo $template;
	}
	
	public function createComponentRetrievePasswordForm() {
		$form = new Form;

		$form->addHidden('backlink', $this->presenter->getParam('backlink'));

		$form->addText('username', 'Username:');

		if(($username = $this->getContext()->httpRequest->getCookie('vStoreLastLoggedUser')) !== NULL)
			$form['username']->setValue($username);
		
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

		$form['username']
				  ->addConditionOn($form['email'], Form::EQUAL, '')
				  ->addRule(Form::FILLED, 'Please provide your username or e-mail.');
		$form['email']
				  ->addConditionOn($form['username'], Form::EQUAL, '')
				  ->addRule(Form::FILLED, 'Please provide your username or e-mail.');
		
		$form->addSubmit('back', 'Back');
		$form->addSubmit('send', 'Send new password');

		$form->onSuccess[] = callback($this, 'retrievePasswordFormSubmitted');
		return $form;
	}
	
	public function retrievePasswordFormSubmitted($form) {
		try {
			$values = $form->getValues();
			$username = $values->username;
			$email = $values->email;
			$newPassword = Nette\Utils\Strings::random(8);

			if(!isset($email) || $email == '') {
				$user = $this->getContext()->repository->findAll('vBuilder\Security\User')->where('[username] = %s', $username)->fetch();
			} else if(!isset($username) || $username == '') {
				$user = $this->getContext()->repository->findAll('vBuilder\Security\User')->where('[email] = %s', $email)->fetch();
			} else {
				$form->addError('Please provide your username or e-mail.');
				return;
			}
			
			// ten vManageri mailer mi prijde nejaky pochybny. Udelame novy?
			/*if($user != false && $user->email != '') {
				$user->setPassword($newPassword);

				$tpl = Mailer::createMailTemplate(__DIR__ . '/../Templates/Emails/pwdReset.latte');
				$tpl->username = $user->username;
				$tpl->newPassword = $newPassword;
		
				$mail = Mailer::createMail();
				$mail->setSubject('vManager - new password');
				$mail->addTo($user->email);
				$mail->setHtmlBody($tpl);

				Mailer::getMailer()->send($mail);
				
				$user->save();

				$this->flashMessage('A new password has been sent to your e-mail address.');
				$this->redirect('Sign:in');
			} else {
				$form->addError('User not found.');
			}*/
		} catch(Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
}
