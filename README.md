#phporser
<ul>
	<li>Introducion</li>
	<li>Find</li>
		<ul>
			<li>Elements</li>
			<li>Text</li>
		</ul>
	<li>What if I have an errors in my html?</li>
</ul>

<h3>Introducion</h3>

This php library contains an instrumentary to work with html document.You can work with html dom and find elements and text.You can also get commentaries from dom, if you want.

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
print_r($html->dump());

```

<h3>Find</h3>

<h4>Elements</h4>

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

<h4>Text</h4>

You can find text easily using this method:

```PHP
$html->plainText();

```

It return the array with text from all elements childrens.

<h3>What if I have an errors in my html?</h3>

Its not a problem.The script solve all the problems that can be in your document.But there are differences with that how browser solve the problems.Lets see:

Блять потом мне уже надоело
