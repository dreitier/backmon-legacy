<?php
namespace Backmon\Policy;

class Util {
	public static function createMetric($value, $warn, $fail) {
		$r = [ $value];
		
		if ($warn > -1 || $fail > -1) {
			if ($warn == -1) {
				$warn = $fail;
			}
			
			if ($fail == -1) {
				$fail = $warn;
			}
			
			$r[] = $warn;
			$r[] = $fail;
		}
		
		return $r;
	}
	
	public static function byteToHuman($size) {
		# size smaller then 1kb
		if ($size < 1024) return $size . ' Byte';
		# size smaller then 1mb
		if ($size < 1048576) return sprintf("%4.2f KB", $size/1024);
		# size smaller then 1gb
		if ($size < 1073741824) return sprintf("%4.2f MB", $size/1048576);
		# size smaller then 1tb
		if ($size < 1099511627776) return sprintf("%4.2f GB", $size/1073741824);
		# size larger then 1tb
		else return sprintf("%4.2f TB", $size/1073741824);
	}

	public static function humanToByte($size) {
		if (preg_match("/^(\d*)$/", $size)) {
			return $size;
		}
		
		if (!preg_match("/(\d*)\s+(\w?)/i", strtolower($size), $matches)) {
			return 0;
		}
		
		$value = $matches[1];
		$metrics = array('k','m','g','t','e');
		
		foreach ($metrics as $metric) {
			$value = $value * 1024;

			if ($metric == $matches[2]) {
				break;
			}
		}

		return $value;
	}
}
