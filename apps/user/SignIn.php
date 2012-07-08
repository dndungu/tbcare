<?php

namespace apps\user;

class SignIn extends \apps\Application {
	
	public function doGet(){
		try {
			$page = $this->doSignInForm();
			return $page;
		} catch (\apps\ApplicationException $e) {
			$this->doCrash($e);
		}
	}
	
	public function doPost(){
		try{
			$user = $this->sandbox->getHelper('user');			
			$user->signIn();
			$this->doRedirect();
		}catch(\apps\ApplicationException $e){
			$page = $this->doSignInForm();
			$page['error'][] = $e->getMessage();
			return $page;
		}
	}
	
	private function doSignInForm(){
		$translator = $this->sandbox->getHelper('translation');
		$form = $this->sandbox->getHelper('formbuilder');
		$base = $this->sandbox->getMeta('base');
		$form->setSource("$base/apps/user/forms/signin.xml");
		$page['title'] = $translator->translate('signin');
		$page['body'] = $form->asHTML();
		return $page;
	}
	
}