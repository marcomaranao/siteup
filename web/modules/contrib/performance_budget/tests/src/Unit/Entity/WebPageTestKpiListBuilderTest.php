<?php

namespace Drupal\Tests\performance_budget\Unit\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\performance_budget\Entity\WebPageTestKpiListBuilder
 *
 * @group performance_budget
 */
class WebPageTestKpiListBuilderTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $storage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $kpi_helper = $this->getMockBuilder('\Drupal\performance_budget\Helper\KpiHelper')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $kpi_helper->setStringTranslation($this->getStringTranslationStub());
    $this->listBuilder = $this->getMockBuilder('\Drupal\performance_budget\Entity\WebPageTestKpiListBuilder')
      ->setConstructorArgs([$entity_type, $storage, $kpi_helper])
      ->setMethods(['buildOperations'])
      ->getMock();
    $this->listBuilder->setStringTranslation($this->getStringTranslationStub());
    $this->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests WebPageTestKpiListBuilder::buildHeader().
   */
  public function testBuildHeader() {
    $expected = [
      'label' => $this->t('Web Page Test KPI Group'),
      'id' => $this->t('Machine name'),
      'kpis' => $this->t('KPIs'),
      'operations' => $this->t('Operations'),
    ];
    $this->assertEquals($expected, $this->listBuilder->buildHeader());
  }

  /**
   * Tests WebPageTestKpiListBuilder::buildRow().
   */
  public function testBuildRow() {
    $entity = $this->getMockBuilder('\Drupal\performance_budget\Entity\WebPageTestKpiInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->any())
      ->method('label')
      ->will($this->returnValue('My Label Here'));
    $entity->expects($this->any())
      ->method('id')
      ->will($this->returnValue('my_id_here'));
    $kpis = [
      'average' => [
        'firstView' => [
          'TTFB' => 1,
          'fullyLoaded' => 1,
        ],
        'repeatView' => [
          'render' => 1,
        ],
      ],
      'median' => [
        'firstView' => [
          'domElements' => 1,
          'loadTime' => 1,
        ],
      ],
    ];
    $entity->expects($this->any())
      ->method('getKpis')
      ->will($this->returnValue($kpis));

    $kpi_html = '<dl><dt>@kpi0</dt><dt>@kpi1</dt><dt>@kpi2</dt><dt>@kpi3</dt><dt>@kpi4</dt></dl>';
    $kpi_replacements = [
      '@kpi0' => 'Average : First View : TTFB',
      '@kpi1' => 'Average : First View : Fully Loaded',
      '@kpi2' => 'Average : Repeat View : Start Render',
      '@kpi3' => 'Median : First View : Dom Elements',
      '@kpi4' => 'Median : First View : Load Time',
    ];

    $expected = [
      'label' => 'My Label Here',
      'id' => 'my_id_here',
      'kpis' => new FormattableMarkup($kpi_html, $kpi_replacements),
      'operations' => ['data' => NULL],
    ];
    $this->assertEquals($expected, $this->listBuilder->buildRow($entity));
  }

}
