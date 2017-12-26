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

	public function write($data) {
		foreach ($this->destList as $_dest) {
			file_put_contents($this->workdir.'/'.$_dest, $data);
			$this->emit('end', [new \SplFileInfo($this->workdir.'/'.$_dest)]);
		}
	}
}
