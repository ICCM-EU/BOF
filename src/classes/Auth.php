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
            if ($login == "admin") {
                $payload = array("is_admin" => true, "userid" => $row->id);
                $goto = $this->router->pathFor("admin");
            } else {
                # going to topics
                $payload = array("is_admin" => false, "userid" => $row->id);
                return $response->withRedirect($this->router->pathFor("topics"))->withStatus(302);
            }
            $token = JWT::encode($payload, $this->secrettoken, "HS256");
            setcookie("authtoken", $token, time()+3600);  // cookie expires in one hour
            return $response->withRedirect($goto)->withStatus(302);
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
        $password = $data['password'];
        $sql = 'SELECT * FROM `participant`
            WHERE ( `name` = ? )';
        $query=$this->db->prepare($sql);
        $param = array ($login);
        $query->execute($param);
        if ($row=$query->fetch(PDO::FETCH_OBJ)) {
			# user already exist, so return with error code 0
			print "User already exists";
			return 0;
		}
		else {
			$sql = 'INSERT INTO `participant`
				(`name`, `password`)
				VALUES (?, PASSWORD(?))';

#			$this->db->beginTransaction();
			$query=$this->db->prepare($sql);
			$param = array ($login, $password);
			try {
			    $query->execute($param);
			} catch (PDOException $e){
			    echo $e->getMessage();
			}
			#TODO: needs to check session to commit() on
#			$this->db->commit();
			# print the auto incremented user's ID
			# print "User added, got ID : " . $this->db->lastInsertId();
			$payload = array("is_admin" => false, "userid" => $this->db->lastInsertId());
			return $response->withRedirect($this->router->pathFor("topics"))->withStatus(302);
		}
    }

		
    public function logout($request, $response, $args) {
        setcookie("authtoken", "", time()-3600);
        return $this->view->render($response, 'loggedout.html');
    }
}

?>
