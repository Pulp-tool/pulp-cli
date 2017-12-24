<?php

namespace Pulp;

class Pulp {

	public $loop;
	public $watchList;

	public function __construct() {
		$this->loop = \React\EventLoop\Factory::create();
	}

	public function task($name, $deps, $callback=NULL) {
		if (is_callable($deps) && $callback == NULL) {
//			echo "Setting up callback as second param for task: $name...\n";
			$callback = $deps;
			$deps     = [];
		}
		if (!is_array($deps)) {
			$deps = array($deps);
		}

		$this->taskList[$name] = ['deps'=>$deps, 'callback'=>$callback];
	}

	public function watch($fileList, $opts=NULL) {
		$w =  new Watch($fileList, $this->loop);
		$this->watchList[] = $w;
		return $w;
	}

	public function dest($fileList, $opts=NULL) {
		$d =  new DestList($fileList, $this->loop);
		$this->loop->futureTick(function() use($d) {
			$d->resume();
		});
		return $d;
	}

	public function src($fileList, $opts=NULL) {
		$s =  new SourceList($fileList, $this->loop);
		$this->loop->futureTick(function() use($s) {
			$s->resume();
		});
		return $s;
	}

	public function run() {
	//	$this->loop->
	}

	public function exec($name, $params=NULL) {
		if (!array_key_exists($name, $this->taskList)) {
			throw new \Exception('unknown task: '.$name);
		}
		$task = $this->taskList[$name];
		foreach ($task['deps'] as $_dep) {
			if (!array_key_exists($_dep, $this->taskList)) {
				throw new \Exception('unknown task '.$_dep.' as dependency of: '.$name);
			}
			$dep = $this->taskList[$_dep];
			$_cb = $dep['callback'];
			if (is_callable( [$_cb, 'call'])) {
				$_cb->call($this);
			} else {
				$_cb();
			}
		}
		$cb = $task['callback'];
		if (is_callable( [$cb, 'call'])) {
			$cb->call($this);
		} else {
			$cb();
		}


		$loop = $this->loop;
		$wl   = $this->watchList;
		/*
		$this->loop->addPeriodicTimer(1.0, function () use ($wl, $loop) {
			foreach ($wl as $_w) {
				stream_set_blocking($_w->inotifyFd, FALSE);
//				$ievent = \inotify_read($_w->inotifyFd);
//				if ($ievent !== FALSE) {
//					var_dump($ievent);
//				}
			}
		//	    $loop->cancelTimer($timer);
		//		    echo 'Done' . PHP_EOL;
		});
		 */
		$this->loop->run();


	}
}
