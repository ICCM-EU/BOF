<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;

class Nomination
{
    private $view;
    private $dbo;
    private $router;

    function __construct($view, $router, $dbo) {
        $this->view = $view;
        $this->dbo = $dbo;
        $this->router = $router;
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
}

?>
