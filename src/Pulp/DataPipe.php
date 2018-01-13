<?php

namespace Pulp;
use \Evenement\EventEmitterTrait;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class DataPipe implements \React\Stream\DuplexStreamInterface {

	use \Evenement\EventEmitterTrait;

	public $writeCallback;
	public $closed = FALSE;

	public function __construct($writeCallable) {
		$this->writeCallback = $writeCallable;
	}

	public function pause() {
	}

	public function end($data=null) {
		$this->close();
		$this->emit('end');
	}

	public function close() {
		$this->closed = TRUE;
	}

	public function resume() {
	}

	public function isReadable() {
		return TRUE;
	}
	public function isWritable() {
		return TRUE;
	}

	public function write($data) {
		$cb = $this->writeCallback;
		$cb($data);
	}

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
//		$options['end'] = FALSE;

		/*
		//default pipe behavior throws away $data for end events
		$this->on('end', $ender = function () use($dest) {
			call_user_func_array([$dest, 'end'], func_get_args());
		});

		$source = $this;
		$dest->on('close', function () use ($source, $ender) {
			$source->removeListener('end', $ender);
		});
		 */

        return Util::pipe($this, $dest, $options);
    }
}
