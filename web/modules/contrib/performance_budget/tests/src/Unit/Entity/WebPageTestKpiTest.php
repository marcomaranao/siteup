<?php

namespace Drupal\Tests\performance_budget\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\performance_budget\Entity\WebPageTestKpi;

/**
 * @coversDefaultClass \Drupal\performance_budget\Entity\WebPageTestKpi
 *
 * @group performance_budget
 */
class WebPageTestKpiTest extends UnitTestCase {

  /**
   * Tests WebPageTestKpi::getKpis().
   */
  public function testGetKpis() {
    $values = [
      'kpis' => [
        'average' => [
          'firstView' => [
            'kpi1' => 1234,
            'kpi2' => 5678,
            'kpi3' => 0,
            'kpi4' => 0,
          ],
          'repeatView' => [
            'kpi1' => 0,
            'kpi2' => 0,
            'kpi3' => 0,
            'kpi4' => 9999,
          ],
        ],
        'median' => [
          'firstView' => [
            'kpi1' => 1500,
            'kpi2' => 0,
            'kpi3' => 3000,
            'kpi4' => 0,
          ],
          'repeatView' => [
            'kpi1' => 0,
            'kpi2' => 0,
            'kpi3' => 0,
            'kpi4' => 0,
          ],
        ],
        'standardDeviation' => [
          'firstView' => [
            'kpi1' => 0,
            'kpi2' => 0,
            'kpi3' => 0,
            'kpi4' => 0,
          ],
          'repeatView' => [
            'kpi1' => 0,
            'kpi2' => 0,
            'kpi3' => 0,
            'kpi4' => 0,
          ],
        ],
      ],
    ];
    $kpi_entity = new WebPageTestKpi($values, 'wpt_kpi');
    $actual = [
      'average' => [
        'firstView' => [
          'kpi1' => 1234,
          'kpi2' => 5678,
        ],
        'repeatView' => [
          'kpi4' => 9999,
        ],
      ],
      'median' => [
        'firstView' => [
          'kpi1' => 1500,
          'kpi3' => 3000,
        ],
      ],
    ];
    $this->assertEquals($actual, $kpi_entity->getKpis());

  }

}
