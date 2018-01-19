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
			$this->setupInotify($fileList, $loop);
		} else {
			$this->setupPoller($fileList, $loop);
		}
	}

	public function setupPoller($fileList, $loop) {

		foreach ($fileList as $_glob) {
			$s =  new SourceList($loop, $_glob, []);
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

		$loop->addPeriodicTimer(1.03, function() use ($watcherList, $loop) {

			foreach ($watcherList as $_glob => $_src) {
				//TODO: remove existing watchers
				$_src->closed  = FALSE;
				$_src->started = FALSE;
				$_src->resume();
			}
		});
	}

	public function setupInotify($fileList, $loop) {

		$this->inotifyFd = \inotify_init();
		foreach ($fileList as $_filePattern) {
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

		$loop->addReadStream($this->inotifyFd, function($stream) use ($fileList, $wdToRealPath, $wdToGlob) {
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
