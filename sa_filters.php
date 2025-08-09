<?php

$config = [];

if (file_exists('/usr/local/cwpsrv/var/services/user_files/modules/sa_filters/config.php')) {
	include '/usr/local/cwpsrv/var/services/user_files/modules/sa_filters/config.php';
} else {
	$config['amavis']['host'] = 'localhost';
	$config['amavis']['name'] = 'amavis';
	$config['amavis']['user'] = 'amavis';
	$config['amavis']['pass'] = 'secret';
}

include "/usr/local/cwpsrv/var/services/user_files/modules/sa_filters/Amavis.php";

$amavis = new Amavis();
$amavis->loadConfig($config['amavis']);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	$data = json_decode(file_get_contents("php://input"), true);

	foreach ($data['whitelist'] as $wl) {
		$amavis->addToWhitelist($wl);
	}

	foreach ($data['blacklist'] as $bl) {
		$amavis->addToBlacklist($wl);
	}
}

$mod['user'] = $_SESSION['username'];
$mod['whitelist'] = $amavis->getWhitelist();
$mod['blacklist'] = $amavis->getBlacklist();
