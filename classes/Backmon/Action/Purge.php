<?php
namespace Backmon\Action;

use \Backmon\Action;
use \Backmon\Logger;

class Purge implements Action {
	private $_forcePurge = false;
	
	public function configure(\Backmon\Runner $runner) {
		$parameters = $runner->getParameters();
		
		if ($parameters['f']) {
			$this->_forcePurge = true;
		}
	}
	
	/**
	 * informational
	 * @return callable
	 */
	public function execute(\Backmon\Filesystem\File $fileDefinition, array $context = []) {
		$retentionPolicy = null;

		foreach ($fileDefinition->policies as $policy) {
			if ($policy instanceof \Backmon\Policy\Retention) {
				$retentionPolicy = $policy;
				break;
			}
		}

		if (!$retentionPolicy) {
			Logger::warn("Skipping " . $fileDefinition->name . ": no retention policy defined");
			return;
		}

		$keep = $policy->min < $policy->max ? $policy->max : $policy->min;

		if ($keep < 1) {
			$keep = 1;
		}
		
		Logger::info("Keeping $keep files in " . $fileDefinition->name . "");

		if (sizeof($fileDefinition->result) == 0) {
			Logger::warn("There are no files available for " . $fileDefinition->name);
		}
			
		while (list($baseDirectory, $files) = each($fileDefinition->result)) {
			Logger::info("Checking $baseDirectory");
			
			if ($keep > sizeof($files)) {
				Logger::warn("Collection $baseDirectory does only contain " . sizeof($files) . " but retention policy requires to keep $keep files");
				continue;
			}

			$remove = sizeof($files) - $keep;
			Logger::info("Removing latest files (keep=$keep, total=" . sizeof($files) . ", to_remove=". sizeof($remove) .")");

			for ($i = 0, $m = sizeof($files); $i < $m; $i++) {
				$path = $files[$i]['path'];
				$name = $path . "/" . $files[$i]['filename'];
				
				if ($i < $remove) {
					if ($this->_forcePurge) {
						if (unlink($name)) {
							Logger::info("Removing '{$name}'");
						}
						else {
							Logger::error("File '{$name}' could not be unlinked. Maybe permission error?");
						}
					} 
					else {
						Logger::info("Would remove '{$name}'. Use -f to force removal.");
					}
				} else {
					Logger::info("Keeping " . $name);
				}
			}
		}
	}
}