<?php

namespace Pulp\Fs;
use Pulp\DataPipe;

class Changed extends DataPipe {

	public $fileToMTimeList = [];

	public function __construct() {
	}

	public function write($file) {
		$entry = $file->getPathname();
		$stat = stat($entry);
		if (!array_key_exists($entry, $this->fileToMTimeList)) {
			$this->setMTime($entry, $stat['mtime']);
			return;
		}

		if ($this->fileToMTimeList[$entry] != $stat['mtime']) {
			$this->setMTime($entry, $stat['mtime']);
			$this->push($file);
		}
	}

	public function setMTime($entry, $mtime) {
		$this->fileToMTimeList[ $entry ] = $mtime;
	}
}
