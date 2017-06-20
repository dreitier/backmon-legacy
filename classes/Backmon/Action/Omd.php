<?php
namespace Backmon\Action;

use \Backmon\Action;

class Omd implements Action {
	private $_checkKeyFormat = '${_.name}/${_.action.omd.group_key}${_.action.omd.suffix}_${_.action.omd.check_type}';
	private $_name = null;
	
	public function configure(\Backmon\Runner $runner) {
		// Disable any logging
		\Backmon\Logger::disable();
	}
	
	private function createCheckKey(array $context, $customCheckKeyFormat = null) {
		$checkKeyFormat = !empty($customCheckKeyFormat) ? $customCheckKeyFormat : $this->_checkKeyFormat;

		$contextParser = new \Backmon\Dsl\ContextParser($checkKeyFormat, $context);
		
		return $contextParser->get();
	}
	
	/**
	 * informational
	 * @return callable
	 */
	public function execute(\Backmon\Filesystem\File $fileDefinition, array $context = []) {
		$results = $fileDefinition->result;
		
		// if directory does not contain any files, we want to spit out the information
		if (sizeof($results) == 0) {
			$results[$fileDefinition->matcher] = [];
		}
		
		foreach ($fileDefinition->policies as $policy) {
			foreach ($results as $groupKey => $files) {
				$result = $policy->check($files);

				$status = $result[1];
				

				switch ($status) {
					case 'OK':
						$statusCode = 0;
						break;
					case 'WARN':
						$statusCode = 1;
						break;
					case 'CRIT':
						$statusCode = 2;
						break;
					default:
						$statusCode = 3;
						$status = "UNKNOWN";
				}

				$metrics = "-";

				if (sizeof($result[2]) > 0) {
					$metrics = [];

					foreach ($result[2] as $key => $value) {
						$values = $value;
						
						if (is_array($value)) {
							// current;warn;crit
							$values = implode(";", $value);
						}
					
						$metrics[] = $key . "=" . $values;
					}

					$metrics = implode("|", $metrics);
				}

				$context['_.action.omd.group_key'] = $groupKey;
				$context['_.action.omd.check_type'] = $result[0];
				$context['_.action.omd.name'] = $fileDefinition->name;
				$context['_.action.omd.suffix'] = $fileDefinition->suffix;
				
				// the check key starts with the grouping name (path of file definition by default)
				$checkKey = $this->createCheckKey($context, $fileDefinition->checkKeyFormat);
				
				echo $statusCode . " " . $checkKey . " " . $metrics . " " . $status . " - " . $result[3] . "\r\n";
			}	
		}
	}
}
