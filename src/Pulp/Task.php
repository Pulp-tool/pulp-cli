<?php

namespace Pulp;

class Task {

	public $color = TRUE;

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
}
