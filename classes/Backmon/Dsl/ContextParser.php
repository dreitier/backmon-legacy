<?php
namespace Backmon\Dsl;

class ContextParser {
	private $input = "";
	private $context = [];
	private $output = null;

	public function __construct($input, $context) {
		$this->input = $input;
		$this->context = $context;
	}

	public function get() {
		if ($this->output === null) {
			$this->output = $this->parse($this->input, $this->context);
		}

		return $this->output;
	}

	private function parse($string, $context) {
		$functions = [
			'lower' => function($string, $all) {
				return strtolower($string);
			},
			'upper' => function($string, $all) {
				return strtoupper($string);
			}
		];
		
		if (preg_match_all('/\$\{([\w\._]+)(:(\w+))?\}/i', $string, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$variable = $match[1];
				$function = null;

				if (sizeof($match) > 2) {
					$function = $match[3];
				}

				$value = $context[$variable] ?? '';

				if ($function != null) {
					if (isset($functions[$function])) {
						$value = $functions[$function]($value, $matches);
					}
				}

				$string = str_replace($match[0], $value, $string);
			}	
		}

		return $string;
	}
}