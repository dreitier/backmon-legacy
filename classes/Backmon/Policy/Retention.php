<?php
namespace Backmon\Policy;

use Backmon\Policy\Util;

class Retention implements \Backmon\Policy {
	public $min = 5;
	public $max = 10;
	public $min_fail = 3;
	public $max_fail = 12;
	public $remove_empty_parent_directories = false;

	public function check($files = array()) {
		if ($this->min == -1 && $this->max == -1) {
			return;		
		}

		$num = sizeof($files);

		$status = "OK";
		$message = "Number of backups: $num";

		if ($num < $this->min || $num > $this->max) {
			$status = "WARN";

			if ($num <= $this->min_fail || ($this->max_fail > 0 && ($num >= $this->max_fail))) {
				$status = "CRIT";
			}
		}
		
		return [ 'retention', $status, 
			[ 
				'total_min' => Util::createMetric($num, $this->min, $this->min_fail),
				'total_max' => Util::createMetric($num, $this->max, $this->max_fail)
			],
			$message 
		];
	}

}