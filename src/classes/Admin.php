<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;

class Admin
{
	private $view;
	private $db;
	private $router;

	function __construct($view, $db, $router) {
		$this->view = $view;
		$this->db = $db;
		$this->router = $router;
	}

	public function showAdminView($request, $response, $args) {
		$is_admin = $request->getAttribute('is_admin');
		if (!$is_admin) die("you don't have permissions for this page");

		$sql = "SELECT * FROM `config`";
		$query=$this->db->prepare($sql);
		$param = array ();
		$query->execute($param);
		$config = array ();
		while ($row=$query->fetch(PDO::FETCH_OBJ)) {
			$config[$row->item] = date("Y-m-d", strtotime($row->value));
			$config[$row->item."_time"] = date("H:i", strtotime($row->value));
		}
		$config['loggedin'] = true;
		$config['localservertime'] = date("Y-m-d H:m:s");
		$sql = "SELECT id, time_period FROM `round`";
		$query = $this->db->prepare($sql);
		$param = array ();
		$query->execute($param);
		$config['rounds'] = array ();
		$count = 0;
		while ($row=$query->fetch(PDO::FETCH_OBJ)) {
			$config['rounds'][$row->id] = $row->time_period;
			$count++;
		}
		$config['num_rounds'] = $count;
		$stage =new Stage($this->db);
		$config['stage'] = $stage->getstage();
		return $this->view->render($response, 'admin.html', $config);
	}

	public function update_config($request, $response, $args) {
		$is_admin = $request->getAttribute('is_admin');
		if (!$is_admin) die("you don't have permissions for this page");

		$data = $request->getParsedBody();

		if (!empty($data["password1"])) {
			if ($data["password1"] != $data["password2"]) {
				die("passwords do not match");
			} else {
				$sql = "UPDATE `participant` SET `password`=PASSWORD(?) WHERE name = 'admin'";
				$query=$this->db->prepare($sql);
				$param = array($data['password1']);
				$query->execute($param);
			}
		}

		if (!empty($data["reset_database"])) {
			if ($data["reset_database"] != "yes") {
				die("invalid request");
			}

			$sql = "DELETE FROM participant where name <> 'admin'";
			$query=$this->db->prepare($sql);
			$param = array();
			$query->execute($param);
			# keep the prep workshop
			$sql = "DELETE FROM workshop where id<>1";
			$query=$this->db->prepare($sql);
			$param = array();
			$query->execute($param);
			$sql = "DELETE FROM workshop_participant";
			$query=$this->db->prepare($sql);
			$param = array();
			$query->execute($param);

			return $this->showAdminView($request, $response, $args);
		}

		if (!empty($data["download_database"])) {
			if ($data["download_database"] != "yes") {
				die("invalid request");
			}

			$settings = require __DIR__.'/../../cfg/settings.php';
			$settings = $settings['settings'];
			$dbhost=$settings['db']['host'];
			$dbname=$settings['db']['name'];
			$dbuser=$settings['db']['user'];
			$dbpassword=$settings['db']['pass'];
			$dumpfile="/tmp/mysqldump.sql";
			passthru("mysqldump --user=$dbuser --password=$dbpassword --host=$dbhost $dbname > $dumpfile");

			Header('Content-type: application/octet-stream');
			Header('Content-Disposition: attachment; filename=db-backup-BOF-'.date('Y-m-d_hi').'.sql');

			echo file_get_contents($dumpfile);
			die();
		}

		$sql = "UPDATE `config` SET value=? WHERE item = 'nomination_begins'";
		$query=$this->db->prepare($sql);
		if (empty($data['time_nomination_begins'])) die("invalid time");
		$param = array($data['nomination_begins']." ".$data['time_nomination_begins'].":00");
		$query->execute($param);

		$sql = "UPDATE `config` SET value=? WHERE item = 'nomination_ends'";
		$query=$this->db->prepare($sql);
		if (empty($data['time_nomination_ends'])) die("invalid time");
		$param = array($data['nomination_ends']." ".$data['time_nomination_ends'].":00");
		$query->execute($param);

		$sql = "UPDATE `config` SET value=? WHERE item = 'voting_begins'";
		$query=$this->db->prepare($sql);
		if (empty($data['time_voting_begins'])) die("invalid time");
		$param = array($data['voting_begins']." ".$data['time_voting_begins'].":00");
		$query->execute($param);

		$sql = "UPDATE `config` SET value=? WHERE item = 'voting_ends'";
		$query=$this->db->prepare($sql);
		if (empty($data['time_voting_ends'])) die("invalid time");
		$param = array($data['voting_ends']." ".$data['time_voting_ends']);
		$query->execute($param);

		# Delete everything from round
		$sql = "DELETE FROM `round`";
		$query=$this->db->prepare($sql);
		$query->execute($param);
		# Now add the data for the sessions
		$round_id = 0;
		$this->db->beginTransaction();
		$sql = "INSERT INTO round(id,time_period) VALUES(:id,:time_period)";
		$query=$this->db->prepare($sql);
		foreach ($data['rounds'] as $round)
		{
			$query->bindValue(':id', $round_id);
			$query->bindValue(':time_period', $round);
			$query->execute();
			$round_id++;
		}
		$query = null;
		$this->db->commit();

		return $this->showAdminView($request, $response, $args);
	}

	public function calcResult($request, $response, $args) {
		$is_admin = $request->getAttribute('is_admin');
		if (!$is_admin) die("you don't have permissions for this page");

		$results = new Results($this->view, $this->db, $this->router);
		return $results->calculateResults($request, $response, $args);
	}

}

?>
