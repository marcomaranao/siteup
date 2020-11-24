<?php

namespace Drupal\Tests\performance_budget\Unit\Plugin\CaptureResponse;

use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Drupal\performance_budget\Plugin\CaptureResponse\WebPageTestCaptureResponse;

/**
 * @coversDefaultClass \Drupal\performance_budget\Plugin\CaptureResponse\WebPageTestCaptureResponse
 *
 * @group performance_budget
 */
class WebPageTestCaptureResponseTest extends UnitTestCase {

  const FIXTURE_PATH = __DIR__ . '/../../../../fixtures';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->translation = $this->getStringTranslationStub();

    $this->kpiHelper = $this->getMockBuilder('\Drupal\performance_budget\Helper\KpiHelper')
      ->setMethods(NULL)
      ->getMock();
    $this->kpiHelper->setStringTranslation($this->translation);

    $this->runStorage = $this->getMockBuilder('\Drupal\web_page_archive\Entity\Sql\WebPageArchiveRunStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $this->runStorage->expects($this->any())
      ->method('loadRevision')
      ->will($this->returnValue($this->getMockRunEntity()));

    $this->kpiStorage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->getMock();
    $kpis = [
      [
        'average' => [
          'firstView' => [
            'TTFB' => 1,
            'TTFB_threshold' => [
              'has_minimum' => 1,
              'minimum' => 25,
              'has_maximum' => 1,
              'maximum' => 100,
            ],
          ],
          'repeatView' => ['fullyLoaded' => 1, 'render' => 1],
        ],
        'median' => ['firstView' => ['TTFB' => 1]],
      ],
      ['standardDeviation' => ['repeatView' => ['domElements' => 1]]],
    ];
    $this->kpiStorage->expects($this->any())
      ->method('loadMultiple')
      ->will($this->returnValue($this->getMockKpiGroups($kpis)));
  }

  /**
   * Retrieves mock kpi group with the specified kpi list.
   *
   * @param array $kpis
   *   List of KPIs to set for the mock groups.
   */
  protected function getMockKpiGroups(array $kpis) {
    $kpi_groups = [];
    for ($i = 1; $i <= count($kpis); $i++) {
      $kpi_group = $this->getMockBuilder('\Drupal\performance_budget\Entity\WebPageTestKpi')
        ->disableOriginalConstructor()
        ->getMock();
      $kpi_group->expects($this->any())
        ->method('label')
        ->will($this->returnValue("KPI Group {$i}"));
      $kpi_group->expects($this->any())
        ->method('getKpis')
        ->will($this->returnValue($kpis[$i - 1]));
      $kpi_groups[] = $kpi_group;
    }
    return $kpi_groups;
  }

