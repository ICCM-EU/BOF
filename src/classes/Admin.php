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
