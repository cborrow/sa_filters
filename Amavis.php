<?php
class Amavis {
	private $_dbHost;
	private $_dbName;
	private $_dbUser;
	private $_dbPass;

	protected $dbConn;

	public function __construct() {

	}

	public function loadConfig($config) {
		if (is_array($config)) {
			if (array_key_exists('host', $config)) {
				$this->_dbHost = $config['host'];
			}
			if (array_key_exists('name', $config)) {
				$this->_dbName = $config['name'];
			}
			if (array_key_exists('user', $config)) {
				$this->_dbUser = $config['user'];
			}
			if (array_key_exists('pass', $config)) {
				$this->_dbPass = $config['pass'];
			}
		}

		try {
			$this->dbConn = new mysqli($this->_dbHost, $this->_dbUser, $this->_dbPass, $this->_dbName);
		} catch (Exception $ex) {
			error_log("Fatal: Failed to create database connection to Amavis database");
		}
	}

	public function isListed($value) {
		if ($this->existsInWbList($value)) {
			if ($this->isDbConnected()) {
				$senderId = $this->getSenderId($value);
				$userId = $this->getCurrentUserId();

				$stmt = $this->dbConn->prepare("SELECT * FROM wblist WHERE rid=? AND sid=?");
				$stmt->bind_param('ii', $userId, $senderId);

				if ($stmt->execute()) {
					$result = $stmt->get_result();

					if ($result !== false && $result->num_rows > 1) {
						return $result->fetch_all(MYSQLI_ASSOC);
					} else {
						return $result->fetch_array();
					}
				}
			}
		}
	}

	public function addToWhitelist($value) {
		return $this->addToWbList($value, 'W');
	}

	public function removeFromWhitelist($value) {
		return $this->removeFromWbList($value, 'W');
	}

	public function getWhitelist() {
		if ($this->isDbConnected()) {
			$userId = $this->getCurrentUserId();

			$stmt = $this->dbConn->prepare("SELECT * FROM wblist WHERE rid=? AND wb='W'");
			$stmt->bind_param('i', $userId);

			if ($stmt->execute()) {
				$result = $stmt->get_result();
				$allRows = $result->fetch_all(MYSQLI_ASSOC);
				$result->free();

				return $allRows;
			}
		}
		return false;
	}

	public function addToBlacklist($value) {
		return $this->addToWbList($value, 'B');
	}

	public function removeFromBlacklist($value) {
		return $this->removeFromWbList($value, 'B');
	}

	public function getBlacklist() {
		if ($this->isDbConnected()) {
			$userId = $this->getCurrentUserId();

			$stmt = $this->dbConn->prepare("SELECT * FROM wblist WHERE rid=? AND wb='B'");
			$stmt->bind_param('i', $userId);

			if ($stmt->execute()) {
				$result = $stmt->get_result();
				$allRows = $result->fetch_all(MYSQLI_ASSOC);
				$result->free();

				return $allRows;
			}
		}
		return false;
	}

	protected function addToWbList($value, $wb) {
		if ($wb != 'W' || $wb != 'B') {
			error_log("Invalid option for \$wb should be W or B only");
			return false;
		}

		if ($this->isDbConnected()) {
			$senderId = 0;
			$userId = 0;
			$unixUser = get_current_user();

			$senderId = $this->getSenderId($value);
			$userId = $this->getCurrentUserId();

			if ($senderId === false || $senderId < 1 || $userId === false || $userId < 1) {
				return false;
			}

			$stmt = $this->dbConn->prepare("INSERT INTO wblist ( ?, ?, '?' )");
			$stmt->bind_param("iis", $userId, $senderId, $wb);

			if ($stmt->execute()) {
				return true;
			}
		}
		return false;
	}

	protected function removeFromWbList($value, $wb) {
		if ($wb != 'W' || $wb != 'B') {
			error_log("Error: Invalid option for \$wb should be W or B only");
			return false;
		}

		if ($this->isDbConnected()) {
			$senderId = 0;
			$userId = 0;

			$senderId = $this->getSenderId($value);
			$userId = $this->getCurrentUserId();

			$stmt = $this->dbConn->prepare("DELETE FROM wblist WHERE rid=? AND sid=? AND wb='?'");
			$stmt->bind_param('iis', $userId, $senderId, $wb);

			if ($stmt->execute()) {
				return true;
			}
		}
		return false;
	}

