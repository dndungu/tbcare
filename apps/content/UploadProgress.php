<?php

namespace apps\content;

class UploadProgress extends \apps\Application {
	
	public function doPost(){
		try {
			if(!array_key_exists('progress', $_POST)) throw new \apps\ApplicationException('progress key missing in $_POST');
			$prefix = strtolower(ini_get("session.upload_progress.prefix"));
			$key = $prefix.$_POST['progress'];
			if(array_key_exists($key, $_SESSION)){
				$result = array('progress' => $_SESSION[$key]);
				if(array_key_exists('complete', $_POST)){
					unset($_SESSION[$key]);
				}
				return $result;
			} else {
				return array('waiting' => $key);
			}
			
		}catch(\apps\ApplicationException $e){
			$this->doCrash($e);
		}	
	}
	
}