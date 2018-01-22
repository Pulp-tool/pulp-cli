<?php

namespace Pulp\Task;

class InitProject extends \Pulp\Task {

	public function __invoke($pulp) {
		exec ('mkdir .pulp');
		$pulp->log("Created directory <file>.pulp</>");
		$sample = $this->getConfig();
		file_put_contents('.pulp/config.php', $sample);
		$pulp->log("Created file <file>.pulp/config.php</>");

		$sample = $this->getComposer();
		file_put_contents('.pulp/composer.json', $sample);
		$pulp->log("Created file <file>.pulp/composer.json</>");
		$pulp->log("Run <name>composer install</> from inside .pulp directory.");
	}

	public function getConfig() {
$sample = <<<EOL
<?php                                                                                                                                                                 
use \Pulp\LiveReload as lr;
use \Pulp\Less       as less;
\$p = new \Pulp\Pulp();

\$watchDirsCode = ['src/**/*.php'];
\$watchDirsCss  = ['public/templates/**/*.less'];
\$outputDirCss  =  'public/templates/dist/css/';


\$p->task('watch', function() use(\$p, \$watchDirsCode, \$watchDirsCss, \$outputDirCss) {
	\$lr = new lr();
	\$lr->listen(\$p->loop);

	\$p->watch( \$watchDirsCode )->on('change', function(\$file) use (\$p) {
		\$lr->fileChanged(\$file);
	});

	\$p->watch( \$watchDirsCss )->on('change', function(\$file) use (\$p, \$watchDirsCss, \$outputDirCss) {
		\$p->src(\$watchDirsCss)
			->pipe(new less( ['compress'=>TRUE] ))
			->pipe(\$p->dest(\$outputDirCss))
			->pipe(\$lr);
	});
});

EOL;

		return $sample;
	}

	public function getComposer() {
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
        },
        {
            "type": "vcs",
            "url": "git@github.com:Pulp-tool/pulp-sass.git"
        }
    ],
    "require": {
        "pulp-livereload": "*",
        "pulp-less": "*",
        "pulp-sass": "*"
    },
    "minimum-stability": "dev"
}
EOL;

		return $sample;
	}
}
