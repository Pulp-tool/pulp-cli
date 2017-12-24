<?php
namespace Pulp\Task;

class Help {

	public function __invoke() {

		echo "Help: run php pulp.phar {task}\n";
		echo "define tasks in .pulp/config.php\n";
	}
}
