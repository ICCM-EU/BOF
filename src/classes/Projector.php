<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;

class Projector
{
	private $view;
	private $dbo;
	private $router;

	function __construct($view, $router, $dbo) {
		$this->view = $view;
		$this->router = $router;
		$this->dbo = $dbo;
	}
	function _getNominationStage($response, $stage2) {
		return $this->dbo->getWorkshops();
	}

	function _getFinishedStage($response, $stage2) {
		$locations = $this->dbo->getLocationNames();
		$rounds = $this->dbo->getRoundNames();

		$bofs = array ();
		$count = 0;
		foreach (array_keys($rounds) as $roundId) {
			$bof = array();
			$bof['name'] = $rounds[$roundId];
			$bof['rooms'] = array();
			// for each room....
			foreach (array_keys($locations) as $locationId) {
				$bof['rooms'][$locationId]['name'] = $locations[$locationId];
				if ($row=$this->dbo->getBookedWorkshop($roundId, $locationId)) {
					$bof['rooms'][$locationId]['topic'] = $row->name;
					$bof['rooms'][$locationId]['description'] = $row->description;
					$bof['rooms'][$locationId]['votes'] = $row->votes;
					$bof['rooms'][$locationId]['facilitators'] = $this->dbo->getFacilitators($row->id);
				}
			}
			$bofs[$roundId] = $bof;
			$count++;
		}
		$num_rounds = $count;

		return $bofs;
	}

	public static function cmpVotes($a, $b)
	{
		if ($a->votes > $b->votes) return -1;
		if ($a->votes < $b->votes) return 1;
		return 0;
	}

	function _getVotingStage($response, $stage2) {
		$bofs = $this->dbo->getCurrentVotes();
		usort($bofs, array("ICCM\BOF\Projector", "cmpVotes"));
		return $bofs;
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
	public function showProjectorView($request, $response, $stage, $args) {
		$stage2 = $stage->getstage();

		if ($stage2 == "nominating") {
			$bofs = $this->_getNominationStage($response, $stage2);
		}
		else if ($stage2 == "voting" || $stage2 == "locked") {
			$bofs = $this->_getVotingStage($response, $stage2);
		}
		else if ($stage2 == "finished") {
			$bofs = $this->_getFinishedStage($response, $stage2);
		}

		return $this->view->render($response, 'proj_layout.html', [
				'bofs' => $bofs,
				'stage' => $stage2,
				'locked' => $stage2=='locked',
		]);
	}
}

?>
