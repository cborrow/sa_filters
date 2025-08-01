<?php

if(!function_exists('str_starts_with')) {
	function str_starts_with($haystack, $needle) {
		if(substr($haystack, 0, strlen($needle)) == $needle) {
			return true;
		}
		return false;
	}
}

class UserPrefs {
	protected $_userConfigPath;
	protected $_saInfo;

	protected $_whitelist;
	protected $_blacklist;
	protected $_customScores;

	public $scoreThreshold = 5.0;
	public $subjectRewriteStr = "";

	public function __construct($path) { 
		$this->_userConfigPath = $path;
	}

	public function exists() {
		if($this->_userConfigPath != null && file_exists($this->_userConfigPath)) {
			return true;
		}
		return false;
	}

	public function lastModTime() {
		if($this->exists()) {
			return filemtime($this->_userConfigPath);
		}
		return 0;
	}

	public function lastAccessTime() {
		if($this->exists()) {
			return fileatime($this->_userConfigPath);
		}
		return 0;
	}
 
	public function write() {
		if(!file_exists($this->_userConfigPath)) {
			touch($this->_userConfigPath);
		}

		$fp = fopen($this->_userConfigPath, "w+");
		
		if($fp != null && flock($fp, LOCK_EX) !== false) {
			fwrite($fp, "## Basic user settings\n");
			fwrite($fp, "required_score {$this->scoreThreshold}\n");
			
			if($this->subjectRewriteStr != null && is_string($this->subjectRewriteStr) 
				&& strlen($this->subjectRewriteStr) > 0) {
				fwrite($fp, "rewrite_header Subject {$this->subjectRewriteStr}");
			}

			fwrite($fp, "\n\n");
			fwrite($fp, "## Custom scores\n");

			foreach($this->_customScores as $scoreName => $scoreValue) {
				fwrite($fp, "score {$scoreName} {$scoreValue}");
			}

			fwrite($fp, "\n\n");
			fwrite($fp, "## User whitelist entries\n");

			foreach($this->_whitelist as $wl) {
				fwrite($fp, "whitelist_from {$wl}\n");
			}

			fwrite($fp, "\n\n");
			fwrite($fp, "## User blacklist entries\n");

			foreach($this->_blacklist as $bl) {
				fwrite($fp, "blacklist_from {$bl}\n");
			}
		}
		else {
			Log::append("Failed to create lock for file {$this->_userConfigPath}", "Error");
			//Return an error
		}

		if($fp != null && fstat($fp) !== false) {
			fclose($fp);
		}
	}

	public function read() {
		$lines = file($this->_userConfigPath);

		foreach($lines as $linenum => $line) {
			#$line = $this->convertWhitespace($line);

			if(str_starts_with($line, "#") || strlen($line) < 5) {
				continue;
			}
			else {
				$line_parts = explode(" ", $line);

				if(str_starts_with($line, "blacklist_from")) {
					$this->_blacklist[] = $line_parts[1];
				}
				else if(str_starts_with($line, "whitelist_from")) {
					$this->_whitelist[] = $line_parts[1];
				}
				else if(str_starts_with($line, "rewrite_header")) {
					if(strtolower($line_parts[1]) != "subject") {
						continue;
					}
					else {
						if(count($line_parts) > 3) {
							for($i = 3; $i < count($line_parts); $i++) {
								$line_parts[2] += " " + $line_parts[$i];
							}
						}
						$this->subjectRewriteStr = $line_parts[2];
					}
				}
				else if(str_starts_with($line, "required_score")) {
					$this->scoreThreshold = $line_parts[1];
				}
				else if(str_starts_with($line, "score")) {
					$this->addCustomScore($line_parts[1], $line_parts[2]);
				}
			}
		}
	}

	public function getCustomScores() {
		return $this->customScores;
	}

	public function addCustomScore($name, $value) {
		if(array_key_exists($this->_customScores, $name)) {
			Log::append("Custom score {$name} already exists. Replacing {$this->_customScores[$name]} with {$value}");
		}
		
		if(!is_numeric($value)) {
			Log::append("Invalid value provided, most be a valid floating point number");
			return false;
		}
		
		$this->_customScores[$name] = $value;
		return true;
	}

	public function getWhitelist() {
		return $this->_whitelist;
	}

	public function addWhitelist($value) {
		if($this->isNullOrEmpty($value)) {
			return false;
		}

		if($this->contains($value, $this->_whitelist)) {
			Log::append("A whitelist entry for {$value} already exists");
		}
		else if($this->contains($value, $this->_blacklist)) {
			Log::apend("A blacklist entry for {$value} already exists, ignoring.", "Warning");
		}
		else {
			$this->_whitelist[] = $value;
			return true;
		}
		return false;
	}

	public function removeWhitelist($value) {
		for($i = 0; $i < count($this->_whitelist); $i++) {
			if($this->_whitelist == $value) {
				unset($this->_whitelist[$i]);
			}
		}
		$this->_whitelist = array_values($this->_whitelist);
	}

	public function getBlacklist() {
		return $this->_blacklist;
	}

	public function addBlacklist($value) {
		if($this->isNullOrEmpty($value)) {
			return false;
		}

		if($this->contains($value, $this->_blacklist)) {
			Log::append("A blacklist entry for {$value} already exists");
		}
		else if($this->contains($value, $this->_whitelist)) {
			Log::append("A whitelist entry for {$value} already exists, ignoring.", "Warning");
		}
		else {
			$this->_blacklist[] = $value;
			return true;
		}
		return false;
	}

	public function removeBlacklist($value) {
		for($i = 0; $i < count($this->_blacklist); $i++) {
			if($this->_blacklist[$i] == $value) {
				unset($this->_blacklist[$i]);
			}
		}
		$this->_blacklist = array_values($this->_blacklist);
	}

	protected function convertWhitespace($str) {
		$str = preg_replace("/\s{2,}/gm", " ", $str);
		return $str;
	}
	
	protected function isNullOrEmpty($str) {
		if($str == null || strlen($str) == 0) {
			return true;
		}
		return false;
	}

	protected function contains($val, $array) {
		foreach($array as $item) {
			if($val == $item) {
				return true;
			}
		}
		return false;
	}
}
