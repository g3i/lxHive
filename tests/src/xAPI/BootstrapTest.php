<?php
namespace Tests\API;

use Tests\TestCase;

use API\Config;
use API\Bootstrap;
use API\AppInitException;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FirePHPHandler;

class BootstrapTest extends TestCase
{
    protected function setUp()
    {
        Bootstrap::factory(Bootstrap::None);
        Bootstrap::reset();
        $this->files = [];
    }

    protected function tearDown()
    {
        foreach($this->files as $fp) {
            if (file_exists($fp)) {
                unlink($fp);
            }
        }
    }

    private function registerFile($fp)
    {
        if (!in_array($fp, $this->files)) {
            $this->files[] = $fp;
        }
        return $fp;
    }

    public function testConstructorIsNotPublic()
    {

        $isPrivate = false;

        $reflect = new \ReflectionClass('API\Bootstrap');
        $privateMethods = $reflect->getMethods(\ReflectionMethod::IS_PRIVATE);

        foreach($privateMethods as $method) {
            if ($method->name === "__construct") {
                $isPrivate = true;
                break;
            }
        }

        $this->assertTrue($isPrivate);
    }

    ////
    // invalid modes
    ////

    public function testModeInvalid()
    {
        $this->expectException(AppInitException::class);
        $bootstrap = Bootstrap::factory(time());
        $bootstrap = Bootstrap::factory(-1);
        $bootstrap = Bootstrap::factory(\INF);
    }

    public function testModeFalseOrNull()
    {
        $bootstrap = Bootstrap::factory(false);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);

