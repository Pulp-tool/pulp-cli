<?php

namespace Pulp\Fs;

use \Evenement\EventEmitterTrait;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class GlobStream extends \Pulp\DataPipe {

	use \Evenement\EventEmitterTrait;

	public $globPattern;
	public $root;
	public $closed = FALSE;

	public function __construct($glob, $opts=[]) {
		$this->root        = $this->findGlobParent($glob);
		$this->globPattern = $glob;
	}

	public function findMatchingFiles() {
		$it = new \RecursiveDirectoryIterator($this->root);
		//this iterator doesn't give you directories as entries but
		//it gives the sub virtual file of '.' and '..' and you're supposed
		//to figure out if it's a directory from there and try to deal with it
		//in some unusual fashion
		foreach(new \RecursiveIteratorIterator($it) as $filename => $file) {
			$entry = $file->getFilename();
			if ($entry == '..') { continue; }
			if ($entry == '.' ) { $filename = $file->getPath(); }

			if ( $this->fileMatchesGlob($filename, $this->globPattern)) {
				$this->emit('data', [$filename]);
			} else {

			}
		}
	}


	public function write($data) {
		if ( $this->fileMatchesGlob($data, $this->globPattern)) {
			$this->emit('write', $data);
		}
	}

	/**
	 * Find the static top part of a dir/file glob
	 *
	 * foo/bar/ => foo/bar/
	 * foo/*.css => foo/
	 * foo/** / *.css => foo/
	 * *.css => .
	 */
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

	public function fileMatchesGlob($name, $glob) {
		$matches   = [];
		$globParts = explode('/', $glob);
		$regex     = '~';

		foreach ($globParts as $_p) {
			if (strpos($_p, '**') !== FALSE) {
				//$regex .= '([./]*)';
				$regex .= '([\w\-\_]+)|';
				continue;
			}
			if (strpos($_p, '*') !== FALSE) {
				$_p     = str_replace('.', '\.', $_p);
				$regex .= '((.+)'.$_p.')';
				continue;
			}
			$regex .= '('.$_p.')/';
		}
		$regex .= '~';

		$x = preg_match($regex, $name, $matches);
		/*
		echo "Glob:  $glob \n";
		echo "Regex: $regex \n";
		echo "Name:  $name \n";
		var_dump($x);
		var_dump($matches);
		*/
		return $x;
	}
}
