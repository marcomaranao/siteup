<?php

namespace Drupal\Tests\performance_budget\Unit\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\performance_budget\Form\WebPageTestKpiForm;

/**
 * @coversDefaultClass \Drupal\performance_budget\Form\WebPageTestKpiForm
 *
 * @group performance_budget
 */
class WebPageTestKpiFormTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * Form instance.
   *
   * @var \Drupal\performance_budget\Form\WebPageTestKpiForm
   */
  protected $form;

  /**
   * KPI Group instance.
   *
   * @var \Drupal\performance_budget\Entity\WebPageTestKpiInterface
   */
  protected $kpiGroup;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $kpi_helper = $this->getMockBuilder('\Drupal\performance_budget\Helper\KpiHelper')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $kpi_helper->setStringTranslation($this->getStringTranslationStub());
    $this->kpiGroup = $this->getMockBuilder('\Drupal\performance_budget\Entity\WebPageTestKpi')
      ->disableOriginalConstructor()
      ->getMock();
    $this->form = new WebPageTestKpiForm($kpi_helper);
    $this->form->setStringTranslation($this->getStringTranslationStub());
    $this->form->setEntity($this->kpiGroup);
    $this->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests WebPageTestKpi::getKpis().
   */
  public function testGetKpis() {
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
    $this->kpiGroup->expects($this->any())
      ->method('getKpis')
      ->will($this->returnValue($kpis));

    $actual = $this->form->form([], new FormState());

    // Average.
    $this->assertTrue($actual['kpis']['average']['#open']);
    $this->assertTrue($actual['kpis']['average']['#tree']);

    // Average : First View.
    $this->assertTrue($actual['kpis']['average']['firstView']['#open']);
    $this->assertTrue($actual['kpis']['average']['firstView']['#tree']);
    $this->assertEquals(0, $actual['kpis']['average']['firstView']['loadTime']['#default_value']);
    $this->assertEquals(1, $actual['kpis']['average']['firstView']['fullyLoaded']['#default_value']);
    $this->assertEquals(1, $actual['kpis']['average']['firstView']['TTFB']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['average']['firstView']['domElements']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['average']['firstView']['SpeedIndex']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['average']['firstView']['render']['#default_value']);

    // Average : Repeat View.
    $this->assertTrue($actual['kpis']['average']['firstView']['#open']);
    $this->assertTrue($actual['kpis']['average']['firstView']['#tree']);
    $this->assertEquals(0, $actual['kpis']['average']['repeatView']['loadTime']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['average']['repeatView']['fullyLoaded']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['average']['repeatView']['TTFB']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['average']['repeatView']['domElements']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['average']['repeatView']['SpeedIndex']['#default_value']);
    $this->assertEquals(1, $actual['kpis']['average']['repeatView']['render']['#default_value']);

    // Median.
    $this->assertTrue($actual['kpis']['median']['#open']);
    $this->assertTrue($actual['kpis']['median']['#tree']);

    // Median : First View.
    $this->assertTrue($actual['kpis']['median']['firstView']['#open']);
    $this->assertTrue($actual['kpis']['median']['firstView']['#tree']);
    $this->assertEquals(1, $actual['kpis']['median']['firstView']['loadTime']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['firstView']['fullyLoaded']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['firstView']['TTFB']['#default_value']);
    $this->assertEquals(1, $actual['kpis']['median']['firstView']['domElements']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['firstView']['SpeedIndex']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['firstView']['render']['#default_value']);

    // Median : Repeat View.
    $this->assertFalse($actual['kpis']['median']['repeatView']['#open']);
    $this->assertTrue($actual['kpis']['median']['repeatView']['#tree']);
    $this->assertEquals(0, $actual['kpis']['median']['repeatView']['loadTime']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['repeatView']['fullyLoaded']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['repeatView']['TTFB']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['repeatView']['domElements']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['repeatView']['SpeedIndex']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['median']['repeatView']['render']['#default_value']);

    // Standard Deviation.
    $this->assertFalse($actual['kpis']['standardDeviation']['#open']);
    $this->assertTrue($actual['kpis']['standardDeviation']['#tree']);

    // Standard Deviation : First View.
    $this->assertFalse($actual['kpis']['standardDeviation']['firstView']['#open']);
    $this->assertTrue($actual['kpis']['standardDeviation']['firstView']['#tree']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['firstView']['loadTime']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['firstView']['fullyLoaded']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['firstView']['TTFB']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['firstView']['domElements']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['firstView']['SpeedIndex']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['firstView']['render']['#default_value']);

    // Standard Deviation : Repeat View.
    $this->assertFalse($actual['kpis']['standardDeviation']['repeatView']['#open']);
    $this->assertTrue($actual['kpis']['standardDeviation']['repeatView']['#tree']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['repeatView']['loadTime']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['repeatView']['fullyLoaded']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['repeatView']['TTFB']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['repeatView']['domElements']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['repeatView']['SpeedIndex']['#default_value']);
    $this->assertEquals(0, $actual['kpis']['standardDeviation']['repeatView']['render']['#default_value']);
  }

}
