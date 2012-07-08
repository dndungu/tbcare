<?php

namespace apps\core;

class AliasExists extends \apps\Application {
	
	public function doPost(){
		try{
			$headers = getallheaders();
			if($headers['X-Powered-By'] != 'Gereji') throw new \apps\ApplicationException('Unauthorised client agent');
			$base = $this->sandbox->getMeta('base');
			require_once("$base/apps/core/models/Launcher.php");
			$launcher = new Launcher($this->sandbox);
			$exists = $launcher->aliasExists();
			return $exists;
		}catch(\apps\ApplicationException $e){
			$this->doCrash($e);
		}
	}
	
}