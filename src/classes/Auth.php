<?php

namespace ICCM\BOF;
use \Firebase\JWT\JWT;
use ICCM\BOF\Cookies;
use \PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Auth
{
    private $view;
    private $dbo;
    private $router;
    private $secrettoken;
    private $cookies;
    private $translator;
    private $settings;
    private $site;

    function __construct($view, $router, $dbo, $secrettoken, $cookies, $translator) {
        $this->view = $view;
        $this->dbo = $dbo;
        $this->router = $router;
        $this->secrettoken = $secrettoken;
        $this->cookies = $cookies;
        $this->translator = $translator;
        $this->settings = require __DIR__.'/../../cfg/settings.php';
        $this->site = $_SERVER['SERVER_NAME'];
    }

    public function authenticate($request, $response, $args) {
        $data = $request->getParsedBody();
        $login = $data['user_name'];
        if (($row = $this->dbo->authenticate($login, $data['password'])) && $row->valid) {
            if (!$row->active) {
                return $response->withRedirect($this->router->pathFor("login") . "?message=waitformoderation")->withStatus(302);
            }
            else if ($login == "admin") {
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
        $language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (array_key_exists('userinfo', $data)) {
            $userinfo = $data['userinfo'];
        } else {
            $userinfo = '';
        }
        if ($this->settings['settings']['moderated_registration']) {
            $active = 0;
        } else {
            $active = 1;
        }
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
            $token = bin2hex(random_bytes(16));
            $id = $this->dbo->addUser($login, $email, $password, $language, $userinfo, $active, $token);
            if (!is_numeric($id)) {
                error_log($id);
                die('error creating user');
            }

            if ($this->settings['settings']['moderated_registration']) {
                $subject = $this->translator->trans("Please confirm your email address");
                $accept_link = "https://".$this->site."/confirm_user?email=".urlencode($email)."&token=".$token;
                $body_html = $this->translator->trans("email_confirm_user", ['%site%' => $this->site, '%login%' => $login, '%link%' => $accept_link]);

                "Hello %login%,<br/>There has been a request to register for the site https://%site%<br/>If that was you, please confirm by visiting <a href=\"%link%\">this link</a><br/><br/>If you don't know anything about this, please ignore this email.<br/><br/>This email has been automatically generated.";
                $body = str_replace("<br/>", "\n", $body_html);
                $this->sendEmail($email, $subject, $body_html, $body);

                return $response->withRedirect($this->router->pathFor("login") . "?confirmuser=1")->withStatus(302);
            }

            # print the auto incremented user's ID
            # print "User added, got ID : " . $id;
            # $payload = array("is_admin" => false, "userid" => $id);
            return $response->withRedirect($this->router->pathFor("login") . "?newuser=1")->withStatus(302);
        }
    }

    public function moderateNewUser($email) {
        $userLang = $this->translator->getLocale();
        $this->translator->setLocale($this->settings['settings']['fallback_language']);
        $subject = $this->translator->trans('new registration %email%', ['%email%' => $email]);
        $accept_link = "https://".$this->site."/confirm_user?email=".urlencode($email)."&token=".$this->settings['settings']['moderation_token'];
        $html_body = $this->translator->trans("email_moderate_user", ['%site%' => $this->site, '%login%' => $login, '%email%' => $email, '%userinfo%' => $userinfo, '%link%' => $accept_link]);
        $text_body = str_replace("<br/>", "\n", $html_body);
        $email_to = $this->settings['settings']['moderation_email'];
        $this->translator->setLocale($userLang);
        $this->sendEmail($email_to, $subject, $html_body, $text_body);
    }

    public function sendEmail($email_to, $subject, $html_body, $text_body) {

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host       = $this->settings['settings']['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->settings['settings']['smtp']['user'];
        $mail->Password   = $this->settings['settings']['smtp']['passwd'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $this->settings['settings']['smtp']['port'];
        $mail->setFrom($this->settings['settings']['smtp']['from'], $this->settings['settings']['smtp']['from_name']);
        $mail->addAddress($email_to);
        $mail->Subject = $subject;
        $mail->CharSet = "UTF-8";

        if ($html_body != '') {
            $mail->isHTML(true);
            $mail->Body = $html_body;
            $mail->AltBody = $text_body;
        }
        else
        {
            $mail->Body = $text_body;
        }

        try {
            if (!$mail->send()) {
                error_log($mail->ErrorInfo);
            }
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    public function confirm_user($request, $response, $args) {
        $data = $request->getParsedBody();
        $token = $data['token'];

        if ($token == '') {
            $data = $request->getQueryParams();
            $token = $data['token'];
        }

        $email = $data['email'] = urldecode($data['email']);

        if (array_key_exists('confirm', $data)) {
            $confirm = $data['confirm'];
            if ($confirm == "true") {
                if ($token == $this->settings['settings']['moderation_token']) {
                    // moderator activates the user
                    $userLang = "en";
                    if ($this->dbo->activateUser($email, $userLang)) {

                        // send an email to the activated user in the language of the user
                        $adminLang = $this->translator->getLocale();
                        $this->translator->setLocale($userLang);
                        // $this->translator->trans(
                        $this->sendEmail($email,
                            $this->translator->trans("Account has been activated"),
                            "",
                            $this->translator->trans("Your account at %site% has been activated", ['%site%' => 'https://'.$this->site]));
                        $this->translator->setLocale($adminLang);

                        // send an email notification for the moderators
                        $email_to = $this->settings['settings']['moderation_email'];
                        $this->sendEmail($email_to, "$email has been activated", "", "$email has been activated");

                        return $this->view->render($response, 'confirm_user.html', array('message' => 'activated'));
                    }
                }
                else {
                    // user must confirm his own email
                    if ($this->dbo->confirmEmail($email, $token)) {

                        // send an email notification for the moderators
                        $this->moderateNewUser($email);

                        return $this->view->render($response, 'confirm_user.html', array('message' => 'confirmed'));
                    }
                }
            }
        }
        return $this->view->render($response, 'confirm_user.html', $data);

    }

    public function logout($request, $response, $args) {
        $this->cookies->set("authtoken", "", time()-3600);
        $config['show_githubforkme'] = true;
        return $this->view->render($response, 'home.html', $config);
    }
}

?>
