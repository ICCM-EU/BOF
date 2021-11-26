<?php

namespace ICCM\BOF;
use RuntimeException;

class Admin
{
	private $view;
	private $dbo;
	private $router;
	private $results;

	function __construct($view, $router, $dbo, $results) {
		$this->view = $view;
		$this->router = $router;
		$this->dbo = $dbo;
		$this->results = $results;
	}

	public function showAdminView($request, $response, $args) {
		$is_admin = $request->getAttribute('is_admin');
		if (!$is_admin) throw new RuntimeException("you don't have permissions for this page");

		$config = $this->dbo->getConfig();

		return $this->view->render($response, 'admin.html', $config);
	}

	public function update_config($request, $response, $args) {
		$is_admin = $request->getAttribute('is_admin');
		if (!$is_admin) throw new RuntimeException("you don't have permissions for this page");

		$data = $request->getParsedBody();

		if (!empty($data["password1"])) {
			if ($data["password1"] != $data["password2"]) {
				throw new RuntimeException("passwords do not match");
			}
			$this->dbo->changePassword('admin', $data['password1']);
		}

		if (!empty($data["reset_database"])) {
			if ($data["reset_database"] != "yes") {
				throw new RuntimeException("invalid request");
			}

			$this->dbo->reset();

			return $this->showAdminView($request, $response, $args);
		}

		if (!empty($data["download_database"])) {
			if ($data["download_database"] != "yes") {
				throw new RuntimeException("invalid request");
			}

			$settings = require __DIR__.'/../../cfg/settings.php';
			$settings = $settings['settings'];
			$dbhost=$settings['db']['host'];
			$dbname=$settings['db']['name'];
			$dbuser=$settings['db']['user'];
			$dbpassword=$settings['db']['pass'];

			// Call ob_get_clean() to force Content-Type header;
			// this works because the header that's already in the
			// output buffer will be lost after this call.
			ob_get_clean();

			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=db-backup-BOF-'.date('Y-m-d_hi').'.sql');
			passthru("mysqldump --user=$dbuser --password='$dbpassword' --host=$dbhost $dbname");
			die();

			throw new RuntimeException();
		}

		if (!empty($data['nomination_begins']) && !empty($data['time_nomination_begins'])) {
			$this->dbo->setConfigDateTime('nomination_begins', strtotime($data['nomination_begins']." ".$data['time_nomination_begins']));
		}

		if (!empty($data['nomination_ends']) && !empty($data['time_nomination_ends'])) {
			$this->dbo->setConfigDateTime('nomination_ends', strtotime($data['nomination_ends']." ".$data['time_nomination_ends']));
		}

		if (!empty($data['voting_begins']) && !empty($data['time_voting_begins'])) {
			$this->dbo->setConfigDateTime('voting_begins', strtotime($data['voting_begins']." ".$data['time_voting_begins']));
		}
		
		if (!empty($data['voting_ends']) && !empty($data['time_voting_ends'])) {
			$this->dbo->setConfigDateTime('voting_ends', strtotime($data['voting_ends']." ".$data['time_voting_ends']));
		}

		$prepRound = -1;
		if (is_array($data['rounds']) && count($data['rounds']) > 0) {
			$this->dbo->setRoundNames($data['rounds']);
			$prepRound = 0;
			foreach ($data['rounds'] as $round) {
				if ($round == $data['prep_bof_round']) {
					break;
				}
				$prepRound++;
			}
			if ($prepRound >= count($data['rounds'])) {
				$prepRound = -1;
			}
		}

		$prepLocation = -1;
		if (is_array($data['locations']) && count($data['locations']) > 0) {
			$this->dbo->setLocationNames($data['locations']);
			$prepLocation = 0;
			foreach ($data['locations'] as $location) {
				if ($location == $data['prep_bof_location']) {
					break;
				}
				$prepLocation++;
			}
			if ($prepLocation >= count($data['locations'])) {
				$prepLocation = -1;
			}
		}

		if (!empty($data['schedule_prep'])) {
			$this->dbo->setConfigPrepBoF('False', -1, -1);
                }
		else {
			$this->dbo->setConfigPrepBoF('True', $prepRound, $prepLocation);
		}

		return $this->showAdminView($request, $response, $args);
	}

	public function calcResult($request, $response, $args) {
		$is_admin = $request->getAttribute('is_admin');
		if (!$is_admin) throw new RuntimeException("you don't have permissions for this page");

		$config = $this->results->calculateResults();
        return $this->view->render($response, 'results.html', $config);
	}
}

?>
