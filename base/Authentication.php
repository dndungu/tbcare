<?php

namespace base;

class Authentication {
	
	protected $sandbox = NULL;
	
	protected $portal = NULL;
		
	protected $user = NULL;
		
	protected $sitemap = NULL;
	
	public function __construct(&$sandbox) {
		$this->sandbox = &$sandbox;
		$this->user = $this->sandbox->getHelper('user');
		$this->sandbox->listen('aliasing.passed', 'init', $this);
	}
	
	public function init($data){
		$this->portal = $this->sandbox->getMeta('portal');
		if(!$this->shieldPortal()) {
			$message = "Access to portal not allowed";
			error_log($message);
			return $this->sandbox->fire('authentication.failed', $message);
		}
		if($this->shieldPortlets()){
			$this->generateNavigation();
			$this->sandbox->setMeta('portal', $this->portal);
			$this->sandbox->fire('authentication.passed', $this->portal);
		} else {
			$message = "Access to any portlets not allowed";
			error_log($message);
			$this->sandbox->fire('authentication.failed', $message);
		}
	}
	
	protected function generateNavigation(){
		$translator = $this->sandbox->getHelper('translation');
		$package = $this->sandbox->getMeta('package');
		$sitemap = NULL;
		foreach ($package as $portal){
			if(!$this->attestUser($portal->access) && !$this->attestRole($portal->access)) continue;
			foreach($portal->navigation as $match){
				$uri['id'] = (string) $match->attributes()->id;
				$uri['uri'] = (string) $match->attributes()->uri;
				$uri['parent'] = (string) $match->attributes()->parent;
				$uri['group'] = (string) $match->attributes()->group;
				$uri['label'] = $translator->translate((string) $match->attributes()->label);
				$uri['weight'] = (int) $match->attributes()->weight;
				$uri['class'] = (string) $match->attributes()->class;
				$sitemap[] = $uri;
			}
		}
		$this->sandbox->setMeta('navigation', $sitemap);
	}
	
	protected function shieldPortal(){
		if(isset($this->portal->access)){
			return ($this->attestUser($this->portal->access) || $this->attestRole($this->portal->access));
		} else {
			return false;
		}
	}
	
	protected function shieldPortlets(){
		foreach($this->portal->portlet as $portlet){
			if($this->attestUser($portlet->access) || $this->attestRole($portlet->access)) {
				$portlets[] = $portlet->asXML();
			}
		}
		
		if(!isset($portlets)) return false;
		$attributes = array();
		foreach($this->portal->attributes() as $key => $value){
			$attributes[] = "$key = \"$value\"";
		}
		$portal[] = sprintf("<portal %s>", implode(' ', $attributes));
		$portal[] = "\t".$this->portal->navigation->asXML();
		foreach($portlets as $portlet){
			$portal[] = "\t".$portlet;
		}
		$portal[] = "</portal>";
		$this->portal = simplexml_load_string(implode("\n", $portal));
		return true;		
	}
	
	public function attestUser($access){
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
	
	public function attestRole($access){
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

?>