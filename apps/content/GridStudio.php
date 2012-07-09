<?php

namespace apps\content;

class GridStudio extends \apps\Application {
	
	public function doGet(){
		try {
			$base = $this->sandbox->getMeta('base');
			$request = explode('/', $this->sandbox->getMeta('URI'));
			$key = count($request) - 1;
			$name = $request[$key];
			$grid = $this->sandbox->getHelper('grid');
			$grid->setSource("$base/apps/content/grids/$name.xml");
			return $grid->asHTML();
		}catch(\helpers\HelperException $e){
			$this->doCrash($e);
		}
	}
	
	public function doPost(){
		if(!array_key_exists('command', $_POST)) return;
		$base = $this->sandbox->getMeta('base');
		$request = explode('/', $this->sandbox->getMeta('URI'));
		$key = count($request) - 1;
		$name = $request[$key];
		$grid = $this->sandbox->getHelper('grid');
		$grid->setSource("$base/apps/content/grids/$name.xml");
		switch(trim($_POST['command'])){
			case 'browse':
				header('Content-type: application/json');
				return $grid->browseRecords();
				break;
			case 'search':
				header('Content-type: application/json');
				return $grid->searchRecords();
				break;
		}
	}
	
}