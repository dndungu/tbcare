<?php

namespace helpers;

require_once('QueryBuilder.php');

class Grid {
	
	private $sandbox = NULL;
	
	private $definition = NULL;
	
	private $name = NULL;
	
	private $editable = NULL;
	
	private $searchable = NULL;
	
	private $sortable = NULL;
		
	private $paginatable = NULL;
	
	private $columnBar = NULL;
	
	private $footerBar = NULL;
	
	private $queryBuilder = NULL;
	
	public function __construct(&$sandbox) {
		$this->sandbox = &$sandbox;
	}
	
	public function setSource($filename){
		if(!is_readable($filename)) {
			throw new HelperException("'$filename' is not readable");
		}
		$this->definition = simplexml_load_file($filename);
		if(!$this->definition) {
			throw new HelperException("'$filename' is not a valid XML table definition");
		}
		if(!property_exists($this->definition, "columns") || !property_exists($this->definition->columns, 'column')){
			throw new HelperException("'$filename' does not have valid table column definitions");
		}
		$this->setup();
	}
	
	private function setup(){
		$attributes = $this->definition->attributes();
		$this->name = (string) $attributes->name;
		require_once('QueryBuilder.php');
		$this->queryBuilder = new QueryBuilder($this->sandbox);
		$this->queryBuilder->setDefinition($this->definition);
		$this->isEditable(trim(strtolower((string) $attributes->editable)) === "true" ? true : false);
		$this->isSearchable(trim(strtolower((string) $attributes->searchable)) === "true" ? true : false);
		$this->isSortable(trim(strtolower((string) $attributes->sortable)) === "true" ? true : false);
		$this->isPaginatable(trim(strtolower((string) $attributes->paginatable)) === "true" ? true : false);
		$this->hasColumnBar(trim(strtolower((string) $attributes->columnBar)) === "true" ? true : false);
		$this->hasFooterBar(trim(strtolower((string) $attributes->footerBar)) === "true" ? true : false);
	}
		
	public function isEditable($editable=NULL){
		if(is_bool($editable)){
			$this->editable = $editable;
		}else{
			return $this->editable;
		}
	}
	
	public function isSearchable($searchable=NULL){
		if(is_bool($searchable)){
			$this->searchable = $searchable;
		}else{
			return $this->searchable;
		}
	}
	
	public function isSortable($sortable=NULL){
		if(is_bool($sortable)){
			$this->sortable = $sortable;
		}else{
			return $this->sortable;
		}
	}
	
	public function isPaginatable($paginatable=NULL){
		if(is_bool($paginatable)){
			$this->paginatable = $paginatable;
		}else{
			return $this->paginatable;
		}
		
	}
	
	public function hasColumnBar($columnBar = NULL){
		if(is_bool($columnBar)){
			$this->columnBar = $columnBar;
		}else{
			return $this->columnBar;
		}
	}
	
	public function hasFooterBar($footerBar = NULL){
		if(is_bool($footerBar)){
			$this->footerBar = $footerBar;
		}else{
			return $this->footerBar;
		}
	}	
	
	private function asHTML(){
		$name = $this->name;
		$class = $this->isEditable() ? "$name editable table" : "$name table";
		$html[] = "\n";
		$html[] = "<div class=\"$class\" name=\"$name\">";
		$html[] = $this->headerBar();
		$html[] = $this->columnsBar();
		$html[] = $this->recordsBody();
		$html[] = $this->footerBar();
		$html[] = "</div>";
		$html[] = "\n";
		return join("\n", $html);
	}
	
	private function headerBar(){
		$title = $this->getTitle($this->definition);
		$html[] = "\t<div class=\"tableHeader\">";
		if($this->isSearchable()){
			$html[] = "\t\t<div class=\"column grid4of10\">$title</div><div class=\"column grid6of10\">".$this->searchForm()."</div>";
		}else{
			$html[] = "\t\t<div class=\"column grid10of10\">$title</div>";
		}
		$html[] = "\t</div>";
		return join("\n", $html);
	}
	
	private function columnsBar(){
		if($this->hasColumnBar()){
			$html[] = "\t<div class=\"tableColumns\">";
			foreach($this->definition->columns->column as $column){
				$class = (string) $column->attributes()->class;
				$title = $this->getTitle($column);
				$field = (string) $column->attributes()->field;
				$sorter = $this->isSortable() ? "<span class=\"sort-icon\" name=\"$field\"></span>" : "";
				$html[] = "\t\t<div class=\"$class\">$title $sorter</div>";
			}
			$html[] = "\t</div>";
			return join("\n", $html);
		}else{
			return "";
		}
	}
	
	private function recordsBody(){
		$html[] = "\t<div class=\"tableBody\">";
		$html[] = "\t\t<div class=\"tableRecord\">";
		foreach($this->definition->columns->column as $column){
			$class = (string) $column->attributes()->class;
			$name = (string) $column->attributes()->name;
			$html[] = "\t\t\t<div class=\"$class\" name=\"$name\"></div>";
		}
		$html[] = "\t\t</div>";
		$html[] = "\t</div>";
		return join("\n", $html);
	}
	
	private function footerBar(){
		if($this->hasFooterBar()){
			$translator = $this->sandbox->getHelper('translation');
			$html[] = "\t<div class=\"tableFooter\">";
			if($this->isPaginatable()){
				$legend = $translator->translate('pagination.legend');
				$html[] = "\t\t<span>$legend</span>";
				$html[] = "\t\t<ul>";
				$html[] = "\t\t\t<li>";
				$html[] = "\t\t\t\t<a name=\"first\" class=\"first button\">".$translator->translate('pagination.first')."</a>";
				$html[] = "\t\t\t\t<a name=\"previous\" class=\"previous button\">".$translator->translate('pagination.previous')."</a>";
				$html[] = "\t\t\t\t<a name=\"next\" class=\"next button\">".$translator->translate('pagination.next')."</a>";
				$html[] = "\t\t\t\t<a name=\"last\" class=\"last button\">".$translator->translate('pagination.last')."</a>";
				$html[] = "\t\t\t</li>";
				$html[] = "\t\t</ul>";
			}
			$html[] = "\t</div>";
			return join("\n", $html);
		}else{
			return "";
		}
	}
	
	private function searchForm(){
		$translator = $this->sandbox->getHelper('translation');
		$searchText = $translator->translate('action.search');
		$addText = $translator->translate('action.add');
		$html[] = "<form action=\"$URI\" method=\"POST\">";
		$html[] = "<input type=\"text\" name=\"keywords\" placeholder=\"$searchText\"/>";
		$html[] = "<input type=\"submit\" value=\"&nbsp;\" class=\"searchButton button\"/>&nbsp;";
		$html[] = "<input type=\"button\" name=\"addButton\" value=\"$addText\" class=\"addButton\"/>";
		$html[] = "</form>";
		return join("", $html);
	}
	
	private function getTitle($node){
		$attributes = $node->attributes();
		if(property_exists($attributes, "title")){
			$title = (string) $attributes->title;
			return strlen($title) ? $this->sandbox->getHelper("translation")->translate($title) : "";
		} else {
			return "";
		}
	}	
	
}