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
        if (strlen($title) == 0 || strlen($description) == 0) {
		print "Empty title or description. Don't do that!";
		return 0;
        }
        $sql = 'INSERT IGNORE INTO `workshop` (`name`,`description`)
            VALUES (?, ?)';
		
		$query=$this->db->prepare($sql);
		$param = array ($title, $description);
		
		$query->execute($param);
		return $this->view->render($response, 'nomination_response.html', [
            'loggedin' => True,
        ]);

		// Handle error
		// return $this->view->render($response, 'nomination_error.html');
		
    }
}

?>
