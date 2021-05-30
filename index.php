<?php
declare(strict_types=1);

require_once 'bootstrap.php';

session_start();

use app\Parser\Dom;
use app\Parser\Element;
use app\Parser\Mark;
use app\Parser\DomText;
use app\Parser\Children;
use app\Parser\Node;

$parser = [
    'Dom' => new Dom($url),
    'Element' => new Element($dom, $element, $number),
    'Mark' => new Mark($dom, $element, $number),
    'DomText' => new DomText($text),
    'Children' => new Children($dom, $contents),
    'Node' => new Node(),
];

// Let's Go blyad'
