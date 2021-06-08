<?php

/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

/**
 * Class Dom
 * @package ShamanHead\PhpPorser\App
 */
class Dom
{

    /**
     * Source text of received html.
     * @var string
     */

    public $__SOURCE_TEXT = false;

    /**
     * Tree with all data. Every leaf of tree is element, or text and comments.
     * @var array
     */

    private $__DOM = false;

    /**
     * Setting to get <style> and <script> tags from file. Not recommended to use.
     * @var bool
     */

    private $__ENABLE_STYLES_SCRIPTS = false;

    /**
     * Setting to get comments from file.
     * @var bool
     */

    private $__ENABLE_COMMENTS = false;

    /**
     * Escape symbols for lexer.
     * @var array
     */

    private $__ESCAPE_SYMBOLS = ["\n", " ", "\t", "\e", "\f", "\v", "\r"];

    /**
     * Mandatory open tags matrix.
     * @var array
     */

    private $__MANDATORY_OPEN_ELEMENTS = [false, false, false, false];

    /**
     * Mandatory close tags matrix.
     * @var array
     */

    private $__MANDATORY_CLOSE_ELEMENTS = [false, false, false];

    /**
     * Href to internet page or file.
     * @var string
     */

    private $__HREF = "";

    /**
     * Standart headers for curl.
     * @var string
     */

    private $__HEADERS = [
        [CURLOPT_HEADER, 0],
        [CURLOPT_RETURNTRANSFER, 1],
        [CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13"],
        [CURLOPT_FOLLOWLOCATION, true],
        [CURLOPT_SSL_VERIFYHOST, false],
        [CURLOPT_SSL_VERIFYPEER, false],
        [CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]]
    ];

    /**
     * Browser instance.
     * @var \HeadlessChromium\Browser\ProcessAwareBrowser
     */
    private $__BROWSER;

    /**
     * Path to chromium-based browser executable.
     * @var string
     */
    private $__BROWSER_PATH = '';

    /**
     * Page class instance of browser.
     * @var
     */
    private $__BROWSER_PAGE;

    /**
     * @var bool
     */
    private $__USE_BROWSER = false;

    /**
     * Function that sets href to file or page for parser.
     * @param string $href
     */

    public function setHref(string $href)
    {
        $this->__HREF = $href;
    }

    /**
     * Function that sets headers for curl.
     * @param string $headers
     */

    public function setHeaders(string $headers)
    {
        $this->__HEADERS = $headers;
    }

    /**
     * Function that enables comments in tree.
     */

    public function enableComments()
    {
        $this->__ENABLE_COMMENTS = true;
    }

    /**
     * Function that enables <style> and <script> tags in tree.
     */

    public function enableStylesAndScripts()
    {
        $this->__ENABLE_STYLES_SCRIPTS = true;
    }

    /**
     * Function that enables <style>, <script> tags and comments in tree.
     */

    public function returnAll()
    {
        $this->__ENABLE_COMMENTS = true;
        $this->__ENABLE_STYLES_SCRIPTS = true;
    }

    /**
     * Magic function.
     * @return string $this->__SOURCE_TEXT Source text of file or page.
     */

    function __toString(): string
    {
        return $this->__SOURCE_TEXT;
    }

    /**
     * @param string $path
     */
    public function setBrowserPath(string $path)
    {
        $this->__BROWSER_PATH = $path;
    }

    /**
     * Setting up browser instance.
     * @param array $browserSettings
     * @param array $waitParams
     * @return bool
     */
    public function useBrowser(array $browserSettings, array $waitParams = [])
    {
        $browserFactory = new \HeadlessChromium\BrowserFactory($this->__BROWSER_PATH);
        $this->__BROWSER = $browserFactory->createBrowser($browserSettings);
        $this->__USE_BROWSER = true;
        $this->__BROWSER_WAIT_PARAMS = $waitParams;

        return true;
    }

    /**
     * @return \HeadlessChromium\Browser\ProcessAwareBrowser
     */
    public function getBrowser(): \HeadlessChromium\Browser\ProcessAwareBrowser
    {
        return $this->__BROWSER;
    }

    /**
     * Gets data from file and starts parsing.
     * @return array $this->__DOM Tree of current state
     */

    public function dom(): array
    {
        if (!$this->__DOM) {
            if (!$this->__SOURCE_TEXT) {
                if ($this->__USE_BROWSER === true) {
                    $this->__BROWSER_PAGE = $this->__BROWSER->createPage();
                    if ($this->__BROSER_WAIT_PARAMS == []) {
                        $this->__BROWSER_PAGE->navigate($this->__HREF)->waitForNavigation();
                    } else $this->__BROWSER_PAGE->navigate($this->__HREF)->waitForNavigation($this->__BROWSER_WAIT_PARAMS[0], $this->__BROWSER_WAIT_PARAMS[1]);
                    $this->__SOURCE_TEXT = $this->__BROWSER_PAGE->getHtml();
                } else {
                    if (preg_match('/http(s)*:\/\//i', $this->__HREF)) {
                        $this->__SOURCE_TEXT = $this->query($this->__HREF, $this->__HEADERS);
                    } else {
                        $this->__SOURCE_TEXT = file_get_contents($this->__HREF);
                    }
                }
            }
            $this->__DOM = $this->node($this->__SOURCE_TEXT)[0];
        }
        return $this->__DOM;
    }

