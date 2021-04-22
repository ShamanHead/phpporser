<?php

/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

class Element{

	private $__DOM = [];
	private $__ELEMENT = '';
	private $__ELEMENT_TYPE = '';
	public $__ELEMENT_CONTENTS = [];
	private $__ELEMENT_DOM = [];
	private $__ELEMENT_NUMBER = 0;
  public $__COUNT = 0;

	function __construct(array $dom, string $element, int $number){
		$this->__DOM = $dom;
		$this->__ELEMENT = str_replace(' ', '',$element);
		switch ($this->__ELEMENT[0]){
			case '.':
				$this->__ELEMENT_TYPE = 'class';
				$this->__ELEMENT = str_replace('.', '',$element);
			break;
			case '#':
				$this->__ELEMENT_TYPE = 'id';
				$this->__ELEMENT = str_replace('#', '',$element);
			break;
			default:
				$this->__ELEMENT_TYPE = 'tag';
			break;
		}
		$this->__ELEMENT_DOM = $this->oneDom($this->parsDom($this->__DOM, $number)[0]);
		$this->__COUNT = count($this->__ELEMENT_DOM);
	}

	public function contents(){
		return $this->__ELEMENT_CONTENTS;
	}

	public function findProperty($name){
		$result = [];
		for($i = 0;$i < count($this->__ELEMENT_CONTENTS);$i++){
			if(isset($this->__ELEMENT_CONTENTS[$i][$name])){
				$result[] =$this->__ELEMENT_CONTENTS[$i][$name];
			}
		}
		return $result;
	}

	private function parsDom($dom = false, $number = -1, $point = 0){
		if(!$dom) $dom = $this->__DOM;
		$temporary_dom = [];
		for($i = 0;$i < count($dom);$i++){
			if($this->__ELEMENT_TYPE == 'tag'){
				if(isset($dom[$i]['tag']) && ($dom[$i]['tag'] == '__COMMENT' || $dom[$i]['tag'] == '__TEXT')) continue;
				if((!isset($dom[$i]['tag']) || strcasecmp($dom[$i]['tag'], $this->__ELEMENT) != 0) && (!isset($dom[$i]['is_closing']) || $dom[$i]['is_closing'] != true)){
					if(isset($dom[$i][0])){
						$obj = $this->parsDom($dom[$i],$number, $point);
						if($obj[0]){
							if(isset($obj[1]) == false){
								return [$obj];
							}else{
								$point = $obj[1];
								array_push($temporary_dom, $obj[0]);
							}
						}
					}
				}else if($dom[$i]['is_closing'] != true){
					$obj = $this->parsDom($dom[$i],$number, $point);
					if(isset($obj[1]) == false){
						return [$obj];
					}else{
						$point = $obj[1]+1;
						if($dom[$i]['is_singleton']){
							array_push($temporary_dom, $dom[$i]);
						}else{
							array_push($temporary_dom, $dom[$i][0]);
						}
					}
					if(($this->__ELEMENT_CONTENTS == [] && $number >= 1) || $number < 1){
						$contents = [];
						for($j = 0, $keys = array_keys($dom[$i]);$j < count($keys);$j++){
							if($keys[$j] !== 0){
								$contents[$keys[$j]] = $dom[$i][$keys[$j]];
							}
						}
						array_push($this->__ELEMENT_CONTENTS, $contents);
					}
					if($point == $number-1){
						return [$dom[$i][0]];
					}
				}
			}
			if($this->__ELEMENT_TYPE == 'id' || $this->__ELEMENT_TYPE == 'class'){
				if((isset($dom[$i]['tag'])) && ($dom[$i]['tag'] == '__COMMENT' || $dom[$i]['tag'] == '__TEXT')) continue;
				if(isset($dom[$i]) && array_key_exists($this->__ELEMENT_TYPE, $dom[$i]) && !$dom[$i][$this->__ELEMENT_TYPE]){
					if(isset($dom[$i][0])){
						$obj = $this->parsDom($dom[$i],$number, $point);
						if($obj[0]){
							if(isset($obj[1]) == false){
								return [$obj];
							}else{
								$point = $obj[1];
								array_push($temporary_dom, $obj[0]);
							}
						}
					}
				}else{
					$finded = false;
					for($j = 0;$j < count(isset($dom[$i][$this->__ELEMENT_TYPE]) ? $dom[$i][$this->__ELEMENT_TYPE] : []);$j++){
						if(array_key_exists($this->__ELEMENT_TYPE, isset($dom[$i]) ? $dom[$i] : []) && strcasecmp($dom[$i][$this->__ELEMENT_TYPE][$j], $this->__ELEMENT) == 0){
							$finded = true;
						}
					}
					if($finded){
						$obj = $this->parsDom($dom[$i],$number, $point);
						if($point == $number-1){
							if(($this->__ELEMENT_CONTENTS == [] && $number >= 1) || $number < 1){
								$contents = [];
								for($j = 0, $keys = array_keys($dom[$i]);$j < count($keys);$j++){
									if($keys[$j] !== 0){
										$contents[$keys[$j]] = $dom[$i][$keys[$j]];
									}
								}
								array_push($this->__ELEMENT_CONTENTS, $contents);
							}
							return [$dom[$i][0]];
						}
						if(isset($obj[1]) == false){
							return [$obj];
						}else{
							$point = $obj[1]+1;
							if($dom[$i]['is_singleton']){
								array_push($temporary_dom, $dom[$i]);
							}else{
								array_push($temporary_dom, $dom[$i][0]);
							}
							$num = count($this->__ELEMENT_CONTENTS);
							if(($this->__ELEMENT_CONTENTS == [] && $number >= 1) || $number < 1){
								$contents = [];
								for($j = 0, $keys = array_keys($dom[$i]);$j < count($keys);$j++){
									if($keys[$j] !== 0){
										$contents[$keys[$j]] = $dom[$i][$keys[$j]];
									}
								}
								array_push($this->__ELEMENT_CONTENTS, $contents);
							}
						}
					}else{
						if(isset($dom[$i][0])){
							$obj = $this->parsDom($dom[$i],$number, $point);
							if($obj[0]){
								if(isset($obj[1]) == false){
									return [$obj];
								}else{
									$point = $obj[1];
									array_push($temporary_dom, $obj[0]);
								}
							}
							if(!$obj[0]){
								continue;
							}
						}
					}
				}
			}
		}
		return [$temporary_dom, $point];
	}

