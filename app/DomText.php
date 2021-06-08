<?php

/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

/**
 * Class DomText
 * @package ShamanHead\PhpPorser\App
 */
class DomText
{
    /**
     * @var array
     */
    private $__TEXT = [];

    /**
     * DomText constructor.
     * @param $text
     */
    function __construct($text)
    {
        $this->__TEXT = $text;
    }

    /**
     * Merges all founded text into string with $symbol separator.
     * @param string $symbol
     * @return string
     */
    public function merge(string $symbol = ''): string
    {
        return implode($symbol, $this->__TEXT);
    }

    /**
     * @return string First founded text.
     */
    public function first(): string
    {
        return isset($this->__TEXT[0]) ? $this->__TEXT[0] : false;
    }

    /**
     * @return string Last founded text.
     */
    public function last(): string
    {
        return end($this->__TEXT);
    }

    /**
     * @return array All founded text.
     */
    function contents(): array
    {
        return $this->__TEXT;
    }
}

?>
