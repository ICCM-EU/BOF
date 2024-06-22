<?php

$settings = require '/var/www/bof/cfg/settings.php';


$db = $settings['settings']['db'];
$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['name'], $db['user'], $db['pass']);

$sql = file_get_contents(__DIR__.'/../../sql/testdata.sql');
$pdo->exec($sql);
$pdo->exec('DELETE FROM config');
$pdo->exec("INSERT INTO config(item, value) VALUES('branding', 'Europe')");
$pdo->exec('DELETE FROM workshop_participant');
$pdo->exec("DELETE FROM participant WHERE name <> 'admin'");
$pdo->exec('DELETE FROM workshop WHERE id > 109');
if ((count($argv) > 1) && ($argv[1] == 'voting')) {
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_begins', DATE_FORMAT(DATE_ADD(UTC_TIMESTAMP(), INTERVAL -3 DAY), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_ends', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL -2 DAY), INTERVAL +1 MINUTE), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_begins', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL -1 DAY), INTERVAL +1 HOUR), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_ends', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL +1 DAY), INTERVAL +2 HOUR), '%Y-%m-%d %H:%i:00'))");
}
else if ((count($argv) > 1) && ($argv[1] == 'finished')) {
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_begins', DATE_FORMAT(DATE_ADD(UTC_TIMESTAMP(), INTERVAL -4 DAY), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_ends', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL -3 DAY), INTERVAL +1 MINUTE), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_begins', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL -2 DAY), INTERVAL +1 HOUR), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_ends', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL -1 DAY), INTERVAL +2 HOUR), '%Y-%m-%d %H:%i:00'))");
}
else {
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_begins', DATE_FORMAT(DATE_ADD(UTC_TIMESTAMP(), INTERVAL -1 DAY), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('nomination_ends', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL +1 DAY), INTERVAL +1 MINUTE), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_begins', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL +1 DAY), INTERVAL +1 HOUR), '%Y-%m-%d %H:%i:00'))");
	$pdo->exec("INSERT INTO config (item, value) VALUES('voting_ends', DATE_FORMAT(DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL +2 DAY), INTERVAL +2 HOUR), '%Y-%m-%d %H:%i:00'))");
}

