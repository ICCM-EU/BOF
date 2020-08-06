<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;
use RuntimeException;

class Moderation
{
	private $view;
	private $dbo;
	private $router;

	function __construct($view, $router, $dbo) {
		$this->view = $view;
		$this->dbo = $dbo;
		$this->router = $router;
	}

	public function showModerationView($request, $response, $args) {
		$is_admin = $request->getAttribute('is_admin');
		if (!$is_admin) throw new RuntimeException("you don't have permissions for this page");

		$bofs = $this->dbo->getWorkshopsDetails();

		$participants = $this->dbo->getUsers();

		return $this->view->render($response, 'moderation.html',[
			'loggedin' => true,
			'bofs' => $bofs,
			'participants' => $participants
			]);
	}

	public function moderate($request, $response, $args) {
		$is_admin = $request->getAttribute('is_admin');
		if (!$is_admin) throw new RuntimeException("you don't have permissions for this page");

		$data = $request->getParsedBody();
		$operation = $data['operation'];
		
		if ($operation == "delete") {
			$this->dbo->deleteWorkshop($data['id']);
		}
		else if ($operation == "merge") {
			$this->dbo->mergeWorkshops($data['id'], $data['mergeWithWorkshop']);
		} 
		else if ($operation == "addFacilitator") {
			$this->dbo->addFacilitator($data['id'], $data['facilitator']);
		} 
		else {
			$this->dbo->updateWorkshop($data['id'], $data['title'], $data['description'], $data['published']);
		}

		return $this->showModerationView($request, $response, $args);
	}

}

?>
