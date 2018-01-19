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
		if ($this->isGlob($glob)) {
			$this->root         = $this->findGlobParent($glob);
		} else {
			$this->root         = $this->findStaticParent($glob);
		}
		$this->globPattern  = $glob;
		$this->regexPattern = $this->compileRegex($this->globPattern);
	}

	public function getGlobParent() {
		return $this->root;
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

			if ( $this->fileMatchesGlob($filename)) {
				$this->emit('data', [$filename]);
			} else {

			}
		}
	}

	public function findMatchingFilesAsync() {
		$it = new \RecursiveDirectoryIterator($this->root);
		//this iterator doesn't give you directories as entries but
		//it gives the sub virtual file of '.' and '..' and you're supposed
		//to figure out if it's a directory from there and try to deal with it
		//in some unusual fashion
		foreach(new \RecursiveIteratorIterator($it) as $filename => $file) {
			$entry = $file->getFilename();
			if ($entry == '..') { continue; }
			if ($entry == '.' ) { $filename = $file->getPath(); }

			if ( $this->fileMatchesGlob($filename)) {
				yield $filename;
			}
		}
	}


	public function write($data) {
		if ( $this->fileMatchesGlob($data)) {
			$this->emit('write', $data);
		}
	}

	public function isGlob($glob) {
		if (strpos($glob, '*')) {
			return TRUE;
		}
		if (strpos($glob, '?')) {
			return TRUE;
		}
		return FALSE;
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
			if (strpos($_part, '?') !== FALSE) {
				return strlen($ret) ? $ret : '.';
			}

			$ret .= $_part.'/';
		}
		return $ret;
	}

	public function findStaticParent($glob) {
		if (substr($glob, -1) == '/') {
			return $glob;
		}

		$fileParts = explode('/', rtrim($glob, '/'));
		array_pop($fileParts);
		return implode('/', $fileParts);
	}


	public function fileMatchesGlob($name, $glob=NULL) {
		if ($glob != NULL) {
			$regex = $this->compileRegex($glob);
		} else {
			$regex = $this->regexPattern;
		}
		$matches   = [];
		$x = preg_match($regex, $name, $matches);
		return $x;
	}

	public function compileRegex($glob) {
		$globParts = explode('/', $glob);
		$regex     = '~';

		foreach ($globParts as $_p) {
			//matches anything in the current directory
			//OR any directory below it
			if (strpos($_p, '**') !== FALSE) {
				//$regex .= '([./]*)';
				//$regex .= '([\w\-\_]+)|';
				$regex .= '(.)*';
				continue;
			}

			//matches anything in this root dir
			//with just *.txt turned into [\w]*\.txt
			//treat ? as exactly one single character like [\w]
			if (strpos($_p, '*') !== FALSE ||
			    strpos($_p, '?') !== FALSE) {
				$_p     = str_replace('.', '\.', $_p);
				$_p     = str_replace('*', '', $_p);
				$_p     = str_replace('?', '[\w]', $_p);
				$regex .= '([\w\-\_]*'.$_p.')';
				continue;
			}

			if (strlen($regex) > 1) {
				$regex .= '/';
			}


			$regex .= '('.$_p.')';
		}
		$regex .= '~';
		return $regex;
	}
}
