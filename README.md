# clue/reactphp-utf8 [![Build Status](https://travis-ci.org/clue/reactphp-utf8.svg?branch=master)](https://travis-ci.org/clue/reactphp-utf8)

Streaming UTF-8 parser, built on top of [ReactPHP](https://reactphp.org/).

**Table of Contents**

* [Support us](#support-us)
* [Usage](#usage)
  * [Sequencer](#sequencer)
* [Install](#install)
* [Tests](#tests)
* [License](#license)
* [More](#more)

## Support us

We invest a lot of time developing, maintaining and updating our awesome
open-source projects. You can help us sustain this high-quality of our work by
[becoming a sponsor on GitHub](https://github.com/sponsors/clue). Sponsors get
numerous benefits in return, see our [sponsoring page](https://github.com/sponsors/clue)
for details.

Let's take these projects to the next level together! 🚀

## Usage

### Sequencer

The `Sequencer` class can be used to make sure you only get back complete, valid
UTF-8 byte sequences when reading from a stream.
It wraps a given `ReadableStreamInterface` and exposes its data through the same
interface.

```php
$stdin = new ReadableResourceStream(STDIN, $loop);

$stream = new Sequencer($stdin);

$stream->on('data', function ($chunk) {
    var_dump($chunk);
});
```

React's streams emit chunks of data strings and make no assumption about its encoding.
These chunks do not necessarily represent complete UTF-8 byte sequences, as a
sequence may be broken up into multiple chunks.
This class reassembles these sequences by buffering incomplete ones.

Also, if you're merely consuming a stream and you're not in control of producing and
ensuring valid UTF-8 data, it may as well include invalid UTF-8 byte sequences.
This class replaces any invalid bytes in the sequence with a `?`.
This replacement character can be given as a second paramter to the constructor:

```php
$stream = new Sequencer($stdin, 'X');
```

As such, you can be sure you never get an invalid UTF-8 byte sequence out of
the resulting stream.

Note that the stream may still contain ASCII control characters or
ANSI / VT100 control byte sequences, as they're valid UTF-8.
This binary data will be left as-is, unless you filter this at a later stage.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require clue/utf8-react:^1.2
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 7+ and
HHVM.
It's *highly recommended to use PHP 7+* for this project.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

This project is released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.

## More

* If you want to learn more about processing streams of data, refer to the documentation of
  the underlying [react/stream](https://github.com/reactphp/stream) component.

* If you want to process ASCII control characters or ANSI / VT100 control byte sequences, you may
  want to use [clue/reactphp-term](https://github.com/clue/reactphp-term) on the raw input
  stream before passing the resulting stream to the UTF-8 sequencer.

* If you want to to display or inspect the byte sequences, you may
  want to use [clue/hexdump](https://github.com/clue/php-hexdump) on the emitted byte sequences.
