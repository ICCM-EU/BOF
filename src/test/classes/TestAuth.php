<?php

use PHPUnit\Framework\TestCase;
use ICCM\BOF\Auth;
use ICCM\BOF\Cookies;
use ICCM\BOF\DBO;
use \Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;
use Slim\Http\Response;
use Slim\Router;
use Slim\Http\Request;
use Symfony\Component\Translation\Translator;

/**
 * @covers \ICCM\BOF\Auth::__construct
 */
class TestAuth extends TestCase
{
    /**
     * @covers \ICCM\BOF\Auth::authenticate
     * @test
     */
    public function authenitcateFailsForBadPassword() {
        $data = [
            'user_name' => 'user1',
            'password' => 'password1'
        ];
        $user = (object) [
           'id' => 1,
           'name' => $data['user_name'],
           'valid' => false
        ];

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['authenticate'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('authenticate')
            ->with($data['user_name'], $data['password'])
            ->willReturn($user);

        // ServerRequestInterface mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // Response mock
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withRedirect', 'withStatus'])
            ->getMock();
        $response->expects($this->once())
            ->method('withRedirect')
            ->with('path/to/login?message=invalid')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturn($response);

        // Router mock
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pathFor'])
            ->getMock();
        $router->expects($this->once())
            ->method('pathFor')
            ->with('login')
            ->willReturn('path/to/login');

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $cookies = $this->getMockBuilder(Cookies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $cookies->expects($this->never())
            ->method('set');

        $auth = new Auth($view, $router, $dbo, 'secret_token', $cookies, null);
        $this->assertEquals($response, $auth->authenticate($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Auth::authenticate
     * @test
     */
    public function authenticateSuccessForRegularUser() {
        $data = [
            'user_name' => 'user1',
            'password' => 'password1'
        ];
        $user = (object) [
           'id' => 101,
           'name' => $data['user_name'],
           'valid' => true
        ];

        $payload = array("is_admin" => false, "userid" => $user->id);
        $checkPayload = function($encoded_payload) use ($payload) {
            $decoded_payload = (array) JWT::decode($encoded_payload, 'secret_token', ['HS256']);
            return $payload == $decoded_payload;;
        };

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['authenticate'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('authenticate')
            ->with($data['user_name'], $data['password'])
            ->willReturn($user);

        // ServerRequestInterface mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // Response mock
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withRedirect', 'withStatus'])
            ->getMock();
        $response->expects($this->once())
            ->method('withRedirect')
            ->with('path/to/topics')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturn($response);

        // Router mock
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pathFor'])
            ->getMock();
        $router->expects($this->once())
            ->method('pathFor')
            ->with('topics')
            ->willReturn('path/to/topics');

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $cookies = $this->getMockBuilder(Cookies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $cookies->expects($this->once())
            ->method('set')
            ->with("authtoken", $this->callback($checkPayload), $this->greaterThanOrEqual(time()+3600))
            ->willReturn(true);

        $auth = new Auth($view, $router, $dbo, 'secret_token', $cookies, null);
        $this->assertEquals($response, $auth->authenticate($request, $response, null, $cookies));
    }

    /**
     * @covers \ICCM\BOF\Auth::authenticate
     * @test
     */
    public function authenticateSuccessForAdmin() {
        $data = [
            'user_name' => 'admin',
            'password' => 'password1'
        ];
        $user = (object) [
           'id' => 1,
           'name' => $data['user_name'],
           'valid' => true
        ];

        $payload = array("is_admin" => true, "userid" => $user->id);
        $checkPayload = function($encoded_payload) use ($payload) {
            $decoded_payload = (array) JWT::decode($encoded_payload, 'secret_token', ['HS256']);
            return $payload == $decoded_payload;;
        };

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['authenticate'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('authenticate')
            ->with($data['user_name'], $data['password'])
            ->willReturn($user);

        // ServerRequestInterface mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // Response mock
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withRedirect', 'withStatus'])
            ->getMock();
        $response->expects($this->once())
            ->method('withRedirect')
            ->with('path/to/admin')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturn($response);

        // Router mock
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pathFor'])
            ->getMock();
        $router->expects($this->once())
            ->method('pathFor')
            ->with('admin')
            ->willReturn('path/to/admin');

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $cookies = $this->getMockBuilder(Cookies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $cookies->expects($this->once())
            ->method('set')
            ->with("authtoken", $this->callback($checkPayload), $this->greaterThanOrEqual(time()+3600))
            ->willReturn(true);

        $auth = new Auth($view, $router, $dbo, 'secret_token', $cookies, null);
        $this->assertEquals($response, $auth->authenticate($request, $response, null, $cookies));
    }

    /**
     * @covers \ICCM\BOF\Auth::logout
     * @test
     */
    public function logout() {
        $config = [
            'show_githubforkme' => true
        ];

        // ResponseInterface mock
        $response = $this->getMockBuilder(ResponseInterface::class)
              ->disableOriginalConstructor()
              ->getMock();

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->once())
            ->method('render')
            ->with($response, 'home.html', $config)
            ->willReturn(4);

        $cookies = $this->getMockBuilder(Cookies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $cookies->expects($this->once())
            ->method('set')
            ->with("authtoken", '', $this->greaterThanOrEqual(time()-3600))
            ->willReturn(true);

        $auth = new Auth($view, null, null, 'secret_token', $cookies, null);
        $this->assertEquals(4, $auth->logout(null, $response, null, $cookies));
    }

    /**
     * @covers \ICCM\BOF\Auth::new_user
     * @test
     */
    public function newUserFailsForEmptyPassword() {
        $data = [
            'user_name' => 'user1',
            'email' => 'user1@example.org',
            'password' => ''
        ];

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkForUser'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('checkForUser');

        // ServerRequestInterface mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // Response mock
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withRedirect', 'withStatus'])
            ->getMock();
        $response->expects($this->never())
            ->method('withRedirect');
        $response->expects($this->never())
            ->method('withStatus');

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        # Cookies mock
        $cookies = $this->getMockBuilder(Cookies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $cookies->expects($this->never())
            ->method('set');

        # Translator mock
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['trans'])
            ->getMock();

        $translator->expects($this->once())
            ->method('trans')
            ->with("Empty user or pass. Don't do that!");

        $auth = new Auth($view, null, $dbo, 'secret_token', $cookies, $translator);
        $this->assertEquals(0, $auth->new_user($request, $response, $data['user_name'], $data['password']));
    }

    /**
     * @covers \ICCM\BOF\Auth::new_user
     * @test
     */
    public function newUserFailsForEmptyUser() {
        $data = [
            'user_name' => '',
            'password' => 'password'
        ];

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkForUser'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('checkForUser');

        // ServerRequestInterface mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // Response mock
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withRedirect', 'withStatus'])
            ->getMock();
        $response->expects($this->never())
            ->method('withRedirect');
        $response->expects($this->never())
            ->method('withStatus');

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        # Cookies mock
        $cookies = $this->getMockBuilder(Cookies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $cookies->expects($this->never())
            ->method('set');

        # Translator mock
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['trans'])
            ->getMock();

        $translator->expects($this->once())
            ->method('trans')
            ->with("Empty user or pass. Don't do that!");

        $auth = new Auth($view, null, $dbo, 'secret_token', $cookies, $translator);
        $this->assertEquals(0, $auth->new_user($request, $response, $data['user_name'], $data['password']));
    }

    /**
     * @covers \ICCM\BOF\Auth::new_user
     * @test
     */
    public function newUserFailsForExistingUser() {
        $data = [
            'user_name' => 'user1',
            'email' => 'user1@example.org',
            'password' => 'password1'
        ];

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkForUser'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('checkForUser')
            ->with($data['user_name'])
            ->willReturn(true);

        // ServerRequestInterface mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // Response mock
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withRedirect', 'withStatus'])
            ->getMock();
        $response->expects($this->never())
            ->method('withRedirect')
            ->with('path/to/login?message=invalid')
            ->willReturn($response);
        $response->expects($this->never())
            ->method('withStatus')
            ->with(302)
            ->willReturn($response);

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        # Cookies mock
        $cookies = $this->getMockBuilder(Cookies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $cookies->expects($this->never())
            ->method('set');

        # Translator mock
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['trans'])
            ->getMock();

        $translator->expects($this->once())
            ->method('trans')
            ->with("User already exists");

        $auth = new Auth($view, null, $dbo, 'secret_token', $cookies, $translator);
        $this->assertEquals(0, $auth->new_user($request, $response, $data['user_name'], $data['password']));
    }

    /**
     * @covers \ICCM\BOF\Auth::new_user
     * @test
     */
    public function newUserSuccessForNewUser() {
        $data = [
            'user_name' => 'user1',
            'email' => 'user1@example.org',
            'password' => 'password1'
        ];
        $user = (object) [
           'id' => 1,
           'name' => $data['user_name'],
           'valid' => false
        ];

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkForUser', 'addUser'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('addUser')
            ->with($data['user_name'], $data['email'], $data['password'])
            ->willReturn(true);

        $dbo->expects($this->once())
            ->method('checkForUser')
            ->with($data['user_name'], $data['email'])
            ->willReturn(false);

        // ServerRequestInterface mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // Response mock
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withRedirect', 'withStatus'])
            ->getMock();
        $response->expects($this->once())
            ->method('withRedirect')
            ->with('path/to/login?newuser=1')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturn($response);

        // Router mock
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pathFor'])
            ->getMock();
        $router->expects($this->once())
            ->method('pathFor')
            ->with('login')
            ->willReturn('path/to/login');

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $cookies = $this->getMockBuilder(Cookies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $cookies->expects($this->never())
            ->method('set');

        $auth = new Auth($view, $router, $dbo, 'secret_token', $cookies, null);
        $this->assertEquals($response, $auth->new_user($request, $response, 'user1', 'user1@example.org', 'password'));
    }

}
