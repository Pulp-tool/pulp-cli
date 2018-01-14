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
		//bubble up log events until we get to something
		//that is connected to main Pulp object and can output
		$dest->on('log', function($data) {
			$this->emit('log', [$data]);
		});

        return Util::pipe($this, $dest, $options);
    }
}
