<?php

/*
	Copyright© Arseniy Romanovskiy aka ShamanHead
	This file is part of Phporser package
	Created by ShamanHead
	Mail: arsenii.romanovskii85@gmail.com
*/

namespace ShamanHead\PhpPorser\App;

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

  private $__ESCAPE_SYMBOLS = ["\n"," ", "\t", "\e", "\f", "\v", "\r"];

	/**
	 * Mandatory open tags matrix.
	 * @var array
	 */

  private $__MANDATORY_OPEN_ELEMENTS= [false, false, false, false];

	/**
	 * Mandatory close tags matrix.
	 * @var array
	 */

  private $__MANDATORY_CLOSE_ELEMENTS= [false, false, false];

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
		[CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data")]
	];

	/**
	 * Function that sets href to file or page for parser.
	 * @param string $href
	 */

	public function setHref(string $href){
		$this->__HREF = $href;
	}

	/**
	 * Function that sets headers for curl.
	 * @param string $headers
	 */

	public function setHeaders(string $headers){
		$this->__HEADERS = $headers;
	}

	/**
	 * Function that enables comments in tree.
	 */

	public function enableComments(){
			$this->__ENABLE_COMMENTS = true;
	}

	/**
	 * Function that enables <style> and <script> tags in tree.
	 */

	public function enableStylesAndScripts(){
			$this->__ENABLE_STYLES_SCRIPTS = true;
	}

	/**
	 * Function that enables <style>, <script> tags and comments in tree.
	 */

	public function returnAll(){
			$this->__ENABLE_COMMENTS = true;
			$this->__ENABLE_STYLES_SCRIPTS = true;
	}

	/**
	 * Magic function.
	 * @return string $this->__SOURCE_TEXT Source text of file or page.
	 */

	function __toString() : string{
		return $this->__SOURCE_TEXT;
	}

	/**
	 * Main function in flow. Gets data from file and starts parsing
	 * @return array $this->__DOM Tree of current state.
	 */

	public function dom() : array{
		if(!$this->__DOM){
				if(!$this->__SOURCE_TEXT){
					if(preg_match('/http(s)*:\/\//i', $this->__HREF)){
						$this->__SOURCE_TEXT = $this->query($this->__HREF, $this->__HEADERS);
					}else{
						$this->__SOURCE_TEXT = file_get_contents($this->__HREF);
					}
				}
				$this->__DOM = $this->node($this->__SOURCE_TEXT)[0];
			}
		return $this->__DOM;
	}

	/**
	 * Service function that finds first position of char, which is not space.
	 * @param  int $position Position, where spaces is.
	 * @param  string $text  Text to find
	 * @return int First founded position, where char is not space.
	 */

	private function spaceJitter(int $position, string $text) : int{
			$text_strlen = strlen($text);
			for($i = $position+1;$i < $text_strlen;$i++){
				if($text[$i] != ' ') return $i;
			}
		}

	/**
	 * Service function. Checks, is current token is singleton token.
	 * @param  string $tag
	 * @return bool
	 */

	private function isSingleton(string $tag) : bool{
			$non_close_tags = ['area','base','basefont','bgsound','br','hr','col','command','embed','img','input','isindex','keygen','link','meta','param','source','track','wbr','!DOCTYPE', 'use', 'path'];
			for($i = 0;$i < count($non_close_tags);$i++){
				if(strcasecmp($this->escapeSymbols($this->__ESCAPE_SYMBOLS,$tag),$non_close_tags[$i]) == 0){
					return true;
				}
			}
			return false;
		}

	/**
	 * Just Another Bicycle.
	 * @param  array  $symb Array of symbols to escape.
	 * @param  string $text Text to refactoring.
	 * @return string Text without $symb symbols.
	 */

	private function escapeSymbols(array $symb, string $text) : string{
			$result = '';
			$finded = false;
			for($i = 0;$i < strlen($text);$i++){
				for($j = 0;$j < count($symb);$j++){
					if($text[$i] == $symb[$j]){
						$finded = true;
					}
				}
				if($finded == true){
					$finded = false;
				}else{
					$result .= $text[$i];
				}
			}
			return $result;
	}

	/**
	 * One of main functions. Creates tree of tokens.
	 * @param  array  $stack     Current list of tokens.
	 * @param  int    $pointer   For recurtion. Sets where last recurtion is stopped.
	 * @param  array  $main_tag  Parent tag of recurtion.
	 * @param  array  $open_tags Current list of open tags. If not all tags closed, close tags from this list.
	 */

	private function stackRecurtion(array $stack, int $pointer, array $main_tag = [], array $open_tags = []){
		$result = [];
		for($i = $pointer;$i < count($stack);$i++){
			$current_token = $stack[$i];
			if((isset($current_token['is_singleton']) ? (!$current_token['is_singleton']) : true) && (isset($current_token['is_closing']) ? (!$current_token['is_closing']) : true) && ($current_token['tag'] != '__TEXT' && $current_token['tag'] != '__COMMENT')){
				$point = $this->recurtionTagTracker($open_tags, $current_token['tag']);
				if(!isset($open_tags[$point])){
					$open_tags[$point] = [];
					$open_tags[$point]['tag'] = $current_token['tag'];
					$open_tags[$point]['count'] = 1;
				}else{
					$open_tags[$point]['count']++;
				}

				$depended_tokens = $this->stackRecurtion($stack, $i+1, $current_token, $open_tags);
				$open_tags = $depended_tokens[2];

				array_push($current_token, $depended_tokens[0]);
				array_push($result,$current_token);

				$i = $depended_tokens[1];
      }

			if($current_token['tag'] == '__TEXT' || $current_token['tag'] == '__COMMENT'){
				$result[] = $current_token;
			}
			if(!empty($current_token['is_singleton'])){
				array_push($result, $current_token);
			}else if(!empty($current_token['is_closing'])){
			  if(strcasecmp($main_tag['tag'], $current_token['tag']) != 0){
			  	$finded = false;
				  	for($h = 0;$h < count($open_tags);$h++){
				  		if($open_tags[$h]['count'] > 0 && strcasecmp($open_tags[$h]['tag'], $main_tag['tag']) == 0){
				  			for($z = 0;$z < count($open_tags);$z++){
				  				if($open_tags[$z]['count'] > 0 && strcasecmp($open_tags[$z]['tag'], $current_token['tag']) == 0){
				  					$finded = $h;
				  				}
				  			}
				  		}
				  	}
			  		if($finded){
			  			$open_tags[$finded]['count']--;
			  			$splice_tag = ['tag' => $open_tags[$finded]['tag'], 'is_closing' => true];
						array_splice($result, $i, 0, [$splice_tag]);
						return [$result, $i-1, $open_tags];
			  		}else{
			  			continue;
			  		}
			  }
        array_push($result, $current_token);
        $point = $this->recurtionTagTracker($open_tags, $current_token['tag']);
        $open_tags[$point]['count']--;
				return [$result, $i, $open_tags];
			}

		}
		return [$result, $i, $open_tags];
	}

	/**
	 * Service function. Escapes comments and returns data, which comment contains.
	 * @param  string  $html     Source html to work.
	 * @param  integer $position Position, where last recurtion is stopped.
	 * @return array
	 */

	private function escapeComments(string $html, $position = 0) : array{
				$result = "";
				$open_tag_position = 0;
      			$html_strlen = strlen($html);
				for($i = $position;$i < $html_strlen;$i++){
					if($html[$i] == "<" && $html[$i + 1] == "!" && $html[$i + 2] == "-" && $html[$i + 3] == "-"){
						$open_tag_position = $i+3;
						$result .= $html[$i];
					}
					else if($html[$i] == ">" && $html[$i - 1] == "-" && $html[$i - 2] == "-" && $open_tag_position < $i-2){
						$result .= $html[$i];
						return [$i+1, $result];
					}else{
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

	public function safeHTML($dom = false, int $level = 0) : string{
		$result = '';
		if(!$this->__DOM) $this->__DOM = $this->dom($this->__SOURCE_TEXT);
		if($dom === false){
			$dom = $this->__DOM;
		}
		for($i = 0;$i < count($dom);$i++){
			if($dom[$i]['tag'] == '__COMMENT' || $dom[$i]['tag'] == '__TEXT'){
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
			if($dom[$i][0]){
				$result .= str_repeat("\t", $level)."<".$dom[$i]['tag']."$properties>\n";
				$result .= $this->safeHTML($dom[$i][0], $level+1);
			}else{
				if($dom[$i]['is_singleton']){
					$result .= str_repeat("\t", $level)."<".$dom[$i]['tag']."$properties>\n";
				}else{
					$result .= str_repeat("\t", $level ? $level-1 : $level)."</".$dom[$i]['tag'].">\n";
				}
			}
		}
		return $result;
	}

	/**
	 * One of main functions. Adds tag to stack, founded from source text.
	 * @param  string  $html      Source text to work.
	 * @param  integer $f_pointer Pointer, where need to start work.
	 * @return array              Return array with tag information.
	 */

	public function readTag(string $html, int $f_pointer = 0) : array{
		$result = ['is_closing' => false, 'is_singleton' => false];

		$html_strlen = strlen($html);
		$tag= '';
		$attribute='';
		$value = '';
		$state = 'tag';
		$bracket = 0;
		$bracket_count = 0;
		$closed_tag = false;
		$without_parth = false;

		for($i = $f_pointer;$i < $html_strlen;$i++, $result['pointer'] = $i){
			if($closed_tag) break;
			if($i == $html_strlen-1 && $html[$i] != '>') throw new Exception('Cannot find \'>\' symbol');
			switch($html[$i]){
				case ' ':
				if($state == 'tag' && $tag != false){
					$result['tag'] = $tag;
					$state = 'attribute';
				}else if($state == 'attribute_value'){
					if($without_parth){
						$result[$attribute] = $value;
						$value = false;
						$attribute = false;
						$state = 'attribute';
					}else{
						$value .= $html[$i];
					}
				}else if($state == 'attribute' && $attribute != false){
					$symbol = $html[$this->spaceJitter($i, $html)];
					if($symbol == '='){
						break;
					}else{
						$result[$attribute] = true;
						$state = 'attribute';
						$attribute = false;
						$value = false;
						$bracket = 0;
						$bracket_count = 0;
					}
					$i = $this->spaceJitter($i, $html)-1;
				}
				break;
				case '>':
				switch($state){
					case 'tag':
					if($tag != false) $result['tag'] = $tag;
					$closed_tag = true;
					break;
					case 'attribute_value_starting':
					return ['error_code' => 2, 'Unknown attribute value in '.$i.' symbol', 'pointer' => $i];
					break;
					case 'attribute_value':
					if($without_parth){
						$result[$attribute] = $value;
						$value = false;
						$attribute = false;
						$closed_tag = true;
					}else{
						$value .= $html[$i];
					}
					break;
					case 'attribute':
					if($attribute != false){
						$result[$attribute] = true;
					}
					$closed_tag = true;
					break;
				}
				if(($state == 'tag' || $state == 'attribute') && $this->isSingleton($tag) ){
				 $result['is_singleton'] = true;
				}
				break;
				case '\'':
				if($state == 'attribute_value_starting' && $attribute && $bracket_count == 0){
					$state = 'attribute_value';
					$bracket = 2;
					$bracket_count++;
				}else if($bracket_count == 1 && $bracket == 2){
					$result[$attribute] = $value;
					$state = 'attribute';
					$attribute = false;
					$value = false;
					$bracket = 0;
					$bracket_count = 0;
				}else{
					$value .= $html[$i];
				}
				break;
				case '"':
				if($state == 'attribute_value_starting' && $attribute && $bracket_count == 0){
					$state = 'attribute_value';
					$bracket = 1;
					$bracket_count++;
				}else if($bracket_count == 1 && $bracket == 1){
					$result[$attribute] = $value;
					$state = 'attribute';
					$attribute = false;
					$value = false;
					$bracket = 0;
					$bracket_count = 0;
				}else{
					$value .= $html[$i];
				}
				break;
				case '=':
				if($state == 'attribute' && $attribute){
					$state = 'attribute_value_starting';
					}else if($state =='attribute_value_starting'){
						return ['error_code' => 3, 'Html syntax error ', 'pointer' => $i.($i-1).($i-2).($i-3).($i-4).($i-5)];
					}else if($state == 'attribute_value'){
						$value .= $html[$i];
					}
					break;
					case '/':
					if($state == 'tag'){
						$result['is_closing'] = true;
					}else if($state == 'attribute_value'){
						$value.= $html[$i];
					}else if($html[$this->spaceJitter($i, $html)] == '>'){
						$result['is_closing'] = true;
						$i = $this->spaceJitter($i, $html)-1;
					}
					break;
					case '<':
					if($state == 'attribute_value'){
						$value .= $html[$i];
					}else if($state == 'attribute'){
						return ['error_code' => 1, 'Html syntax error '.$i.' '.$html[$i].$html[$i+1].$html[$i+2].$html[$i+3], 'pointer' => $i];
					}
					break;
					default:
					switch($state){
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
			if(isset($result['id'])) $result['id'] = explode(' ', $result['id']);
			if(isset($result['class'])) $result['class'] = explode(' ', $result['class']);
			return $result;
	}

	/**
	 * One of main functions. Creates stack.
	 * @param  string  $html      Source text.
	 * @param  integer $f_pointer Pointer, where
	 * @return array              [description]
	 */

	private function node(string $html, int $f_pointer = 0) : array {
		$lenght = strlen($html);

		$stack = [];
		$text='';
		$ignore_html = false;
		$s_quotes = 0;
		$s_quotes_type = 0;

		for($i = 0;$i < $lenght;$i++){
			if($html[$i] == '<' && $this->escapeSymbols(["<","\n"," ", "\t", "\e", "\f", "\v", "\r"], $html[$i+1])){
				if($this->escapeSymbols(["a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","A","B","C","D","E","F","G","H","I","J","K","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","/", "!"], $html[$i+1])){
					$text .= $html[$i];
					$text .= $html[$i+1];
					$i++;
					continue;
				}
				if($html[$i + 1] == "!" && $html[$i + 2] == "-" && $html[$i + 3] == "-"){
					$comment = $this->escapeComments($html, $i);
					if($this->__ENABLE_COMMENTS == true){
						 $stack[] = ['tag' => '__COMMENT', htmlspecialchars($comment[1])];
					}
					$i = $comment[0]-1;
					if(!isset($comment[0])){
						die(print_r($comment));
					}
					continue;
				}
				$temporary_token = $this->readTag($html, $i);
				if($ignore_html && $s_quotes == 1){
					$text .= "<";
				}
				if(isset($temporary_token['error_code']) && $temporary_token['error_code'] && $ignore_html && $s_quotes != 1){
					$text = '';
					$i = $temporary_token['pointer']-1;
					continue;
				}else
				if(isset($temporary_token['error_code']) && $temporary_token['error_code'] && !$ignore_html){
					// throw new \Exception(print_r($temporary_token, 1));
					return $this->stackRecurtion($stack, 0);
				}
				if(($temporary_token['tag'] == 'script' || $temporary_token['tag'] == 'style') && $temporary_token['is_closing'] == 0 && $s_quotes != 1){
					$text = '';
					// if($this->__ENABLE_STYLES_SCRIPTS){
					// 	if(trim($text, "\n\t\e\f\v\r ")) $stack[] = ['tag' => '__TEXT',trim($text, "\n\t\e\f\v\r ")];
					// 	$stack[] = $temporary_token;
					// }
					$i = $temporary_token['pointer']-1;
					$ignore_html = true;
				}else if(($temporary_token['tag'] == 'script' || $temporary_token['tag'] == 'style') && $temporary_token['is_closing'] == 1 && $s_quotes != 1){
					// if($this->__ENABLE_STYLES_SCRIPTS){
					// 	if(trim($text, "\n\t\e\f\v\r ")) $stack[] = ['tag' => '__TEXT',trim($text, "\n\t\e\f\v\r ")];
					// 	$stack[] = $temporary_token;
					// }
					$ignore_html = false;
				}
				if($ignore_html == false){
					if($temporary_token['is_singleton']){
						if(trim($text, "\n\t\e\f\v\r ")){
							$stack[] = ['tag' => '__TEXT',trim($text, "\n\t\e\f\v\r" )];
							$text = '';
						}else if(trim($text, "\n\t\e\f\v\r ") === '0'){
							$stack[] = ['tag' => '__TEXT',trim($text, "\n\t\e\f\v\r ")];
							$text = '';
						}else{
							$text = '';
						}
						$stack[] = $temporary_token;
					}else{
						if(($temporary_token['tag'] != 'script') && ($temporary_token['tag'] != 'style')){
							if(trim($text, "\n\t\e\f\v\r ")){
								$stack[] = ['tag' => '__TEXT',trim($text, "\n\t\e\f\v\r ")];
								$text = '';
							}else if(trim($text, "\n\t\e\f\v\r ") === '0'){
								$stack[] = ['tag' => '__TEXT',trim($text, "\n\t\e\f\v\r ")];
								$text = '';
							}else{
								$text = '';
							}
							$stack[] = $temporary_token;
						}else{
							$text = '';
						}
					}
					$this->ommitedMandatoryTagChecker($temporary_token);
					$i = $temporary_token['pointer']-1;
				}
			}else{
				// if($html[$i] == '\'' && $ignore_html){
				// 	if($s_quotes == 1 && $s_quotes_type == 1){
				// 		$s_quotes = 0;
				// 		$s_quotes_type = 0;
				// 	}else if($s_quotes == 0){
				// 		$s_quotes = 1;
				// 		$s_quotes_type = 1;
				// 	}
				// }else if($html[$i] == '"' && $ignore_html){
				// 	if($s_quotes == 1 && $s_quotes_type == 2){
				// 		$s_quotes = 0;
				// 		$s_quotes_type = 0;
				// 	}else if($s_quotes == 0){
				// 		$s_quotes = 1;
				// 		$s_quotes_type = 2;
				// 	}
				// }
				$text .= $html[$i];
			}
		}
		$stack = $this->ommitedMandatoryTags($stack);
		$stack = $this->ommitedCloseTags($stack);
		return $this->stackRecurtion($stack, 0);
	}

	public function read() : string{
		return $this->__SOURCE_TEXT;
	}

	private function ommitedMandatoryTagChecker($temporary_token){
		$mandatory_tags = ['html', 'head' ,'body', '!doctype'];
			for($j = 0;$j < 4;$j++){
				if(strcasecmp($mandatory_tags[$j], $temporary_token['tag']) == 0){
					if($temporary_token['is_closing']){
						$this->__MANDATORY_CLOSE_ELEMENTS[$j] = true;
					}else{
						$this->__MANDATORY_OPEN_ELEMENTS[$j] = true;
					}
				}
			}
	}

	private function ommitedMandatoryTags($stack){
	if($this->__MANDATORY_OPEN_ELEMENTS[3] == false){
		array_splice($stack, 0, 0, [['tag' => '!DOCTYPE', 'html' => true, 'is_singleton' => true]]);
	}
	if($this->__MANDATORY_OPEN_ELEMENTS[0] == false){
		$pointer = 0;
		for($i = 1;$i <= count($stack);$i++){
			if($stack[$i]['tag'] != "__COMMENT" && strcasecmp($stack[$i]['tag'], '!DOCTYPE') != 0){
				$pointer = $i;
				break;
			}
		}
		array_splice($stack, $pointer, 0, [['tag' => 'html', 'is_singleton' => false, 'is_closing' => false]]);
	}
	if($this->__MANDATORY_CLOSE_ELEMENTS[0] == false){
		array_splice($stack, count($stack), 0, [['tag' => 'html', 'is_closing' => true]]);
	}
	if($this->__MANDATORY_OPEN_ELEMENTS[1] == false){
		for($i = 0;$i < count($stack);$i++){
			if(strcasecmp($stack[$i]['tag'] ,'html') == 0 && $stack[$i]['is_closing'] == true){
				array_splice($stack, $i, 0, [['tag' => 'head', 'is_singleton' => false, 'is_closing' => false]]);
				break;
			}
			if(strcasecmp($stack[$i]['tag'], '!DOCTYPE') == 0 || strcasecmp($stack[$i]['tag'] ,'html') == 0 || $stack[$i]['tag'] == '__COMMENT') continue;
			if($stack[$i]['tag'] != '__TEXT' && $stack[$i]['tag'] != '__COMMENT'){
				array_splice($stack, $i, 0, [['tag' => 'head', 'is_singleton' => false, 'is_closing' => false]]);
				break;
			}
		}
	}
	if($this->__MANDATORY_CLOSE_ELEMENTS[1] == false){
		$first_time = false;
		for($i = 0;$i < count($stack);$i++){
			if(strcasecmp($stack[$i]['tag'], '!DOCTYPE') == 0 || strcasecmp($stack[$i]['tag'], 'html') == 0 || strcasecmp($stack[$i]['tag'], 'head') == 0 || $stack[$i]['tag'] == '__COMMENT') continue;
			if($stack[$i]['tag'] != 'link' && $stack[$i]['tag'] != 'meta' && $stack[$i]['tag'] != 'title'){
				array_splice($stack, $i, 0, [['tag' => 'head', 'is_singleton' => false, 'is_closing' => true]]);
				break;
			}
		}
	}
	if($this->__MANDATORY_OPEN_ELEMENTS[2] == false){
		for($i = 0;$i < count($stack);$i++){
			if(strcasecmp($stack[$i]['tag'], 'head') == 0 && $stack[$i]['is_closing'] == true){
				array_splice($stack, $i+1, 0, [['tag' => 'body', 'is_singleton' => false, 'is_closing' => false]]);
				break;
			}
		}
	}
	if($this->__MANDATORY_CLOSE_ELEMENTS[2] == false){
		array_splice($stack, count($stack)-1, 0, [['tag' => 'body', 'is_closing' => true]]);
	}
	for($i = 0;$i < count($stack);$i++){
		if($stack[$i]['tag'] == 'table'){
			$colgroup = false;
			$tbody = false;
			$thead = false;
			for($j = $i+1;$j < count($stack);$j++){
				if(strcasecmp($stack[$j]['tag'], 'col') == 0 && !$colgroup){
					if($tbody || $thead){
						if($thead){
							array_splice($stack, $j, 0, [['tag' => 'thead', 'is_closing' => true]]);
							array_splice($stack, $j+1, 0, [['tag' => 'colgroup', 'is_closing' => false]]);
							$thead = false;
						}else if($tbody){
							array_splice($stack, $j, 0, [['tag' => 'tbody', 'is_closing' => true]]);
							array_splice($stack, $j+1, 0, [['tag' => 'colgroup', 'is_closing' => false]]);
							$tbody = false;
						}
					}else{
						array_splice($stack, $j, 0, [['tag' => 'colgroup', 'is_closing' => false]]);
					}
					$colgroup = true;
				}
				if((strcasecmp($stack[$j]['tag'], 'col') != 0 && strcasecmp($stack[$j]['tag'], 'colgroup') != 0) && $colgroup == true){
					$colgroup = false;
				}
				if(strcasecmp($stack[$j]['tag'], 'colgroup') == 0 && $stack[$j]['is_closing'] == false){
					$colgroup = true;
				}else if(strcasecmp($stack[$j]['tag'], 'colgroup') == 0 && $stack[$j]['is_closing'] == true){
					$colgroup = false;
				}
				if(strcasecmp($stack[$j]['tag'], 'table') == 0 && $stack[$j]['is_closing']){
					$i = $j;
					break;
				}
				if(strcasecmp($stack[$j]['tag'], 'thead') == 0 && !$stack[$j]['is_closing']){
					 $thead = true;
					 $colgroup = false;
				}
				if(strcasecmp($stack[$j]['tag'], 'tbody') == 0 && !$stack[$j]['is_closing']){
					$tbody = true;
					$colgroup = false;
				}
				if(strcasecmp($stack[$j]['tag'], 'tbody') == 0 && $stack[$j]['is_closing']){
					$tbody = false;
					$colgroup = false;
				}
				if(strcasecmp($stack[$j]['tag'], 'tr') == 0 && !$tbody && !$thead){
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

	private function ommitedCloseTags($stack, $pointer = 0, $has_caused = false){
		$ommited_tags_list = [
			['li', ['li'], ['ul', 'ol']],
			['dt', ['dd', 'dt'], ['dl']],
			['dd', ['dt', 'dd'], ['dl']],
			['rt', ['rt', 'rp'], ['rtc']],
			['optgroup', ['optgroup']],
			['option', ['option', 'optgroup']],
			['tr', ['tr']],
			['td', ['td','th']],
			['th', ['th','td']],
			['thead', ['thead','tbody', 'tfoot']],
			['tbody', ['tbody','tfoot']],
			['tfoot', ['tfoot','table']],
			['a', ['a']]
		];
		for($i = $pointer; $i < count($stack);$i++){
			if(strcasecmp($stack[$i]['tag'],'caption') == 0 || strcasecmp($stack[$i]['tag'],'colgroup') == 0){
				$tag = $stack[$i]['tag'];
				for($j = $i+1; $j < count($stack);$j++){
					if($stack[$j]['tag'] == '__TEXT' || $stack[$j]['tag'] == '__COMMENT') continue;
					if(strcasecmp($stack[$j]['tag'],$tag) == 0 && $stack[$j]['is_closing']) continue;
					if(strcasecmp($tag, 'colgroup') == 0 && strcasecmp($stack[$j]['tag'],'col') == 0) continue;
					if(strcasecmp($stack[$j]['tag'],$tag) != 0){
						array_splice($stack, $j, 0, [['tag' => $tag , 'is_closing' => 1]]);
						$i = $j;
						break;
					}
					if(strcasecmp($stack[$j]['tag'],$tag) == 0 && !$stack[$j]['is_closing']) {
						array_splice($stack, $j, 0, [['tag' => $tag , 'is_closing' => 1]]);
						$i = $j;
						break;
					}
				}
			}
			if($has_caused){
				for($x = 0;$x < count($ommited_tags_list);$x++){
					for($c = 0;$c < count($ommited_tags_list[$x][2]);$c++){
						if($stack[$i]['tag'] == $ommited_tags_list[$x][2][$c]){
							$first_time = false;
							$tag = '';
							for($j = $i+1;$j < count($stack);$j++){
								for($z = 0;$z < count($ommited_tags_list[$x][1]);$z++){
									if(strcasecmp($stack[$j]['tag'], $ommited_tags_list[$x][1][$z]) == 0 && !$stack[$j]['is_closing'] && $first_time){
										array_splice($stack, $j, 0, [['tag' => $tag , 'is_closing' => 1]]);
									}else if($first_time == false && strcasecmp($stack[$j]['tag'], $ommited_tags_list[$x][1][$z]) == 0 && !$stack[$j]['is_closing']){
										$first_time = true;
										$tag = $ommited_tags_list[$x][1][$z];
									}
									if(strcasecmp($stack[$j]['tag'], $ommited_tags_list[$x][1][$z]) == 0 && $stack[$j]['is_closing'] && $first_time){
										$first_time = false;
									}else if(strcasecmp($stack[$j]['tag'], $ommited_tags_list[$x][1][$z]) == 0 && $stack[$j]['is_closing'] && !$first_time){array_splice($stack, $j, 1);
									}
									if(isset($stack[$j]['is_closing']) && !$stack[$j]['is_closing'] && strcasecmp($stack[$j]['tag'], $ommited_tags_list[$x][2][$c]) == 0){

										$ul = $this->ommitedCloseTags($stack, $j, true);
										if(!isset($ul[1])) return $stack;
										$stack = $ul[0];
										$j = $ul[1];
									}if(isset($stack[$j]['is_closing']) && $stack[$j]['is_closing'] && strcasecmp($stack[$j]['tag'], $ommited_tags_list[$x][2][$c]) == 0){
										return [$stack, $j];
									}

								}
							}
						}
					}
				}
			}else{
				for($k = 0;$k < count($ommited_tags_list);$k++){
					if(strcasecmp($stack[$i]['tag'],$ommited_tags_list[$k][0]) == 0){
						$first_time = false;
							$tag = '';
						for($j = $i;$j < $i+100;$j++){
							for($h = 0;$h < @count($ommited_tags_list[$k][2]);$h++){
								if(isset($stack[$j]['tag']) && strcasecmp($stack[$j]['tag'], $ommited_tags_list[$k][2][$h]) == 0 && !$stack[$j]['is_closing']){
									$ul = $this->ommitedCloseTags($stack, $j, true);
									if(!isset($ul[1])) return $stack;
									$stack = $ul[0];
									$j = $ul[1];
									break;
								}
							}
							for($z = 0;$z < count($ommited_tags_list[$k][1]);$z++){
								if(isset($stack[$j]['tag']) && strcasecmp($stack[$j]['tag'], $ommited_tags_list[$k][1][$z]) == 0 && isset($stack[$j]['is_closing'])? $stack[$j]['is_closing'] : false){ $first_time = false;}
								if(isset($stack[$j]['tag']) && strcasecmp($stack[$j]['tag'], $ommited_tags_list[$k][1][$z]) == 0 && (isset($stack[$j]['is_closing'])? !$stack[$j]['is_closing'] : false) && $first_time){
									array_splice($stack, $j, 0, [['tag' => $tag , 'is_closing' => 1]]);
									$first_time = false;
								}else if($first_time == false && isset($stack[$j]['tag']) && strcasecmp($stack[$j]['tag'], $ommited_tags_list[$k][1][$z]) == 0 && !$stack[$j]['is_closing']){
									$first_time = true;
									$tag = $ommited_tags_list[$k][1][$z];
								}
							}
						}
					}
				}
			}
		}
		return $stack;
	}

	public function dump(string $filename = '') {
		if($filename == '') return htmlspecialchars($this->__SOURCE_TEXT);
		$dump = fopen($filename, 'w+');
		fwrite($dump, $this->__SOURCE_TEXT);
		return true;
	}

	private function recurtionTagTracker($array ,string $tag){
		for($i = 0;$i < count($array);$i++){
			if(strcasecmp($array[$i]['tag'], $tag) == 0){
				return $i;
			}
		}
		return count($array);
	}

	public function find(string $element, $number = -1){
		if(!$this->__DOM) $this->__DOM = $this->dom($this->__SOURCE_TEXT);
		return new Element($this->__DOM, $element, $number);
	}

	private function query(string $url, $headers = 0): string {
		$curlSession = curl_init();

			curl_setopt($curlSession, CURLOPT_URL, $url);
			if(gettype($headers) == 'array'){
				for($i = 0;$i < count($headers);$i++){
					curl_setopt($curlSession, $headers[$i][0], $headers[$i][1]);
				}
			}
	    $returningData = curl_exec($curlSession);
	    curl_close($curlSession);

	    return $returningData;
	}

}
?>
