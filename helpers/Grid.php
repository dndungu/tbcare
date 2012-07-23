<?php

namespace helpers;

require_once('QueryBuilder.php');

class Grid {
	
	private $sandbox = NULL;
	
	private $definition = NULL;
	
	private $name = NULL;
	
	private $flow = NULL;
	
	private $insertable = NULL;
		
	private $deleteable = NULL;
	
	private $searchable = NULL;
	
	private $sortable = NULL;
		
	private $paginatable = NULL;
	
	private $showColumnBar = NULL;
	
	private $showFooterBar = NULL;
	
	private $queryBuilder = NULL;
	
	private $records = NULL;
	
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
		$this->records = $this->getStorage()->query($this->queryBuilder->browseQuery());
		$this->formatRecords();
		$result['body'] = $this->records;
		$result['ordercolumn'] = $this->queryBuilder->getOrderColumn();
		$result['orderdirection'] = $this->queryBuilder->getOrderDirection();
		$result['primarykey'] = (string) $this->definition->columns->attributes()->primarykey;

		return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	
	private function formatRecords(){
		if(!$this->records) return;
		$settings = $this->sandbox->getHelper('site')->getSettings();
		foreach($this->records as $key => $record){
			if(array_key_exists('creationTime', $record)){
				$this->records[$key]['creationTime'] = date($settings['timeformat'], $record['creationTime']);
			}
			if(array_key_exists('expiryTime', $record)){
				$this->records[$key]['expiryTime'] = date($settings['timeformat'], $record['expiryTime']);
			}
		}
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
		$this->initOptions();
		$this->initFlow();
		$this->initQueryBuilder();
	}
	
	private function initFlow(){
		$base = $this->sandbox->getMeta('base');
		require_once("$base/helpers/Flow.php");
		$id = (string) $this->definition->attributes()->id;
		$name = strlen($id) ? $id : $this->name;
		$this->flow = new Flow($this->sandbox);
		$this->flow->setSource("$base/apps/content/flows/$name.xml");
	}	
	
	private function initQueryBuilder(){
		$base = $this->sandbox->getMeta('base');
		require_once("$base/helpers/QueryBuilder.php");
		$this->queryBuilder = new QueryBuilder($this->sandbox);
		$this->queryBuilder->setDefinition($this->definition);
		$storage = $this->getStorage();
		$this->queryBuilder->setStorage($storage);
	}
	
	private function initOptions(){
		$attributes = $this->definition->attributes();
		$this->name = (string) $attributes->name;
		$this->isSearchable(trim(strtolower((string) $attributes->searchable)) === "true" ? true : false);
		$this->isSortable(trim(strtolower((string) $attributes->sortable)) === "true" ? true : false);
		$this->isPaginatable(trim(strtolower((string) $attributes->paginatable)) === "true" ? true : false);
		$this->hasColumnBar(trim(strtolower((string) $attributes->showColumnBar)) === "true" ? true : false);
		$this->hasFooterBar(trim(strtolower((string) $attributes->showFooterBar)) === "true" ? true : false);
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
			return ($this->paginatable && $this->showFooterBar);
		}
		
	}
	
	public function hasColumnBar($showColumnBar = NULL){
		if(is_bool($showColumnBar)){
			$this->showColumnBar = $showColumnBar;
		}else{
			return $this->showColumnBar;
		}
	}
	
	public function hasFooterBar($showFooterBar = NULL){
		if(is_bool($showFooterBar)){
			$this->showFooterBar = $showFooterBar;
		}else{
			return $this->showFooterBar;
		}
	}	
	
	public function asHTML(){
		$html[] = "\n";
		$html[] = '<div class="' . $this->getClass() . '" name="' . $this->name . '">';
		$html[] = $this->headerBar();
		$html[] = $this->columnsBar();
		$html[] = $this->recordsBody();
		$html[] = $this->showFooterBar();
		$html[] = "</div>";
		$html[] = "\n";
		return join("\n", $html);
	}
	
	private function getClass(){
		$class[] = 'grid';
		$class[] = $this->name;
		if($this->flow->isInsertable()){
			$class[] = 'insertable';
		}
		if($this->isSearchable()){
			$class[] = 'searchable';
		}
		if($this->isPaginatable()){
			$class[] = 'paginatable';
		}
		if($this->isSortable()){
			$class[] = 'sortable';
		}
		return implode(' ', $class);
	}
	
	private function headerBar(){
		$title = $this->getTitle($this->definition);
		$html[] = "\t<div class=\"gridHeader\">";
		if($this->isSearchable()){
			$html[] = "\t\t<div class=\"column grid6of10\">$title</div><div class=\"column grid4of10\">".$this->searchForm()."</div>";
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
	
	private function showFooterBar(){
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
		if($this->flow->isInsertable()){
			$html[] = "<input type=\"button\" name=\"addButton\" value=\"$addText\" class=\"addButton gridPrimaryButton\"/>";
		}
		$html[] = "<input type=\"text\" name=\"keywords\" placeholder=\"$searchText\"/>";
		$html[] = "<input type=\"submit\" value=\"&nbsp;\" class=\"searchButton gridSecondaryButton\"/>&nbsp;";
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