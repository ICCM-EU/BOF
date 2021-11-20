<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;

class Nomination
{
    private $view;
    private $dbo;
    private $router;
    private $settings;

    function __construct($view, $router, $dbo) {
        $this->view = $view;
        $this->dbo = $dbo;
        $this->router = $router;
        $this->settings = require __DIR__.'/../../cfg/settings.php';
    }

    public function nominate($request, $response, $args) {
        $data = $request->getParsedBody();
        $title = $data['title'];
        $description = $data['description'];
        $userid = $request->getAttribute('userid');
        if (strlen($title) == 0 || strlen($description) == 0) {
            print "Empty title or description. Don't do that!";
            return 0;
        }
        try
        {
            $this->dbo->nominate($title, $description, $userid);
            return $this->view->render($response, 'nomination_response.html', [
                'loggedin' => True,
            ]);
        }
        catch (\Exception $ex) { $ex; }

        // Handle error
        return $this->view->render($response, 'nomination_error.html');
    }

    // check if we can edit the nomination: either admin, or author, or facilitator
    private function canEditNomination($bof, $userid) {
        if ($this->settings['settings']['allow_edit_nomination'] == false) {
            return false;
        }
        return ($userid == 1 || $bof->creator_id == $userid || $bof->leader == $userid);
    }

    public function editNomination($request, $response, $args) {
        $bof = $this->dbo->getWorkshopDetails($args['id']);
        $user_id = $request->getAttribute('userid');

        return $this->view->render($response, 'nomination_edit.html', [
            'topic' => $bof[0],
            'user_id' => $user_id,
            'canedit' => $this->canEditNomination($bof[0], $user_id),
            ]);
    }

    public function updateNomination($request, $response, $args) {
        $data = $request->getParsedBody();
        $id = $data['id'];
        $title = $data['title'];
        $description = $data['description'];
        $userid = $request->getAttribute('userid');
        if (strlen($title) == 0 || strlen($description) == 0) {
            print "Empty title or description. Don't do that!";
            return 0;
        }

        $bof = $this->dbo->getWorkshopDetails($id);

        if (!$this->canEditNomination($bof[0], $request->getAttribute('userid')))
        {
            print "You don't have permissions to edit this topic. Don't do that!";
            return 0;
        }

        try
        {
            $this->dbo->nominate_edit($id, $title, $description, $userid);
            return $this->view->render($response, 'nomination_updated.html', [
                'loggedin' => True,
            ]);
        }
        catch (\Exception $ex) { $ex; }

        // Handle error
        return $this->view->render($response, 'nomination_error.html');
    }
}

?>
