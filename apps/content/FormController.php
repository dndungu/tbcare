<?php

namespace apps\content;

class FormController extends \apps\Application {
	
	public function doGet(){
		try {
			$base = $this->sandbox->getMeta('base');
			$form = $this->sandbox->getHelper('formbuilder');
			$request = explode('/', $this->sandbox->getMeta('URI'));
			$key = count($request) - 1;
			$name = $request[$key];
			$form->setSource("$base/apps/content/forms/$name.xml");
			return ($form->asHTML());
		}catch(\apps\ApplicationException $e){
			$this->doCrash($e);
		}
	}
}