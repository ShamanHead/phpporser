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
	public function merge(){
		if(gettype($this->__TEXT) == 'array'){
			return implode('', $this->__TEXT);
		}else{
			return $this->__TEXT;
		}
	}

	function contents(){
			return $__TEXT;
	}
}

?>
