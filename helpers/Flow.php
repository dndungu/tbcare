<?php

namespace helpers;

class Flow {
	
	private $sandbox = NULL;
	
	private $definition = NULL;
	
	private $user = NULL;
	
	public function __construct(&$sandbox){
		$this->sandbox = &$sandbox;
		$this->user = $this->sandbox->getHelper('user');
	}
	
	public function setSource(){
		if(!is_readable($filename)) {
			throw new HelperException("'$filename' is not readable");
		}
		$this->definition = simplexml_load_file($filename);
		if(!$this->definition) {
			throw new HelperException("'$filename' is not a valid XML table definition");
		}
	}

	public function attestUser($action){
		$access = $this->definition->$action->access;
		if(isset($access->user)){
			foreach($access->user as $user){
				if((string) $user === "everyone") return true;
				if($this->user->getLogin() === (string) $user) return true;
			}
			return false;
		} else {
			return false;
		}
	}
	
	public function attestRole($action){
		$access = $this->definition->$action->access;
		if(isset($access->role)){
			foreach($access->role as $role){
				if((string) $role === "everyone") return true;
				$roles = $this->user->getRoles();
				if(is_null($roles)) return false;
				if(in_array((string) $role, $roles)) return true;
			}
			return false;
		} else {
			return false;
		}
	}
	
}