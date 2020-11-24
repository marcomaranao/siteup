<?php

namespace Drupal\Tests\performance_budget\Unit\Helper;

use Drupal\Tests\UnitTestCase;
use Drupal\performance_budget\Helper\KpiHelper;

/**
 * @coversDefaultClass \Drupal\performance_budget\Helper\KpiHelper
 *
 * @group performance_budget
 */
class KpiHelperTest extends UnitTestCase {

  /**
   * KPI Helper service.
   *
   * @var \Drupal\performance_budget\Helper\KpiHelper
   */
  protected $kpiHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->kpiHelper = new KpiHelper();
    $this->kpiHelper->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests KpiHelper::getKpiMap().
   */
  public function testGetKpiMap() {
    $expected = [
      'loadTime' => [
        'title' => 'Load Time',
        'description' => 'The Load Time is measured as the time from the start of the initial navigation until the beginning of the window load event (onload).',
        'units' => 'milliseconds',
      ],
      'fullyLoaded' => [
        'title' => 'Fully Loaded',
        'description' => 'The Fully Loaded time is measured as the time from the start of the initial navigation until there was 2 seconds of no network activity after Document Complete.  This will usually include any activity that is triggered by javascript after the main page loads.',
        'units' => 'milliseconds',
      ],
      'TTFB' => [
        'title' => 'TTFB',
        'description' => 'The First Byte time (often abbreviated as TTFB) is measured as the time from the start of the initial navigation until the first byte of the base page is received by the browser (after following redirects).',
        'units' => 'milliseconds',
      ],
      'domElements' => [
        'title' => 'Dom Elements',
        'description' => 'The DOM Elements metric is the count of the DOM elements on the tested page as measured at the end of the test.',
        'units' => 'elements',
      ],
      'SpeedIndex' => [
        'title' => 'Speed Index',
        'description' => 'The Speed Index is a calculated metric that represents how quickly the page rendered the user-visible content (lower is better).  More information on how it is calculated is available here: <a href="https://sites.google.com/a/webpagetest.org/docs/using-webpagetest/metrics/speed-index" target="_blank">Speed Index</a>',
        'units' => 'milliseconds',
      ],
      'render' => [
        'title' => 'Start Render',
        'description' => 'The Start Render time is measured as the time from the start of the initial navigation until the first non-white content is painted to the browser display.',
        'units' => 'milliseconds',
      ],
      'requests' => [
        'title' => 'Request Count',
        'description' => 'The Request Count will always contain the total number of requests for the test.',
        'units' => 'requests',
      ],
      'responses_200' => [
        'title' => '200 Response Count',
        'description' => 'The 200 Response Count will always contain the total number of 200 responses for the test.',
        'units' => 'responses',
      ],
      'responses_404' => [
        'title' => '404 Response Count',
        'description' => 'The 404 Response Count will always contain the total number of 404 responses for the test.',
        'units' => 'responses',
      ],
      'responses_other' => [
        'title' => 'Other Response Count',
        'description' => 'The Other Response Count will always contain the total number of non-200/404 responses for the test.',
        'units' => 'responses',
      ],
    ];
    $this->assertEquals($expected, $this->kpiHelper->getKpiMap());
  }

  /**
   * Tests KpiHelper::getFormattedAverageValue('average').
   */
  public function testGetFormattedAverageValueOnAverage() {
    $this->assertEquals('Average', $this->kpiHelper->getFormattedAverageValue('average'));
  }

  /**
   * Tests KpiHelper::getFormattedAverageValue('median').
   */
  public function testGetFormattedAverageValueOnMedian() {
    $this->assertEquals('Median', $this->kpiHelper->getFormattedAverageValue('median'));
  }

  /**
   * Tests KpiHelper::getFormattedAverageValue('standardDeviation').
   */
  public function testGetFormattedAverageValueOnStandardDeviation() {
    $this->assertEquals('Standard Deviation', $this->kpiHelper->getFormattedAverageValue('standardDeviation'));
  }

  /**
   * Tests KpiHelper::getFormattedAverageValue('Does Not Exist').
   */
  public function testGetFormattedAverageValueOnUnknownAverage() {
    $this->assertEquals('Unknown Average', $this->kpiHelper->getFormattedAverageValue('Does Not Exist'));
  }

