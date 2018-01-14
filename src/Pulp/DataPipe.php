<?php

namespace Pulp;
use \Evenement\EventEmitterTrait;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class DataPipe implements \React\Stream\DuplexStreamInterface {

	use \Evenement\EventEmitterTrait;

	public $writeCallback;
	public $endCallback;
	public $closed = FALSE;

	public function __construct($writeCallable=NULL, $endCallable=NULL) {
		$this->writeCallback = $writeCallable;
		$this->endCallback   = $endCallable;
	}

	public function log($msg, $params=array()) {
		$this->emit('log', [$msg, $params]);
	}

	public function pause() {
	}

	public function end($data=null) {
		$cb = $this->endCallback;
		if ($cb) {
			$cb($this);
		}

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
		if ($cb) {
			$cb($data, $this);
		}
	}

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
		//bubble up log events until we get to something
		//that is connected to main Pulp object and can output
		$dest->on('log', function($data, $params=NULL) {
			$this->emit('log', [$data, $params]);
		});

        return Util::pipe($this, $dest, $options);
    }
}
