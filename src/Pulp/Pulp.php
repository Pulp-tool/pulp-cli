<?php

namespace Pulp;
use \React\Stream\ReadableStreamInterface;

class Pulp {

	public $loop;
	public $watchList;
	public $sourceList = [];
	public $color = TRUE;

	public function __construct() {
		$this->loop = \React\EventLoop\Factory::create();
	}

	public function colorize($msg) {
		if (!$this->color) {
			$msg = str_replace('<meta>', '', $msg);
			$msg = str_replace('</>', '', $msg);
			return $msg;
		}

		$msg = str_replace('<meta>', "\033".'[90m', $msg);
		$msg = str_replace('</>', "\033".'[0m', $msg);

		$msg = str_replace('<file>', "\033".'[35m', $msg);

		$msg = str_replace('<name>', "\033".'[96m', $msg);
		return $msg;
	}

	public function output($msg, $params = array()) {
		$msg = vsprintf($msg, $params);
		$msg = sprintf ("[<meta>%s</>] %s\n", date('H:i:s'), $msg);
		echo $this->colorize($msg);
	}

	public function log($level, $msg, $params = array()) {
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
		return $d;
	}

	public function src($fileList, $opts=NULL) {
		$s =  new SourceList($fileList);
		$s->on('log', function($data,$params) {
			$this->output($data, $params);
		});

		$this->sourceList[] = $s;
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

			$this->output('Starting task \'<name>'.$_dep.'</>\'');
			$start = microtime(1);
			if (is_callable( [$_cb, 'call'])) {
				$pipe = $_cb->call($this);
			} else {
				$pipe = $_cb($this);
			}
			if($pipe instanceof ReadableStreamInterface) {
				$pipe->on('end', function() use($start, $_dep) {
					$this->output('Finished task \'<name>'.$_dep.'</>\' (took: %0.3f ms)', [((microtime(1)-$start)*1000)]);
				});
			} else {
				$this->output('Finished task \'<name>'.$_dep.'</>\' (took: %0.3f ms)', [((microtime(1)-$start)*1000)]);
			}
		}


		try {
			$this->output('Starting task \'<name>'.$name.'</>\'');
			$start = microtime(1);
			$cb = $task['callback'];
			if (is_callable( [$cb, 'call'])) {
				$pipe = $cb->call($this);
			} else {
				$pipe = $cb($this);
			}
			if($pipe instanceof ReadableStreamInterface) {
				$pipe->on('end', function() use($start, $name) {
					$this->output('Finished task \'<name>'.$name.'</>\' (took: %0.3f ms)', [((microtime(1)-$start)*1000)]);
				});
			} else {
				$this->output('Finished task \'<name>'.$name.'</>\' (took: %0.3f ms)', [((microtime(1)-$start)*1000)]);
			}
		} catch (\Exception $e) {
			$this->output('Error: '.$e->getMessage());
		}


		$this->loop->futureTick(function() {
			$this->flushReadable();
		});


		try {
			$this->loop->run();
		} catch (\Exception $e) {
			$this->output('Error: '.$e->getMessage());
		}
	}

	public function flushReadable() {
		foreach ($this->sourceList as $_s) {
			if (!$_s->closed) {
				$this->loop->futureTick(function() use($_s) {
					$_s->resume();
				});
			}
		}
	}
}
