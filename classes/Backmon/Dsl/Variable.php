<?php
namespace Backmon\Dsl;

/**
 * TODO refactor to merge it together with context variables
 */
class Variable {
	public $variable = false;
	public $value = "";

	public static function create($string) {
		$r = new Variable();
		$r->value = $string;
		
		if (preg_match("/^\{\{(.*)\}\}$/", $string, $matches)) {
			$r->variable = true;
			$r->value = $matches[1];
		}
		
		return $r;
	}
}