    /**
     * Skips all space symbols and return first non-space symbol position.
     * @param  int $position Position, where spaces is
     * @param  string $text Text to find
     * @return int First founded position, where char is not space
     */

    private function spaceSkip(int $position, string $text): int
    {
        $text_strlen = strlen($text);
        for ($i = $position + 1; $i < $text_strlen; $i++) {
            if ($text[$i] != ' ') return $i;
        }
        return 0;
    }

    /**
     * Checks, is current token is singleton token.
     * @param  string $tag
     * @return bool
     */

    private function isSingleton(string $tag): bool
    {
        $non_close_tags = ['area', 'base', 'basefont', 'bgsound', 'br', 'hr', 'col', 'command', 'embed', 'img', 'input', 'isindex', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr', '!DOCTYPE', 'use', 'path'];
        for ($i = 0; $i < count($non_close_tags); $i++) {
            if (strcasecmp($this->symbolsSkip($this->__ESCAPE_SYMBOLS, $tag), $non_close_tags[$i]) == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns text without symbols in $symb array.
     * @param  array $symb Array of symbols to escape
     * @param  string $text Text to refactoring
     * @return string Text without $symb symbols
     */

    private function symbolsSkip(array $symb, string $text): string
    {
        $result = '';
        $found = false;
        for ($i = 0; $i < strlen($text); $i++) {
            for ($j = 0; $j < count($symb); $j++) {
                if ($text[$i] == $symb[$j]) {
                    $found = true;
                }
            }
            if ($found == true) {
                $found = false;
            } else {
                $result .= $text[$i];
            }
        }
        return $result;
    }

    /**
     * Creates tree of tokens.
     * @param  array $stack Current list of tokens
     * @param  int $pointer For recursion. Sets where last recursion is stopped
     * @param  array $main_tag Parent tag of recursion
     * @param  array $open_tags Current list of open tags. If not all tags closed, close tags from this list
     */

    private function stackRecursion(array $stack, int $pointer, array $main_tag = [], array $open_tags = [])
    {
        $result = [];
        for ($i = $pointer; $i < count($stack); $i++) {
            $current_token = $stack[$i];
            if ((isset($current_token['is_singleton']) ? (!$current_token['is_singleton']) : true) && (isset($current_token['is_closing']) ? (!$current_token['is_closing']) : true)
                && ($current_token['tag'] != '__TEXT' && $current_token['tag'] != '__COMMENT')) {
                $point = $this->recursionTagTracker($open_tags, $current_token['tag']);
                if (!isset($open_tags[$point])) {
                    $open_tags[$point] = [];
                    $open_tags[$point]['tag'] = $current_token['tag'];
                    $open_tags[$point]['count'] = 1;
                } else {
                    $open_tags[$point]['count']++;
                }

                $depended_tokens = $this->stackRecursion($stack, $i + 1, $current_token, $open_tags);
                $open_tags = $depended_tokens[2];

                array_push($current_token, $depended_tokens[0]);
                array_push($result, $current_token);

                $i = $depended_tokens[1];
            }

            if ($current_token['tag'] == '__TEXT' || $current_token['tag'] == '__COMMENT') {
                $result[] = $current_token;
            }
            if (!empty($current_token['is_singleton'])) {
                array_push($result, $current_token);
            } else if (!empty($current_token['is_closing'])) {
                if (strcasecmp($main_tag['tag'], $current_token['tag']) != 0) {
                    $found = false;
                    for ($h = 0; $h < count($open_tags); $h++) {
                        if ($open_tags[$h]['count'] > 0 && strcasecmp($open_tags[$h]['tag'], $main_tag['tag']) == 0) {
                            for ($z = 0; $z < count($open_tags); $z++) {
                                if ($open_tags[$z]['count'] > 0 && strcasecmp($open_tags[$z]['tag'], $current_token['tag']) == 0) {
                                    $found = $h;
                                }
                            }
                        }
                    }
                    if ($found) {
                        $open_tags[$found]['count']--;
                        $splice_tag = ['tag' => $open_tags[$found]['tag'], 'is_closing' => true];
                        array_splice($result, $i, 0, [$splice_tag]);
                        return [$result, $i - 1, $open_tags];
                    } else {
                        continue;
                    }
                }
                array_push($result, $current_token);
                $point = $this->recursionTagTracker($open_tags, $current_token['tag']);
                $open_tags[$point]['count']--;
                return [$result, $i, $open_tags];
            }

        }
        return [$result, $i, $open_tags];
    }

    /**
     * Skips comments and returns data, which comment contains.
     * @param  string $html Source html to work
     * @param  integer $position Position, where last recursion is stopped
     * @return array
     */

    private function escapeComments(string $html, $position = 0): array
    {
        $result = "";
        $open_tag_position = 0;
        $html_strlen = strlen($html);
        for ($i = $position; $i < $html_strlen; $i++) {
            if ($html[$i] == "<" && $html[$i + 1] == "!" && $html[$i + 2] == "-" && $html[$i + 3] == "-") {
                $open_tag_position = $i + 3;
                $result .= $html[$i];
            } else if ($html[$i] == ">" && $html[$i - 1] == "-" && $html[$i - 2] == "-" && $open_tag_position < $i - 2) {
                $result .= $html[$i];
                return [$i + 1, $result];
            } else {
                $result .= $html[$i];
            }
        }
    }

    /**
     * Deprecated, but works.
     * @param  boolean $dom
     * @param  integer $level
     * @return string
     */

    public function safeHTML($dom = false, int $level = 0): string
    {
        $result = '';
        if (!$this->__DOM) $this->__DOM = $this->dom($this->__SOURCE_TEXT);
        if ($dom === false) {
            $dom = $this->__DOM;
        }
        for ($i = 0; $i < count($dom); $i++) {
            if ($dom[$i]['tag'] == '__COMMENT' || $dom[$i]['tag'] == '__TEXT') {
                $result .= str_repeat("\t", $level) . $dom[$i][0] . "\n";
                continue;
            }
            $properties = '';
            $ficators = '';
            for ($j = 0, $keys = array_keys($dom[$i]); $j < count($keys); $j++) {
                if ($keys[$j] === 'class' || $keys[$j] === 'id') {
                    for ($u = 0; $u < count($dom[$i][$keys[$j]]); $u++) {
                        if ($u == count($dom[$i][$keys[$j]]) - 1) {
                            $ficators .= $dom[$i][$keys[$j]][$u];
                            continue;
                        }
                        $ficators .= $dom[$i][$keys[$j]][$u] . ', ';
                    }
                    $properties .= " " . $keys[$j] . '=\'' . $ficators . '\' ';
                }

                if ($keys[$j] !== 0 && $keys[$j] !== 'pointer' && $keys[$j] !== 'is_singleton' && $keys[$j] !== 'is_closing' && $keys[$j] !== 'tag' && $keys[$j] !== 'class' && $keys[$j] !== 'id') {
                    if ($dom[$i][$keys[$j]] === true) {
                        $properties .= " $keys[$j]";
                        continue;
                    }
                    $founded = false;
                    for ($z = 0; $z < strlen($dom[$i][$keys[$j]]); $z++) {
                        if ($dom[$i][$keys[$j]][$z] == '"') {
                            $properties .= " $keys[$j]='" . $dom[$i][$keys[$j]] . "'";
                            $founded = true;
                            break;
                        }
                        if ($dom[$i][$keys[$j]][$z] == "'") {
                            $properties .= " $keys[$j]=\"" . $dom[$i][$keys[$j]] . "\"";
                            $founded = true;
                            break;
                        }
                    }
                    if ($founded == false) {
                        $properties .= " $keys[$j]='" . $dom[$i][$keys[$j]] . "'";
                    }
                }
            }
            if ($dom[$i][0]) {
                $result .= str_repeat("\t", $level) . "<" . $dom[$i]['tag'] . "$properties>\n";
                $result .= $this->safeHTML($dom[$i][0], $level + 1);
            } else {
                if ($dom[$i]['is_singleton']) {
                    $result .= str_repeat("\t", $level) . "<" . $dom[$i]['tag'] . "$properties>\n";
                } else {
                    $result .= str_repeat("\t", $level ? $level - 1 : $level) . "</" . $dom[$i]['tag'] . ">\n";
                }
            }
        }
        return $result;
    }

    /**
     * Adds tag to stack, founded from source text.
     * @param  string $html Source text to work
     * @param  integer $f_pointer Pointer, where need to start work
     * @return array              Return array with tag information
     */

    public function readTag(string $html, int $f_pointer = 0): array
    {
        $result = ['is_closing' => false, 'is_singleton' => false];

        $html_strlen = strlen($html);
        $tag = '';
        $attribute = '';
        $value = '';
        $state = 'tag';
        $bracket = 0;
        $bracket_count = 0;
        $closed_tag = false;
        $without_parth = false;

        for ($i = $f_pointer; $i < $html_strlen; $i++, $result['pointer'] = $i) {
            if ($closed_tag) break;
            if ($i == $html_strlen - 1 && $html[$i] != '>') throw new \Exception('Cannot find \'>\' symbol');
            switch ($html[$i]) {
                case ' ':
                    if ($state == 'tag' && $tag != false) {
                        $result['tag'] = $tag;
                        $state = 'attribute';
                    } else if ($state == 'attribute_value') {
                        if ($without_parth) {
                            $result[$attribute] = $value;
                            $value = false;
                            $attribute = false;
                            $state = 'attribute';
                        } else {
                            $value .= $html[$i];
                        }
                    } else if ($state == 'attribute' && $attribute != false) {
                        $symbol = $html[$this->spaceSkip($i, $html)];
                        if ($symbol == '=') {
                            break;
                        } else {
                            $result[$attribute] = true;
                            $state = 'attribute';
                            $attribute = false;
                            $value = false;
                            $bracket = 0;
                            $bracket_count = 0;
                        }
                        $i = $this->spaceSkip($i, $html) - 1;
                    }
                    break;
                case '>':
                    switch ($state) {
                        case 'tag':
                            if ($tag != false) $result['tag'] = $tag;
                            $closed_tag = true;
                            break;
                        case 'attribute_value_starting':
                            return ['error_code' => 2, 'Unknown attribute value in ' . $i . ' symbol', 'pointer' => $i];
                            break;
                        case 'attribute_value':
                            if ($without_parth) {
                                $result[$attribute] = $value;
                                $value = false;
                                $attribute = false;
                                $closed_tag = true;
                            } else {
                                $value .= $html[$i];
                            }
                            break;
                        case 'attribute':
                            if ($attribute != false) {
                                $result[$attribute] = true;
                            }
                            $closed_tag = true;
                            break;
                    }
                    if (($state == 'tag' || $state == 'attribute') && $this->isSingleton($tag)) {
                        $result['is_singleton'] = true;
                    }
                    break;
                case '\'':
                    if ($state == 'attribute_value_starting' && $attribute && $bracket_count == 0) {
                        $state = 'attribute_value';
                        $bracket = 2;
                        $bracket_count++;
                    } else if ($bracket_count == 1 && $bracket == 2) {
                        $result[$attribute] = $value;
                        $state = 'attribute';
                        $attribute = false;
                        $value = false;
                        $bracket = 0;
                        $bracket_count = 0;
                    } else {
                        $value .= $html[$i];
                    }
                    break;
                case '"':
                    if ($state == 'attribute_value_starting' && $attribute && $bracket_count == 0) {
                        $state = 'attribute_value';
                        $bracket = 1;
                        $bracket_count++;
                    } else if ($bracket_count == 1 && $bracket == 1) {
                        $result[$attribute] = $value;
                        $state = 'attribute';
                        $attribute = false;
                        $value = false;
                        $bracket = 0;
                        $bracket_count = 0;
                    } else {
                        $value .= $html[$i];
                    }
                    break;
                case '=':
                    if ($state == 'attribute' && $attribute) {
                        $state = 'attribute_value_starting';
                    } else if ($state == 'attribute_value_starting') {
                        return ['error_code' => 3, 'Html syntax error ', 'pointer' => $i . ($i - 1) . ($i - 2) . ($i - 3) . ($i - 4) . ($i - 5)];
                    } else if ($state == 'attribute_value') {
                        $value .= $html[$i];
                    }
                    break;
                case '/':
                    if ($state == 'tag') {
                        $result['is_closing'] = true;
                    } else if ($state == 'attribute_value') {
                        $value .= $html[$i];
                    } else if ($html[$this->spaceSkip($i, $html)] == '>') {
                        $result['is_closing'] = true;
                        $i = $this->spaceSkip($i, $html) - 1;
                    }
                    break;
                case '<':
                    if ($state == 'attribute_value') {
                        $value .= $html[$i];
                    } else if ($state == 'attribute') {
                        return ['error_code' => 1, 'Html syntax error ' . $i . ' ' . $html[$i] . $html[$i + 1] . $html[$i + 2] . $html[$i + 3], 'pointer' => $i];
                    }
                    break;
                default:
                    switch ($state) {
                        case 'attribute':
                            $attribute .= $html[$i];
                            break;
                        case 'attribute_value':
                            $value .= $html[$i];
                            break;
                        case 'tag':
                            $tag .= $html[$i];
                            break;
                        case 'attribute_value_starting':
                            $without_parth = true;
                            $state = 'attribute_value';
                            $value .= $html[$i];
                            break;
                    }
                    break;
            }
        }
        if (isset($result['id'])) $result['id'] = explode(' ', $result['id']);
        if (isset($result['class'])) $result['class'] = explode(' ', $result['class']);
        return $result;
    }

    /**
     * Creates stack.
     * @param  string $html Source text.
     * @return array Stack
     */

    private function node(string $html): array
    {
        $lenght = strlen($html);

        $stack = [];
        $text = '';
        $ignore_html = false;
        $s_quotes = 0;

        for ($i = 0; $i < $lenght; $i++) {
            if ($html[$i] == '<' && $this->symbolsSkip(["<", "\n", " ", "\t", "\e", "\f", "\v", "\r"], $html[$i + 1])) {
                if ($this->symbolsSkip(["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "/", "!"], $html[$i + 1])) {
                    $text .= $html[$i];
                    $text .= $html[$i + 1];
                    $i++;
                    continue;
                }
                if ($html[$i + 1] == "!" && $html[$i + 2] == "-" && $html[$i + 3] == "-") {
                    $comment = $this->escapeComments($html, $i);
                    if ($this->__ENABLE_COMMENTS == true) {
                        $stack[] = ['tag' => '__COMMENT', htmlspecialchars($comment[1])];
                    }
                    $i = $comment[0] - 1;
                    if (!isset($comment[0])) {
                        die(print_r($comment));
                    }
                    continue;
                }
                $temporary_token = $this->readTag($html, $i);
                if ($ignore_html && $s_quotes == 1) {
                    $text .= "<";
                }
                if (isset($temporary_token['error_code']) && $temporary_token['error_code'] && $ignore_html && $s_quotes != 1) {
                    $text = '';
                    $i = $temporary_token['pointer'] - 1;
                    continue;
                } else
                    if (isset($temporary_token['error_code']) && $temporary_token['error_code'] && !$ignore_html) {
                        return $this->stackRecursion($stack, 0);
                    }
                if (($temporary_token['tag'] == 'script' || $temporary_token['tag'] == 'style') && $temporary_token['is_closing'] == 0 && $s_quotes != 1) {
                    $text = '';
                    $i = $temporary_token['pointer'] - 1;
                    $ignore_html = true;
                } else if (($temporary_token['tag'] == 'script' || $temporary_token['tag'] == 'style') && $temporary_token['is_closing'] == 1 && $s_quotes != 1) {
                    $ignore_html = false;
                }
                if ($ignore_html == false) {
                    if ($temporary_token['is_singleton']) {
                        if (trim($text, "\n\t\e\f\v\r ")) {
                            $stack[] = ['tag' => '__TEXT', trim($text, "\n\t\e\f\v\r")];
                            $text = '';
                        } else if (trim($text, "\n\t\e\f\v\r ") === '0') {
                            $stack[] = ['tag' => '__TEXT', trim($text, "\n\t\e\f\v\r ")];
                            $text = '';
                        } else {
                            $text = '';
                        }
                        $stack[] = $temporary_token;
                    } else {
                        if (($temporary_token['tag'] != 'script') && ($temporary_token['tag'] != 'style')) {
                            if (trim($text, "\n\t\e\f\v\r ")) {
                                $stack[] = ['tag' => '__TEXT', trim($text, "\n\t\e\f\v\r ")];
                                $text = '';
                            } else if (trim($text, "\n\t\e\f\v\r ") === '0') {
                                $stack[] = ['tag' => '__TEXT', trim($text, "\n\t\e\f\v\r ")];
                                $text = '';
                            } else {
                                $text = '';
                            }
                            $stack[] = $temporary_token;
                        } else {
                            $text = '';
                        }
                    }
                    $this->omittedMandatoryTagChecker($temporary_token);
                    $i = $temporary_token['pointer'] - 1;
                }
            } else {
                $text .= $html[$i];
            }
        }
        $stack = $this->omittedMandatoryTags($stack);
        $stack = $this->omittedCloseTags($stack);
        return $this->stackRecursion($stack, 0);
    }

    /**
     * @return string
     */
    public function read(string $fileName = ''): string
    {
        if ($fileName == '') return $this->__SOURCE_TEXT;
        $read = fopen($fileName, 'w+');
        fwrite($read, $this->__SOURCE_TEXT);
        fclose($read);
        return true;
    }

    /**
     * Checks for optional tags.
     * @param $temporary_token
     */
    private function omittedMandatoryTagChecker($temporary_token)
    {
        $mandatory_tags = ['html', 'head', 'body', '!doctype'];
        for ($j = 0; $j < 4; $j++) {
            if (strcasecmp($mandatory_tags[$j], $temporary_token['tag']) == 0) {
                if ($temporary_token['is_closing']) {
                    $this->__MANDATORY_CLOSE_ELEMENTS[$j] = true;
                } else {
                    $this->__MANDATORY_OPEN_ELEMENTS[$j] = true;
                }
            }
        }
    }

    /**
     * Adds optional tags to stack.
     * @param $stack
     * @return array
     */
    private function omittedMandatoryTags($stack) : array
    {
        if ($this->__MANDATORY_OPEN_ELEMENTS[3] == false) {
            array_splice($stack, 0, 0, [['tag' => '!DOCTYPE', 'html' => true, 'is_singleton' => true]]);
        }
        if ($this->__MANDATORY_OPEN_ELEMENTS[0] == false) {
            $pointer = 0;
            for ($i = 1; $i <= count($stack); $i++) {
                if ($stack[$i]['tag'] != "__COMMENT" && strcasecmp($stack[$i]['tag'], '!DOCTYPE') != 0) {
                    $pointer = $i;
                    break;
                }
            }
            array_splice($stack, $pointer, 0, [['tag' => 'html', 'is_singleton' => false, 'is_closing' => false]]);
        }
        if ($this->__MANDATORY_CLOSE_ELEMENTS[0] == false) {
            array_splice($stack, count($stack), 0, [['tag' => 'html', 'is_closing' => true]]);
        }
        if ($this->__MANDATORY_OPEN_ELEMENTS[1] == false) {
            for ($i = 0; $i < count($stack); $i++) {
                if (strcasecmp($stack[$i]['tag'], 'html') == 0 && $stack[$i]['is_closing'] == true) {
                    array_splice($stack, $i, 0, [['tag' => 'head', 'is_singleton' => false, 'is_closing' => false]]);
                    break;
                }
                if (strcasecmp($stack[$i]['tag'], '!DOCTYPE') == 0 || strcasecmp($stack[$i]['tag'], 'html') == 0 || $stack[$i]['tag'] == '__COMMENT') continue;
                if ($stack[$i]['tag'] != '__TEXT' && $stack[$i]['tag'] != '__COMMENT') {
                    array_splice($stack, $i, 0, [['tag' => 'head', 'is_singleton' => false, 'is_closing' => false]]);
                    break;
                }
            }
        }
        if ($this->__MANDATORY_CLOSE_ELEMENTS[1] == false) {
            //$first_time = false; FIXME
            for ($i = 0; $i < count($stack); $i++) {
                if (strcasecmp($stack[$i]['tag'], '!DOCTYPE') == 0 || strcasecmp($stack[$i]['tag'], 'html') == 0 || strcasecmp($stack[$i]['tag'], 'head') == 0 || $stack[$i]['tag'] == '__COMMENT') continue;
                if ($stack[$i]['tag'] != 'link' && $stack[$i]['tag'] != 'meta' && $stack[$i]['tag'] != 'title') {
                    array_splice($stack, $i, 0, [['tag' => 'head', 'is_singleton' => false, 'is_closing' => true]]);
                    break;
                }
            }
        }
        if ($this->__MANDATORY_OPEN_ELEMENTS[2] == false) {
            for ($i = 0; $i < count($stack); $i++) {
                if (strcasecmp($stack[$i]['tag'], 'head') == 0 && $stack[$i]['is_closing'] == true) {
                    array_splice($stack, $i + 1, 0, [['tag' => 'body', 'is_singleton' => false, 'is_closing' => false]]);
                    break;
                }
            }
        }
        if ($this->__MANDATORY_CLOSE_ELEMENTS[2] == false) {
            array_splice($stack, count($stack) - 1, 0, [['tag' => 'body', 'is_closing' => true]]);
        }
        for ($i = 0; $i < count($stack); $i++) {
            if ($stack[$i]['tag'] == 'table') {
                $colgroup = false;
                $tbody = false;
                $thead = false;
                for ($j = $i + 1; $j < count($stack); $j++) {
                    if (strcasecmp($stack[$j]['tag'], 'col') == 0 && !$colgroup) {
                        if ($tbody || $thead) {
                            if ($thead) {
                                array_splice($stack, $j, 0, [['tag' => 'thead', 'is_closing' => true]]);
                                array_splice($stack, $j + 1, 0, [['tag' => 'colgroup', 'is_closing' => false]]);
                                $thead = false;
                            } else if ($tbody) {
                                array_splice($stack, $j, 0, [['tag' => 'tbody', 'is_closing' => true]]);
                                array_splice($stack, $j + 1, 0, [['tag' => 'colgroup', 'is_closing' => false]]);
                                $tbody = false;
                            }
                        } else {
                            array_splice($stack, $j, 0, [['tag' => 'colgroup', 'is_closing' => false]]);
                        }
                        $colgroup = true;
                    }
                    if ((strcasecmp($stack[$j]['tag'], 'col') != 0 && strcasecmp($stack[$j]['tag'], 'colgroup') != 0) && $colgroup == true) {
                        $colgroup = false;
                    }
                    if (strcasecmp($stack[$j]['tag'], 'colgroup') == 0 && $stack[$j]['is_closing'] == false) {
                        $colgroup = true;
                    } else if (strcasecmp($stack[$j]['tag'], 'colgroup') == 0 && $stack[$j]['is_closing'] == true) {
                        $colgroup = false;
                    }
                    if (strcasecmp($stack[$j]['tag'], 'table') == 0 && $stack[$j]['is_closing']) {
                        $i = $j;
                        break;
                    }
                    if (strcasecmp($stack[$j]['tag'], 'thead') == 0 && !$stack[$j]['is_closing']) {
                        $thead = true;
                        $colgroup = false;
                    }
                    if (strcasecmp($stack[$j]['tag'], 'tbody') == 0 && !$stack[$j]['is_closing']) {
                        $tbody = true;
                        $colgroup = false;
                    }
                    if (strcasecmp($stack[$j]['tag'], 'tbody') == 0 && $stack[$j]['is_closing']) {
                        $tbody = false;
                        $colgroup = false;
                    }
                    if (strcasecmp($stack[$j]['tag'], 'tr') == 0 && !$tbody && !$thead) {
                        array_splice($stack, $j, 0, [['tag' => 'tbody', 'is_closing' => false]]);
                        $tbody = true;
                        $colgroup = false;
                        $i = $j;
                        continue;
                    }
                }
            }
        }
        return $stack;
    }

    /**
     * Adds optional close tags to stack.
     * @param array $stack
     * @param int $pointer Where need to start work
     * @param bool $has_caused Is method instance has caused by another instance
     * @return array
     */
    private function omittedCloseTags(array $stack, int $pointer = 0, bool $has_caused = false) : array
    {
        $omitted_tags_list = [
            ['li', ['li'], ['ul', 'ol']],
            ['dt', ['dd', 'dt'], ['dl']],
            ['dd', ['dt', 'dd'], ['dl']],
            ['rt', ['rt', 'rp'], ['rtc']],
            ['optgroup', ['optgroup']],
            ['option', ['option', 'optgroup']],
            ['tr', ['tr']],
            ['td', ['td', 'th']],
            ['th', ['th', 'td']],
            ['thead', ['thead', 'tbody', 'tfoot']],
            ['tbody', ['tbody', 'tfoot']],
            ['tfoot', ['tfoot', 'table']],
            ['a', ['a']]
        ];
        for ($i = $pointer; $i < count($stack); $i++) {
            if (strcasecmp($stack[$i]['tag'], 'caption') == 0 || strcasecmp($stack[$i]['tag'], 'colgroup') == 0) {
                $tag = $stack[$i]['tag'];
                for ($j = $i + 1; $j < count($stack); $j++) {
                    if ($stack[$j]['tag'] == '__TEXT' || $stack[$j]['tag'] == '__COMMENT') continue;
                    if (strcasecmp($stack[$j]['tag'], $tag) == 0 && $stack[$j]['is_closing']) continue;
                    if (strcasecmp($tag, 'colgroup') == 0 && strcasecmp($stack[$j]['tag'], 'col') == 0) continue;
                    if (strcasecmp($stack[$j]['tag'], $tag) != 0) {
                        array_splice($stack, $j, 0, [['tag' => $tag, 'is_closing' => 1]]);
                        $i = $j;
                        break;
                    }
                    if (strcasecmp($stack[$j]['tag'], $tag) == 0 && !$stack[$j]['is_closing']) {
                        array_splice($stack, $j, 0, [['tag' => $tag, 'is_closing' => 1]]);
                        $i = $j;
                        break;
                    }
                }
            }
            if ($has_caused) {
                for ($x = 0; $x < count($omitted_tags_list); $x++) {
                    for ($c = 0; $c < count($omitted_tags_list[$x][2]); $c++) {
                        if ($stack[$i]['tag'] == $omitted_tags_list[$x][2][$c]) {
                            $first_time = false;
                            $tag = '';
                            for ($j = $i + 1; $j < count($stack); $j++) {
                                for ($z = 0; $z < count($omitted_tags_list[$x][1]); $z++) {
                                    if (strcasecmp($stack[$j]['tag'], $omitted_tags_list[$x][1][$z]) == 0 && !$stack[$j]['is_closing'] && $first_time) {
                                        array_splice($stack, $j, 0, [['tag' => $tag, 'is_closing' => 1]]);
                                    } else if ($first_time == false && strcasecmp($stack[$j]['tag'], $omitted_tags_list[$x][1][$z]) == 0 && !$stack[$j]['is_closing']) {
                                        $first_time = true;
                                        $tag = $omitted_tags_list[$x][1][$z];
                                    }
                                    if (strcasecmp($stack[$j]['tag'], $omitted_tags_list[$x][1][$z]) == 0 && $stack[$j]['is_closing'] && $first_time) {
                                        $first_time = false;
                                    } else if (strcasecmp($stack[$j]['tag'], $omitted_tags_list[$x][1][$z]) == 0 && $stack[$j]['is_closing'] && !$first_time) {
                                        array_splice($stack, $j, 1);
                                    }
                                    if (isset($stack[$j]['is_closing']) && !$stack[$j]['is_closing'] && strcasecmp($stack[$j]['tag'], $omitted_tags_list[$x][2][$c]) == 0) {

                                        $ul = $this->omittedCloseTags($stack, $j, true);
                                        if (!isset($ul[1])) return $stack;
                                        $stack = $ul[0];
                                        $j = $ul[1];
                                    }
                                    if (isset($stack[$j]['is_closing']) && $stack[$j]['is_closing'] && strcasecmp($stack[$j]['tag'], $omitted_tags_list[$x][2][$c]) == 0) {
                                        return [$stack, $j];
                                    }

                                }
                            }
                        }
                    }
                }
            } else {
                for ($k = 0; $k < count($omitted_tags_list); $k++) {
                    if (strcasecmp($stack[$i]['tag'], $omitted_tags_list[$k][0]) == 0) {
                        $first_time = false;
                        $tag = '';
                        for ($j = $i; $j < $i + 100; $j++) {
                            for ($h = 0; $h < @count($omitted_tags_list[$k][2]); $h++) {
                                if (isset($stack[$j]['tag']) && strcasecmp($stack[$j]['tag'], $omitted_tags_list[$k][2][$h]) == 0 && !$stack[$j]['is_closing']) {
                                    $ul = $this->omittedCloseTags($stack, $j, true);
                                    if (!isset($ul[1])) return $stack;
                                    $stack = $ul[0];
                                    $j = $ul[1];
                                    break;
                                }
                            }
                            for ($z = 0; $z < count($omitted_tags_list[$k][1]); $z++) {
                                if (isset($stack[$j]['tag']) && strcasecmp($stack[$j]['tag'], $omitted_tags_list[$k][1][$z]) == 0 && isset($stack[$j]['is_closing']) ? $stack[$j]['is_closing'] : false) {
                                    $first_time = false;
                                }
                                if (isset($stack[$j]['tag']) && strcasecmp($stack[$j]['tag'], $omitted_tags_list[$k][1][$z]) == 0 && (isset($stack[$j]['is_closing']) ? !$stack[$j]['is_closing'] : false) && $first_time) {
                                    array_splice($stack, $j, 0, [['tag' => $tag, 'is_closing' => 1]]);
                                    $first_time = false;
                                } else if ($first_time == false && isset($stack[$j]['tag']) && strcasecmp($stack[$j]['tag'], $omitted_tags_list[$k][1][$z]) == 0 && !$stack[$j]['is_closing']) {
                                    $first_time = true;
                                    $tag = $omitted_tags_list[$k][1][$z];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $stack;
    }

    /**
     * @param string $filename
     * @return bool|string
     */
    public function dump(string $fileName = '')
    {
        if ($fileName == '') return htmlspecialchars($this->__SOURCE_TEXT);
        $dump = fopen($fileName, 'w+');
        fwrite($dump, $this->__SOURCE_TEXT);
        fclose($dump);
        return true;
    }

    /**
     * @param $table Table of open tags in recursion
     * @param string $tag Tag name
     * @return int Count of open tags with name $tag
     */
    private function recursionTagTracker(array $table, string $tag)
    {
        for ($i = 0; $i < count($table); $i++) {
            if (strcasecmp($table[$i]['tag'], $tag) == 0) {
                return $i;
            }
        }
        return count($table);
    }

    /**
     * Creates new Element instance with id search params.
     * @param string $id
     * @return Element
     */
    public function id(string $id): Element
    {
        return new Element($this->dom(), ['id', $id]);
    }

    /**
     * Creates new Element instance with tag search params.
     * @param string $tag
     * @return Element
     */
    public function tag(string $tag): Element
    {
        return new Element($this->dom(), ['tag', $tag]);
    }

    /**
     * Creates new Element instance with class search params.
     * @param string $class
     * @return Element
     */
    public function class(string $class): Element
    {
        return new Element($this->dom(), ['class', $class]);
    }

    /**
     * Creates new Element instance with custom search params.
     * @param array $element Array with search params
     * @return Element
     */
    public function custom(array $element): Element
    {
        return new Element($this->dom(), $element);
    }

    /**
     * @param int $number Number of element in tree.
     * @return Element
     */
    public function select(int $number): Element
    {
        return new Element($this->dom()[$number]);
    }

    /**
     * @param string $url
     * @param int $headers
     * @return string
     */
    private function query(string $url, array $headers): string
    {
        $curlSession = curl_init();

        curl_setopt($curlSession, CURLOPT_URL, $url);
        for ($i = 0; $i < count($headers); $i++) {
            curl_setopt($curlSession, $headers[$i][0], $headers[$i][1]);
        }
        $returningData = curl_exec($curlSession);
        curl_close($curlSession);

        return $returningData;
    }

}

?>
