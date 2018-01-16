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
	public $opts    = [];

	public function __construct($sourceList, $opts=NULL) {
		$this->sourceList = $sourceList;
		if (!is_array($this->sourceList)) {
			$this->sourceList = array($this->sourceList);
		}
		if (!is_array($opts)) {
			$opts = array($opts);
		}
		if (!array_key_exists('cwd', $opts)) {
			$opts['cwd'] = getcwd();
		}
		$this->workdir = $opts['cwd'];
		$this->opts    = $opts;
	}


	/**
	 * Usually called from futureTick after
	 * all pipes are setup
	 */
	public function resume() {
		foreach ($this->sourceList as $_src) {

			$stream = new \Pulp\Fs\GlobStream($_src);
			$base   = $stream->getGlobParent();
			$opts   = $this->opts;
			if (!array_key_exists('base', $opts)) {
				$opts['base'] = $base;
			}
			$stream->on('data', function($data) use($opts) {
				if (is_dir($data)) {
					return;
				}
				$file = new Fs\VirtualFile($data, $opts);
				$this->emit('data', [$file]);
			});
			$stream->findMatchingFiles();
		}

		$this->end();
	}
}
