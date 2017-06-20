<?php
namespace Backmon;

/**
 * Runner for a backup definition
 */
class BackupDefinitionRunner {
	public $name = "";
	private $root = "";
	public $cron_definition = "";
	public $cron_estimated_duration = "";
	public $directories = [];
	private $beforeCommand = null;
	private $afterCommand = null;

	public function __construct($baseDirectory) {
		$this->root = $baseDirectory;
	}

	public function configure(\Backmon\Runner $runner, $json = array()) {
		$parameters = $runner->getParameters();
		
		if (!isset($json['name'])) {
			throw new \Backmon\Exception("Attribute 'name' for $baseDirectory missing");
		}

		$this->name = $json['name'];

		Logger::info("Configuring root {$this->name} with root directory {$this->root}");
		
		if (!isset($json['cron'])) {
			throw new \Backmon\Exception("Attribute 'cron' for $baseDirectory missing");
		}
		
		$this->cron_definition = $json['cron'];
		$this->cron_estimated_duration = $json['cron_estimated_duration'] ?? ''; 

		$this->beforeCommand = !empty($parameters['before-definition']) ? $parameters['before-definition'] : null;
		$this->afterCommand = !empty($parameters['after-definition']) ? $parameters['after-definition'] : null;
	}
	
	public function run($context = array()) {
		$context['_.root'] = $this->root;
		$context['_.name'] = $this->name;

		$this->execute($this->beforeCommand);
		
		foreach ($this->directories as $directory) {
			$directory->run($context);
		}
		
		$this->execute($this->afterCommand);
	}
	
	private function execute($command) {
		if (!$command) {
			return;
		}
		
		$fullCommand = $command . " " . realpath($this->root) . "";
		
		\Backmon\Logger::info("Executing \"$fullCommand\"");
		$r = shell_exec($fullCommand);
		\Backmon\Logger::info("Result of \"$fullCommand\": " . $r);
	}
}