# Phporser

<ul>
	<li>
	    <b>Getting Started</b>
	    <ul>
	        <li>Requirements
	        <li>Installing via composer
	        <li>Installing via archive
	        <li>Installing chromium executable
	    </ul>
	<li><b>Parsing your first page</b>
	<li><b>Search methods</b>
	<li><b>Working with text</b>
    <li><b>Contribute</b>
	<li><b>License</b>
</ul>

<h2>Getting Started</h2>
<h3>Requirements</h3>
Requires PHP 7.1+.

Also needs <a href="https://github.com/chrome-php/headless-chromium-php">Headless Chromium PHP</a> and chromium executable, if you want to use this library with headless browsing support(includes by standart in packagist version).
<h3>Installing via composer</h3>

`$ composer require shamanhead/phpporser`
<h3>Installing via archive</h3>
You can install this library also from archive, by downloading it from github. There is no dependendies needed, besides <a href="https://github.com/chrome-php/headless-chromium-php">Headless Chromium PHP</a>, if you want to use this library with headless browsing feature.
</h3>Installing chromium executable</h3>
If you want to use this library with headless browser, first you need to download executable of this browser. 

This might works on Windows, MacOs and Linux.
<h4>Choose browser that you want to use</h4>
Headless chromium supports all chomium-based browsers, like Chrome, Opera, Chromium etc.
<h4>Installing chromium executable</h4>
I can recommend to use chromium instead of chrome, because of my observation he works better than chrome.

So, go on the <a href = 'https://download-chromium.appspot.com/'>official chromium browser downloading page</a> and download it.

After doing this step, unpack archive and move to necessary place.

Then, specify path in your script:
```PHP
require_once "vendor/autoload.php";

use HeadlessChromium\Page;

use ShamanHead\PhpPorser\App\Dom as Dom;

$dom = new Dom();
$dom->setHref('file:///home/shamanhead/dev/porser/phpporser-master/test.html');
$dom->setBrowserPath('PATH_TO_CHROME');
```
If you done all right, parser would work. If you have any errors occuring during this step, you can go see here, is there solution to solve your problem. In other case, please, open new issue here or on <a href="https://github.com/chrome-php/headless-chromium-php">Headless Chromium PHP</a> page.
<h2>Parsing your first page</h2>
Huh, half of work done. So now, let's try to parse simple page, like <a href='https://en.wikipedia.org/wiki/Computer_science'>Computer sciense on wikipedia</a>. With the help of it, I will show all the capabilities of the parser.

First of all, let's try to get 'Computer sciense' string on top of the page:

```PHP
<?php

require_once "vendor/autoload.php";

use ShamanHead\PhpPorser\App\Dom as Dom;

$dom = new Dom();
$dom->setHref('https://en.wikipedia.org/wiki/Computer_science');

print_r($dom->tag('h1')->class('firstHeading')->text()->merge());

?>
```

It's works! But how? Let's me explain:
<ol>
<li>Parser get's all tags with name 'h1'</li>
<li>Then parser get's all tags with class 'firstHeading' in h1 tags range(and it's dependencies)</li>
<li>Get's text from it</li>
<li>Converts result array to string format</li>
</ol>
<h2>Search methods</h2>
To find elements in html dom, there is 4 functions in this library:

```PHP
<?php

require_once "vendor/autoload.php";

use ShamanHead\PhpPorser\App\Dom as Dom;

$dom = new Dom();
$dom->setHref('href to file');

print_r($dom->tag('h1')->array()); //finds by tag name 'h1'
print_r($dom->id('firstHeading')->array()); //finds by id name 'firstHeading'
print_r($dom->class('wrapper__main')->array()); //finds by class name 'wrapper_main'
print_r($dom->custom(['name', 'button'])->array()); //finds by 'name' attribute value 'button'

?>
```
You can combine search methods with each other, to find elements in special way:

```PHP
<?php

require_once "vendor/autoload.php";

use ShamanHead\PhpPorser\App\Dom as Dom;

$dom = new Dom();
$dom->setHref('href to file');

print_r($dom->class('main')->id('firstHeading')->tag('h1')->array());

?>
```
<h2>Working with text</h2>

```PHP
<?php

require_once "vendor/autoload.php";

use ShamanHead\PhpPorser\App\Dom as Dom;

$dom = new Dom();
$dom->setHref('href to file');

$divText = $dom->tag('div')->id('someDiv')->text();

$divText->contents(); //Returns all text in array form.

$divText->merge('symbol'); //Returns all text in string form with 'symbol' separator
                          //'\n' by default.

$divText->first(); //Returns first founded text.

$divText->last(); //Returns last founded text.

?>
```
<h2>Contribute</h2>
Hey, want to contribute? Just notice me on my email ( <b>arsenii.romanovskii85@gmail.com</b> ), where will you indicate what you want to help.
<h2>License</h2>
See the LICENSE file.
