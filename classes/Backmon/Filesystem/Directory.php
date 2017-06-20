<?php
namespace Backmon\Filesystem;

use \Backmon\Logger;

class Directory {
	public $directory = "";
	public $files = [];

	public function __construct() {
	}

	public function run($context = array()) {
		// make it work on Windows and Linux
		$stack = preg_split("/[\\|\/]+/", $this->directory);

		// remove last item if empty
		if (sizeof($stack) > 0 && $stack[sizeof($stack) - 1] == "") {
			array_pop($stack);
		}

		$this->visitDirectory($context, $context['_.root'], $stack);
		$this->execute($context);
	}

	private function visitDirectory($context, $dir, $stack) {
		$context['_.directory.root'] = $dir;
		
		Logger::info("Visiting directory '" . $dir . "'");
		
		if (sizeof($stack) == 0) {
			$this->visitFiles($context, $dir);
			return;
		}
		
		$var = \Backmon\Dsl\Variable::create(array_shift($stack));

		if ($var->variable) {
			if (!is_dir($dir)) {
				throw new \Backmon\Exception("Directory definition seems to be wrong. Directory '$dir' does not exist in template {$this->directory}");
			}
		
			$it = new \DirectoryIterator($dir);

			foreach ($it as $file) {
				if ($it->isDir() && !$it->isDot()) {
					$context[$var->value] = $it->getFilename();
					$this->visitDirectory($context, $dir . "/" . $context[$var->value], $stack);
				}
			}
		}
		else {
			$this->visitDirectory($context, $dir . "/" . $var->value, $stack);
		}
		
	}

	private function visitFiles($context, $dir) {
		foreach ($this->files as $fileDefinition) {
			$fileDefinition->collect($context, $dir);		
		}
	}

	private function execute(array $context) {
		foreach ($this->files as $fileDefinition) {
			$fileDefinition->execute($context);
		}
	}
}