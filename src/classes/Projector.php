<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;

class Projector
{
	private $view;
	private $db;
	private $router;

	function __construct($view, $db, $router) {
		$this->view = $view;
		$this->db = $db;
		$this->router = $router;
	}

	// Returns a comma-delimited string of facilitators
	function _getFacilitators() {
		static $queryFacilitators = null;
		if ($queryFacilitators == null) {
			$sqlFacilitators = "SELECT p.`name`
				FROM `workshop_participant` wp, `participant` p
				WHERE wp.`participant_id` = p.`id`
				AND wp.`leader` = 1
				AND wp.workshop_id = :id";
			$this->db->prepare($sqlFacilitators);
		}

		$queryFacilitators->bindValue(':id', $row->id);
		$queryFacilitators->execute();
		$facilitators="";

		while ($frow=$queryFacilitators->fetch(PDO::FETCH_OBJ)) {
			foreach ($frow as $facilitator) {
				if ($facilitators != "") {
					$facilitators .= ", ";
				}
				$facilitators .= $frow->name;
			}
		}

		return $facilitators;
	}

	function _showFinishedStage($response, $stage2) {
		$sqlRooms = "SELECT id, name FROM `location`";
		$queryRooms = $this->db->prepare($sqlRooms);
		$queryRooms->execute();
		$locations = $queryRooms->fetchAll(PDO::FETCH_OBJ);

		$sqlRounds = "SELECT id, time_period FROM `round`";
		$sqlWorkshop = "SELECT id, name, description, votes
						  FROM workshop
						 WHERE location_id=:room
						   AND round_id=:round";
		$queryRounds = $this->db->prepare($sqlRounds);
		$queryRounds->execute($param);
		$rounds = $queryRounds->fetchAll(PDO::FETCH_OBJ);

		$queryWorkshop = $this->db->prepare($sqlWorkshop);

		$param = array ();
		$bofs = array ();
		$count = 0;
		foreach ($rounds as $round) {
			$bof = array();
			$bof['name'] = $round->time_period;
			$bof['rooms'] = array();
			// for each room....
			foreach ($locations as $location) {
				$bof['rooms'][$location->id]['name'] = $location->name;
				$queryWorkshop->bindValue(':room', $location->id);
				$queryWorkshop->bindValue(':round', $round->id);
				$queryWorkshop->execute();
				if ($row=$queryWorkshop->fetch(PDO::FETCH_OBJ)) {
					$bof['rooms'][$location->id]['topic'] = $row->name;
					$bof['rooms'][$location->id]['description'] = $row->description;
					$bof['rooms'][$location->id]['votes'] = $row->votes;
					$workshop = new Workshop($this->db, $id);
					$bof['rooms'][$location->id]['facilitators'] = $this->_getFacilitators($this->db, $row->id);
				}
			}
			$bofs[$round->id] = $bof;
			$count++;
		}
		$num_rounds = $count;

		return $this->view->render($response, 'proj_layout.html', [
				'rounds' => $bofs,
				'stage' => $stage2,
				'locked' => $stage2=='locked',
		]);
	}

	function _showVotingStage($response, $stage2) {
		$sql = "SELECT workshop.name, workshop.id, 0 as votes, '' as leader
				FROM workshop ORDER BY id DESC";
		$query=$this->db->prepare($sql);
		$param = array ();
		$query->execute($param);
		$bofs = array ();
		while ($row=$query->fetch(PDO::FETCH_OBJ)) {
			$bofs [$row->id] = $row;
		}
		$sql = 'SELECT workshop.id, SUM(participant) as `votes`
				FROM workshop
				LEFT JOIN workshop_participant ON workshop_participant.workshop_id = workshop.id
				GROUP BY workshop.id
				ORDER BY `votes` DESC';
		$query=$this->db->prepare($sql);
		$param = array ();
		$query->execute($param);
		while ($row=$query->fetch(PDO::FETCH_OBJ)) {
			$bofs[$row->id]->votes = $row->votes;
			$bofs[$row->id]->leader = $this->_getFacilitators($this->db, $row->id);
		}
		$bofs2 = array();
		foreach ($bofs as $bof) {
			$bofs2[] = $bof;
		}

		function cmp($a, $b)
		{
			if ($a->votes > $b->votes) return -1;
			if ($a->votes < $b->votes) return 1;
			return 0;
		}

		usort($bofs2, "cmp");
		return $this->view->render($response, 'proj_layout.html', [
				'bofs' => $bofs2,
				'stage' => $stage2,
				'locked' => $stage2=='locked',
		]);
	}

	/* 
	* gets topics list and generate the projector phase.
	* depending on the stage we're in we see topics and will be able to nominate a new one
	* or the possibility to vote for topics
	* in the last stage the list of selected topics will be there and location and time slot
	* 
	* 'stage' can be: 'nominating', 'voting', 'finished'
	* System can be locked down with variable 'locked'
	* 
	* voting and nominating stages are configured in the config table by timestamps
	* finished is when voting is over.
	* locked is automatically when out of periods of voting and nominating and finished
	* TODO: config item for locked False/True
	* 
	*/
	public function showProjectorView($request, $response, $args) {
		$stage =new Stage($this->db);
		$stage2 =$stage->getstage();

		if ($stage2 == "voting" || $stage2 == "locked") {
			return $this->_showVotingStage($response, $stage2);
		}

		if ($stage2 == "finished") {
			return $this->_showFinishedStage($response, $stage2);
		}

		// I hope we can't get here, but just in case....
		return $this->view->render($response, 'proj_layout.html', [
				'bofs' => array(),
				'stage' => $stage2,
				'locked' => $stage2=='locked',
		]);
	}
}

?>
