<?php
namespace Backmon\Action;

use \Backmon\Action\Omd;
use \Backmon\Action\Purge;
use \Backmon\Action\Info;

class Factory {
	/**
	 * Create a new callable action
	 * @param $action
	 * @return callable
	 */
	public static function create($action) {
		switch ($action) {
			case 'omd':
				return new Omd();
			case 'purge':
				return new Purge();
			default:
				return new Info();
		}
	}
}