<?php

namespace Pulp\Task;

class InitProject {


	public function __invoke() {
	exec ('mkdir .pulp');
	echo "Created directory .pulp\n";
$sample = <<<EOL
<?php                                                                                                                                                                 
\$p = new \Pulp\Pulp();

#\$p->task('build', ['log'], function() {
#   echo "Building...\\n";
#});
#
#\$t = time();
#
#\$p->task('log', function() use (\$t) {
#   echo "Logging with closure variable: \$t...\\n";
#});
EOL;
	file_put_contents('.pulp/config.php', $sample);
	echo "Created file .pulp/config.php\n";

$sample = <<<EOL
{
    "require": {
		"pulp/watch": "*"
     }
}
EOL;
	file_put_contents('.pulp/composer.json', $sample);
	echo "Created file .pulp/composer.json\n";
	echo "Run composer install from inside .pulp directory.\n";
	}
}
