pulp
===

pulp is gulp in PHP.

```php
$p = new \Pulp\Pulp();

$p->task('build', ['log'], function() {
	echo "Building...\n";
});

$foo = time();

$p->task('log', function() use ($foo) {
	echo "Logging with variable: $foo...\n";
});

$p->task('watch', function() use($p) {
	$p->watch( ['src/**/*.php', 'foo/**/*.php'])->on('change', function($file) use ($p) {
		$p->src(['src/', 'foo/'])
			->pipe(new \Pulp\DataPipe(function($data) {
				echo "Data Pipe got "; var_dump($data);
			})
		);
			
		echo "File changed: ".$file." ...\n";
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

Setup your project
===
Run pulp init-project to create a .pulp directory and .pulp/config.php file

Write your tasks
===
Edit the .pulp/config.php file and create tasks for building your project
