<?php

$settings = require '/var/www/bof/cfg/settings.php';


$db = $settings['settings']['db'];
$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['name'], $db['user'], $db['pass']);

$sql = file_get_contents('../../sql/testdata.sql');
$pdo->exec($sql);
$pdo->exec('DELETE FROM config');
$pdo->exec("INSERT INTO config(item, value) VALUES('branding', 'Europe')");
$pdo->exec('DELETE FROM workshop_participant');
$pdo->exec("DELETE FROM participant WHERE name <> 'admin'");
$pdo->exec('DELETE FROM workshop WHERE id > 109');
if ((count($argv) > 1) && ($argv[1] == 'voting')) {
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_begins', DATE_ADD(NOW(), INTERVAL -3 DAY))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_ends', DATE_ADD(DATE_ADD(NOW(), INTERVAL -2 DAY), INTERVAL +1 MINUTE))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_begins', DATE_ADD(DATE_ADD(NOW(), INTERVAL -1 DAY), INTERVAL +1 HOUR))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_ends', DATE_ADD(DATE_ADD(NOW(), INTERVAL +1 DAY), INTERVAL +2 HOUR))");
}
else if ((count($argv) > 1) && ($argv[1] == 'finished')) {
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_begins', DATE_ADD(NOW(), INTERVAL -4 DAY))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_ends', DATE_ADD(DATE_ADD(NOW(), INTERVAL -3 DAY), INTERVAL +1 MINUTE))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_begins', DATE_ADD(DATE_ADD(NOW(), INTERVAL -2 DAY), INTERVAL +1 HOUR))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_ends', DATE_ADD(DATE_ADD(NOW(), INTERVAL -1 DAY), INTERVAL +2 HOUR))");
}
else {
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_begins', DATE_ADD(NOW(), INTERVAL -1 DAY))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_ends', DATE_ADD(DATE_ADD(NOW(), INTERVAL +1 DAY), INTERVAL +1 MINUTE))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_begins', DATE_ADD(DATE_ADD(NOW(), INTERVAL +1 DAY), INTERVAL +1 HOUR))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_ends', DATE_ADD(DATE_ADD(NOW(), INTERVAL +2 DAY), INTERVAL +2 HOUR))");
}

