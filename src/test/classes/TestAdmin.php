<?php

use PHPUnit\Framework\TestCase;
use phpmock\spy\Spy;
use ICCM\BOF\Admin;
use ICCM\BOF\DBO;
use ICCM\BOF\Results;
use ICCM\BOF\Timezones;
use Slim\Views\Twig;
use Slim\Http\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \ICCM\BOF\Admin::__construct
 */
class TestAdmin extends TestCase
{
    /**
     * @covers \ICCM\BOF\Admin::calcResult
     * @test
     */
    public function calcResultShowsResults() {
        $config = [
            'loggedin' => true,
            'stage' => 'blah',
            'csvdata' => 'blah,blah,blah',
            'log' => 'Logged info',
            'timezones' => Timezones::List()
        ];

        // Results mock
        $results = $this->getMockBuilder(Results::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['calculateResults'])
            ->getMock();

        $results->expects($this->once())
            ->method('calculateResults')
            ->willReturn($config);
        
        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
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
            ->with($response, 'results.html', $config);

        $admin = new Admin($view, null, $dbo, $results);
        $admin->calcResult($request, $response, null);
    }

    /**
     * @covers \ICCM\BOF\Admin::calcResult
     * @test
     */
    public function calcResultThrowsExceptionIfNotAdmin() {
        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
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

        $admin = new Admin($view, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $admin->calcResult($request, $response, null);
    }

    /**
     * @covers \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function showAdminViewRendersPage() {
        $config = [
            'loggedin' => true,
            'num_rounds' => 8,
            'num_locations' => 6,
            'timezones' => Timezones::List()
        ];
        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->showAdminView($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function showAdminViewThrowsExceptionIfNotAdmin() {
        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('getConfig');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
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

        $admin = new Admin($view, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $admin->showAdminView($request, $response, null);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @test
     */
    public function updateConfigThrowsExceptionIfNotAdmin() {
        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
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

        $admin = new Admin($view, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $admin->update_config($request, $response, null);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigThrowsExceptionIfPasswordsDoNotMatch() {
        $data = [
            'password1' => 'password1',
            'password2' => 'password2'
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['changepassword'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('changePassword');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
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

        $view->expects($this->never())
            ->method('render');

        $admin = new Admin($view, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $admin->update_config($request, $response, null);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigDownloadsDatabase() {
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => null,
            'download_database' => 'yes',
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => null,
            'locations' => null,
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
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

        $view->expects($this->never())
            ->method('render');

        $admin = new Admin($view, null, $dbo, null);
        $passthruSpy = new Spy('ICCM\BOF', 'passthru', function() {});
        $passthruSpy->enable();

        $headerSpy = new Spy('ICCM\BOF', 'header', function() {});
        $headerSpy->enable();

        // Note that we really don't care if ob_get_clean() is called, but we
        // need to make sure that it's not really called, or else this unit
        // test will fail, as a "Risky" test.
        $ob_get_cleanSpy = new Spy('ICCM\BOF', 'ob_get_clean', function() {});
        $ob_get_cleanSpy->enable();

        // Note that a RuntimeException is thrown even though it's all OK.
        $this->expectException(RuntimeException::class);
        $admin->update_config($request, $response, null);

        $invocations = $passthruSpy->getInvocations();
        $this->assertEquals(1, count($invocations));
        $this->assertStringStartsWith('mysqldump', $invocations[0]->getArguments()[0]);
        $passthruSpy->disable();

        $invocations = $headerSpy->getInvocations();
        $this->assertEquals(2, count($invocations));
        $this->assertEquals('Content-Type: application/octet-stream', $invocations[0]->getArguments()[0]);
        $this->assertStringStartsWith('Content-Disposition: attachment; filename=db-backup-BOF-', $invocations[1]->getArguments()[0]);
        $this->assertStringEndsWith('.sql', $invocations[1]->getArguments()[0]);
        $headerSpy->disable();

        // Since we don't care, we don't assert on it, just disable it now
        // we're done.
        $ob_get_cleanSpy->disable();
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigFailsToDownloadDatabase() {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => null,
            'download_database' => 'no',
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => null,
            'locations' => null,
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
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

        $view->expects($this->never())
            ->method('render');

        $admin = new Admin($view, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $admin->update_config($request, $response, null);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigFailsToResetDatabase() {
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => 'no',
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => null,
            'locations' => null,
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['reset'])
            ->getMock();

        $dbo->expects($this->never())
            ->method('reset');

        // Request mock
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'getParsedBody'])
            ->getMock();

        $request->expects($this->once())
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

        $view->expects($this->never())
            ->method('render');

        $admin = new Admin($view, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $admin->update_config($request, $response, null);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigResetsDatabase() {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => 'yes',
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => null,
            'locations' => null,
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig', 'reset'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $dbo->expects($this->once())
            ->method('reset');

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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->update_config($request, $response, null));
    }

    /**
     * Calls Admin::update_config to check if setConfigDateTime is called properly, or not called at all.
     * @param string $which Which date time configuration is being tested.
     * @param int failType Inidicates the failure type; 0 means no failure, 1
     * means the date component is null, 2 indicates the time component is
     * null. 3 means both time and date components are null.
     */
    private function _updateConfigUpdatesDateTime($which, $failType) {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => null,
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => null,
            'locations' => null,
        ];

        if (($failType & 1) == 0) {
            $data[$which] = '2019-06-25';
        }
        if (($failType & 2) == 0) {
            $data['time_'.$which] = '10:03';
        }

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig', 'setConfigDateTime', 'setConfigPrepBoF'])
            ->getMock();

        if ($failType == 0) {
            $dbo->expects($this->once())
                ->method('setConfigDateTime')
                ->with($which, 1561456980);
        }
        else {
            $dbo->expects($this->never())
                ->method('setConfigDateTime');
        }

        $dbo->expects($this->once())
            ->method('setConfigPrepBoF')
            ->with('True');

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->update_config($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesNominationBegins() {
        $this->_updateConfigUpdatesDateTime('nomination_begins', 0);
        $this->_updateConfigUpdatesDateTime('nomination_begins', 1);
        $this->_updateConfigUpdatesDateTime('nomination_begins', 2);
        $this->_updateConfigUpdatesDateTime('nomination_begins', 3);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesNominationEnds() {
        $this->_updateConfigUpdatesDateTime('nomination_ends', 0);
        $this->_updateConfigUpdatesDateTime('nomination_ends', 1);
        $this->_updateConfigUpdatesDateTime('nomination_ends', 2);
        $this->_updateConfigUpdatesDateTime('nomination_ends', 3);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesVotingBegins() {
        $this->_updateConfigUpdatesDateTime('voting_begins', 0);
        $this->_updateConfigUpdatesDateTime('voting_begins', 1);
        $this->_updateConfigUpdatesDateTime('voting_begins', 2);
        $this->_updateConfigUpdatesDateTime('voting_begins', 3);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesVotingEnds() {
        $this->_updateConfigUpdatesDateTime('voting_ends', 0);
        $this->_updateConfigUpdatesDateTime('voting_ends', 1);
        $this->_updateConfigUpdatesDateTime('voting_ends', 2);
        $this->_updateConfigUpdatesDateTime('voting_ends', 3);
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigDoesNotUpdateEmptyLocationsOrRounds() {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => null,
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => [],
            'locations' => []
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig', 'setConfigPrepBoF'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $dbo->expects($this->once())
            ->method('setConfigPrepBoF')
            ->with('True');

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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->update_config($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesLocations() {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => null,
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => null,
            'locations' => [
                'Room A',
                'Room B'
            ],
            'prep_bof_round' => -1,
            'prep_bof_location' => 'Room B'
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setLocationNames', 'setConfigPrepBoF', 'getConfig'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('setLocationNames')
            ->with($data['locations']);

        $dbo->expects($this->once())
            ->method('setConfigPrepBoF')
            ->with('True', -1, 1);

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->update_config($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesPassword() {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => 'password1',
            'password2' => 'password1',
            'reset_database' => null,
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => null,
            'locations' => null,
            'prep_bof_round' => -1,
            'prep_bof_location' => -1
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['changepassword', 'setConfigPrepBoF', 'getConfig'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('changePassword')
            ->with('admin', $data['password1']);

        $dbo->expects($this->once())
            ->method('setConfigPrepBoF')
            ->with('True', -1, -1);

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->update_config($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesRounds() {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => null,
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => [
                'Round 1',
                'Round 2',
                'Round 3'
            ],
            'locations' => null,
            'prep_bof_round' => 'Round 2',
            'prep_bof_location' => -1
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setRoundNames', 'setConfigPrepBoF', 'getConfig'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('setConfigPrepBoF')
            ->with('True', 1, -1);

        $dbo->expects($this->once())
            ->method('setRoundNames')
            ->with($data['rounds']);

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->update_config($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesPrepBoF() {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => null,
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => null,
            'locations' => null,
            'schedule_prep' => 'False',
            'prep_bof_round' => -1,
            'prep_bof_location' => -1
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setConfigPrepBoF', 'getConfig'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('setConfigPrepBoF')
            ->with($data['schedule_prep']);

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->update_config($request, $response, null));
    }

    /**
     * @covers \ICCM\BOF\Admin::update_config
     * @uses \ICCM\BOF\Admin::showAdminView
     * @test
     */
    public function updateConfigUpdatesPrepBoFWithInvalidRoundAndLocation() {
        $config = [
            'loggedin' => true,
            'timezones' => Timezones::List()
        ];
        $data = [
            'password1' => null,
            'password2' => null,
            'reset_database' => null,
            'download_database' => null,
            'nomination_begins' => null,
            'time_nomination_begins' => null,
            'nomination_ends' => null,
            'time_nomination_ends' => null,
            'voting_begins' => null,
            'time_voting_begins' => null,
            'voting_ends' => null,
            'time_voting_ends' => null,
            'rounds' => [
                'Round 1',
                'Round 2',
                'Round 3'
            ],
            'locations' => [
                'Room A',
                'Room B'
            ],
            'schedule_prep' => 'False',
            'prep_bof_location' => 'Room C',
            'prep_bof_round' => 'Round 5'
        ];

        // DBO mock
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setRoundNames', 'setLocationNames', 'setConfigPrepBoF', 'getConfig'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('setRoundNames')
            ->with($data['rounds']);

        $dbo->expects($this->once())
            ->method('setLocationNames')
            ->with($data['locations']);

        $dbo->expects($this->once())
            ->method('setConfigPrepBoF')
            ->with($data['schedule_prep'], -1, -1);

        $dbo->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

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
            ->with($response, 'admin.html', $config)
            ->willReturn(4);

        $admin = new Admin($view, null, $dbo, null);
        $this->assertEquals(4, $admin->update_config($request, $response, null));
    }
}
