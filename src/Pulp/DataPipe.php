<?php

namespace Pulp;
use \Evenement\EventEmitterTrait;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class DataPipe implements \React\Stream\DuplexStreamInterface {

	use \Evenement\EventEmitterTrait;

	public $writeCallback;
	public $endCallback;
	public $closed       = FALSE;
	public $delayedPush  = FALSE;
	protected $chunkList = [];

	public function __construct($writeCallable=NULL, $endCallable=NULL) {
		$this->writeCallback = $writeCallable;
		$this->endCallback   = $endCallable;
	}

	public function log($msg, $params=array()) {
		$this->emit('log', [$msg, $params]);
	}

	public function pause() {
	}

	public function push($data) {
		if ($this->delayedPush) {
			$this->chunkList[] = $data;
		} else {
			$this->emit('data', [$data]);
		}
	}

	public function end($data=null) {
		$this->flush();

		$cb = $this->endCallback;
		if (is_array($cb)) {
			call_user_func($cb, $this);
		} else if ($cb) {
			$cb($this);
		}

		$this->close();
		$this->emit('end');
	}

	public function close() {
		$this->closed = TRUE;
	}

	public function flush() {
		while ($chunk = array_pop($this->chunkList)) {
			$this->emit('data', [$chunk]);
		}
	}

	public function resume() {
		$this->flush();
	}

	public function isReadable() {
		return !$this->closed;
	}

	public function isWritable() {
		return TRUE;
	}

	//meant for sub-classing
	protected function _onWrite($data) {
	}

	public function write($data) {
		$cb = $this->writeCallback;
		if (is_array($cb)) {
			call_user_func($cb, $data, $this);
		} else if ($cb) {
			$cb($data, $this);
		}
		$this->_onWrite($data);

	}

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
		//bubble up log events until we get to something
		//that is connected to main Pulp object and can output
		$bubbler = function($data, $params=NULL) {
			$this->emit('log', [$data, $params]);
		};
		$dest->removeAllListeners('log');
		$dest->on('log', $bubbler);

        return Util::pipe($this, $dest, $options);
    }
}
