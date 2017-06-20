<?php
namespace Backmon;

use \Backmon\Logger;
use \Backmon\Policy\Util;

/**
 * Entrypoint
 */
class Runner {
	private $_paths = [];
	private $_definitions = [];
	private $_action = 'info';
	private $_enabledPolicies = [ 'cron', 'size', 'retention' ];
	private $_parameters = [];
	
	public function __construct($parameters = []) {
		$this->_parameters = $parameters;
	}

	public function getParameters() {
		return $this->_parameters;
	}
	
	public function register($backupDefinition, $baseDirectory) {
		if (!file_exists($backupDefinition)) {
			Logger::error("Backup definition file '" . $backupDefinition . "' does not exist");
			return;
		}

		$this->_paths[$backupDefinition] = $baseDirectory;
	}

	public function setEnabledPolicies($policies = array()) {
		$this->_enabledPolicies = $policies;
	}

	/**
	 * Provide a JSON configuration file which contains the mapping of backup_definitions to its base directories
	 */
	public function registerFromFile($configurationFile) {
		Logger::info("Loading definitions file from '$configurationFile'");
		
		if (!file_exists($configurationFile)) {
			throw new \Backmon\Exception("configuration file '$configurationFile' does not exist");
		}

		$content = file_get_contents($configurationFile);
		$definitions = json_decode($content, true);

		if (!is_array($definitions)) {
			throw new Exception("configuration file '$configurationFile' does not contain valid JSON or could not be deserialized.");
		}

		foreach ($definitions as $backupDefinition => $baseDirectory) {
			if (!file_exists($backupDefinition)) {
				Logger::error("Skipping definition '$backupDefinition' from configuration file '$configurationFile': file does not exist");
				continue;
			}
			
			if (empty($baseDirectory)) {
				$baseDirectory = dirname($backupDefinition);
			}

			$this->register($backupDefinition, $baseDirectory);
		}
	}

	public function run($action = 'info') {
		$this->_action = \Backmon\Action\Factory::create($action);
		$this->_action->configure($this);

		foreach ($this->_paths as $path => $baseDirectory) {
			if (!file_exists($path)) {
				Logger::error("File " . $path . " does not exist");
				continue;
			}
			
			$content = file_get_contents($path);
			$definitions = json_decode($content, true);	
			
			if ($definitions) {
				foreach ($definitions as $definition) {
					$this->add($baseDirectory, $definition);
				}
			} 
			else {
				Logger::error("Failed to parse JSON with error: " . json_last_error());
			}
		}

		foreach ($this->_definitions as $definition) {
			$definition->run();
		}
	}
	
	public function add($baseDirectory, $json) {
		$r = new BackupDefinitionRunner($baseDirectory);
		$r->configure($this, $json);

		$this->_definitions[] = $r;

		if (isset($json['directories'])) {
			foreach ($json['directories'] as $directory => $subjson) {
				$this->addDirectory($r, $directory, $subjson);
			}
		}

		return $r;
	}	

	/**
	 * add a new directory below the "directories" attribute
	 * @param $backupDefinition
	 * @param $directory
	 * @param $json
	 */	
	public function addDirectory($backupDefinition, $directory, $json) {
		$r = new \Backmon\Filesystem\Directory();
		$r->directory = $directory;

		$backupDefinition->directories[] = $r;

		if (isset($json['files'])) {
			$defaults = (isset($json['defaults']) && is_array($json['defaults'])) ? $json['defaults'] : []; 

			foreach ($json['files'] as $file => $subjson) {
				$mergedJson = array_merge($defaults, $subjson);

				$this->addFile($backupDefinition, $r, $file, $mergedJson);
			}
		}

		return $r;
	}

	/**
	 * add a new file below the "files" attribute
	 * @param $backupDefinition
	 * @param $directoryDefinition
	 * @param $file
	 * @param $json
	 */
	public function addFile($backupDefinition, $directoryDefinition, $file, $json) {
		$r = new \Backmon\Filesystem\File($this->_action);
		$r->matcher = $file;

		$r->name = $json['name'] ?? $file;
		$r->sort = $json['sort'] ?? $r->sort;
		$r->order = $json['order'] ?? $r->order;
		$r->grouping = $json['grouping'] ?? $r->grouping;
		$r->suffix = $json['suffix'] ?? null;
		$r->checkKeyFormat = $json['check_key_format'] ?? null;
		
		// todo: move policies to own factory
		// size policy
		if (in_array('size', $this->_enabledPolicies)) {
			if (isset($json['size']) || isset($json['size_max'])) {
				$sizePolicy = new \Backmon\Policy\Size();
			
				$sizePolicy->min = 	(int)(isset($json['size']) ? Util::humanToByte($json['size']) : 100);
				$sizePolicy->min_fail = (int)(isset($json['size_min_fail']) ? Util::humanToByte($json['size_min_fail']) : -1);
				$sizePolicy->max = 	(int)(isset($json['size_max']) ? Util::humanToByte($json['size_max']) : -1);
				$sizePolicy->max_fail = (int)(isset($json['size_max_fail']) ? Util::humanToByte($json['size_max_fail']) : -1);
			
				$r->policies[] = $sizePolicy;
			}
		}

		// retention policy
		if (in_array('retention', $this->_enabledPolicies)) {
			if (isset($json['retention']) || isset($json['retention_max'])) {
				$retentionPolicy = new \Backmon\Policy\Retention();
			
				$retentionPolicy->min = (int)($json['retention'] ?? 5);
				$retentionPolicy->min_fail = (int)($json['retention_min_fail'] ?? -1);
				$retentionPolicy->max = (int)($json['retention_max'] ?? 10);
				$retentionPolicy->max_fail = (int)($json['retention_max_fail'] ?? -1);
				$retentionPolicy->remove_empty_parent_directories = (bool)($json['remove_empty_parent_directories'] ?? false);

				$r->policies[] = $retentionPolicy;
			}	
		}

		if (in_array('cron', $this->_enabledPolicies)) {
			if ($backupDefinition->cron_definition) {
				$r->policies[] = new \Backmon\Policy\Cron($backupDefinition->cron_definition, $backupDefinition->cron_estimated_duration);
			}
		}

		$directoryDefinition->files[] = $r;

		return $r;
	}
}