  /**
   * Retreives a mocked config entity.
   */
  protected function getMockConfigEntity() {
    $capture_utilities = $this->getMockBuilder('\Drupal\Core\Plugin\DefaultLazyPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();
    $capture_utilities->expects($this->any())
      ->method('getConfiguration')
      ->will($this->returnValue([]));

    $wpa = $this->getMockBuilder('\Drupal\web_page_archive\Entity\WebPageArchive')
      ->disableOriginalConstructor()
      ->getMock();
    $wpa->expects($this->any())
      ->method('getCaptureUtilities')
      ->will($this->returnValue($capture_utilities));
    return $wpa;
  }

  /**
   * Retreives a mocked run entity.
   */
  protected function getMockRunEntity() {
    $wpa = $this->getMockConfigEntity();
    $run = $this->getMockBuilder('\Drupal\web_page_archive\Entity\WebPageArchiveRun')
      ->disableOriginalConstructor()
      ->getMock();
    $run->expects($this->any())
      ->method('getConfigEntity')
      ->will($this->returnValue($wpa));
    return $run;
  }

  /**
   * Tests WebPageTestCaptureResponse::getId().
   */
  public function testGetId() {
    $this->assertEquals('pb_wpt_capture_response', WebPageTestCaptureResponse::getId());
  }

  /**
   * Tests WebPageTestCaptureResponse::renderable() with valid file.
   */
  public function testRenderableOnValidFile() {
    $json_file = static::FIXTURE_PATH . '/https-www-drupal-org.json';
    $response = new WebPageTestCaptureResponse($json_file, 'http://www.somesite.com');
    $options = ['vid' => 4, 'delta' => 1];
    $route_params = ['web_page_archive_run_revision' => 4, 'delta' => 1];
    $response->setStringTranslation($this->translation);
    $response->setKpiHelper($this->kpiHelper);
    $response->setKpiStorage($this->kpiStorage);
    $response->setRunStorage($this->runStorage);
    $actual = $response->renderable($options);
    $expected = [
      '#theme' => 'pb-wpt-preview',
      '#url' => 'http://www.somesite.com',
      '#from' => 'Dulles, VA - Chrome - Cable',
      '#kpis' => [
        'KPI Group 1' => [
          'Average' => [
            'First View' => [
              'TTFB' => '0.271s',
            ],
            'Repeat View' => [
              'Fully Loaded' => '2.972s',
              'Start Render' => '0.8s',
            ],
          ],
          'Median' => [
            'First View' => [
              'TTFB' => '0.271s',
            ],
          ],
        ],
        'KPI Group 2' => [
          'Standard Deviation' => [
            'Repeat View' => [
              'Dom Elements' => 0,
            ],
          ],
        ],
        '#threshold_violations' => [
          [
            'type' => 'maximum',
            'kpi' => 'Average : First View : TTFB',
            'threshold' => '0.1s',
            'actual' => '0.271s',
          ],
        ],
      ],
      '#view_button' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.web_page_archive.modal', $route_params),
        '#title' => 'View Detailed Report',
        '#attributes' => ['class' => ['button', 'pb-displayFullButton']],
      ],
    ];

    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests WebPageTestCaptureResponse::renderable() with invalid file.
   */
  public function testRenderableOnInvalidFile() {
    $json_file = static::FIXTURE_PATH . '/invalid.json';
    $response = new WebPageTestCaptureResponse($json_file, 'http://www.somesite.com');
    $options = ['vid' => 4, 'delta' => 1];
    $route_params = ['web_page_archive_run_revision' => 4, 'delta' => 1];
    $response->setStringTranslation($this->translation);
    $response->setKpiHelper($this->kpiHelper);
    $response->setKpiStorage($this->kpiStorage);
    $response->setRunStorage($this->runStorage);
    $actual = $response->renderable($options);

    $expected = [
      '#theme' => 'pb-wpt-preview',
      '#url' => 'http://www.somesite.com',
      '#from' => 'Unknown',
      '#kpis' => [],
      '#view_button' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.web_page_archive.modal', $route_params),
        '#title' => 'View Detailed Report',
        '#attributes' => ['class' => ['button', 'pb-displayFullButton']],
      ],
    ];
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests WebPageTestCaptureResponse::renderable() with missing file.
   */
  public function testRenderableOnMissingFile() {
    $json_file = static::FIXTURE_PATH . '/does-not-exist.json';
    $response = new WebPageTestCaptureResponse($json_file, 'http://www.somesite.com');
    $options = ['vid' => 4, 'delta' => 1];
    $route_params = ['web_page_archive_run_revision' => 4, 'delta' => 1];
    $response->setStringTranslation($this->translation);
    $response->setKpiHelper($this->kpiHelper);
    $response->setKpiStorage($this->kpiStorage);
    $response->setRunStorage($this->runStorage);
    $actual = $response->renderable($options);

    $expected = [
      '#theme' => 'pb-wpt-preview',
      '#url' => 'http://www.somesite.com',
      '#from' => 'Unknown',
      '#kpis' => [],
      '#view_button' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.web_page_archive.modal', $route_params),
        '#title' => 'View Detailed Report',
        '#attributes' => ['class' => ['button', 'pb-displayFullButton']],
      ],
    ];
    $this->assertEquals($expected, $actual);
  }

}
