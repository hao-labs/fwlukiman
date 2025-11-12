<?php

declare(strict_types=1);

namespace Lukiman\tests\Cores;

use Lukiman\Cores\Env;
use PHPUnit\Framework\TestCase;
use Lukiman\Cores\Loader;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class LoaderTest extends TestCase {
  protected ReflectionClass $loaderReflection;
  protected ReflectionProperty $loaderEnv;
  protected ReflectionMethod $resolveEnv;

  protected function setUp(): void {
    $this->loaderReflection = new ReflectionClass(Loader::class);
    
    $this->loaderEnv = $this->loaderReflection->getProperty('env');
    $this->loaderEnv->setAccessible(true);
    
    $this->resolveEnv = $this->loaderReflection->getMethod('resolveEnv');
    $this->resolveEnv->setAccessible(true);
  }

  public function testResolveConfigFileWithoutEnv(): void {
    $file = 'Cache';
    $expected = 'config/Cache.php';
    $this->assertEquals($expected, Loader::resolveConfigFile($file));
  }

  public function testResolveEnvMethodOutputNull(): void {
    $result = $this->resolveEnv->invoke(null, 'config/Env.php');
    $this->assertNull($result);
  }

  /**
  * Data Provider for testResolveEnvMethodOutput
  *
  * @return array
  * */
  public static function envResolveDataProvider(): array {
    return [
      'staging' => [Env::STAGING, '.staging'],
      'production' => [Env::PRODUCTION, '.production'],
      'development' => [Env::DEVELOPMENT, ''],
    ];
  }

  /**
   * @dataProvider envResolveDataProvider
   */
  public function testResolveEnvMethodOutput(Env $env, string $expectedPath): void {
    $file = 'DummyConfig';
    $expected = 'config/' . $file . $expectedPath . '.php';
    $envFile = 'config/Env.php';
    file_put_contents($envFile, '<?php return \Lukiman\Cores\Env::' . $env->name . ';');
    touch($expected);
    $result = $this->resolveEnv->invoke($this->loaderReflection, $envFile);
    $this->assertNotNull($result);
    $this->assertEquals($env, $result);
    $this->assertEquals($expectedPath, $result->getPathname());
    unlink($expected);
    unlink($envFile);
  }

  public function testResolveEnvWhenTheEnvFileContentIsEmpty(): void {
    $envFile = 'config/Env.php';
    file_put_contents($envFile, '');
    $this->loaderEnv->setValue($this->loaderReflection, null);
    $result = $this->resolveEnv->invoke(null, $envFile);
    $this->assertNull($result);
    unlink($envFile);

    $file = 'DummyConfig';
    $expected = 'config/' . $file . '.php';
    $this->assertEquals($expected, Loader::resolveConfigFile($file));

  }

  public function testResolveConfigFileWithStagingEnv(): void {
    $file = 'DummyConfig';
    $envFile = 'config/Env.php';
    $expected = 'config/' . $file . '.staging.php';
    file_put_contents($envFile, '<?php use Lukiman\Cores\Env; return Env::STAGING;');

    touch($expected);
    $this->assertEquals($expected, Loader::resolveConfigFile($file));
    unlink($expected);
    unlink($envFile);
  }

  public function testResolveConfigFileDefaultProductionEnv(): void {
    $file = 'DummyConfig';
    $expected = 'config/' . $file . '.production.php';
    copy('config/Env_example.php', 'config/Env.php');

    touch($expected);
    $this->assertEquals($expected, Loader::resolveConfigFile($file));
    unlink($expected);
    unlink('config/Env.php');
  }

  public function testResolveConfigFileDefaultDevelopmentEnv(): void {
    $file = 'DummyConfig';
    $envFile = 'config/Env.php';
    $expected = 'config/' . $file . '.php';
    file_put_contents($envFile, '<?php use Lukiman\Cores\Env; return Env::DEVELOPMENT;');

    touch($expected);
    $this->assertEquals($expected, Loader::resolveConfigFile($file));
    unlink($expected);
    unlink($envFile);
  }
}
