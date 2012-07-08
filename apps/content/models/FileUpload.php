<?php

namespace apps\content;

class FileUpload {
	
	private $sandbox = NULL;
	
	public function __construct(&$sandbox) {
		$this->sandbox = &$sandbox;
	}
	
	public function saveFiles(){
		try {
			$uploads = array();
			foreach($_FILES as $key => $value){
				$upload = $value;
				$tmp_name = explode('/', $value['tmp_name']);
				$name = explode('.', $value['name']);
				$extension = count($name) ? ('.' . strtolower($name[(count($name)-1)])) : "";
				$tmp_file = $tmp_name[(count($tmp_name)-1)];
				$tmp_file = strlen($extension) ? ($tmp_file.$extension) : $tmp_file;
				if(!move_uploaded_file($value['tmp_name'], ($this->siteDir().'/'.$tmp_file))) throw new \Exception('Unable to move uploaded file');
				$upload['tmp_name'] = $tmp_file;
				$upload['ID'] = $this->createUpload($upload);
				$uploads[] = $upload;
			}
			return $uploads;
		}catch(\Exception $e){
			throw new \apps\ApplicationException($e->getMessage());
		}
	}
	
	private function createUpload($upload){
		$insert['table'] = 'upload';
		$insert['content']['site'] = $this->sandbox->getHelper('site')->getID();
		$insert['content']['user'] = $this->sandbox->getHelper('user')->getID();
		$insert['content']['size'] = $upload['size'];
		$insert['content']['name'] = $upload['name'];
		$insert['content']['mimetype'] = $upload['type'];
		$insert['content']['tmp_name'] = $this->siteDir().'/'.$upload['tmp_name'];
		$insert['content']['creationTime'] = time();
		return $this->sandbox->getGlobalStorage()->insert($insert);
	}
	
	private function siteDir(){
		$base = $this->sandbox->getMeta('base');
		$site = $this->sandbox->getHelper('site')->getID();
		$dir = "$base/uploads/$site";
		if(!is_dir($dir)){
			mkdir($dir);
		}
		return $dir;
	}
	
}