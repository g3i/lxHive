<?php
namespace Tests\API\Util;

use Tests\TestCase;

use API\Config;
use API\Util\Filesystem;



class FilesystemTest extends TestCase
{
    protected function setUp(): void
    {
        $this->files = [];
    }

    protected function tearDown(): void
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

    public function testLocalAdapterWrite()
    {

        $config = [
            'in_use' => 'local',
            'local' => [
                'root_dir' => '../storage/files', // @see phpunit.xml for root dir
            ],
        ];

        $fn = 'phpunit/test-'.time().'.txt';
        $fp = Filesystem::getStoragePath($config).'/'.$fn;
        $this->registerFile($fp);

        $filesystem = Filesystem::generateAdapter($config);
        $filesystem->write($fn, 'contents');

        $this->assertFileExists($fp);
        $contents = file_get_contents($fp);
        $this->assertEquals('contents', $contents);

        $filesystem->delete($fn);
        $this->assertFalse(realpath($fp));
    }

}
