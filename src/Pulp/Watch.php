<?php

namespace Pulp;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Evenement\EventEmitter;

//class Watch implements EventEmitterInterface {
class Watch extends EventEmitter {

	public $inotifyFd;
	public $loop;
	public $wdToRealPath  = [];
	public $wdToGlob      = [];
	public $fsWatcherList = [];
	public $cwd;

	public function __construct($fileList, $loop) {

		$this->cwd = getcwd();
		if (extension_loaded('inotify')) {
			$this->setupInofity($fileList, $loop);
		} else {
			$this->setupPoller($fileList, $loop);
		}
	}

	public function setupPoller($fileList, $loop) {

		foreach ($fileList as $_filePattern) {
			$globParent = $this->findGlobParent($_filePattern);
			$realParent = realpath($globParent);
			if (!$realParent) {
				throw new \Exception("No such directory found for glob pattern: $_filePattern");
			}
			$realParent .= '/';
			$this->fsWatcherList[$_filePattern]   = new Fs\PollWatcher($realParent);
		}

		$watcherList = $this->fsWatcherList;

		$loop->addPeriodicTimer(1.03, function() use ($watcherList) {

			foreach ($watcherList as $_glob=>$_w) {
				$changedList = $_w->findChangedFiles();
			}

			foreach ($changedList as $filename) {
				$filenameShort = ltrim(str_replace($this->cwd, '', $filename), '/');
				if ($this->fileMatchesGlob($filenameShort, $_glob)) {
					$this->emit('change', [new \SplFileInfo($filename)]);
				}
			}
		});
	}

	public function setupInotify($fileList, $loop) {

		$this->inotifyFd = \inotify_init();
		foreach ($fileList as $_filePattern) {
			$globParent = $this->findGlobParent($_filePattern);
			$realParent =  realpath($globParent).'/';
			var_dump($realParent);
			$wd = \inotify_add_watch($this->inotifyFd, $realParent, IN_MODIFY|IN_CLOSE_WRITE);
			$this->wdToRealPath[$wd] = $realParent;
			$this->wdToGlob[$wd]     = $_filePattern;
		}

		$wdToRealPath = $this->wdToRealPath;
		$wdToGlob     = $this->wdToGlob;

		$loop->addReadStream($this->inotifyFd, function($stream) use ($fileList, $wdToRealPath, $wdToGlob) {
			$ievent = \inotify_read($stream);
			if ($ievent == FALSE) {
				return;
			}

			//var_dump($ievent);
			foreach ($ievent as $_in) {
				//determine if filename matches pattern
				$parentPath = $wdToRealPath[$_in['wd']];
				$filename = $parentPath.$_in['name'];
				if ($this->fileMatchesGlob($_in['name'], $wdToGlob[$_in['wd']])) {
					if ($_in['mask'] & IN_MODIFY) {
						$this->emit('change', [new \SplFileInfo($filename)]);
					}
				}
			}
		});
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
		$regex     = '|(foo/)([./]*)((.+)\.css)|';
		$matches   = [];
		$globParts = explode('/', $glob);
		$regex     = '|';
		foreach ($globParts as $_p) {
			if (strpos($_p, '**') !== FALSE) {
				$regex .= '([./]*)';
				continue;
			}
			if (strpos($_p, '*') !== FALSE) {
				$_p     = str_replace('.', '\.', $_p);
				$regex .= '((.+)'.$_p.')';
				continue;
			}
			$regex .= '('.$_p.')/';
		}
		$regex .= '|';

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
