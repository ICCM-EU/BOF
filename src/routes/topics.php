<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/* 
 * gets topics list and generate the topics phase.
 * depending on the stage we're in we see topics and will be able to nominate a new one
 * or the possibility to vote for topics
 * in the last stage the list of selected topics will be there and location and time slot
 * 
 * 'stage' can be: 'nominating', 'voting', 'call_for_workshops'
 * System can be locked down with variable 'locked'
 * 
 * voting and nominating stages are configured in the config table by timestamps
 * call_for_workshops is if the voting and nominating stages overlap
 * locked is automatically when out of periods of voting and nominating
 * TODO: config item for locked False/True
 * 
 */
function float_eq($a, $b) {
    return abs(abs($a) - abs($b)) < 0.000001;
}
$app->get('/topics', function (Request $request, Response $response, array $args) {
    $userid = $request->getAttribute('userid');
    $settings = require __DIR__.'/../../cfg/settings.php';

    $filter = trim($request->getQueryParam('filtertags', ''));

    $sql = "SELECT *, '' as leader, '' as createdby
            FROM `workshop`";
    if ($filter != '') {
        $sql .= " WHERE 1=1";
        $filtertags = explode(" AND ", strtoupper($filter));
        foreach ($filtertags as $tag) {
            $negate = '';
            if (strlen($tag) > 4 && strtoupper(substr($tag, 0,3)) == "NOT") {
                $negate = 'NOT';
                $tag = substr($tag, 4);
            }
            if ($tag == "NONE") {
                $sql .= " AND `tags` = ''";
            } else if (strtoupper($tag) == "ALL") {
                $sql .= " AND 1=1";
            } else {
                $sql .= " AND LOWER(`tags`) $negate LIKE '%".trim(strtolower($tag)).";%'";
            }
        }
    }

    $query=$this->db->prepare($sql);
    $query->execute();
    $bofs = $query->fetchAll();

    // only display the first 200 characters of the description
    if ($settings['settings']['max_length_description_preview'] > 0) {
        $maxlength = $settings['settings']['max_length_description_preview'];
        foreach($bofs as &$bof) {
            if (strlen($bof['description']) > $maxlength) {
                $bof['description'] = substr($bof['description'], 0, $maxlength);
                $bof['more'] = '/topics/'.$bof['id'];
            }
        }
    }

    $sql = 'SELECT participant.name, participant.email, workshop_id
            FROM workshop_participant
            JOIN participant ON workshop_participant.participant_id = participant.id
            WHERE workshop_participant.leader = 1';
    $query=$this->db->prepare($sql);
    $param = array ();
    $query->execute($param);
    while ($row=$query->fetch(PDO::FETCH_OBJ)) {
        foreach($bofs as &$bof) {
            if($bof['id'] === $row->workshop_id) {
                if (strlen($bof['leader']) > 0) {
                    $bof['leader'] .= ', ';
                    $bof['leader_email'] .= ', ';
                }
                $bof['leader'] .= $row->name;
                $bof['leader_email'] .= $row->email;
            }
        }
    }

    $sql = 'SELECT participant.name, participant.email, workshop.id as workshop_id
            FROM workshop
            JOIN participant ON workshop.creator_id = participant.id';
    $query=$this->db->prepare($sql);
    $param = array ();
    $query->execute($param);
    while ($row=$query->fetch(PDO::FETCH_OBJ)) {
        foreach($bofs as &$bof) {
            if($bof['id'] === $row->workshop_id) {
                $bof['createdby'] = $row->name;
                $bof['createdby_email'] = $row->email;
            }
        }
    }

    $sql = 'SELECT workshop_id, participant
            FROM `workshop_participant`
            WHERE participant_id = :uid';
    $query = $this->db->prepare($sql);
    $query->execute(['uid' => $userid]);
    $votes = $query->fetchAll();
    $fullvotesleft = 20;

    foreach($votes as $vote) {
        $fullvotesleft -= float_eq($vote['participant'], 1) ? 1 : 0;
        foreach($bofs as &$bof) {
            if($vote['workshop_id'] === $bof['id'] && float_eq($vote['participant'], 1.0)) {
                $bof['fullvote'] = True;
                $bof['vote'] = 1.0;
            }
            if($vote['workshop_id'] === $bof['id'] && float_eq($vote['participant'], 0.25)) {
                $bof['quartervote'] = True;
                $bof['vote'] = 0.25;
            }
        }
    }

    // format the tags
    foreach($bofs as &$bof) {
        $bof['formatted_tags'] = '';
        if ($bof['tags'] != '') {
            $tags = explode(";", $bof['tags']);
            foreach($tags as $tag) {
                $bof['formatted_tags'] .= "<a href='?filtertags=".rawurlencode($tag)."'>$tag</a>&nbsp;";
            }
        }
    }

    function cmp($a, $b)
    {
        if ($a['vote'] > $b['vote']) return -1;
        if ($a['vote'] < $b['vote']) return 1;
        if ($a['id'] < $b['id']) return -1;
        if ($a['id'] > $b['id']) return 1;
        return 0;
    }

    usort($bofs, "cmp");

    global $app;
    $dbo = $app->getContainer()->get('ICCM\BOF\DBO');
    $config = $dbo->getConfig();
    $stage =$dbo->getStage();
    $params = $request->getQueryParams();
    $show_vote_message = array_key_exists('voted', $params) && $params['voted'] === '1';
    return $this->view->render($response, 'topics.html', [
        'is_moderator' => $request->getAttribute('is_moderator'),
        'bofs' => $bofs,
        'stage' => $stage,
        'locked' =>  $stage=='locked',
        'newuser' => $params['newuser'],
        'loggedin' => True,
        'left_votes' => $fullvotesleft,
        'voted_successfull' => $show_vote_message,
        'filtertags' => $filter,
        'allowedit' => $settings['settings']['allow_edit_nomination'] != false,
        'allowcomments' => $settings['settings']['allow_nomination_comments'] != false,
    ]);
})->setName('topics');

$app->get('/topics/{id}', 'ICCM\BOF\Nomination:editNomination')->setName('edittopic');

$app->post('/topics/{id}', 'ICCM\BOF\Nomination:updateNomination')->setName('updatetopic');

$app->post('/topics/{id}/comment', 'ICCM\BOF\Nomination:addComment')->setName('addcomment');

$app->post('/topics/{topic_id}/comment/{id}', 'ICCM\BOF\Nomination:updateComment')->setName('updatecomment');

?>
