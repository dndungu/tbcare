<?php

namespace apps\user;

use apps\ApplicationException;

class SignUp extends \apps\Application {
	
	public function doGet(){
		try {
			$page = $this->doSignUpForm();
			return $page;
		} catch (\apps\ApplicationException $e) {
			$this->doCrash($e);
		}
	}
	
	public function doPost(){
		try {
			$user = $this->sandbox->getHelper('user');
			$user->signUp();
			$this->doRedirect();
		}catch(\apps\ApplicationException $e){
			$page = $this->doSignUpForm();
			$page['error'][] = $e->getMessage();
			return $page;
		}
	}
	
	private function doSignUpForm(){
		try {
			$translator = $this->sandbox->getHelper('translation');
			$form = $this->sandbox->getHelper('formbuilder');
			$base = $this->sandbox->getMeta('base');
			$form->setSource("$base/apps/user/forms/signup.xml");
			$this->title = $translator->translate('signup');
			$this->body = $form->asHTML();
			return array('title' => $this->title, 'body' => $this->body);
		}catch(\apps\ApplicationException $e){
			throw new \apps\ApplicationException($e->getMessage());
		}
	}
				
}