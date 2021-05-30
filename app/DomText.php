<?php

/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

class DomText{
	private $__TEXT = [];

	function __construct($text){
		$this->__TEXT = $text;
	}

	public function merge($symbol = ''){
		if(gettype($this->__TEXT) == 'array'){
			return implode($symbol, $this->__TEXT);
		}else{
			return $this->__TEXT;
		}
	}

	public function getFirstElement(){
		return isset($this->__TEXT[0]) ? $this->__TEXT[0] : false;
	}

	public function getLastElement(){
		return end($this->__TEXT);
	}

	function contents(){
			return $this->__TEXT;
	}
}

?>
