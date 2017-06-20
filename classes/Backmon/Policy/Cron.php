<?php
namespace Backmon\Policy;

class Cron implements \Backmon\Policy {
	private $cron;

	public function __construct($cronDefinition) {
		$this->cron = \Cron\CronExpression::factory($cronDefinition);
	}

	public function check($files = array()) {
		$status = "CRIT";
		$message = "No files available for checking cron";

		if (sizeof($files) > 0) {
			$newest = $files[sizeof($files) - 1];

			$status = "CRIT";
			$message = "No backup found";

			if ($newest) {
				$format = "Y-m-d H:i:s";

				$timestampM = new \DateTime("@" . $newest['mtime']);
				$timestampC = new \DateTime("@" . $newest['ctime']);

				// use the latest timestamp available
				$dateTimeFile = $timestampM > $timestampC ? $timestampM : $timestampC;

				// TODO make timezone configurable
				$dateTimeFile = $dateTimeFile->setTimezone(new \DateTimeZone('Europe/Berlin'));
				$dateTimeLastRun = $this->cron->getPreviousRunDate('now', 0, false, 'Europe/Berlin');

				if ($dateTimeFile < $dateTimeLastRun) {
					$message = "Date of last backup is " . $dateTimeFile->format($format) . ". Last expected backup should have run at " . $dateTimeLastRun->format($format);
				}
				else {
					$status = "OK";
					$message = "Last backup run: " . $dateTimeFile->format($format) . " (expected=" . $dateTimeLastRun->format($format) . ")";
				}
			}
		}
		
		return [ 'last_backup', $status, [], $message ];	
	}
}
