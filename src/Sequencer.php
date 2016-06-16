<?php

namespace Clue\React\Utf8;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;

/**
 * forwards only complete UTF-8 sequences
 */
class Sequencer extends EventEmitter implements ReadableStreamInterface
{
    private $input;
    private $invalid;

    private $buffer = '';
    private $closed = false;

    public function __construct(ReadableStreamInterface $input, $replacementCharacter = '?')
    {
        $this->input = $input;
        $this->invalid = $replacementCharacter;

        if (!$input->isReadable()) {
            return $this->close();
        }

        $this->input->on('data', array($this, 'handleData'));
        $this->input->on('end', array($this, 'handleEnd'));
        $this->input->on('error', array($this, 'handleError'));
        $this->input->on('close', array($this, 'close'));
    }

    /** @internal */
    public function handleData($data)
    {
        $this->buffer .= $data;
        $len = strlen($this->buffer);

        $sequence = '';
        $expect = 0;
        $out = '';

        for ($i = 0; $i < $len; ++$i) {
            $char = $this->buffer[$i];
            $code = ord($char);

            if ($code & 128) {
                // multi-byte sequence
                if ($code & 64) {
                    // this is the start of a sequence

                    // unexpected start of sequence because already within sequence
                    if ($expect !== 0) {
                        $out .= str_repeat($this->invalid, strlen($sequence));
                        $sequence = '';
                    }

                    $sequence = $char;
                    $expect = 2;

                    if ($code & 32) {
                        ++$expect;
                        if ($code & 16) {
                            ++$expect;

                            if ($code & 8) {
                                // invalid sequence start length
                                $out .= $this->invalid;
                                $sequence = '';
                                $expect = 0;
                            }
                        }
                    }
                } else {
                    // this is a follow-up byte in a sequence
                    if ($expect === 0) {
                        // we're not within a sequence in first place
                        $out .= $this->invalid;
                    } else {
                        // valid following byte in sequence
                        $sequence .= $char;

                        // sequence reached expected length => add to output
                        if (strlen($sequence) === $expect) {
                            $out .= $sequence;
                            $sequence = '';
                            $expect = 0;
                        }
                    }
                }
            } else {
                // simple ASCII character found

                // unexpected because already within sequence
                if ($expect !== 0) {
                    $out .= str_repeat($this->invalid, strlen($sequence));
                    $sequence = '';
                    $expect = 0;
                }

                $out .= $char;
            }
        }

        if ($out !== '') {
            $this->buffer = substr($this->buffer, strlen($out));

            $this->emit('data', array($out));
        }
    }

    /** @internal */
    public function handleEnd()
    {
        if ($this->buffer !== '' && $this->invalid !== '') {
            $data = str_repeat($this->invalid, strlen($this->buffer));
            $this->buffer = '';

            $this->emit('data', array($data));
        }

        if (!$this->closed) {
            $this->emit('end');
            $this->close();
        }
    }

    /** @internal */
    public function handleError(\Exception $error)
    {
        $this->emit('error', array($error));
        $this->close();
    }

    public function isReadable()
    {
        return !$this->closed && $this->input->isReadable();
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->buffer = '';

        $this->input->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    public function pause()
    {
        $this->input->pause();
    }

    public function resume()
    {
        $this->input->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }
}
