#No Follow Links

This simple PHP package has one simple purpose; to take some HTML (as a string), go through it and add `rel="nofollow"` to all links, subject to certain exceptions.

So give it this:

```
<p>Check out our <a href="http://example.com">affordable web design services</a>!</p>
```

…and you get this:

```
<p>Check out our <a href="http://example.com" rel="nofollow">affordable web design services</a>!</p>
```

By default, it will ignore relative links, so this would remain untouched:

```
<p>Check out the <a href="/about">about</a> page</p>
```

It will also ignore absolute links to the current domain.

You can also provide a whitelist of domains, and it will skip them.

##Installation

```
composer require lukaswhite/no-follow-links
```

##Usage

Create an instance:

```
$processor = new Lukaswhite\NoFollow\Processor();
```

Optionally specify the current host:

```
$processor->setCurrentHost('example.com');    
```

> If you don't set this, it'll grab it from `$_SERVER['HTTP_HOST']`

Then run:

```
$html = $processor->addLinks('<p>Check out our <a href="http://example.com">affordable web design services</a>!</p>');
```

The method signature is this:

```
public function addLinks($html, $whitelist = array(), $ignoreRelative = true)
```

So, to whitelist certain hosts:

```
$html = $processor->addLinks(
  '<p>Check out our <a href="http://example.com">affordable web design services</a>!</p>'
  [
  	'example.com'
  ]
);
```

To add `nofollow` to relative links anyway, set the third argument to true, e.g.:

```
$html = $processor->addLinks(
  '<p>Check out the <a href="/about">about</a> page</p>'
  [],
  TRUE
);
```

This will output the following:

```
<p>Check out the <a href="/about" rel="nofollow">about</a> page</p>
```

##Tests

The package contains tests, to run them:

```
phpunit
```