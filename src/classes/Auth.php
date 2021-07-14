<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use ICCM\BOF\Cookies;
use \PDO;

class Auth
{
    private $view;
    private $dbo;
    private $router;
    private $secrettoken;
    private $cookies;
    private $translator;

    function __construct($view, $router, $dbo, $secrettoken, $cookies, $translator) {
        $this->view = $view;
        $this->dbo = $dbo;
        $this->router = $router;
        $this->secrettoken = $secrettoken;
        $this->cookies = $cookies;
        $this->translator = $translator;
    }

    public function authenticate($request, $response, $args) {
        $data = $request->getParsedBody();
        $login = $data['user_name'];
        if (($row = $this->dbo->authenticate($login, $data['password'])) && $row->valid) {
            if ($login == "admin") {
                $payload = array("is_admin" => true, "userid" => $row->id);
                $goto = $this->router->pathFor("admin");
            } else {
                # going to topics
                $payload = array("is_admin" => false, "userid" => $row->id);
                $goto = $this->router->pathFor("topics");
            }
            $token = JWT::encode($payload, $this->secrettoken, "HS256");
            $this->cookies->set("authtoken", $token, time()+3600);  // cookie expires in one hour
            return $response->withRedirect($goto)->withStatus(302);
        } else {
            // echo json_encode("No valid user or password");
            return $response->withRedirect($this->router->pathFor("login") . "?message=invalid")->withStatus(302);
        }
    }
    
    
    /*
    	register a user;
    	user supllies a username and password
    	check if user exists, if so respond with return code 0
    	if users doesn't exist, create it and return with the user's ID
	*/
    public function new_user($request, $response, $args) {
        $data = $request->getParsedBody();
        $login = $data['user_name'];
        $email = $data['email'];
        $password = $data['password'];
        if (strlen($login) == 0 || strlen($password) == 0 || strlen($email) == 0) {
            print $this->translator->trans("Empty user or pass. Don't do that!");
            return 0;
        }
        if ($this->dbo->checkForUser($login, $email)) {
            # user already exist, so return with error code 0
            print $this->translator->trans("User already exists");
            return 0;
        }
        else {
            $id = $this->dbo->addUser($login, $email, $password);
            //if (is_string($id)) {
                //echo $id;
            //}
            # print the auto incremented user's ID
            # print "User added, got ID : " . $id;
            $payload = array("is_admin" => false, "userid" => $id);
            return $response->withRedirect($this->router->pathFor("login") . "?newuser=1")->withStatus(302);
        }
    }

		
    public function logout($request, $response, $args) {
        $this->cookies->set("authtoken", "", time()-3600);
        $config['show_githubforkme'] = true;
        return $this->view->render($response, 'home.html', $config);
    }
}

?>
