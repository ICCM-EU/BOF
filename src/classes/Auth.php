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

    private function signin($response, $login, $userid) {
        if ($login == "admin") {
            $payload = array("is_admin" => true, "userid" => $userid);
            $goto = $this->router->pathFor("admin");
        } else {
            # going to topics
            $payload = array("is_admin" => false, "userid" => $userid);
            $goto = $this->router->pathFor("topics");
        }
        $token = JWT::encode($payload, $this->secrettoken, "HS256");
        $session_duration = 3600; // cookie expires in one hour by default
        if (array_key_exists('session_duration_days', $this->settings['settings'])) {
            $session_duration = $this->settings['settings']['session_duration_days']*24*3600;
        }
        else if (array_key_exists('session_duration_hours', $this->settings['settings'])) {
            $session_duration = $this->settings['settings']['session_duration_hours']*3600;
        }
        $this->cookies->set("authtoken", $token, time()+$session_duration);
        return $response->withRedirect($goto)->withStatus(302);
    }

    public function authenticate($request, $response, $args) {
        $data = $request->getParsedBody();
        $login = $data['user_name'];
        if (($row = $this->dbo->authenticate($login, $data['password'])) && $row->valid) {
            if (!$row->active) {
                return $this->view->render($response, 'login.html', array('error' => $this->translator->trans("Wait for moderation.")));
	        } else {
                return $this->signin($response, $login, $row->id);
	        }
        } else {
            return $this->view->render($response, 'login.html', array('error' => $this->translator->trans("Invalid username or password.")));
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
            return $this->view->render($response, 'register.html', array('error' => $this->translator->trans("Empty user or pass. Don't do that!"),
                'user_name' => $login, 'email' => $email, 'userinfo' => $userinfo));
        }
        if (!$this->checkPasswordQuality($password)) {
            return $this->view->render($response, 'register.html', array('error' => $this->translator->trans("password_policy_violated"),
                'user_name' => $login, 'email' => $email, 'userinfo' => $userinfo, 'moderated_registration' => $this->settings['settings']['moderated_registration']));
        }
        if ($this->dbo->checkForUser($login, $email)) {
            # user already exist
            return $this->view->render($response, 'register.html', array('error' => $this->translator->trans("User already exists"),
                'user_name' => $login, 'email' => $email, 'userinfo' => $userinfo));
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
                $body = str_replace("<br/>", "\n", $body_html);
                $this->sendEmail($email, $subject, $body_html, $body);

                return $this->view->render($response, 'login.html', array('message' => $this->translator->trans('Please confirm your email address by visiting the link sent to your email address.')));
            }

            return $this->signin($response, $login, $id);
        }
    }

    public function reset_pwd($request, $response, $args) {
        $data = $request->getParsedBody();

        if (!$data) {
            $data = $request->getQueryParams();
        }

        if (!array_key_exists('email', $data) || $data['email'] == '') {
            return $this->view->render($response, 'reset_pwd.html');
        }

        $email = $data['email'] = urldecode($data['email']);

        if (!array_key_exists('token', $data)) {
            $token = bin2hex(random_bytes(16));
            $subject = $this->translator->trans("Reset Password");
            $accept_link = "https://".$this->site."/reset_pwd?email=".urlencode($email)."&token=".$token;
            $html_body = $this->translator->trans("email_reset_pwd", ['%site%' => $this->site, '%link%' => $accept_link]);
            $text_body = str_replace("<br/>", "\n", $html_body);

            if ($this->dbo->startResetPassword($email, $token)) {
                $this->sendEmail($email, $subject, $html_body, $text_body);
            }

            return $this->view->render($response, 'reset_pwd.html', array('message' => $this->translator->trans("An Email has been sent for the password reset")));
        }

        $token = $data['token'];

        if (!array_key_exists('password', $data) || $data['password'] == '') {
            return $this->view->render($response, 'reset_pwd.html', array('token' => $token, 'email' => $email));
        }

        $password = $data['password'];
        if (!$this->checkPasswordQuality($password)) {
            return $this->view->render($response, 'reset_pwd.html', array('error' => $this->translator->trans("password_policy_violated"),
                'token' => $token, 'email' => $email));
        }
        if ($this->dbo->resetPassword($email, $token, $password) === true) {
            return $this->view->render($response, 'login.html', array('message' => $this->translator->trans('Password has been reset')));
        }

        return $this->view->render($response, 'reset_pwd.html');
    }

    /**
     * Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.
     */
    private function checkPasswordQuality($password) {

        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);
        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
            return false;
        }

        return true;
    }

    public function moderateNewUser($email, $login, $userinfo) {
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

        if (!$data) {
            $data = $request->getQueryParams();
        }

        $token = $data['token'];
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
                        $this->dbo->getUserDetails($email, $login, $userinfo);
                        // send an email notification for the moderators
                        $this->moderateNewUser($email, $login, $userinfo);

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
