<?php

namespace apps\content;

class Thumbnailer extends \apps\Application {
	
	public function doGet(){
		$base = $this->sandbox->getMeta('base');
		$site = $this->sandbox->getHelper('site')->getID();
		$URI = explode('/', $this->sandbox->getMeta('URI'));		
		$resource = $URI[(count($URI)-1)];
		$filename = "$base/uploads/$site/$resource";
		if(!file_exists($filename)){
			header("Location: /default.png");
			exit;
		}
		require_once("$base/apps/content/models/ImageResize.php");
		try {
			$thumbnail = new ImageResize($filename);
			$part = $URI[(count($URI)-2)];
			if($part != 'uploadthumbnail'){
				if(substr_count($part, 'x')){
					$size = explode('x', $part);
					$thumbnail->setWidth($size[0]);
					$thumbnail->setHeight($size[1]);
				}
			}
			$thumbnail->doPrint();
		}catch(\apps\ApplicationException $e){
			$this->doLog($e);
			header("Location: /default.png");
			exit;
		}		
	}
	
}