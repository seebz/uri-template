# uri_template()

Implementation of [RFC 6570][rfc6570] (URI Template) in a single PHP function.


## Installation

Install the latest version with [composer][]:

```bash
$ composer require seebz/uri-template
```


## Usage

```php
<?php

require 'vendor/autoload.php';


$template = 'https://api.github.com/repos{/user,repo,function,id}';
$variables = array(
	'user'     => 'Seebz',
	'repo'     => 'uri-template',
	'function' => 'commits',
);

$uri = uri_template($template, $variables);
// "https://api.github.com/repos/Seebz/uri-template/commits"

```


## License

Simply [do what the fuck you want](LICENSE "View Licence").



[rfc6570]:  https://tools.ietf.org/html/rfc6570
[composer]: https://getcomposer.org/
