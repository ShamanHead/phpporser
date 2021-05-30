<?php
/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

interface ElementInterface{

  public function safeHTML($dom = false, int $level = 0) : string;
  public function find(string $element, int $number = -1) : Element;
  public function children(int $number) : Children;
  public function getCount($element = false, $dom = '', $count = 0) : array;
  public function findProperty($name);
  public function findAllText() : DomText;

}
