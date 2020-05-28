<ul>
	<li><a href="#Introducion">Introducion</a></li>
	<li><a href="#Find">Finding tags and childrens</a></li>
		<ul>
			<li><a href="#Find$Elements">Find Elements</a></li>
			<li><a href="#Find$Text">Find Text</a></li>
		</ul>
	<li><a href="#Errors">What if I have an errors in my html?</a></li>
	<li><a href="#License">License</a></li>
</ul>

<h3 id = 'Introducion'>Introducion</h3>

This php library contains an instrumentary to work with html document.You can work with dom and find elements and text.You can also get commentaries from dom, if you want.

To create a Parser object you need to include this library to your project:

```PHP
	
use Parser\Dom as Dom;

$html = new Dom('url or href to file');

```

You can look the dom using this:

```PHP

print_r($html->dom());

```

You can also look source file code using this:

```PHP
	
print_r($html->dump(string $filename)); //You can indicate file where will be writen dump file.

```

<h3 id = 'Find'>Finding tags and childrens</h3>

<h3 id = 'Find$Elements'>Find Elements</h3>

To find element, you can this two functions:

```PHP

$html->find('elem');

$html->children(1);

```

First method finds tag with name "elem".You can also find elements by class or id.You can do it, marking element by special symbols "." or "#".

Second method finds second children in your main dom("head" at example) and all his childs.

You can also use this method together:

```PHP
	
$html->find('head')->children(0); //It can be link

```

<h3 id = 'Find$Text'>Find Text</h3>

You can find text easily using this method:

```PHP
	
$html->plainText();

```

It return the array with text from all elements childrens.

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

<h3 id='License'>License</h3>

Please see the LICENSE for more info.

