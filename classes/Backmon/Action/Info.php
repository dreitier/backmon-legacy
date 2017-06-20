<?php
namespace Backmon\Action;

use \Backmon\Action;

class Info implements Action {
	public function configure(\Backmon\Runner $runner) {
	}
	
	/**
	 * informational
	 * @return callable
	 */
	public function execute(\Backmon\Filesystem\File $fileDefinition, array $context = []) {
		foreach ($fileDefinition->result as $groupKey => $files) {
			echo "Group $groupKey\r\n";
			echo "  Total matches: " . sizeof($files) . "\r\n";
			$last = $files[sizeof($files) - 1];
			echo "  Last file: " . $last['path'] . "/" . $last['filename'] . "\r\n";
			echo "  Last size: " . \Backmon\Policy\Util::byteToHuman($last['size']) . "\r\n";
			echo "  Oldest file: " . $files[0]['path'] . "/"  . $files[0]['filename'] . "\r\n";
			echo "\r\n";
		}
	}
}
