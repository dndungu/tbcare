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
	
	public function browseRecords(){
		$countQuery = $this->queryBuilder->countQuery();
		$rowCount = $this->getStorage()->query($countQuery);
		$result['footer']['rowCount'] = $rowCount[0]['rowCount'];
		$result['footer']['rowOffset'] = $this->queryBuilder->getOffset();
		$result['footer']['rowLimit'] = $this->queryBuilder->getLimit();
		
		$result['body'] = $this->getStorage()->query($this->queryBuilder->browseQuery());
		$result['ordercolumn'] = $this->queryBuilder->getOrderColumn();
		$result['orderdirection'] = $this->queryBuilder->getOrderDirection();
		$result['primarykey'] = (string) $this->definition->columns->attributes()->primarykey;

		return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	
	public function getStorage(){
		$storage = (string) $this->definition->attributes()->storage;
		switch($storage){
			case "global":
				return $this->sandbox->getGlobalStorage();
				break;
			case "parent":
				return $this->sandbox->getParentStorage();
				break;
			case "local":
				return $this->sandbox->getLocalStorage();
				break;
			default:
				return $this->sandbox->getLocalStorage();
				break;
		}
	}	
	
	private function setup(){
		$attributes = $this->definition->attributes();
		$this->name = (string) $attributes->name;
		require_once('QueryBuilder.php');
		$this->queryBuilder = new QueryBuilder($this->sandbox);
		$this->queryBuilder->setDefinition($this->definition);
		$storage = $this->getStorage();
		$this->queryBuilder->setStorage($storage);
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
	
	public function asHTML(){
		$name = $this->name;
		$class = $this->isEditable() ? "$name editable grid" : "$name grid";
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
		$html[] = "\t<div class=\"gridHeader\">";
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
			$html[] = "\t<div class=\"gridColumns gradientSilver\">";
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
		$html[] = "\t<div class=\"gridContent\">";
		$html[] = "\t\t<div class=\"gridContentRecord\" title=\"{{primarykey}}\">";
		foreach($this->definition->columns->column as $column){
			$class = (string) $column->attributes()->class;
			$name = (string) $column->attributes()->name;
			$html[] = "\t\t\t<div class=\"$class\" name=\"$name\">{{".$name."}}</div>";
		}
		$html[] = "\t\t</div>";
		$html[] = "\t</div>";
		return join("\n", $html);
	}
	
	private function footerBar(){
		if($this->hasFooterBar()){
			$translator = $this->sandbox->getHelper('translation');
			$html[] = "\t<div class=\"gridFooter\">";
			if($this->isPaginatable()){
				$legend = $translator->translate('pagination.legend');
				$html[] = "\t\t<span>$legend</span>";
				$html[] = "\t\t<ul>";
				$html[] = "\t\t\t\t<li><a name=\"first\" class=\"first pagenavigator\">".$translator->translate('pagination.first')."</a></li>";
				$html[] = "\t\t\t\t<li><a name=\"previous\" class=\"previous pagenavigator\">".$translator->translate('pagination.previous')."</a></li>";
				$html[] = "\t\t\t\t<li><a name=\"next\" class=\"next pagenavigator\">".$translator->translate('pagination.next')."</a></li>";
				$html[] = "\t\t\t\t<li><a name=\"last\" class=\"last pagenavigator\">".$translator->translate('pagination.last')."</a></li>";
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
		$URI = $this->sandbox->getMeta('URI');
		$html[] = "<form action=\"$URI\" method=\"POST\">";
		$html[] = "<input type=\"text\" name=\"keywords\" placeholder=\"$searchText\"/>";
		$html[] = "<input type=\"submit\" value=\"&nbsp;\" class=\"searchButton\"/>&nbsp;";
		$html[] = "<input type=\"button\" name=\"addButton\" value=\"$addText\" class=\"addButton gridButton\"/>";
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