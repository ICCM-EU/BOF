<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;

class Nomination
{
    private $view;
    private $db;
    private $router;

    function __construct($view, $db, $router) {
        $this->view = $view;
        $this->db = $db;
        $this->router = $router;
    }

    public function nominate($request, $response, $args) {
        $data = $request->getParsedBody();
        $title = $data['title'];
        $description = $data['description'];
        $sql = 'INSERT IGNORE INTO `workshop` (`name`,`description`)
            VALUES (?, ?)';
		
		$query=$this->db->prepare($sql);
		$param = array ($title, $description);
		
		$query->execute($param);
		return $this->view->render($response, 'nomination_response.html');

		// Handle error
		// return $this->view->render($response, 'nomination_error.html');
		
    }
}

?>
