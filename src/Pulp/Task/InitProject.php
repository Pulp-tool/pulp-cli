<?php

namespace Pulp\Task;

class InitProject extends \Pulp\Task {

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
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:Pulp-tool/pulp-livereload.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:Pulp-tool/pulp-less.git"
        }
    ],
    "require": {
        "pulp-livereload": "*",
        "pulp-less": "*"
     }
}
EOL;
	file_put_contents('.pulp/composer.json', $sample);
	echo "Created file .pulp/composer.json\n";
	echo "Run composer install from inside .pulp directory.\n";
	}
}
