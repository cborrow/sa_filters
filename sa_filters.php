<?php

$config = [];

if (file_exists('/usr/local/cwpsrv/var/services/users/modules/sa_filters/sa_filters/config.php')) {
	include '/usr/local/cwpsrv/var/services/users/modules/sa_filters/sa_filters/config.php';
} else {
	$config['amavis']['host'] = 'localhost';
	$config['amavis']['name'] = 'amavis';
	$config['amavis']['user'] = 'amavis';
	$config['amavis']['pass'] = 'secret';
}

include "/usr/local/cwpsrv/var/services/users/modules/sa_filters/Amavis.php";

$amavis = new Amavis();
$amavis->loadConfig($config['amavis']);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	$data = json_decode(file_get_contents("php://input"), true);

	// if(isset($data['score_threshold'])) {
	// 	$prefs->scoreThreshold = floatval($data['score_threshold']);
	// }
	// if(isset($data['prepend_subject'])) {
	// 	$prefs->subjectPrependStr = $data['prepend_subject'];
	// }

	foreach ($data['whitelist'] as $wl) {
		#$prefs->addWhitelist($wl);
		$amavis->addToWhitelist($wl);
	}

	foreach ($data['blacklist'] as $bl) {
		#$prefs->addBlacklist($bl);
		$amavis->addToBlacklist($wl);
	}

	#$prefs->write();
}

$mod['user'] = $_SESSION['username'];
#$mod['score'] = $prefs->scoreThreshold;
#$mod['prepend'] = $prefs->subjectPrependStr;
#$mod['whitelist'] = $prefs->getWhitelist();
#$mod['blacklist'] = $prefs->getBlacklist();
#$mod['last_mod_time'] = date ("M d, Y H:i:s", $prefs->lastModTime());

$mod['whitelist'] = $amavis->getWhitelist();
$mod['blacklist'] = $amavis->getBlacklist();
