pulp
===

pulp is gulp in PHP.

```php
$p = new \Pulp\Pulp();

//The log task is a dependency of the build task.
//This will run the log task before running the build task

$p->task('build', ['log'], function() {
	echo "Building...\n";
});


//The log task is a regular closure

$foo = time();
$p->task('log', function() use ($foo) {
	echo "Logging with variable: $foo...\n";
});

//The watch method will either poll or use inotify to detect file changes

$p->task('wait-and-pipe', function() use($p) {
	$p->watch( ['src/**/*.php', 'foo/**/*.php'])->on('change', function($file) use ($p) {
		//this will pipe *all* files in src/ and foo/, not just the ones that changed
		$p->src(['src/', 'foo/'])
			->pipe(new \Pulp\DataPipe(function($data) {
				echo "Data Pipe got "; var_dump($data);
			})
		);
			
		echo "This file triggered the change event: ".$file." ...\n";
	});
});
```

```bash
pulp.phar build
pulp.phar watch
```

Install the phar file
===
Download the phar file, make it executable and move it to /usr/local/bin/pulp
```bash
wget https://github.com/Pulp-tool/pulp-cli/releases/download/0.4.0/pulp.phar
chmod a+x pulp.phar
mv pulp.phar /usr/local/bin
```

Setup your project
===
Run pulp init-project to create a .pulp directory, a config.php file and a composer.json file.

```bash
.pulp/
   config.php # <== tasks go here
   composer.json # <== pulp plugins go here
```

CD into the .pulp directory and run *composer install*

Write your tasks
===
Edit the .pulp/config.php file and create tasks for building your project
