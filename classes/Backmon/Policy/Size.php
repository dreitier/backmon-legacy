<?php
namespace Backmon\Policy;

use Backmon;
use Backmon\Policy\Util;
use Brick\Math\BigInteger;

/**
 * This policy checks the file size.
 * Please note that we use BigInteger because we can not represent a big file not with simple bytes as PHP only stores integers as 32 bit.
 */
class Size implements \Backmon\Policy {
	public $min = null;
	public $max = null;
	public $min_fail = null;
	public $max_fail = null;

	public function __construct() {
		$this->min = BigInteger::of(-1);
		$this->max = BigInteger::of(-1);
		$this->min_fail = BigInteger::of(-1);
		$this->max_fail = BigInteger::of(-1);
	}

	public function check($files = array()) {
		$status = "CRIT";
		$message = "No files available for checking size";
		$size = BigInteger::of(0);
	
		if (sizeof($files) > 0) {
			$newest = $files[sizeof($files) - 1];
			$_size = $newest['size'];
			$size = $_size->getSize();


			$status = "OK";
			$message = "File size of last backup " . $newest['path'] . "/" . $newest['filename'] . ": " . Util::bigNumberByteToHuman($size);

			$sizeLtMinimum = $size->compareTo($this->min) == -1;
			$sizeMaxDefined = $this->max->compareTo(-1) != 0;
			$sizeGtMaximum = $size->compareTo($this->max) == 1;

			if ($sizeLtMinimum || ($sizeMaxDefined && $sizeGtMaximum)) {
			// if ($size < $this->min || ($this->max != -1 && ($size > $this->max))) {
				$status = "WARN";
			
				$sizeLtMinimumFail = $size->compareTo($this->min_fail) == -1;
				$sizeMaxFailDefined = $this->max_fail->compareTo(0) == 1;
				$sizeGtMaxFail = $size->compareTo($this->max_fail) >= 0;
				
				if ($sizeLtMinimumFail || ($sizeMaxFailDefined && $sizeGtMaxFail)) {	
				// if ($size <= $this->min_fail || ($this->max_fail > 0 && ($size >= $this->max_fail))) {
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
