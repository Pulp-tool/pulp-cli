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
	public $sourceList    = [];
	public $workdir;

	public function __construct($loop, $fileList, $opts=NULL) {
		$this->loop       = $loop;
		$this->sourceList = $fileList;

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


		if (extension_loaded('inotify')) {
			$this->setupInotify();
		} else {
			$this->setupPoller();
		}
	}

	public function setupPoller($loop=NULL, $fileList=NULL) {
		if ($loop != NULL) {
			$this->loop = $loop;
		}
		if ($fileList != NULL) {
			$this->sourceList = $fileList;
			if (!is_array($this->sourceList)) {
				$this->sourceList = array($this->sourceList);
			}
		}

		foreach ($this->sourceList as $_glob) {
			$s =  new SourceList($this->loop, $_glob, []);
			$s->on('log', function($data,$params) {
				$this->log($data, $params);
			});
			$s->pipe( new Fs\Changed())
				->on('data', function($file) {
					$this->emit('change', [$file]);
			});
			$this->fsWatcherList[ $_glob ] = $s;
		}

		$watcherList = $this->fsWatcherList;

		$this->loop->addPeriodicTimer(1.03, function() use ($watcherList) {

			foreach ($watcherList as $_glob => $_src) {
				//TODO: remove existing watchers
				$_src->closed  = FALSE;
				$_src->started = FALSE;
				$_src->resume();
			}
		});
	}

	public function setupInotify($loop=NULL, $fileList=NULL) {
		if ($loop != NULL) {
			$this->loop = $loop;
		}
		if ($fileList != NULL) {
			$this->sourceList = $fileList;
			if (!is_array($this->sourceList)) {
				$this->sourceList = array($this->sourceList);
			}
		}

		$this->inotifyFd = \inotify_init();
		foreach ($this->sourceList as $_filePattern) {
			$gs = new Fs\GlobStream($_filePattern);

			$wd = \inotify_add_watch($this->inotifyFd, $gs->root, IN_MODIFY|IN_CLOSE_WRITE);
			$this->wdToGlob[$wd]     = $gs;

			/*
			$globParent = $this->findGlobParent($_filePattern);
			$realParent =  realpath($globParent).'/';
			$this->wdToRealPath[$wd] = $realParent;
			$this->wdToGlob[$wd]     = $_filePattern;
			 */
		}

		$wdToRealPath = $this->wdToRealPath;
		$wdToGlob     = $this->wdToGlob;

		$this->loop->addReadStream($this->inotifyFd, function($stream) use ($fileList, $wdToRealPath, $wdToGlob) {
			$ievent = \inotify_read($stream);
			if ($ievent == FALSE) {
				return;
			}

			//var_dump($ievent);
			foreach ($ievent as $_in) {
				//determine if filename matches pattern
				$gs       = $wdToGlob[$_in['wd']];
				$filename = $gs->root.$_in['name'];
				$file = new Fs\VirtualFile($filename);
				if ($gs->fileMatchesGlob($file->getPathname())) {
					if ($_in['mask'] & IN_MODIFY) {
						$this->emit('change', [$file]);
					}
				}
			}
		});
	}
}
