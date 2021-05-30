<?php

/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

Class Children implements ElementInterface{
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

	public function findAllText($returnParent = false,$dom = false, $parent = false) : DomText {
		if(!$dom) $dom = $this->oneDom($this->__DOM);
		$result = [];
		// is_countable($dom) == false && die();
		for($i = 0;$i < count($dom);$i++){
			if(isset($dom[$i]['tag']) && $dom[$i]['tag'] == '__TEXT'){
				array_push($result, ($returnParent ? [$parent,$dom[$i][0]] : $dom[$i][0]));
			}else if(isset($dom[$i]['tag'])  && $dom[$i]['tag'] != '__COMMENT' && !$dom[$i]['is_singleton']){
				$obj = $this->findAllText($returnParent, $dom[$i], $dom[$i])->contents();
				for($j = 0;$j < count($obj);$j++){
					$result[] = $obj[$j];
				}
			}else if(!isset($dom[$i]['tag']) && isset($dom[$i][0])){
				$obj = $this->findAllText($returnParent, $dom[$i], ($returnParent ?  @array_diff_key($parent, [0]) : false))->contents();
				for($j = 0;$j < count($obj);$j++){
					$result[] = $obj[$j];
				}
			}
		}
		return new DomText($result);
	}

	public function safeHTML($dom = false, int $level = 0) : string {
		$result = '';
		if($dom === false){
			$dom = $this->oneDom($this->__DOM);
		}
		$dom = $this->oneDom($dom);
		for($i = 0;$i < count($dom);$i++){
			if(isset($dom[$i]['tag']) && ($dom[$i]['tag'] == '__COMMENT' || $dom[$i]['tag'] == '__TEXT')){
				$result .= str_repeat("\t", $level).$dom[$i][0]."\n";
				continue;
			}
			$properties = '';
			for($j = 0, $keys = array_keys($dom[$i]);$j < count($keys);$j++){
				if($keys[$j] === 'class' || $keys[$j] === 'id'){
					for($u = 0;$u < count($dom[$i][$keys[$j]]);$u++){
						if($u == count($dom[$i][$keys[$j]])-1){
							$ficators .= $dom[$i][$keys[$j]][$u];
							continue;
						}
						$ficators .= $dom[$i][$keys[$j]][$u].', ';
					}
					$properties .= " ".$keys[$j].'=\''.$ficators.'\' ';
				}

				if($keys[$j] !== 0 && $keys[$j] !== 'pointer' && $keys[$j] !== 'is_singleton' && $keys[$j] !== 'is_closing' && $keys[$j] !== 'tag' && $keys[$j] !== 'class' && $keys[$j] !== 'id'){
					if($dom[$i][$keys[$j]] === true){
						$properties .= " $keys[$j]";
						continue;
					}
					$founded = false;
					for($z = 0;$z < strlen($dom[$i][$keys[$j]]);$z++){
						if($dom[$i][$keys[$j]][$z] == '"'){
							$properties .= " $keys[$j]='".$dom[$i][$keys[$j]]."'";
							$founded = true;
							break;
						}
						if($dom[$i][$keys[$j]][$z] == "'"){
							$properties .= " $keys[$j]=\"".$dom[$i][$keys[$j]]."\"";
							$founded = true;
							break;
						}
					}
					if($founded == false){
						$properties .= " $keys[$j]='".$dom[$i][$keys[$j]]."'";
					}
				}
			}
			if(!empty($dom[$i][0])){
				$result .= str_repeat("\t", $level)."<".$dom[$i]['tag']."$properties>\n";
				$result .= $this->safeHTML($dom[$i][0], $level+1);
			}else{
				if(isset($dom[$i]['is_singleton']) && $dom[$i]['is_singleton'] == true){
					$result .= str_repeat("\t", $level)."<".$dom[$i]['tag']."$properties>\n";
				}else if(isset($dom[$i]['is_singleton'])){
					$result .= str_repeat("\t", $level ? $level-1 : $level)."</".$dom[$i]['tag'].">\n";
				}
			}
		}
		return $result;
	}

	public function contents(){
		return $this->__ELEMENT_CONTENTS;
	}

	public function find(string $element, $number = 0) : Element {
		return new Element($this->__DOM, $element, $number);
	}

	public function viewDom(){
		return $this->__DOM;
	}

	public function children(int $number) : Children {
		$result = [];
		$contents = [];
		$count = 0;
		if((isset($this->__DOM[$number]['tag']) && $this->__DOM[$number]['tag'] != "__TEXT" && $this->__DOM[$number]['tag'] != "__COMMENT") && empty($this->__DOM[$number]['is_singleton'])){
			array_push($result, $this->__DOM[$number]);
			$count+=count($this->oneDom($this->__DOM[$number][0]));
			for($j = 0, $keys = array_keys($this->__DOM[$number]);$j < count($keys);$j++){
				if($keys[$j] !== 0){
					$contents[$keys[$j]] = $this->__DOM[$number][$keys[$j]];
				}
			}
		}else{
			$count++;
			array_push($result, $this->__DOM[$number]);
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

	public function findProperty($name){
		$result = [];
		for($i = 0;$i < count($this->__ELEMENT_CONTENTS);$i++){
			if(isset($this->__ELEMENT_CONTENTS[$i][$name])){
				$result[] =$this->__ELEMENT_CONTENTS[$i][$name];
			}
		}
		return $result;
	}

	public function getCount($element = false, $dom = '', $count = 0) : array{
		if($dom == '') $dom = $this->__DOM;
		for($i = 0; $i < count($dom);$i++){
			if(isset($dom[$i]['tag'])){
				if($element != false){
					switch($element[0]){
						case '.':
							$class = str_replace('.', '', $element);
							if(isset($dom[$i]['class'])){
								$found = false;
								for($u = 0;$u < count($dom[$i]['class']);$u++){
									if($dom[$i]['class'][$u] == $class){
										$found = true;
										// die($class." ".$dom[$i]['class'][$u]);
										break;
									}
								}
								if($found){
									$count++;
								}
							}
							break;
						case '#':
							break;
						default:
							break;
					}
				}else{
					$count++;
				}
			}
			if(isset($dom[$i][0]) && !isset($dom[$i]['tag'])){
				$rec = $this->getCount($element,$dom[$i], $count);
				$count = $rec[1];
			}
		}
		return [$dom, $count];
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
}

?>
