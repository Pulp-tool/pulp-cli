<?php

namespace Pulp;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;
use Evenement\EventEmitterTrait;

class Debug extends DataPipe {

	public $verbose = FALSE;

	public function __construct($opts=[]) {
		if (array_key_exists('verbose', $opts)) {
			$this->verbose = $opts['verbose'];
		}

	}

	public function write($data) {
		$this->emit('log', ['pulp-debug: <file>'.$data->getFilename().'</>']);
		if ($this->verbose) {
			$this->emit('log', ['      base: <file>'.$data->base.'</>']);
			$this->emit('log', ['  pathname: <file>'.$data->getPathname().'</>']);
			$this->emit('log', ['   partial: <file>'.$data->getPartialPathname().'</>']);
		}
		$this->emit('data', [$data]);
	}
}
