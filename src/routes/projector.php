<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/* 
 * gets topics list and generate the projector phase.
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
$app->get('/projector', function (Request $request, Response $response, array $args) {

	$stage =new ICCM\BOF\Stage($this->db);
        $stage2 =$stage->getstage();

        $sql = 'SELECT workshop.name, workshop.id, 0 as votes
                FROM workshop';
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
                GROUP BY workshop_id
                ORDER BY `votes` DESC';
        $query=$this->db->prepare($sql);
        $param = array ();
        $query->execute($param);
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
           $bof[$row->id]->votes = $row->votes;
        }
// TODO order
        $bofs2 = array();
        foreach ($bofs as $bof) {
            $bofs2[] = $bof;
        }

        return $this->view->render($response, 'proj_layout.html', [
                'bofs' => $bofs2,
                'stage' => $stage2,
                'locked' => $stage2=='locked',
	]);
})->setName('projector');

?>
