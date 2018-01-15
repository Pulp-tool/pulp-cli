<?php

namespace Pulp;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;
use Evenement\EventEmitterTrait;

class SourceList extends DataPipe {

	use EventEmitterTrait;

	public $sourceList;
	public $workdir = '.';

	public function __construct($sourceList, $opts=NULL) {
		$this->sourceList = $sourceList;
		if (!is_array($this->sourceList)) {
			$this->sourceList = array($this->sourceList);
		}
		if (!is_array($opts)) {
			$opts = array($opts);
		}
		if (array_key_exists('cwd', $opts)) {
			$this->workdir = $opts['cwd'];
		}
	}


	/**
	 * Usually called from futureTick after
	 * all pipes are setup
	 */
	public function resume() {
		foreach ($this->sourceList as $_src) {

			$stream = new \Pulp\Fs\GlobStream($_src);
			$stream->on('data', function($data) {
				$this->emit('data', [new Fs\VirtualFile($data)]);
			});
			$stream->findMatchingFiles();
		}

		$this->end();
	}
}
