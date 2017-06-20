<?php
namespace Backmon\Policy;

use Backmon;
use Backmon\Policy\Util;

class Size implements \Backmon\Policy {
	public $min = -1;
	public $max = -1;
	public $min_fail = -1;
	public $max_fail = -1;

	public function check($files = array()) {
		$status = "CRIT";
		$message = "No files available for checking size";
		$size = 0;
	
		if (sizeof($files) > 0) {
			$newest = $files[sizeof($files) - 1];
			$size = $newest['size'];
		
			$status = "OK";
			$message = "File size of last backup " . $newest['path'] . "/" . $newest['filename'] . ": " . Util::byteToHuman($size);
			
			if ($size < $this->min || ($this->max != -1 && ($size > $this->max))) {
				$status = "WARN";
				
				if ($size <= $this->min_fail || ($this->max_fail > 0 && ($size >= $this->max_fail))) {
					$status = "CRIT";
				}	
			}
		}
		
		return [ 'size', $status, 
			[ 
				'size_min' => Util::createMetric($size, $this->min, $this->min_fail),
				'size_max' => Util::createMetric($size, $this->max, $this->max_fail)
			],
			$message 
		];
	}
}
