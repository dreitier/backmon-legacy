<?php
namespace Backmon\Filesystem;

use \Backmon\Logger;
use \Backmon\Action;
use \Backmon\Dsl\ContextParser;
use BigFileTools;

class File {
	public $matcher = "";
	public $name = "<unknown>";
	public $sort = "filename";
	public $order = "asc";
	public $policies = [];
	public $grouping = '${path}';
	public $result = [];
	public $files = [];
	public $action = null;
	public $suffix = null;
	public $directory = null;
	public $checkKeyFormat = null;

	public function __construct(Action $action) {
		$this->action = $action;
	}
	
	public function collect($context, $dir) {
		$regexMatcher = $this->createFileMatcher($this->matcher, $context);
		$this->directory = $dir;
		
		if (!is_dir($dir)) {
			Logger::error("Directory '$dir' is not valid'");
			return $this->files;
		}
		
		$it = new \DirectoryIterator($dir);

		$r = [];

		foreach ($it as $file) {
			if (!$it->isDir()) {
				$filename = $it->getFilename();
				
				if (preg_match("/^" . $regexMatcher . "$/", $filename, $result)) {
					// copy context
					$fileContext = $context;

					foreach ($result as $name => $value) {
						if (is_string($name)) {
							$fileContext[$name] = $value;
						}
					}

					$fileContext = array_merge($fileContext,
						[
							'matcher' => $this->matcher,
							'filename' => $file->getFilename(),
							'mtime' => $file->getMTime(),
							'ctime' => $file->getCTime(),
							'atime' => $file->getATime(),
							'path' => $file->getPath(),
							// we don't use $file->getSize() b/c it does not work with files larger than 2 GB
							'size' => BigFileTools\BigFileTools::createDefault()->getFile($file->getPathname())
						]
					);

					$this->files[] = $fileContext;
				}
			}
		}

		return $this->files;
	}

	public function execute(array $context) {
		$groups = $this->group($this->files);
		$sortedGroups = $this->sort($groups, $this->sort);
		$this->result = $sortedGroups;

		$this->action->execute($this, $context);
	}
	
	private function group($files) {
		$r = [];		

		foreach ($files as $file) {
			$contextParser = new ContextParser($this->grouping, $file);

			$groupname = $contextParser->get();
			
			if (!isset($r[$groupname])) {
				$r[$groupname] = [];
			}

			$r[$groupname][] = $file;
		}

		return $r;
	}

	private function sort($groups, $sortBy) {
		$r = [];

		foreach ($groups as $groupKey => $files) {
			usort($files, function($file1, $file2) use (&$sortBy){
				if (!isset($file1[$sortBy])) {
					throw new \Backmon\Exception("Property '" . $sortBy . "' does not exist. You can use one of the properties [" . implode(", ", array_keys($file1)) . "] to sort");
				}
				
				return $file1[$sortBy] <=> $file2[$sortBy];
			});

			if ($this->order == 'desc') {
				$files = array_reverse($files);
			}
			
			$r[$groupKey] = $files;
		}

		return $r;
	}


	private function createFileMatcher($string, $context) {
		$shortcodes = [
			'%Y'	=> [ '\d{4}', 'year' ],
			'%y'	=> [ '\d{2}', 'year_two_digits' ],
			'%m'	=> [ '\d{2}', 'month' ],
			'%d'	=> [ '\d{2}', 'day' ],
			'%H'	=> [ '\d{2}', 'hour' ],
			'%M'	=> [ '\d{2}', 'minute' ],
			'%S'	=> [ '\d{2}', 'second' ],
			'%i'	=> [ '\d+', 'integer' ],
			'%w'	=> [ '\w+', 'word' ],
			'%W'	=> [ '.*', 'wildcard' ],
		];

		$contextParser = new ContextParser($string, $context);
		$string = $contextParser->get();

		// escape regex characters
		$string = preg_quote($string);
		
		// replace each shortcode
		foreach ($shortcodes as $shortcode => $setting) {
			$regex = $setting[0];
			$namedSubpattern = $setting[1];
			// total of *this* shortcode
			$usageCount = 0;
			
			// replace occurence of the shortcode with a named subpattern. The first would be named "integer", the second "integer_2" and so on
			$string = preg_replace_callback("/" . $shortcode . "/", function ($match) use ($regex, $namedSubpattern, &$usageCount) {
				$suffix = "";
				$usageCount++;
				
				// append usage suffix if shortcode is already in use
				if ($usageCount > 1) {
					$suffix = "_" . $usageCount;
				}
				
				return "(?<" . $namedSubpattern . $suffix . ">" . $regex . ")";
			}, $string);
		}
		
		return $string;
	}
}
