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
	/**
	 * @deprecated
	 */
	public $loop    = NULL;
	public $started = FALSE;
	protected $generator;

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
		}
		if ($this->started) {
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
		if ($this->generator == NULL) {
			$this->generator = $this->readOneFile();
			$this->generator->rewind();
		}
		if (!$this->generator->valid()) {
			$this->end();
			$this->generator = NULL;
			return;
		}
		$file = $this->generator->current();
		$this->emit('data', [$file]);
		$this->generator->next();

		/*
        if (!$this->closed && is_object($this->loop)) {
			$this->loop->futureTick([$this, 'tickSourceFile']);
		}
		 */
		if ($this->closed) {
			$this->generator = NULL;
		}
	}

	/**
	 * @generator
	 */
	public function readOneFile() {
		foreach ($this->sourceList as $_src) {

			//handle objects of Fs\VirtualFiles
			//these can be injected from watch()
			if (is_object($_src) && $_src instanceof Fs\VirtualFile) {
				yield $_src;
				continue;
			}
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
