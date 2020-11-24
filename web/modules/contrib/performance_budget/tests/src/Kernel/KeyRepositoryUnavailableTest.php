<?php

namespace Drupal\Tests\performance_budget\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests key provider functionality with key module is not installed.
 *
 * @group performance_budget
 */
class KeyRepositoryUnavailableTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['performance_budget'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['performance_budget']);
  }

  /**
   * Tests that KeyProvider::hasKeyRepository() returns FALSE.
   */
  public function testHasKeyRepositoryIsFalse() {
    $this->assertFalse($this->container->get('performance_budget.key_provider')->hasKeyRepository());
  }

  /**
   * Tests key provider can't retrieve keys if key module is missing.
   */
  public function testKeyProviderGetKeysThrowsException() {
    $this->expectException(\Exception::class);
    $keys = $this->container->get('performance_budget.key_provider')->getKeys();
  }

  /**
   * Tests key provider can't retrieve specific keys if key module is missing.
   */
  public function testKeyProviderGetKeyThrowsException() {
    $this->expectException(\Exception::class);
    $keys = $this->container->get('performance_budget.key_provider')->getKey('some key');
  }

}
