<?php

require_once __DIR__ . '/../../classes/Nomination.php';
require_once __DIR__ . '/../../classes/DBO.php';
require_once __DIR__ . '/../../vendor/slim/twig-view/src/Twig.php';

use PHPUnit\Framework\TestCase;
use ICCM\BOF\Nomination;
use ICCM\BOF\DBO;
use Slim\Views\Twig;
use Slim\Http\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers ICCM\BOF\Nomination
 */
class TestNomination extends TestCase
{
    /**
     * @test
     */
    public function nominateInvokesDBONominateWithProperArgs() {
        $data = [
            'title' => 'Title',
            'description' => 'Description',
        ];
        $userid = 1;

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['nominate'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('nominate')
            ->with($data['title'], $data['description'], $userid);

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getAttribute')
            ->willReturn($userid);

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

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
            ->with($response, 'nomination_response.html', ['loggedin' => True])
            ->willReturn(4);

        $nomination = new Nomination($view, null, $dbo);
        $this->assertEquals(4, $nomination->nominate($request, $response, null));
    }

    /**
     * @test
     */
    public function nominateReturnsErrorForEmptyTitle() {
        $this->setOutputCallback(function() {});
        $data = [
            'title' => '',
            'description' => 'Description',
        ];
        $userid = 1;

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['nominate'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('nominate');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getAttribute')
            ->willReturn($userid);

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // ResponseInterface mock
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $nomination = new Nomination($view, null, $dbo);
        $this->assertEquals(0, $nomination->nominate($request, $response, null));
    }

    /**
     * @test
     */
    public function nominateReturnsErrorForNullTitle() {
        $this->setOutputCallback(function() {});
        $data = [
            'title' => null,
            'description' => 'Description',
        ];
        $userid = 1;

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['nominate'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('nominate');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getAttribute')
            ->willReturn($userid);

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // ResponseInterface mock
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $nomination = new Nomination($view, null, $dbo);
        $this->assertEquals(0, $nomination->nominate($request, $response, null));
    }

    /**
     * @test
     */
    public function nominateReturnsErrorForEmptyDescription() {
        $this->setOutputCallback(function() {});
        $data = [
            'title' => 'Title',
            'description' => '',
        ];
        $userid = 1;

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['nominate'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('nominate');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getAttribute')
            ->willReturn($userid);

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // ResponseInterface mock
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $nomination = new Nomination($view, null, $dbo);
        $this->assertEquals(0, $nomination->nominate($request, $response, null));
    }

    /**
     * @test
     */
    public function nominateReturnsErrorForNullDescription() {
        $this->setOutputCallback(function() {});
        $data = [
            'title' => 'Title',
            'description' => null,
        ];
        $userid = 1;

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['nominate'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('nominate');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getAttribute')
            ->willReturn($userid);

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // ResponseInterface mock
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $nomination = new Nomination($view, null, $dbo);
        $this->assertEquals(0, $nomination->nominate($request, $response, null));
    }

    /**
     * @test
     */
    public function nominateReturnsErrorForEmptyTitleAndDescription() {
        $this->setOutputCallback(function() {});
        $data = [
            'title' => '',
            'description' => '',
        ];
        $userid = 1;

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['nominate'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('nominate');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getAttribute')
            ->willReturn($userid);

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($data);

        // ResponseInterface mock
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $view->expects($this->never())
            ->method('render');

        $nomination = new Nomination($view, null, $dbo);
        $this->assertEquals(0, $nomination->nominate($request, $response, null));
    }

}
