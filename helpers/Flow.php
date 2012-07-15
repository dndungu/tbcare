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
	
	public function setSource($filename){
		if(!is_readable($filename)) {
			throw new HelperException("'$filename' is not readable");
		}
		$this->definition = simplexml_load_file($filename);
		if(!$this->definition) {
			throw new HelperException("'$filename' is not a valid XML table definition");
		}
	}
	
	public function isInsertable(){
		$permission = (string) $this->definition->insert->attributes()->access;
		return $this->attestPermissions($permission);
	}
	
	public function isUpdateable(){
		$permission = (string) $this->definition->update->attributes()->access;
		return $this->attestPermissions($permission);
	}
	
	public function isDeleteable(){
		$permission = (string) $this->definition->delete->attributes()->access;
		return $this->attestPermissions($permission);
	}
	
	public function attestPermissions($permission){
		return in_array($permission, $this->user->getPermissions());
	}	
	
}