	public function viewDom(){
		return $this->__ELEMENT_DOM;
	}

	public function findAllText($dom = false){
		if(!$dom) $dom = $this->oneDom($this->__ELEMENT_DOM);
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

	public function getElementType(){
		return $this->__ELEMENT_TYPE;
	}

	public function plainText(){
		$result = [];
		$this->__ELEMENT_DOM = $this->oneDom($this->__ELEMENT_DOM);
		if(isset($this->__ELEMENT_DOM['tag'])){
			if($this->__ELEMENT_DOM['tag'] == '__TEXT'){
				return $this->__ELEMENT_DOM[0];
			}
		}else{
			for($i = 0;$i < count($this->__ELEMENT_DOM);$i++){
				if($this->__ELEMENT_DOM[$i]['tag'] == '__TEXT'){
					array_push($result, $this->__ELEMENT_DOM[$i][0]);
				}
			}
		}
		return new DomText($result);
	}

	public function find(string $element, int $number = -1){
		return new Element([$this->__ELEMENT_DOM], $element, $number);
	}

	private function oneDom($dom){
		if(gettype($dom) != 'array') return $dom;
		$is_empty = true;
		$is_empty_dom = $dom;
		while(!empty($is_empty_dom) && count($is_empty_dom) <= 1 && !isset($is_empty_dom['tag'])){
			$is_empty_dom = $is_empty_dom[0];
		}
		return $is_empty_dom;
	}

	public function children(int $number){
		$result = [];
		$contents = [];
		$count = 0;
		if($this->__ELEMENT_DOM[$number]['tag'] != "__TEXT" && $this->__ELEMENT_DOM[$number]['tag'] != "__COMMENT" && !$this->__ELEMENT_DOM[$number]['is_singleton']){
			array_push($result, $this->__ELEMENT_DOM[$number]);
			$count+=count($this->oneDom($this->__ELEMENT_DOM[$number][0]));
			for($j = 0, $keys = array_keys($this->__ELEMENT_DOM[$number]);$j < count($keys);$j++){
				if($keys[$j] !== 0){
					$contents[$keys[$j]] = $this->__ELEMENT_DOM[$number][$keys[$j]];
				}
			}
		}else{
			$count++;
			array_push($result, $this->__ELEMENT_DOM[$number]);
		}
		return new Children($result, $contents, $count);
	}
}

?>
