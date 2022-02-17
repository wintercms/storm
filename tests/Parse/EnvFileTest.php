<?php

use Winter\Storm\Parse\EnvFile;

class EnvFileTest extends TestCase
{
    public function testReadFile()
    {
        $filePath = __DIR__ . '/../fixtures/parse/test.env';

        $env = EnvFile::open($filePath);

        $this->assertInstanceOf(EnvFile::class, $env);

        $arr = $env->getVariables();

        $this->assertArrayHasKey('APP_URL', $arr);
        $this->assertArrayHasKey('APP_KEY', $arr);
        $this->assertArrayHasKey('MAIL_HOST', $arr);
        $this->assertArrayHasKey('MAIL_DRIVER', $arr);
        $this->assertArrayHasKey('ROUTES_CACHE', $arr);
        $this->assertArrayNotHasKey('KEY_WITH_NO_VALUE', $arr);

        $this->assertEquals('http://localhost', $arr['APP_URL']);
        $this->assertEquals('changeme', $arr['APP_KEY']);
        $this->assertEquals('smtp.mailgun.org', $arr['MAIL_HOST']);
        $this->assertEquals('smtp', $arr['MAIL_DRIVER']);
        $this->assertEquals('false', $arr['ROUTES_CACHE']);
    }

    public function testWriteFile()
    {
        $filePath = __DIR__ . '/../fixtures/parse/test.env';
        $tmpFile = __DIR__ . '/../fixtures/parse/temp-test.env';

        $env = EnvFile::open($filePath);
        $env->write($tmpFile);

        $result = file_get_contents($tmpFile);

        $this->assertStringContainsString('APP_DEBUG=true', $result);
        $this->assertStringContainsString('DB_USE_CONFIG_FOR_TESTING=false', $result);
        $this->assertStringContainsString('MAIL_HOST="smtp.mailgun.org"', $result);
        $this->assertStringContainsString('ROUTES_CACHE=false', $result);
        $this->assertStringContainsString('ENABLE_CSRF=true', $result);
        $this->assertStringContainsString('KEY_WITH_NO_VALUE', $result);

        unlink($tmpFile);
    }

    public function testWriteFileWithUpdates()
    {
        $filePath = __DIR__ . '/../fixtures/parse/test.env';
        $tmpFile = __DIR__ . '/../fixtures/parse/temp-test.env';

        $env = EnvFile::open($filePath);
        $env->set('APP_KEY', 'winter');
        $env->write($tmpFile);

        $result = file_get_contents($tmpFile);

        $this->assertStringContainsString('APP_DEBUG=true', $result);
        $this->assertStringContainsString('APP_KEY="winter"', $result);
        $this->assertStringContainsString('DB_USE_CONFIG_FOR_TESTING=false', $result);
        $this->assertStringContainsString('MAIL_HOST="smtp.mailgun.org"', $result);
        $this->assertStringContainsString('ROUTES_CACHE=false', $result);
        $this->assertStringContainsString('ENABLE_CSRF=true', $result);
        $this->assertStringContainsString('# HELLO WORLD', $result);
        $this->assertStringContainsString('#ENV_TEST="wintercms"', $result);
        $this->assertStringContainsString('KEY_WITH_NO_VALUE', $result);

        unlink($tmpFile);
    }

    public function testWriteFileWithUpdatesArray()
    {
        $filePath = __DIR__ . '/../fixtures/parse/test.env';
        $tmpFile = __DIR__ . '/../fixtures/parse/temp-test.env';

        $env = EnvFile::open($filePath);
        $env->set([
            'APP_KEY' => 'winter',
            'ROUTES_CACHE' => 'winter',
        ]);
        $env->write($tmpFile);

        $result = file_get_contents($tmpFile);

        $this->assertStringContainsString('APP_DEBUG=true', $result);
        $this->assertStringContainsString('APP_KEY="winter"', $result);
        $this->assertStringContainsString('DB_USE_CONFIG_FOR_TESTING=false', $result);
        $this->assertStringContainsString('MAIL_HOST="smtp.mailgun.org"', $result);
        $this->assertStringContainsString('ROUTES_CACHE="winter"', $result);
        $this->assertStringContainsString('ENABLE_CSRF=true', $result);
        $this->assertStringContainsString('# HELLO WORLD', $result);
        $this->assertStringContainsString('#ENV_TEST="wintercms"', $result);
        $this->assertStringContainsString('KEY_WITH_NO_VALUE', $result);

        unlink($tmpFile);
    }

    public function testCasting()
    {
        $filePath = __DIR__ . '/../fixtures/parse/test.env';
        $tmpFile = __DIR__ . '/../fixtures/parse/temp-test.env';

        $env = EnvFile::open($filePath);
        $env->set(['APP_KEY' => 'winter']);
        $env->write($tmpFile);

        $result = file_get_contents($tmpFile);
        $this->assertStringContainsString('APP_KEY="winter"', $result);

        $env->set(['APP_KEY' => '123']);
        $env->write($tmpFile);

        $result = file_get_contents($tmpFile);
        $this->assertStringContainsString('APP_KEY=123', $result);

        $env->set(['APP_KEY' => true]);
        $env->write($tmpFile);

        $result = file_get_contents($tmpFile);
        $this->assertStringContainsString('APP_KEY=true', $result);

        $env->set(['APP_KEY' => false]);
        $env->write($tmpFile);

        $result = file_get_contents($tmpFile);
        $this->assertStringContainsString('APP_KEY=false', $result);

        $env->set(['APP_KEY' => null]);
        $env->write($tmpFile);

        $result = file_get_contents($tmpFile);
        $this->assertStringContainsString('APP_KEY=null', $result);

        unlink($tmpFile);
    }

    public function testRender()
    {
        $filePath = __DIR__ . '/../fixtures/parse/test.env';

        $env = EnvFile::open($filePath);

        $this->assertEquals(file_get_contents($filePath), $env->render());
    }
}
