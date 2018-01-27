<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use \PDO;

class Auth
{
    private $view;
    private $db;
    private $router;
    private $secrettoken;

    function __construct($view, $db, $router, $secrettoken) {
        $this->view = $view;
        $this->db = $db;
        $this->router = $router;
        $this->secrettoken = $secrettoken;
    }

    public function authenticate($request, $response, $args) {
        $data = $request->getParsedBody();
        $login = $data['user_name'];
        $password = $data['password'];
        $sql = 'SELECT * FROM `participant`
            WHERE (
                `name` = ?
            ) AND (
                `password` = PASSWORD(?)
            )';
        $query=$this->db->prepare($sql);
        $param = array ($login, $password);
        $query->execute($param);
        if ($row=$query->fetch(PDO::FETCH_OBJ)) {
            # TODO: at the moment there is only one admin. could be a separate flag in the table participants
            if ($login == "admin") {
                $payload = array("is_admin" => true);
                $token = JWT::encode($payload, $this->secrettoken, "HS256");
                setcookie("authtoken", $token, time()+3600);  // cookie expires in one hour
                return $response->withRedirect($this->router->pathFor("admin"))->withStatus(302);
            } else {
                # TODO redirect somewhere for the normal user, either nomination or voting, depending on the current stage
            }
        } else {
            echo json_encode("No valid user or password");
        }
    }

    public function logout($request, $response, $args) {
        setcookie("authtoken", "", time()-3600);
        return $this->view->render($response, 'loggedout.html');
    }
}

?>
