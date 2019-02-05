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
		$stage =new Stage($this->db);
		$config['stage'] = $stage->getstage();
		return $this->view->render($response, 'admin.html', $config);
	}

	public function update_config($request, $response, $args) {
		$data = $request->getParsedBody();

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
		$results = new Results($this->view, $this->db, $this->router);
		return $results->calculateResults($request, $response, $args);
	}

}

?>
