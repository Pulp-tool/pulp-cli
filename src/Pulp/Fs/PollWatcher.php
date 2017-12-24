<?php

namespace Pulp\Fs;

class PollWatcher {

	public $root;
	public $recursive       = FALSE;
	public $fileToMTimeList = [];

	public function __construct($root, $recursive=FALSE) {
		$this->root      = $root;
		$this->recursive = $recursive;
		$this->populateMTime();
	}

	public function findChangedFiles() {
		$deltaList = [];

		$d = dir($this->root);
		while(FALSE !== ($entry = $d->read())) {
			if ($entry == '.' || $entry == '..') { continue; }
			$stat = stat($this->root.$entry);
			if (!array_key_exists($entry, $this->fileToMTimeList)) {
				$deltaList[] = $this->root.$entry;
				$this->setMTime($entry, $stat['mtime']);
			}

			if ($this->fileToMTimeList[$entry] != $stat['mtime']) {
				$deltaList[] = $this->root.$entry;
				$this->setMTime($entry, $stat['mtime']);
			}
		}
		return $deltaList;
	}

	public function populateMTime() {
		$d = dir($this->root);

		while(FALSE !== ($entry = $d->read())) {
			if ($entry == '.' || $entry == '..') { continue; }
			$stat = stat($this->root.$entry);
			$this->setMTime($entry, $stat['mtime']);
		}
	}

	public function setMTime($entry, $mtime) {
		$this->fileToMTimeList[ $entry ] = $mtime;
	}
}
