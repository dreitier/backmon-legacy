<?php
namespace Backmon;

/**
 * Actions are executed after grouping has been done of each collected files.
 * Feel free to add your own extension
 */
interface Action {
	public function configure(\Backmon\Runner $runner);
	public function execute(\Backmon\Filesystem\File $fileDefinition, array $context = []);
}