<?php

use Clue\React\Utf8\Sequencer;
use React\Stream\ThroughStream;

class SequencerTest extends TestCase
{
    private $input;
    private $sequencer;

    public function setUp()
    {
        $this->input = new ThroughStream();
        $this->sequencer = new Sequencer($this->input);
    }

    public function testEmitDataSingleCharWillForward()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('h'));

        $this->input->emit('data', array('h'));
    }

    public function testEmitDataSimpleInOneChunkWillForward()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('hello'));

        $this->input->emit('data', array('hello'));
    }

    public function testEmitEndWillForwardEnd()
    {
        $this->sequencer->on('data', $this->expectCallableNever());
        $this->sequencer->on('end', $this->expectCallableOnce());

        $this->input->emit('end');
    }

    public function testEmitDataWithSingleCharUtf8SequenceInOneChunkWillForward()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('ä'));

        $this->input->emit('data', array('ä'));
    }

    public function testEmitDataWithSingleCharLongUtf8SequenceInOneChunkWillForward()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('😀'));

        $this->input->emit('data', array('😀'));
    }

    public function testEmitDataWithUtf8SequenceInOneChunkWillForward()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('hällo'));

        $this->input->emit('data', array('hällo'));
    }

    public function testEmitDataWithIncompleteUtf8SequenceWillNotForward()
    {
        $this->sequencer->on('data', $this->expectCallableNever());

        $this->input->emit('data', array("\xc3"));
    }

    public function testEmitDataWithChunkedUtf8SequenceWillForwardOnceComplete()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('ä'));

        $this->input->emit('data', array("\xc3"));
        $this->input->emit('data', array("\xa4"));
    }

    public function testEmitDataWithIncompleteUtf8SequenceWillForwardOneEnd()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('?'));

        $this->input->emit('data', array("\xc3"));
        $this->input->emit('end');
    }

    public function testEmitDataWithIncompleteUtf8SequenceWillForwardThusFar()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('h'));

        $this->input->emit('data', array("h\xc3"));
    }

    public function testEmitDataWithChunkedUtf8SequenceWillForwardMultipleChunks()
    {
        $this->sequencer->once('data', $this->expectCallableOnceWith('h'));

        $buffer = '';
        $this->sequencer->on('data', function ($chunk) use (&$buffer) {
            $buffer .= $chunk;
        });

        $this->input->emit('data', array("h\xc3"));
        $this->input->emit('data', array("\xa4llo"));

        $this->assertEquals('hällo', $buffer);
    }

    public function testEmitDataWithInvalidStartUtf8SequencesWillForwardOnceReplaced()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('?'));

        $this->input->emit('data', array("\xFF"));
    }

    public function testEmitDataWithInvalidFollowingUtf8SequencesWillForwardOnceReplaced()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('?'));

        $this->input->emit('data', array("\xA4"));
    }

    public function testEmitDataWithInvalidUtf8SequenceWillForwardOnceReplaced()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('h?llo'));

        $this->input->emit('data', array("h\xc3llo"));
    }

    public function testEmitDataWithTwoInvalidUtf8SequencesWillForwardOnceReplaced()
    {
        $buffer = '';
        $this->sequencer->on('data', function ($chunk) use (&$buffer) {
            $buffer .= $chunk;
        });

        $this->input->emit('data', array("h\xc3\xc3llo"));

        $this->assertEquals('h??llo', $buffer);
    }

    public function testEmitDataInMultipleChunksWillForwardMultipleChunks()
    {
        $this->sequencer->once('data', $this->expectCallableOnceWith('hello'));

        $buffer = '';
        $this->sequencer->on('data', function ($chunk) use (&$buffer) {
            $buffer .= $chunk;
        });

        $this->input->emit('data', array('hello'));
        $this->input->emit('data', array('world'));

        $this->assertEquals('helloworld', $buffer);
    }

    public function testClosingInputWillCloseSequencer()
    {
        $this->sequencer->on('close', $this->expectCallableOnce());

        $this->assertTrue($this->sequencer->isReadable());

        $this->input->close();

        $this->assertFalse($this->sequencer->isReadable());
    }

    public function testClosingInputWillRemoveAllDataListeners()
    {
        $this->input->close();

        $this->assertEquals(array(), $this->input->listeners('data'));
        $this->assertEquals(array(), $this->sequencer->listeners('data'));
    }

    public function testClosingSequencerWillCloseInput()
    {
        $this->input->on('close', $this->expectCallableOnce());
        $this->sequencer->on('close', $this->expectCallableOnce());

        $this->assertTrue($this->sequencer->isReadable());

        $this->sequencer->close();

        $this->assertFalse($this->sequencer->isReadable());
    }

    public function testClosingSequencerWillRemoveAllDataListeners()
    {
        $this->sequencer->close();

        $this->assertEquals(array(), $this->input->listeners('data'));
        $this->assertEquals(array(), $this->sequencer->listeners('data'));
    }

    public function testClosingSequencerDuringFinalDataEventFromEndWillNotEmitEnd()
    {
        $this->sequencer->on('data', $this->expectCallableOnceWith('?'));
        $this->sequencer->on('data', array($this->sequencer, 'close'));

        $this->sequencer->on('end', $this->expectCallableNever());

        $this->input->emit('data', array("\xc3"));
        $this->input->emit('end');
    }

    public function testCustomReplacementEmitDataWithInvalidStartUtf8SequencesWillForwardOnceReplaced()
    {
        $this->sequencer = new Sequencer($this->input, 'X');
        $this->sequencer->on('data', $this->expectCallableOnceWith('X'));

        $this->input->emit('data', array("\xFF"));
    }

    public function testEmptyReplacementEmitDataWithInvalidStartUtf8SequencesWillNotForward()
    {
        $this->sequencer = new Sequencer($this->input, '');
        $this->sequencer->on('data', $this->expectCallableNever());

        $this->input->emit('data', array("\xFF"));
    }

    public function testEmptyReplacementEmitDataWithInvalidUtf8SequencesWillForward()
    {
        $this->sequencer = new Sequencer($this->input, '');
        $this->sequencer->on('data', $this->expectCallableOnceWith('hllo'));

        $this->input->emit('data', array("h\xFFllo"));
    }

    public function testUnreadableInputWillResultInUnreadableSequencer()
    {
        $this->input->close();
        $this->sequencer = new Sequencer($this->input);

        $this->assertFalse($this->sequencer->isReadable());
    }

    public function testUnreadableInputWillNotAddAnyEventListeners()
    {
        $this->input->close();
        $this->sequencer = new Sequencer($this->input);

        $this->assertEquals(array(), $this->input->listeners('data'));
        $this->assertEquals(array(), $this->sequencer->listeners('data'));
    }

    public function testEmitErrorEventWillForwardAndClose()
    {
        $this->sequencer->on('error', $this->expectCallableOnce());
        $this->sequencer->on('close', $this->expectCallableOnce());

        $this->input->emit('error', array(new \RuntimeException()));
    }

    public function testPipeReturnsDestStream()
    {
        $dest = $this->getMockBuilder('React\Stream\WritableStreamInterface')->getMock();

        $ret = $this->sequencer->pipe($dest);

        $this->assertSame($dest, $ret);
    }

    public function testForwardPauseToInput()
    {
        $this->input = $this->getMockBuilder('React\Stream\ReadableStreamInterface')->getMock();
        $this->input->expects($this->once())->method('pause');

        $this->sequencer = new Sequencer($this->input);
        $this->sequencer->pause();
    }

    public function testForwardResumeToInput()
    {
        $this->input = $this->getMockBuilder('React\Stream\ReadableStreamInterface')->getMock();
        $this->input->expects($this->once())->method('resume');

        $this->sequencer = new Sequencer($this->input);
        $this->sequencer->resume();
    }
}
