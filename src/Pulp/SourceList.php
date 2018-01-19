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
	public $loop    = NULL;
	public $started = FALSE;

	public function __construct($loop, $sourceList, $opts=NULL) {
		$this->loop = $loop;

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
	 */
	public function resume() {
		if (!$this->closed && !$this->started) {
			$this->started = TRUE;
			$this->tickSourceFile();
		}
	}

	/**
	 * Tick one source file, schedule next tick
	 *
	 * This will emit "data" for each file in the
	 * glob stream and reschedule itself for next loop tick.
	 */
	public function tickSourceFile() {
		static $generator = NULL;
		if ($generator == NULL) {
			$generator = $this->readOneFile();
			$generator->rewind();
		}
		if (!$generator->valid()) {
			$this->end();
			$generator = NULL;
			return;
		}
		$file = $generator->current();
		$this->emit('data', [$file]);
		$generator->next();

        if (!$this->closed) {
			$this->loop->futureTick([$this, 'tickSourceFile']);
		}
		if ($this->closed) {
			$generator = NULL;
		}
	}

	/**
	 * @generator
	 */
	public function readOneFile() {
		foreach ($this->sourceList as $_src) {

			$stream = new \Pulp\Fs\GlobStream($_src);
			$base   = $stream->getGlobParent();
			$opts   = $this->opts;
			if (!array_key_exists('base', $opts)) {
				$opts['base'] = $base;
			}
			foreach ($stream->findMatchingFilesAsync() as $fname) {
				if (is_dir($fname)) {
					continue;
				}
				yield new Fs\VirtualFile($fname, $opts);
			}
		}
	}
}
