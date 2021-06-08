<?php

/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

/**
 * Class Element
 * @package ShamanHead\PhpPorser\App
 */
class Element
{

    /**
     * @var array
     */
    private $__DOM = [];
    /**
     * @var array
     */
    public $__ELEMENT_DOM = [];
    /**
     * @var int
     */
    public $__COUNT = 0;

    /**
     * Element constructor.
     * @param array $dom
     * @param array|null $element
     */
    function __construct(array $dom, array $element = null)
    {
        $this->__PARENT_DOM = $dom;
        if ($element == null) {
            $this->__DOM = $this->__PARENT_DOM;
        } else $this->__DOM = $this->parsDom($element, $this->__PARENT_DOM);
        $this->__COUNT = count($this->__DOM);
    }

    /**
     * Walks through the tree and finds element with $element params.
     * @param array $element Search params
     * @param array $dom Dom tree
     * @return array List of founded elements
     * @throws \Exception
     */
    public function parsDom(array $element, array $dom): array
    {

        $elementName = $element[0];
        $elementValue = $element[1];

        if (!isset($elementValue) || !isset($elementName)) {
            throw new \Exception('Element params can\'t be empty');
        }

        $result = [];

        for ($i = 0; $i < count($dom); $i++) {
            $found = false;
            if (isset($dom[$i][$elementName])) {
                if ($elementName == 'id' || $elementName == 'class') {
                    for ($k = 0; $k < count($dom[$i][$elementName]); $k++) {
                        if ($dom[$i][$elementName][$k] == $elementValue) {
                            $found = true;
                        }
                    }
                } else if ($dom[$i][$elementName] == $elementValue) $found = true;
                if ($found) {
                    if ($dom[$i]['is_singleton']) {
                        $result = array_merge($result, [$dom[$i]]);
                    } else if ($dom[$i]['is_closing'] != true) {
                        $result = array_merge($result, [$dom[$i]], $this->parsDom($element, $dom[$i]));
                    }
                } else if ($dom[$i]['tag'] != '__TEXT' && $dom[$i]['tag'] != '__COMMENT') {
                    $result = array_merge($result, $this->parsDom($element, $dom[$i]));
                }
            } else if (isset($dom[$i][0]) && isset($dom[$i]['tag']) && $dom[$i]['tag'] != '__TEXT' && $dom[$i]['tag'] != '__COMMENT') {
                $result = array_merge($result, $this->parsDom($element, $dom[$i][0]));
            } else if (isset($dom[$i][0]) && !isset($dom[$i]['tag'])) {
                $result = array_merge($result, $this->parsDom($element, $dom[$i]));
            }
        }

        return $result;
    }

    /**
     * Finds attribute by name.
     * @param array $attr Array with attribute names
     * @param bool $strict Strict mode (for multiple attrs only)
     * @return array
     */
    public function attr(array $attr, bool $strict = true): array
    {
        $dom = $this->__DOM;
        $result = [];
        $counter = count($attr);
        for ($i = 0; $i < $this->__COUNT; $i++) {
            $element = $dom[$i];
            $attrCount = 0;
            $preResult = [];
            $found = false;
            for ($j = 0; $j < count($attr); $j++) {
                if (isset($element[$attr[$j]])) {
                    $preResult[] = $element[$attr[$j]];
                    $attrCount++;
                }
            }

            if (($strict === true && $counter == $attrCount) || $strict === false) {
                $found = true;
            }

            if ($found === true) {
                if ($counter > 1) {
                    $result[] = $preResult;
                } else {
                    $result = array_merge($result, $preResult);
                }
            }

        }
        return $result;
    }

    /**
     * @return int Count of founded elements
     */
    public function count(): int
    {
        return $this->__COUNT;
    }

    /**
     * @return array List of founded elements
     */
    public function array(): array
    {
        return $this->__DOM;
    }

    /**
     * @return array
     */
    public function parentDom(): array
    {
        return $this->__PARENT_DOM;
    }

    /**
     * @param string $id
     * @return Element
     */
    public function id(string $id): Element
    {
        return new Element($this->__DOM, ['id', $id]);
    }

    /**
     * Creates new Element instance with tag search params.
     * @param string $tag
     * @return Element
     */
    public function tag(string $tag): Element
    {
        return new Element($this->__DOM, ['tag', $tag]);
    }

    /**
     * Creates new Element instance with class search params.
     * @param string $class
     * @return Element
     */
    public function class(string $class): Element
    {
        return new Element($this->__DOM, ['class', $class]);
    }

    /**
     * Creates new Element instance with custom search params.
     * @param array $element Array with search params
     * @return Element
     */
    public function custom(array $element): Element
    {
        return new Element($this->__DOM, $element);
    }

    /**
     * @param int $number Number of element in tree.
     * @return Element
     */
    public function select(int $number): Element
    {
        return new Element($this->__DOM[$number]);
    }

    /**
     * Finds all text in $dom tree array.
     * @param bool $dom
     * @return DomText
     */
    public function text($dom = false): DomText
    {
        if (!$dom) $dom = $this->__DOM;
        $result = [];
        for ($i = 0; $i < count($dom); $i++) {
            if (isset($dom[$i]['tag']) && $dom[$i]['tag'] == '__TEXT') {
                array_push($result, $dom[$i][0]);
            } else if (isset($dom[$i]['tag']) && $dom[$i]['tag'] != '__COMMENT' && !$dom[$i]['is_singleton']) {
                $obj = $this->text($dom[$i])->contents();
                for ($j = 0; $j < count($obj); $j++) {
                    $result[] = $obj[$j];
                }
            } else if (!isset($dom[$i]['tag']) && isset($dom[$i][0])) {
                $obj = $this->text($dom[$i])->contents();
                for ($j = 0; $j < count($obj); $j++) {
                    $result[] = $obj[$j];
                }
            }
        }
        return new DomText($result);
    }

}

?>
