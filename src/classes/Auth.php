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
                print "hello, how are you doing?";
            }
        } else {
            echo json_encode("No valid user or password");
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
        $sql = 'SELECT * FROM `participant`
            WHERE ( `name` = ? )';
        $query=$this->db->prepare($sql);
        $param = array ($login);
        $query->execute($param);
        if ($row=$query->fetch(PDO::FETCH_OBJ)) {
			# user already exist, so return with error code 0
			return 0;
		}
		else {
			$data = $request->getParsedBody();
			$login = $data['user_name'];
			$password = $data['password'];
			$sql = 'INSERT INTO `participant`
				(`name`, `password`)
				VALUES (?, PASSWORD(?))';
			$query=$this->db->prepare($sql);
			$param = array ($login, $password);
			try {
			    $query->execute($param);
			} catch (PDOException $e){
			    echo $e->getMessage();
			}
			$this->db->commit();
			# print the auto incremented user's ID
			print "User added, got ID : " . $this->db->lastInsertId();
		}
    }

		
    public function logout($request, $response, $args) {
        setcookie("authtoken", "", time()-3600);
        return $this->view->render($response, 'loggedout.html');
    }
}

?>
