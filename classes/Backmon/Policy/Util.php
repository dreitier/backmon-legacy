<?php
namespace Backmon\Policy;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

class Util {
	public static function createMetric($value, $warn, $fail) {
		$value = BigInteger::of($value);
		$warn = BigInteger::of($warn);
		$fail = BigInteger::of($fail);

		$r = [ $value->__toString() ];

		
		$hasWarn = $warn->compareTo(-1) == 1;
		$hasFail = $fail->compareTo(-1) == 1;

		if ($hasWarn || $hasFail) {
		// if ($warn > -1 || $fail > -1) {
			if ($warn->compareTo(-1) == 0) {
			// if ($warn == -1) {
				$warn = $fail;
			}
			
			if ($warn->compareTo(-1) == 0) {
			// if ($fail == -1) {
				$fail = $warn;
			}
			
			$r[] = $warn;
			$r[] = $fail;
		}
		
		return $r;
	}

	public static function bigNumberByteToHuman(BigInteger $size) {
		$check = BigInteger::of(1024);

		if ($size->compareTo($check) <= 0) {
			echo $size . "\r\n";
			return $size . ' Byte';
		}

		$check = BigInteger::of(1024 * 1024);
		
		if ($size->compareTo($check) <= 0) {
			return $size->toBigDecimal()->dividedBy(1024, 2, RoundingMode::HALF_DOWN) . ' KB';
		}

		$check = BigInteger::of(1024 * 1024 * 1024);
		if ($size->compareTo($check) <= 0) {
			return $size->toBigDecimal()->dividedBy(1024 * 1024, 2, RoundingMode::HALF_DOWN) . ' MB';
		}

		$check = BigInteger::of("1099511627776");

		if ($size->compareTo($check)) {
			return $size->toBigDecimal()->dividedBy((1024 * 1024 * 1024), 2, RoundingMode::HALF_DOWN) . ' GB';
		}

		return $size->dividedBy("1099511627776") . " TB";
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

	public static function humanToBigNumberByte($size) {
		if (preg_match("/^(\d*)$/", $size)) {
			return BigInteger::of($size);
		}
		
		if (!preg_match("/(\d*)\s+(\w?)/i", strtolower($size), $matches)) {
			return BigInteger::of(0);
		}
		
		$value = BigInteger::of($matches[1]);
		$metrics = array('k','m','g','t','e');
		
		foreach ($metrics as $metric) {
			$value = $value->multipliedBy(1024);

			if ($metric == $matches[2]) {
				break;
			}
		}

		return $value;
	}
}
