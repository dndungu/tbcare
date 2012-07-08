<?php

namespace apps\content;

class FileUploader extends \apps\Application {
	
	public function doGet(){
		return $this->getMaxSize();
	}
	
	public function doPost(){
		if(!count($_FILES)) {
			throw new \apps\ApplicationException('no file data received');
		}
		try{
			$base = $this->sandbox->getMeta('base');
			require_once("$base/apps/content/models/FileUpload.php");
			$uploader = new FileUpload($this->sandbox);
			$result = array('files' => ($uploader->saveFiles()), 'upload' => $_POST['upload']);
			$result['jScript'] = ($this->jScript($result));
			return $result;
		}catch(\apps\ApplicationException $e){
			$message = $e->getMessage();
			$this->doLog($e);
			return array('error' => $message);
		}
	}
	
	private function jScript($result){
		$html[] = '<script type="text/javascript">';
		$html[] = "\t".'var fileuploaderinfo = '.json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).';';
		$html[] = '</script>';
		return implode("\n", $html);
	}
	
	private function getMaxSize(){
		$setting = ini_get('post_max_size');
		switch(strtolower($setting[strlen($setting)-1])){
			case 'g':
				return ($setting * 1024 * 1024 * 1024);
			break;
			case 'm':
				return ($setting * 1024 * 1024);
			break;
			case 'k':
				return ($setting * 1024 * 1024);
			break;
		}
	}
	
}