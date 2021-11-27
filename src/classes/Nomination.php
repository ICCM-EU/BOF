<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;
use ICCM\BOF\Mailer;

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
        $this->mailer = new \ICCM\BOF\Mailer($dbo);
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
            $topic_id = $this->dbo->nominate($title, $description, $userid);
            $this->dbo->addQuarterVote($topic_id, $userid);
            $this->send_notification('new_post', $userid, $topic_id, -1);
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
            'loggedin' => True,
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
            if ($this->dbo->nominate_edit($id, $title, $description, $userid)) {
                $this->send_notification('edit_post', $userid, $id, -1);
            }
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
            $comment_id = $this->dbo->comment_add($topic_id, $comment, $userid);
            $this->dbo->addQuarterVote($topic_id, $userid);
            $this->send_notification('new_comment', $userid, $topic_id, $comment_id);
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
            if ($this->dbo->comment_edit($id, $comment_text)) {
                $this->send_notification('edit_comment', $userid, $topic_id, $id);
            }
            return $response->withRedirect($this->router->pathFor('edittopic', ['id' => $topic_id]), 302);
        }
        catch (\Exception $ex) { $ex; }

        // Handle error
        return $this->view->render($response, 'comment_error.html', ['errormsg' => 'Error updating comment', 'topic_id' => $topic_id]);
    }

    /**
     * send a notification per email to all people interested
     *
     * @param $action: new_post, edit_post, new_comment, edit_comment
     * @param $user_id
     * @param $topic_id
     * @param $comment_id
     */
    private function send_notification($action, $user_id, $topic_id, $comment_id) {

        $users_sent = array();

        // all users that voted for this bof
        $UsersThatVotedForThisWorkshop = $this->dbo->getAllVotersForWorkshop($topic_id);
        foreach ($UsersThatVotedForThisWorkshop as $user) {
            $users_sent[$user->email] = 1;
        }

        // if new topic, send to all users that want to know about new topics
        if ($action == 'new_post') {
            $UsersWithNewNotifications = $this->dbo->getUsersByNotificationsSetting(20);
            foreach ($UsersWithNewNotifications as $user) {
                $users_sent[$user->email] = 1;
            }
        }

        // send to all users that want everything
        $UsersWithAllNotifications = $this->dbo->getUsersByNotificationsSetting(99);
        foreach ($UsersWithAllNotifications as $user) {
            $users_sent[$user->email] = 1;
        }

        $bof = $this->dbo->getWorkshopDetails($topic_id);
        $bof = $bof[0];
        $comment = $this->dbo->getWorkshopComment($comment_id);
        $comment = $comment[0];
        $title = $bof->name;
        if (strlen($title) > 20) {
            $title = substr($title, 0, 20)."...";
        }
        $topic_url = "https://".$_SERVER['HTTP_HOST']."/topics/$topic_id";
        $comment_url = "https://".$_SERVER['HTTP_HOST']."/topics/$topic_id#comment$comment_id";

        // now send all the emails, in english.
        // TODO: use the language of each user???
        foreach (array_keys($users_sent) as $email) {
            if ($action == 'new_post') {
                $this->mailer->sendEmail($email, 'ICCM Workshops: new topic '.$title,
                    "see new topic at <a href='$topic_url'>".$_SERVER['HTTP_HOST']."</a><br/><br/>topic title: ".$bof->name ."<br/><br/>description: ". $bof->description,
                    "see new topic at $topic_url\n\ntopic title: ".$bof->name ."\n\ndescription: ". $bof->description);
            }
            if ($action == 'edit_post') {
                $this->mailer->sendEmail($email, 'ICCM Workshops: modified topic '.$title,
                    "see modified topic at <a href='$topic_url'>".$_SERVER['HTTP_HOST']."</a><br/><br/>topic title: ".$bof->name ."<br/><br/>description: ". $bof->description,
                    "see modified topic at $topic_url\n\ntopic title: ".$bof->name ."\n\ndescription: ". $bof->description);
            }
            if ($action == 'new_comment') {
                $this->mailer->sendEmail($email, 'ICCM Workshops: new comment on topic '.$title,
                    "see new comment at <a href='$comment_url'>".$_SERVER['HTTP_HOST']."</a><br/><br/>topic title: ".$bof->name ."<br/><br/>comment: ". $comment->comment,
                    "see new comment at $comment_url\n\ntopic title: ".$bof->name ."\n\ncomment: ". $comment->comment);
            }
            if ($action == 'edit_comment') {
                $this->mailer->sendEmail($email, 'ICCM Workshops: modified comment on topic '.$title,
                    "see modified comment at <a href='$comment_url'>".$_SERVER['HTTP_HOST']."</a><br/><br/>topic title: ".$bof->name ."<br/><br/>comment: ". $comment->comment,
                    "see modified comment at $comment_url\n\ntopic title: ".$bof->name ."\n\ncomment: ". $comment->comment);
            }
        }
    }
}

?>