        $bootstrap = Bootstrap::factory(null);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);
    }

    ////
    // Bootstrap::None
    ////

    public function testModeNone()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::None);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);
    }

    public function testModeNoneMultiple()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::None);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);

        // 'Boostrap::factory(Bootstrap::None) can be called multiple times
        $bootstrap = Bootstrap::factory(Bootstrap::None);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);
    }

    public function testModeNoneDoesNotInitializeConfig()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::None);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);

        // 'Boostrap::factory(Bootstrap::None) does not initializes config singleton
        $this->expectException(AppInitException::class);
        Config::set('test_'.time(), 123);
    }


    ////
    // Bootstrap::Config
    ////

    public function testModeConfig()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Config);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Config);
    }

    public function testModeConfigInitializesConfig()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Web);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Web);

        // 'Boostrap::factory(Bootstrap::Web) initializes config singleton
        $now = time();
        Config::set('test_'.$now, $now);
        $this->assertEquals(Config::get('test_'.$now), $now);
    }

    public function testModeConfigMultiple()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Config);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Config);

        $bootstrap = Bootstrap::factory(Bootstrap::None);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);
        Bootstrap::reset();

        $bootstrap = Bootstrap::factory(Bootstrap::Config);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Config);
    }

    public function testModeConfigCannotBeCalledAfterModeWeb()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Web);
        $this->expectException(AppInitException::class);
        // You need to reset Bootstrap!
        $bootstrap = Bootstrap::factory(Bootstrap::Config);
    }

    ////
    // Bootstrap::Web
    ////

    public function testModeWeb()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Web);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Web);
    }

    public function testModeWebSingleton()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Web);
        $this->expectException(AppInitException::class);
        $bootstrap = Bootstrap::factory(Bootstrap::Web);
    }

    public function testModeWebInitializesConfig()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Web);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Web);

        // 'Boostrap::factory(Bootstrap::Web) initializes config singleton
        $now = time();
        Config::set('test_'.$now, $now);
        $this->assertEquals(Config::get('test_'.$now), $now);
    }

    ////
    // Bootstrap::Testing
    ////

    public function testModeTesting()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Testing);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Testing);
    }

    public function testModeTestingMultiple()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Testing);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Testing);

        $bootstrap = Bootstrap::factory(Bootstrap::None);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);
        Bootstrap::reset();

        $bootstrap = Bootstrap::factory(Bootstrap::Testing);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Testing);
    }

    public function testModeTestingCannotBeCalledAfterModeWeb()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Web);
        $this->expectException(AppInitException::class);
        // You need to reset Bootstrap!
        $bootstrap = Bootstrap::factory(Bootstrap::Testing);
    }

    ////
    // Bootstrap::reset()
    ////

    public function testResetModeNone()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::None);
        Bootstrap::reset();
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);
    }

    public function testResetModeConfig()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Config);
        Bootstrap::reset();
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);
    }

    public function testResetModeTesting()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Testing);
        Bootstrap::reset();
        $this->assertEquals(Bootstrap::mode(), Bootstrap::None);
    }

    public function testResetModeConsoleThrowsException()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Console);
        $this->expectException(AppInitException::class);
        Bootstrap::reset();
    }

    public function testResetModeWebThrowsException()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Web);
        $this->expectException(AppInitException::class);
        Bootstrap::reset();
    }

    public function testResetClearsConfig()
    {
        $bootstrap = Bootstrap::factory(Bootstrap::Testing);
        $this->assertEquals(Bootstrap::mode(), Bootstrap::Testing);

        $now = time();
        Config::set('test_'.$now, $now);
        $this->assertEquals(Config::get('test_'.$now), $now);

        Bootstrap::reset();
        $this->expectException(AppInitException::class);
        Config::all();
    }

    ////
    // Logging
    ////

    public function test_log_level_global_level()
    {
        $overwrite = [
            'log' =>  [
                'handlers'=> ['StreamHandler', 'ErrorLogHandler'],
                'level' => 'WARNING',
            ],
        ];

        $bootstrap = Bootstrap::factory(Bootstrap::Testing, $overwrite);
        $logger = $bootstrap->initWebContainer()->get('logger');

        $tick = 0;
        $handlers = $logger->getHandlers();
        foreach($handlers as $handler) {
            if ($handler instanceof StreamHandler) {
                // custom level
                $this->assertEquals($handler->getLevel(), Logger::WARNING);
                $tick++;
            }
            if ($handler instanceof ErrorLogHandler) {
                // default level
                $this->assertEquals($handler->getLevel(), Logger::WARNING);
                $tick++;
            }
        }
        $this->assertEquals($tick, 2, 'all handlers were tested');
    }

    public function test_log_level_individual_level()
    {
        $overwrite = [
            'log' =>  [
                'handlers'=> ['StreamHandler', 'ErrorLogHandler', 'FirePHPHandler'],
                'level' => 'ERROR',
                'StreamHandler' => [
                    'level' => 'NOTICE',
                ],
                'ErrorLogHandler' => [
                    'level' => 'WARNING',
                ],
            ],
        ];

        $bootstrap = Bootstrap::factory(Bootstrap::Testing, $overwrite);
        $logger = $bootstrap->initWebContainer()->get('logger');

        $handlers = $logger->getHandlers();
        $tick = 0;
        foreach($handlers as $handler) {
            $level = $handler->getLevel();
            // $name = $logger->getLevelName($level);

            if ($handler instanceof StreamHandler) {
                // custom level
                $this->assertEquals($level, Logger::NOTICE);
                $tick++;
            }
            if ($handler instanceof ErrorLogHandler) {
                // default level
                $this->assertEquals($level, Logger::WARNING);
                $tick++;
            }
            if ($handler instanceof FirePHPHandler) {
                // default level
                $this->assertEquals($level, Logger::ERROR);
                $tick++;
            }
        }
        $this->assertEquals($tick, 3, 'all handlers were tested');
    }

    public function test_log_StreamHandler_stream()
    {
        $token = '--'.time().'--';

        $logFile = $this->registerFile('/tmp/'.__FUNCTION__.'.log');
        $overwrite = [
            'log' =>  [
                'handlers'=> ['StreamHandler'],
                'StreamHandler' => [
                    'stream' => $logFile,
                ],
            ],

        ];

        $bootstrap = Bootstrap::factory(Bootstrap::Testing, $overwrite);
        $logger = $bootstrap->initWebContainer()->get('logger');

        $logger->error('The time is '.$token);
        // print("\n => logFile: ".$logFile."\n".file_get_contents($logFile)."\n");

        $this->assertEquals(Config::get(['log', 'StreamHandler', 'stream']), $logFile);
        $this->assertFileExists($logFile);

        $contents = file_get_contents($logFile);
        $this->assertContains($token, $contents);
    }

    public function test_log_ErrorLogHandler_error_log()
    {
        $token = '--'.time().'--';

        $logFile = $this->registerFile('/tmp/'.__FUNCTION__.'.log');
        $overwrite = [
            'log' =>  [
                'handlers'=> ['ErrorLogHandler'],
                'ErrorLogHandler' => [
                    'error_log' => $logFile,
                ],
            ],

        ];

        $bootstrap = Bootstrap::factory(Bootstrap::Testing, $overwrite);
        $logger = $bootstrap->initWebContainer()->get('logger');

        $logger->error('The time is '.$token);
        // print("\n => logFile: ".$logFile."\n".file_get_contents($logFile)."\n");

        $this->assertEquals(Config::get(['log', 'ErrorLogHandler', 'error_log']), $logFile);
        $this->assertFileExists($logFile);

        $contents = file_get_contents($logFile);
        $this->assertContains($token, $contents);
    }

    /**
     * Error 'error_log: default' sets ini error_log to defaultLog
     *
     * @see Bootstrap::initWebContainer()
     */
    public function test_log_ErrorLogHandler_error_log_default()
    {
        $token = '--'.time().'--';
        $logFileBefore = ini_get('error_log');

        $overwrite = [
            'log' =>  [
                'handlers'=> ['ErrorLogHandler'],
                'ErrorLogHandler' => [
                    'error_log' => 'default',
                ],
            ],

        ];

        $bootstrap = Bootstrap::factory(Bootstrap::Testing, $overwrite);
        $logger = $bootstrap->initWebContainer()->get('logger');
        $logFile = ini_get('error_log');

        $logger->error('The time is '.$token);
        // print("\n => logFile: ".$logFile."\n".file_get_contents($logFile)."\n");

        $this->assertNotEquals($logFile, $logFileBefore);
        $this->assertNotEquals($logFile, 'default');
        $this->assertContains('storage/logs/', $logFile);
        $this->assertFileExists($logFile);

        $contents = file_get_contents($logFile);
        $this->assertContains($token, $contents);
    }
}
