<?php
namespace Backmon;

class Logger {
	private static $disabled = false;
	
	public static function disable() {
		self::$disabled = true;
	}
	
	public static function info($message) {
		self::write('INFO', $message);
	}
	
	public static function error($message) {
		self::write('ERROR', $message);
	}
	
	public static function debug($message) {
		self::write('DEBUG', $message);
	}

	public static function warn($message) {
		self::write('WARN', $message);
	}

	public static function write($level, $message) {
		if (self::$disabled) {
			return;
		}
		
		print "[$level] " . $message . "\r\n";
	}
}