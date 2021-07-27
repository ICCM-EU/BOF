<?php

$settings = require '/var/www/bof/cfg/settings.php';

$db = $settings['settings']['db'];
$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['name'], $db['user'], $db['pass']);

if ((count($argv) > 1) && ($argv[1] === 'prepBoF')) {
	if (count($argv) < 5) {
		# Not enough args!
		exit(1);
	}
	$query = $pdo->prepare("SELECT value FROM config WHERE item='schedule_prep'");
	$query->execute();
	$schedule_prep = $query->fetch(PDO::FETCH_NUM);
	$query = $pdo->prepare("SELECT value FROM config WHERE item='prep_round'");
	$query->execute();
	$prep_round = $query->fetch(PDO::FETCH_NUM);
	$query = $pdo->prepare("SELECT value FROM config WHERE item='prep_location'");
	$query->execute();
	$prep_location = $query->fetch(PDO::FETCH_NUM);
        print("schedule_prep: " . $schedule_prep[0]);
        print("prep_round: " . $prep_round[0]);
        print("prep_location: " . $prep_location[0]);
	if (($schedule_prep[0] == $argv[2])
	    && (($prep_round[0] - 1) == $argv[3])
	    && (($prep_location[0] - 1) == $argv[4])) {
		exit(0);
	}

}

exit(1);