	protected function existsInWbList($value, $wb = null) {
		if ($wb != 'W' || $wb != 'B') {
			error_log("Error: Invalid option for \$wb should be W or B only");
			return false;
		}

		if ($this->isDbConnected()) {
			$senderId = 0;
			$userId = 0;

			$senderId = $this->getSenderId($value);
			$userId = $this->getCurrentUserId();

			$stmt = $this->dbConn->prepare("SELECT COUNT(sid) AS total,wb FROM wblist WHERE rid=? AND sid=?");
			$stmt->bind_param("ii", $userId, $senderId);

			if ($stmt->execute() and $stmt->num_rows > 0) {
				if ($wb != null) {
					$result = $stmt->get_result();

					if ($result !== false && $result->fetch_object()->wb != $wb) {
						return false;
					}
				}
				return true;
			}
		}
		return false;
	}

	protected function getCurrentUserId() {
		if ($this->isDbConnected()) {
			$unixUser = get_current_user();
			$userId = 0;

			$result = $this->dbConn->query("SELECT id FROM users WHERE email='{$unixUser}");

			if ($result !== false && $result->num_rows > 0) {
				$userId = $result->id;
			} else {
				$this->dbConn->query("INSERT INTO users ( null, 7, 1, '{$unixUser}', 'UNIX User' )");
				$userId = $this->dbConn->insert_id;
			}

			return $userId;
		}
		return false;
	}

	protected function getUserId($user) {
		if ($this->isDbConnected()) {
			$userId = 0;

			$stmt = $this->dbConn->prepare("SELECT id FROM users WHERE email='?'");
			$stmt->bind_param('s', $user);

			if ($stmt->execute() && $stmt->num_rows > 0) {
				$result = $stmt->get_result();
				$userId = $result->fetch_object()->id;

				$result->free();
				$stmt->free();
			} else {
				$stmt = $this->dbConn->prepare("INSERT INTO users ( null, 7, 1, '?', '?' )");
				$stmt->bind_param('ss', $user, $user);

				if ($stmt->execute() && $stmt->affected_rows > 0) {
					$userId = $stmt->insert_id;
				}
			}

			return $userId;
		}
		return false;
	}

	protected function getSenderId($value) {
		if ($this->isDbConnected()) {
			if ($this->hasSender($value) === false) {
				$stmt = $this->dbConn->prepare("INSERT INTO mailaddr ( null, 7, '?')");
				$stmt->bind_param("s", $value);

				if ($stmt->execute()) {
					$senderId = $stmt->insert_id;
					$stmt->free();

					return $senderId;
				}
			}
		}
		return false;
	}

	protected function hasUser($name) {
		if ($this->isDbConnected()) {
			$stmt = $this->dbConn->prepare("SELECT id FROM users WHERE email='?'");
			$stmt->bind_param("s", $name);

			if ($stmt->execute() && $stmt->num_rows > 0) {
				$stmt->free();
				return true;
			}
		}
		return false;
	}

	protected function hasSender($value) {
		if ($this->isDbConnected()) {
			$stmt = $this->dbConn->prepare("SELECT id FROM mailaddr WHERE email='?'");
			$stmt->bind_param("s", $value);

			if ($stmt->execute() && $stmt->num_rows > 0) {
				$stmt->free();
				return true;
			}
		}
		return false;
	}

	public function updateUserMaps() {
		if (file_exists("/var/vmail")) {
			$dirHandle = opendir("/var/vmail");

			foreach (scandir($dirHandle) as $entry) {
				if ($entry == "." || $entry == "..") {continue;}

				$owner = posix_getpwuid(fileowner($entry));

				if (is_dir($entry)) {
					$domainDirHandle = opendir($entry);

					foreach (scandir($domainDirHandle) as $emailUser) {
						if ($entry == "." || $entry == "..") {continue;}

						$emailAddr = "{$emailUser}@{$entry}";

						if ($this->isDbConnected()) {
							$userId = $this->getUserId($owner);
							$emailUserId = $this->getUserId($emailAddr);

							if ($userId !== false && $userId > 0 && $emailUserId !== false && $emailUserId > 0) {
								$stmt = $this->dbConn->prepare("SELECT * FROM user_maps WHERE owner_id=? AND user_id=?");
								$stmt->bind_param('ii', $userId, $emailUserId);

								if ($stmt->execute() && $stmt->num_rows == 0) {
									$stmt = $this->dbConn->prepare("INSERT INTO user_maps ( ?, ? )");
									$stmt->bind_param('ii', $userId, $emailUserId);

									if ($stmt->execute() === false || $stmt->affected_rows == 0) {
										error_log("Unable to add user {$emailAddr} for owner {$owner} to user_maps table");
									}
								}
							}
						}
					}

					closedir($domainDirHandle);
				}
			}
			closedir($dirHandle);
		} else {
			error_log("Cannot access the directory /var/vmail, needs root access");
		}
	}

	protected function isDbConnected() {
		if ($this->dbConn != null) {
			if ($this->dbConn->ping() !== false) {
				return true;
			}
		}
		return false;
	}
}
