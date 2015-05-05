# Aura.Accept

Provides content-negotiation tools using `Accept*` headers.

## Foreword

### Installation

This library requires PHP 5.3 or later; we recommend using the latest available version of PHP as a matter of principle. It has no userland dependencies.

It is installable and autoloadable via Composer as [aura/accept](https://packagist.org/packages/aura/accept).

Alternatively, [download a release](https://github.com/auraphp/Aura.Accept/releases) or clone this repository, then require or include its _autoload.php_ file.

### Quality

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/auraphp/Aura.Accept/badges/quality-score.png?b=develop-2)](https://scrutinizer-ci.com/g/auraphp/Aura.Accept/)
[![Code Coverage](https://scrutinizer-ci.com/g/auraphp/Aura.Accept/badges/coverage.png?b=develop-2)](https://scrutinizer-ci.com/g/auraphp/Aura.Accept/)
[![Build Status](https://travis-ci.org/auraphp/Aura.Accept.png?branch=develop-2)](https://travis-ci.org/auraphp/Aura.Accept)

To run the unit tests at the command line, issue `composer install` and then `phpunit` at the package root. This requires [Composer](http://getcomposer.org/) to be available as `composer`, and [PHPUnit](http://phpunit.de/manual/) to be available as `phpunit`.

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

### Community

To ask questions, provide feedback, or otherwise communicate with the Aura community, please join our [Google Group](http://groups.google.com/group/auraphp), follow [@auraphp on Twitter](http://twitter.com/auraphp), or chat with us on #auraphp on Freenode.


## Getting Started

### Instantiation

First, instantiate a _AcceptFactory_ object, then use it to create an _Accept_
object.

```php
<?php
use Aura\Accept\AcceptFactory;

$accept_factory = new AcceptFactory($_SERVER);
$accept = $accept_factory->newInstance();
?>
```

The _Accept_ object provides convenienence methods to negotiate between
acceptable (to the client) and available (from the application) charset,
encoding, language, and media-type values. The methods are:

- `negotiateCharset()`
- `negotiateEncoding()`
- `negotiateLanguage()`
- `negotiateMedia()`

Your application code, knowing what values it has available, should pass an
array of the available values to the `negotiate*()` method. (The values that are
acceptable to the client are already indicated by `$_SERVER`).

The result returned from the method will be a `*Value` object describing the
highest-quality match that was negotiated between the available and acceptable
values. If there is no negotiable match, the result will be `false`.

> N.b. Accept headers can be kind of complicated. See the
> [HTTP Header Field Definitions](http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html)
> for more detailed information regarding quality factors, matching rules,
> and parameters extensions.


### Negotiating Media Types

To negotiate a media type (aka a content type), call the `negotiateMedia()`
method with a list of available media types. The available types should be in
the order you prefer for delivery, from "most preferred" to "least preferred".

```php
<?php
// assume the request indicates these Accept values (XML is best, then CSV,
// then anything else)
$_SERVER['HTTP_ACCEPT'] = 'application/xml;q=1.0,text/csv;q=0.5,*;q=0.1';

// create the accept factory
$accept_factory = new AcceptFactory($_SERVER);

// create the accept object
$accept = $accept_factory->newInstance();

// assume our application has `application/json` and `text/csv` available
// as media types, in order of highest-to-lowest preference for delivery
$available = array(
    'application/json',
    'text/csv',
);

// get the best match between what the request finds acceptable and what we
// have available; the result in this case is 'text/csv'
$media = $accept->negotiateMedia($available);
echo $media->getValue(); // text/csv
?>
```

If the requested URL ends in a recognized file extension for a media type,
the _MediaNegotiator_ object used by _Accept_ will use that file extension
instead of the explicit `Accept` header value to determine the acceptable media
type:

```php
<?php
// assume the request indicates these Accept values (XML is best, then CSV,
// then anything else)
$_SERVER['HTTP_ACCEPT'] = 'application/xml;q=1.0,text/csv;q=0.5,*;q=0.1';

// assume also that the request URI explicitly notes a .json file extension
$_SERVER['REQUEST_URI'] = '/path/to/entity.json';

// create the accept factory
$accept_factory = new AcceptFactory($_SERVER);

// factory the accept object
$accept = $accept_factory->newInstance();

// assume our application has `application/json` and `text/csv` available
// as media types, in order of highest-to-lowest preference for delivery
$available = array(
    'application/json',
    'text/csv',
);

// get the best match between what the request finds acceptable and what we
// have available; the result in this case is 'application/json' because of
// the file extenstion overriding the Accept header values
$media = $accept->negotiateMedia($available);
echo $media->getValue(); // application/json
?>
```

(See the _MediaNegotiator_ class file for the list of what file extensions map
to what media types.)

To create your own mappings, set them into the _AcceptFactory_ object at
construction time:

```php
<?php
$accept_factory = new AcceptFactory($_SERVER, array(
    '.foo' => 'application/x-foo-content-type',
));

$accept = $accept_factory->newInstance();
?>
```

If the acceptable values indicate additional parameters, you can match on those
as well:

```php
<?php
// assume the request indicates these Accept values (XML is best, then CSV,
// then anything else)
$_SERVER['HTTP_ACCEPT'] = 'text/html;level=1;q=0.5,text/html;level=3';

// create the accept factory
$accept_factory = new AcceptFactory($_SERVER);

// factory the accept object
$accept = $accept_factory->newInstance();

// assume our application has these available as media types,
// in order of highest-to-lowest preference for delivery
$available = array(
    'text/html;level=1',
    'text/html;level=2',
);

// get the best match between what the request finds acceptable and what we
// have available; the result in this case is 'text/html;level=1'
$media = $accept->negotiateMedia($available);
echo $media->getValue(); // text/html
var_dump($media->getParameters()); // array('level' => '1')
?>
```

> N.b. Parameters in the acceptable values that are not present in the
> available values will not be used for matching.


### Negotiating Other Values

The other negotiation methods work much the same way, although they are less
complex than media-type negotiation.

```php
<?php
// assume the request indicates these Accept-* values
$_SERVER['HTTP_ACCEPT_CHARSET'] = 'iso-8859-5, unicode-1-1;q=0.8';
$_SERVER['HTTP_ACCEPT_ENCODING'] = 'compress;q=0.5, gzip;q=1.0';
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US, en-GB, en, *';

// create the accept factory
$accept_factory = new AcceptFactory($_SERVER);

// factory the accept object
$accept = $accept_factory->newInstance();

// charset negotiation
$available_charsets = array('iso-1234', 'unicode-1-1');
$charset = $accept->negotiateCharset($available_charsets);
echo $charset->getValue('unicode-1-1');

// encoding negotiation
$available_encodings = array();
$encoding = $accept->negotiateEncoding($available_encodings);
var_dump($encoding); // false

// language negotiation
$available_languages = array('pt-BR', 'fr-FR');
$language = $accept->negotiateLanguage($available_languages);
echo $language->getValue(); // pt-BR
?>
```
