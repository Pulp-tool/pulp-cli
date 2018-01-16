<?php

namespace Pulp;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;
use Evenement\EventEmitterTrait;

class DestList extends DataPipe {

	use EventEmitterTrait;

	public $destList;
	public $workdir = '.';

	public function __construct($destList, $opts=NULL) {
		$this->destList = $destList;
		if (!is_array($this->destList)) {
			$this->destList = array($this->destList);
		}
		if (!is_array($opts)) {
			$opts = array($opts);
		}
		if (array_key_exists('cwd', $opts)) {
			$this->workdir = $opts['cwd'];
		}
	}

	/**
	 * Take a virtual file, write its contents to all dest folders.
	 * update the virtual files pathname to the dest folder.
	 * signal a data event downstream.
	 */
	public function _onWrite($file) {
		foreach ($this->destList as $_dest) {
			$outputPath = Fs\Path\resolve($this->workdir, $_dest, $file->getPartialFilename() );
			$this->mkdirp($outputPath);
			file_put_contents($outputPath, $file->getContents());
			$file->setPathname($outputPath);
			$this->emit('data', [$file]);
		}
	}

	public function mkdirp($outputPath) {
		$finfo = new \SplFileInfo($outputPath);
		$pinfo = $finfo->getPathInfo();
		@mkdir($pinfo->getPathname(), 0755, TRUE);
	}
}
