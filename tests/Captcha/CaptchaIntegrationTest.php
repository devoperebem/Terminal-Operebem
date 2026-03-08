<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CaptchaIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $root = dirname(__DIR__);
        if (!class_exists('Composer\Autoload\ClassLoader')) {
            require $root . '/vendor/autoload.php';
        }
        if (file_exists($root.'/.env')) {
            Dotenv\Dotenv::createImmutable($root)->safeLoad();
        }
    }

    public function testConfigValidation(): void
    {
        $result = OpereBem\Captcha\Config::validate();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsArray($result['errors']);
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('Extensão GD ausente; pulando testes de imagem.');
        }
        $this->assertCount(0, array_filter($result['errors'], fn($e) => str_contains(strtolower($e), 'gd')));
    }

    public function testGenerateCaptchaClaro(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD ausente.');
        }
        $gen = new OpereBem\Captcha\Generator();
        $out = $gen->generate('claro');
        $this->assertTrue($out['success'] ?? false);
        $this->assertNotEmpty($out['token'] ?? '');
        $cfg = OpereBem\Captcha\Config::load();
        $cacheDir = rtrim($cfg['paths']['cache_dir'] ?? '', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->assertNotEmpty($cacheDir, 'cache_dir config vazio');
        $this->assertFileExists($cacheDir . basename((string)$out['full']));
        $this->assertFileExists($cacheDir . basename((string)$out['piece']));
    }

    public function testGenerateCaptchaEscuro(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD ausente.');
        }
        $gen = new OpereBem\Captcha\Generator();
        $out = $gen->generate('escuro');
        $this->assertTrue($out['success'] ?? false);
        $this->assertNotEmpty($out['token'] ?? '');
        $cfg = OpereBem\Captcha\Config::load();
        $cacheDir = rtrim($cfg['paths']['cache_dir'] ?? '', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->assertNotEmpty($cacheDir, 'cache_dir config vazio');
        $this->assertFileExists($cacheDir . basename((string)$out['full']));
        $this->assertFileExists($cacheDir . basename((string)$out['piece']));
    }
}
