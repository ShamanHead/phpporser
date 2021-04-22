<?php

/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

Class Children{
	private $__DOM = [];
	private $__ELEMENT_CONTENTS = [];
	public $__NOT_FOUND = false;

	function __construct($dom, $contents, $count = 0){
		if($dom[0]){
			$this->__COUNT = $count;
			$this->__DOM = $this->oneDom($dom);
			$this->__ELEMENT_CONTENTS = $contents;
		}else{
			$this->__NOT_FOUND = true;
		}
	}

	public function findAllText($dom = false){
		if(!$dom) $dom = $this->oneDom($this->__DOM);
		$result = [];
		for($i = 0;$i < count($dom);$i++){
			if(isset($dom[$i]['tag']) && $dom[$i]['tag'] == '__TEXT'){
				array_push($result, $dom[$i][0]);
			}else if(isset($dom[$i]['tag']) && $dom[$i]['tag'] != '__COMMENT' && !$dom[$i]['is_singleton']){
				$obj = $this->findAllText($dom[$i]);
				for($j = 0;$j < count($obj);$j++){
					$result[] = $obj[$j];
				}
			}else if(!isset($dom[$i]['tag']) && isset($dom[$i][0])){
				$obj = $this->findAllText($dom[$i]);
				for($j = 0;$j < count($obj);$j++){
					$result[] = $obj[$j];
				}
			}
		}
		return $result;

	}

	public function contents(){
		return $this->__ELEMENT_CONTENTS;
	}

	public function find(string $element, $number = 0){
		return new Element($this->__DOM, $element, $number);
	}

	public function viewDom(){
		return $this->__DOM;
	}

	public function children(int $number) {
		$result = [];
		$contents = [];
		$count = 0;
		if($this->__DOM[$number]['tag'] != "__TEXT" && $this->__DOM[$number]['tag'] != "__COMMENT" && !$this->__DOM[$number]['is_singleton']){
			array_push($result, $this->__DOM[$number]);
			$count+=count($this->oneDom($this->__DOM[$number]));
			for($j = 0, $keys = array_keys($this->_DOM[$number]);$j < count($keys);$j++){
				if($keys[$j] !== 0){
					$contents[$keys[$j]] = $this->_DOM[$number][$keys[$j]];
				}
			}
		}else{
			array_push($result, $this->__DOM[$number]);
			$count++;
		}
		return new Children($result, $contents, $count);
	}

	public function plainText(){
		$result = [];
		$this->__DOM = $this->oneDom($this->__DOM);
		if(isset($this->__DOM['tag']) && $this->__DOM['tag'] == '__TEXT'){
			return new DomText($this->__DOM[0]);
		}else{
			for($i = 0;$i < count($this->__DOM);$i++){
				if($this->__DOM[$i]['tag'] == '__TEXT'){
					array_push($result, $this->__DOM[$i][0]);
				}
			}
		}
		return new DomText($result);
	}

	private function oneDom($dom){
		if(gettype($dom) != 'array') return $dom;
		if($dom['tag']) return $dom;
		$is_empty = true;
		$is_empty_dom = $dom;
		while(@count($is_empty_dom) <= 1 && @!$is_empty_dom['tag']){
			if($is_empty_dom[0] == NULL) return $is_empty_dom;
			$is_empty_dom = $is_empty_dom[0];
		}
		return $is_empty_dom;
	}
}

?>
