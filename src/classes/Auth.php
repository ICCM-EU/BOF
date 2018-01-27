<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;

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
        if ($login == "admin" && $password == "admin") {
            $payload = array("is_admin" => true);
            $token = JWT::encode($payload, $this->secrettoken, "HS256");
            setcookie("authtoken", $token, time()+3600);  // cookie expires in one hour
            return $response->withRedirect($this->router->pathFor("admin"))->withStatus(302);
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
