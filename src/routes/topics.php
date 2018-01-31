<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/* 
 * gets topics list and generate the topics phase.
 * depending on the stage we're in we see topics and will be able to nominate a new one
 * or the possibility to vote for topics
 * in the last stage the list of selected topics will be there and location and time slot
 * 
 * 'stage' can be: 'nominating', 'voting'
 * System can be locked down with variable 'locked'
 * 
 * voting and nominating stages are configured in the config table by timestamps
 * locked is automatically when out of periods of voting and nominating
 * TODO: config item for locked False/True
 * 
 */
$app->get('/topics', function (Request $request, Response $response, array $args) {
    $sql = 'SELECT * FROM `workshop`';
    $query=$this->db->prepare($sql);
    $param = array ();
    $query->execute($param);
    $bofs = array ();
    while ($row=$query->fetch(PDO::FETCH_OBJ)) {
        $bofs [] = $row;
    }
    $allgetvars = $request->getQueryParams();
    $stage =new ICCM\BOF\Stage($this->db);
	  $stage2 =$stage->getstage();
    return $this->view->render($response, 'topics.html', [
        'bofs' => $bofs,
        'stage' => $stage2,
        'locked' =>  $stage2=='locked',
        'newuser' => $allgetvars['newuser'],
        'loggedin' => True,
    ]);
})->setName('topics');

?>
