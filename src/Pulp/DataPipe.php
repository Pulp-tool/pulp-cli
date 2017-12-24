<?php

namespace Pulp;
use \Evenement\EventEmitterTrait;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class DataPipe implements \React\Stream\DuplexStreamInterface {

	use \Evenement\EventEmitterTrait;

	public $writeCallback;

	public function __construct($writeCallable) {
		$this->writeCallback = $writeCallable;
	}

	public function pause() {
	}
	public function end($data=null) {
	}
	public function close() {
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
        return Util::pipe($this, $dest, $options);
    }
}
