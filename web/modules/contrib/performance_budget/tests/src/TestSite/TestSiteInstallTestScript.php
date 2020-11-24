<?php

namespace Drupal\TestSite;

/**
 * Setup file used by Nightwatch tests.
 */
class TestSiteInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    $modules = [
      'field',
      'node',
      'user',
      'web_page_archive',
      'performance_budget',
    ];
    \Drupal::service('module_installer')->install($modules);
    require 'dbdump.php';
  }

}
