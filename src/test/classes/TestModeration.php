<?php

require_once __DIR__ . '/../../classes/Moderation.php';
require_once __DIR__ . '/../../classes/DBO.php';
require_once __DIR__ . '/../../vendor/slim/twig-view/src/Twig.php';

use PHPUnit\Framework\TestCase;
use ICCM\BOF\Moderation;
use ICCM\BOF\DBO;
use Slim\Views\Twig;
use Slim\Http\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \ICCM\BOF\Moderation::__construct
 */
class TestModeration extends TestCase
{
    /**
     * @covers \ICCM\BOF\Moderation::moderate
     * @uses \ICCM\BOF\Moderation::showModerationView
     * @test
     */
    public function moderateAddsFacilitator() {
        $bofs = [
            'bof' => 'bof'
        ];
        $participants = [
            'participants' => 'participants'
        ];
        $config = [
            'loggedin' => true,
            'bofs' => $bofs,
            'participants' => $participants 
        ];
        $data = [
            'operation' => 'addFacilitator',
            'id' => 101,
            'facilitator' => 102,
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkshopsDetails', 'getUsers', 'addFacilitator'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getWorkshopsDetails')
            ->willReturn($bofs);

        $dbo->expects($this->once())
            ->method('getUsers')
            ->willReturn($participants);

        $dbo->expects($this->once())
            ->method('addFacilitator')
            ->with($data['id'], $data['facilitator']);

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->with('is_admin')
            ->willReturn(true);

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
            ->with($response, 'moderation.html', $config)
            ->willReturn(4);

        $moderation = new Moderation($view, null, $dbo);
        $this->assertEquals(4, $moderation->moderate($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Moderation::moderate
     * @uses \ICCM\BOF\Moderation::showModerationView
     * @test
     */
    public function moderateDeletesWorkshop() {
        $bofs = [
            'bof' => 'bof'
        ];
        $participants = [
            'participants' => 'participants'
        ];
        $config = [
            'loggedin' => true,
            'bofs' => $bofs,
            'participants' => $participants 
        ];
        $data = [
            'operation' => 'delete',
            'id' => 101,
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkshopsDetails', 'getUsers', 'deleteWorkshop'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getWorkshopsDetails')
            ->willReturn($bofs);

        $dbo->expects($this->once())
            ->method('getUsers')
            ->willReturn($participants);

        $dbo->expects($this->once())
            ->method('deleteWorkshop')
            ->with($data['id']);

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->with('is_admin')
            ->willReturn(true);

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
            ->with($response, 'moderation.html', $config)
            ->willReturn(4);

        $moderation = new Moderation($view, null, $dbo);
        $this->assertEquals(4, $moderation->moderate($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Moderation::moderate
     * @uses \ICCM\BOF\Moderation::showModerationView
     * @test
     */
    public function moderateMergesWorkshops() {
        $bofs = [
            'bof' => 'bof'
        ];
        $participants = [
            'participants' => 'participants'
        ];
        $config = [
            'loggedin' => true,
            'bofs' => $bofs,
            'participants' => $participants 
        ];
        $data = [
            'operation' => 'merge',
            'id' => 101,
            'mergeWithWorkshop' => 102,
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkshopsDetails', 'getUsers', 'mergeWorkshops'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getWorkshopsDetails')
            ->willReturn($bofs);

        $dbo->expects($this->once())
            ->method('getUsers')
            ->willReturn($participants);

        $dbo->expects($this->once())
            ->method('mergeWorkshops')
            ->with($data['id'], $data['mergeWithWorkshop']);

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->with('is_admin')
            ->willReturn(true);

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
            ->with($response, 'moderation.html', $config)
            ->willReturn(4);

        $moderation = new Moderation($view, null, $dbo);
        $this->assertEquals(4, $moderation->moderate($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Moderation::moderate
     * @uses \ICCM\BOF\Moderation::showModerationView
     * @test
     */
    public function moderateUpdatesWorkshops() {
        $bofs = [
            'bof' => 'bof'
        ];
        $participants = [
            'participants' => 'participants'
        ];
        $config = [
            'loggedin' => true,
            'bofs' => $bofs,
            'participants' => $participants 
        ];
        $data = [
            'operation' => null,
            'id' => 101,
            'title' => 'title',
            'description' => 'description',
            'published' => 0,
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkshopsDetails', 'getUsers', 'updateWorkshop'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getWorkshopsDetails')
            ->willReturn($bofs);

        $dbo->expects($this->once())
            ->method('getUsers')
            ->willReturn($participants);

        $dbo->expects($this->once())
            ->method('updateWorkshop')
            ->with($data['id'], $data['title'], $data['description'], $data['published']);

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->exactly(2))
            ->method('getAttribute')
            ->with('is_admin')
            ->willReturn(true);

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
            ->with($response, 'moderation.html', $config)
            ->willReturn(4);

        $moderation = new Moderation($view, null, $dbo);
        $this->assertEquals(4, $moderation->moderate($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Moderation::showModerationView
     * @test
     */
    public function showModerationViewThrowsExceptionForNonAdmin() {
        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkshopsDetails', 'getUsers'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('getWorkshopsDetails');

        $dbo->expects($this->never())
            ->method('getUsers');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getAttribute')
            ->with('is_admin')
            ->willReturn(false);

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

        $moderation = new Moderation($view, null, $dbo);
        $this->expectException(RuntimeException::class);
        $moderation->showModerationView($request, $response, null);
    }

    /**
     * @covers \ICCM\BOF\Moderation::showModerationView
     * @test
     */
    public function showModerationViewRendersPage() {
        $bofs = [
            'bof' => 'bof'
        ];
        $participants = [
            'participants' => 'participants'
        ];
        $config = [
            'loggedin' => true,
            'bofs' => $bofs,
            'participants' => $participants 
        ];
        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkshopsDetails', 'getUsers'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getWorkshopsDetails')
            ->willReturn($bofs);

        $dbo->expects($this->once())
            ->method('getUsers')
            ->willReturn($participants);

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
            ->method('getAttribute')
            ->with('is_admin')
            ->willReturn(true);

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
            ->with($response, 'moderation.html', $config)
            ->willReturn(4);

        $moderation = new Moderation($view, null, $dbo);
        $this->assertEquals(4, $moderation->showModerationView($request, $response, null));
    }

}
