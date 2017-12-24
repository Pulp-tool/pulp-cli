<?php

namespace Pulp;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;
use Evenement\EventEmitterTrait;

class SourceList implements \React\Stream\ReadableStreamInterface {

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
	 * check if source dir is readable 
	 */
	public function isReadable() {
		return TRUE;
	}

	public function pause() {
		//do nothing
	}


    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        return Util::pipe($this, $dest, $options);
    }

	/**
	 * Usually called from futureTick after
	 * all pipes are setup
	 */
	public function resume() {
		foreach ($this->sourceList as $_src) {

			$src = $this->findGlobParent($_src);

			$src = realpath($this->workdir.'/'.$src).'/';
			$d = \dir($src);
			while(FALSE !== ($entry = $d->read())) {
				if (is_dir($src.$entry)) { continue; }
				$this->emit('data', [$src.$entry]);
			}
		}
	}

	public function close() {
		$this->closed = TRUE;
	}

	public function findGlobParent($glob) {
		$ret = '';
		$fileParts = explode('/', rtrim($glob, '/'));
		foreach ($fileParts as $_part) {
			if (strpos($_part, '*') !== FALSE) {
				return strlen($ret) ? $ret : '.';
			}
			$ret .= $_part.'/';
		}
		return $ret;
	}
}
