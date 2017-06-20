<?php
namespace Backmon;

interface Policy {
	/**
	 * Check the given files
	 * @param array with files, can be null if no files could be found
	 */
	public function check($files = array());
}
