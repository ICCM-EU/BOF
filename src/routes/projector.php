<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/projector', function (Request $request, Response $response, array $args) {
        $sql = 'SELECT * FROM `workshop` where published >0 order by votes desc, id desc';
        $query=$this->db->prepare($sql);
        $param = array ();
        $query->execute($param);
	$bofs = array ();
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
		$bofs [] = $row;
	}
        return $this->view->render($response, 'projector.html',[
        'bofs' => $bofs
	]);
})->setName('projector');

?>
