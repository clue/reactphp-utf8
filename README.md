# clue/utf8-react [![Build Status](https://travis-ci.org/clue/php-utf8-react.svg?branch=master)](https://travis-ci.org/clue/php-utf8-react)

Streaming UTF-8 parser, built on top of [ReactPHP](https://reactphp.org/)

**Table of Contents**

* [Usage](#usage)
  * [Sequencer](#sequencer)
* [Install](#install)
* [Tests](#tests)
* [License](#license)
* [More](#more)

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

This will install the latest supported version:

```bash
$ composer require clue/utf8-react:^1.1
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](http://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

MIT

## More

* If you want to learn more about processing streams of data, refer to the documentation of
  the underlying [react/stream](https://github.com/reactphp/stream) component.

* If you want to process ASCII control characters or ANSI / VT100 control byte sequences, you may
  want to use [clue/term-react](https://github.com/clue/php-term-react) on the raw input
  stream before passing the resulting stream to the UTF-8 sequencer.

* If you want to to display or inspect the byte sequences, you may
  want to use [clue/hexdump](https://github.com/clue/php-hexdump) on the emitted byte sequences.