  /**
   * Tests KpiHelper::getFormattedViewValue('firstView').
   */
  public function testGetFormattedViewValueOnFirstView() {
    $this->assertEquals('First View', $this->kpiHelper->getFormattedViewValue('firstView'));
  }

  /**
   * Tests KpiHelper::getFormattedViewValue('repeatView').
   */
  public function testGetFormattedViewValueOnRepeatView() {
    $this->assertEquals('Repeat View', $this->kpiHelper->getFormattedViewValue('repeatView'));
  }

  /**
   * Tests KpiHelper::getFormattedViewValue('Does Not Exist').
   */
  public function testGetFormattedViewValueOnUnknownView() {
    $this->assertEquals('Unknown View', $this->kpiHelper->getFormattedViewValue('Does Not Exist'));
  }

  /**
   * Tests KpiHelper::getFormattedKpiValue('docTime', 1500).
   */
  public function testGetFormattedKpiValueOnDocTimeNumber() {
    $this->assertEquals('1.5s', $this->kpiHelper->getFormattedKpiValue('docTime', 1500));
  }

  /**
   * Tests KpiHelper::getFormattedKpiValue('loadTime', 750).
   */
  public function testGetFormattedKpiValueOnLoadTimeNumber() {
    $this->assertEquals('0.75s', $this->kpiHelper->getFormattedKpiValue('loadTime', 750));
  }

  /**
   * Tests KpiHelper::getFormattedKpiValue('fullyLoaded', 970).
   */
  public function testGetFormattedKpiValueOnFullLoadedNumber() {
    $this->assertEquals('0.97s', $this->kpiHelper->getFormattedKpiValue('fullyLoaded', 970));
  }

  /**
   * Tests KpiHelper::getFormattedKpiValue('render', 3500).
   */
  public function testGetFormattedKpiValueOnRenderNumber() {
    $this->assertEquals('3.5s', $this->kpiHelper->getFormattedKpiValue('render', 3500));
  }

  /**
   * Tests KpiHelper::getFormattedKpiValue('TTFB', 1500).
   */
  public function testGetFormattedKpiValueOnTtfbNumber() {
    $this->assertEquals('1.234s', $this->kpiHelper->getFormattedKpiValue('TTFB', 1234));
  }

  /**
   * Tests KpiHelper::getFormattedKpiValue('other', 1500).
   */
  public function testGetFormattedKpiValueOnOtherNumber() {
    $this->assertEquals('1500', $this->kpiHelper->getFormattedKpiValue('other', 1500));
  }

  /**
   * Tests KpiHelper::getReplacementKey([]).
   */
  public function testGetReplacementKeyIsFalseWhenTokensListIsEmpty() {
    $tokens = [];
    $this->assertFalse($this->kpiHelper->getReplacementKey($tokens));
  }

  /**
   * Tests KpiHelper::getReplacementKey(['a']).
   */
  public function testGetReplacementKeyWorksWithOneItem() {
    $tokens = ['a'];
    $this->assertEquals('@a', $this->kpiHelper->getReplacementKey($tokens));
  }

  /**
   * Tests KpiHelper::getReplacementKey(['a', 'b', 'c', 'd', 'e', 'f']).
   */
  public function testGetReplacementKeyWorksWithSixItems() {
    $tokens = ['a', 'b', 'c', 'd', 'e', 'f'];
    $this->assertEquals('@a__b__c__d__e__f', $this->kpiHelper->getReplacementKey($tokens));
  }

  /**
   * Tests KpiHelper::getReplacementKey(['A', 'b', 'C', 'd', 'E', 'f']).
   */
  public function testGetReplacementSwitchesToLowerCase() {
    $tokens = ['A', 'b', 'C', 'd', 'E', 'f'];
    $this->assertEquals('@a__b__c__d__e__f', $this->kpiHelper->getReplacementKey($tokens));
  }

  /**
   * Tests KpiHelper::getReplacementKey(['a b c', 'd e f']).
   */
  public function testGetReplacementReplacesSpacesWithUnderscores() {
    $tokens = ['a b c', 'd e f'];
    $this->assertEquals('@a_b_c__d_e_f', $this->kpiHelper->getReplacementKey($tokens));
  }

}
