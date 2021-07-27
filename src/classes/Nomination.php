<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;

class Nomination
{
    private $view;
    private $dbo;
    private $router;
    private $config;

    function __construct($view, $router, $dbo) {
        $this->view = $view;
        $this->dbo = $dbo;
        $this->router = $router;
        $this->config = $dbo->getConfig();
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
        return $this->view->render($response, 'nomination_error.html', array('errormsg' => 'A BOF with that title has already been submitted.'));
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
        $comments = $this->dbo->getWorkshopComments($args['id']);
        $user_id = $request->getAttribute('userid');

        return $this->view->render($response, 'nomination_edit.html', [
            'topic' => $bof[0],
            'user_id' => $user_id,
            'canedit' => $this->canEditNomination($bof[0], $user_id),
            'allowcomments' => $this->settings['settings']['allow_nomination_comments'] != false,
            'comments' => $comments
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

    public function addComment($request, $response, $args) {
        global $app;
        if ($this->settings['settings']['allow_nomination_comments'] == false) {
            print "Comments are disabled. Don't do that!";
            return 0;
        }

        $data = $request->getParsedBody();
        $topic_id = $data['topic_id'];
        $comment = $data['comment'];
        $userid = $request->getAttribute('userid');
        if (strlen($topic_id) == 0 || strlen($comment) == 0) {
            print "Empty topic id or comment. Don't do that!";
            return 0;
        }

        try
        {
            $this->dbo->comment_add($topic_id, $comment, $userid);
            return $response->withRedirect($this->router->pathFor('edittopic', ['id' => $topic_id]), 302);
        }
        catch (\Exception $ex) { $ex; }

        // Handle error
        return $this->view->render($response, 'comment_error.html', ['errormsg' => 'Error adding comment', 'topic_id' => $topic_id]);
    }

    public function updateComment($request, $response, $args) {
        $data = $request->getParsedBody();
        $id = $data['id'];
        $topic_id = $data['topic_id'];
        $comment_text = $data['comment'];
        $userid = $request->getAttribute('userid');
        if (strlen($id) == 0 || strlen($comment_text) == 0) {
            print "Empty comment id or comment. Don't do that!";
            return 0;
        }

        $comment = $this->dbo->getWorkshopComment($id);

        // is this the creator of the comment?
        if ($comment->user_id != $user_id)
        {
            print "You don't have permissions to edit this comment. Don't do that!";
            return 0;
        }

        try
        {
            $this->dbo->comment_edit($id, $comment_text);
            return $response->withRedirect($this->router->pathFor('edittopic', ['id' => $topic_id]), 302);
        }
        catch (\Exception $ex) { $ex; }

        // Handle error
        return $this->view->render($response, 'comment_error.html', ['errormsg' => 'Error updating comment', 'topic_id' => $topic_id]);
    }

}

?>
