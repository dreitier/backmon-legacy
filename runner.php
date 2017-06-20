<?php
require_once(dirname(__FILE__) . '/vendor/autoload.php');

// import for get-opt interface
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;
use Backmon\Runner;

define("DEFINITION_JSON_FILE", "backup_definitions.json");

class Md5ChecksumChecker {
	public $filenameDefinition = "";

	public function check($context) {
		if (!file_exists($a)) {
			return false;
		}
	}
}

$getopt = new Getopt(array(
		new Option('a', 'action', Getopt::REQUIRED_ARGUMENT, 'can be "omd", "purge" or "info"'),	
		new Option('c', 'config-definitions', Getopt::OPTIONAL_ARGUMENT, 'JSON configuration containing paths to backup definitions'),
		new Option('p', 'policy', Getopt::OPTIONAL_ARGUMENT, 'you can specify multiple policies. Valid policies are "size", "retention", "cron"'),
		new Option('f', 'force', Getopt::OPTIONAL_ARGUMENT, 'when purging, delete the actually files instead of dry-running it'),
		new Option(null, 'before-definition', Getopt::OPTIONAL_ARGUMENT, 'execute shell command before definition is run. First argument is base directory'),
		new Option(null, 'after-definition', Getopt::OPTIONAL_ARGUMENT, 'execute shell command after definition has been run. First argument is base directory')
	)
);

try {
	$getopt->parse();
	$runner = new Runner($getopt);

	if (!in_array($getopt['action'], ['omd', 'purge', 'info'] )) {
		throw new UnexpectedValueException("parameter 'action' must be 'omd' or 'info'");
		exit(1);
	}

	if (!empty($getopt['config-definitions'])) {
		$configurationFile = $getopt['config-definitions'];
		
		if ($configurationFile == basename($configurationFile)) {
			$configurationFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . $configurationFile;
		}
		
		$runner->registerFromFile($configurationFile);
	}

	if (isset($getopt['policy'])) {
		$policies = explode(",", $getopt['policy']);
		$runner->setEnabledPolicies($policies);
	}

	$directories = !empty($getopt->getOperands()) ? $getopt->getOperands() : [];

	foreach ($directories as $baseDirectory) {
		$suffix = ".json";
		$length = strlen($suffix);
		
		if (substr($baseDirectory, -$length) === $suffix) {
			$backupDefinition = $baseDirectory;
			$baseDirectory = dirname($backupDefinition);
		}
		else {
			$backupDefinition = $baseDirectory . "/" . DEFINITION_JSON_FILE;
		}

		$runner->register($backupDefinition, $baseDirectory);		
	}

	$runner->run($getopt['action']);
}
catch (UnexpectedValueException $e) {
	echo "Error: " . $e->getMessage() . "\n";
	echo $getopt->getHelpText();
	exit(1);
}
catch (Exception $e) {
	echo "Failed to execute: " . $e->getMessage() . "\n";
	exit(1);
}

