<?php
namespace Pulp\Task;

class Help extends \Pulp\Task {

	public function __invoke($pulp) {

		$this->output("

(_)(_)(_)(_)_                (_)(_)                     
(_)        (_) _         _      (_)    _  _  _  _       
(_) _  _  _(_)(_)       (_)     (_)   (_)(_)(_)(_)_     
(_)(_)(_)(_)  (_)       (_)     (_)   (_)        (_)    
(_)           (_)       (_)     (_)   (_)        (_)    
(_)           (_)_  _  _(_)_  _ (_) _ (_) _  _  _(_)    
(_)             (_)(_)(_) (_)(_)(_)(_)(_)(_)(_)(_)      
                                      (_)               
");

		$this->output("Help: run php pulp.phar <name>{task}</>");
		$this->output("define tasks in <file>.pulp/config.php</>");
		$this->output("");
		$this->output("Available tasks:");

		foreach ( array_keys($pulp->taskList) as $name ) {
			$this->output("<name>".$name."</>");
		}
	}
}
