# Phporser

<ul>
	<li><b><a href="#Introducion">Introducion</a></b></li>
	<li><b><a href="#Settings">Setting up</a></b></li>
	<li><b><a href="#Find">Finding tags and childrens</a></b></li>
		<ul>
			<li><a href="#Find$Elements">Find Elements</a></li>
			<li><a href="#Find$Text">Find Text</a></li>
		</ul>
	<li><b><a href="#Find$Arguments">Finding arguments</a></b></li>
	<li><b><a href="#Errors">What if I have an errors in my html?</a></b></li>
	<li><b><a href="#License">License</a></b></li>
</ul>

<h3 id = 'Introducion'>Introducion</h3>

This php library contains an instrumentary to work with html document.You can work with dom and find elements and text.You can also get commentaries from dom, if you want.

To create a Parser object you need to include this library into your project:

```PHP
require_once("vendor/autoload.php");

use ShamanHead\PhpPorser\Dom as Dom;

$html = new Dom;
$html->setHref("Href to your file or site");
$html->dom(); //Start parsing

```

You can look at the dom using this:

```PHP
require_once("vendor/autoload.php");

use ShamanHead\PhpPorser\Dom as Dom;

$html = new Dom;
$html->setHref("Href to your file or site");

print_r($html->dom());

```

You can also look source file code using this:

```PHP
use ShamanHead\PhpPorser\Dom as Dom;

$html = new Dom;
$html->setHref("Href to your file or site");

print_r($html->dump($filename)); //You can indicate file where will be writen dump file.

```

You can also look html that was finded by url:

```PHP
use ShamanHead\PhpPorser\Dom as Dom;

$html = new Dom;
$html->setHref("Href to your file or site");

echo $html->read();

```

<h3 id = 'Settings'>Setting up</h3>

<h3 id = 'Find'>Finding elements and childrens</h3>

<h3 id = 'Find$Elements'>Find Elements</h3>

To find element, you can use those functions:

```PHP
use ShamanHead\PhpPorser\Dom as Dom;

$html = new Dom;
$html->setHref("Href to your file or site");

$html->find('elem', 1); //You can indicate what element with tag "elem" you want to get

$html->children(1);

$html->children(1)->viewDom(); //you can use this method to see the result that will founded by script.
```

First method finds tag with name "elem".You can also find elements by class or id. You can do it, marking element by special symbols "." or "#".

Second method finds second children in your main dom("head" at example) and all his childs.

You can also use this method together:

```PHP
use ShamanHead\PhpPorser\Dom as Dom;

$html = new Dom;
$html->setHref("Href to your file or site");

$html->find('head')->children(0); //It can be link

```

<h3 id = 'Find$Text'>Find Text</h3>

You can find text easily using this method:

```PHP
use ShamanHead\PhpPorser\Dom as Dom;

$html = new Dom;
$html->setHref("Href to your file or site");

$html->findAllText(); //Finds all text data on dom(or elem)

```

It return the array with text from all children elements.

<h3 id='Find$Arguments'>Getting arguments from element</h3>

If you want to get arguments from some element, use this:

```PHP
use ShamanHead\PhpPorser\Dom as Dom;

$html = new Dom;
$html->setHref("Href to your file or site");
$html->find('html')->contents(); //Finds all arguments in elem
$html->find('a')->findParam('src'); //Finds certain argument

```

<h3 id = 'Errors'>What if I have an errors in my html?</h3>

Its not a problem.The script solve all the problems that can be in your document.But there are differences with that how browser solve the problems and this script.Lets see:

№1 What about tags, who dont closing or opening?

Script finds tags who dont opening or closing and fix it.

№2 Hmm, okay, then what if tags closing wrongly?

Lets take this html:
```HTML
<span>
 <p>
  </span>
 </p>
```

Thats how my script handles something like that:

```HTML
<span>
 <p>
 </p>
</span>
```

<h3>Contributors</h3>
ShamanHead - Main dev
Wterh - For toxic behavior and some tips

<h3 id='License'>License</h3>

Please see the LICENSE for more info.
