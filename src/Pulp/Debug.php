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
		$lg = 'pulp-debug: <file>'.$data->getPathname().'</>';
		if ($this->verbose) {
			$lg .= PHP_EOL;
			$lg .= '        base: <file>'.$data->base.'</>'.PHP_EOL;
			$lg .= '    pathname: <file>'.$data->getPathname().'</>'.PHP_EOL;
			$lg .= '     partial: <file>'.$data->getPartialPathname().'</>';
		}
		$this->emit('log', [$lg]);
		$this->emit('data', [$data]);
	}
}
