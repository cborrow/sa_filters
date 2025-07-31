<?php

 class Log {
	#public static $logPath = __DIR__ . "/error.log";
	public static $logPath = "/usr/local/cwpsrv/var/services/user_files/modules/sa_filters/sa_filters/error.log";
	public static $logForceWriteThreshold = 25;

	protected static $pendingLogEntries = [];

	public static function append($message, $type = "Information") {
		$entry = date("[m J, Y H:i:s] {$type} {$message}");

		self::$pendingLogEntries[] = $entry;

		if(count(self::$pendingLogEntries) > self::$logForceWriteThreshold) {
			self::flushPendingEntries();
		}
	}

	public static function flushPendingEntries() {
		$fp = fopen(self::$logPath, "a+");

		if($fp != null && flock($fp, LOCK_EX) !== false) {
			fwrite($fp, "\n");

			foreach(self::$pendingLogEntries as $entry) {
				fwrite($fp, $entry . "\n");
			}

			self::$pendingLogEntries = [];
		}

		if($fp != null) {
			fclose($fp);
		}
	}
}

