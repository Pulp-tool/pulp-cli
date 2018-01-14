<?php

namespace Pulp;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;
use Evenement\EventEmitterTrait;

class Debug extends DataPipe {

	public function __construct() {
	}

	public function write($data) {
		$this->emit('log', ['Got: <file>'.$data->getFilename().'</>']);
		$this->emit('data', [$data]);
	}
}
