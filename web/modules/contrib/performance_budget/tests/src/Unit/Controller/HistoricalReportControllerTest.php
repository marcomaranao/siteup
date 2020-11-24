<?php

namespace Drupal\Tests\performance_budget\Unit\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\performance_budget\Controller\HistoricalReportController;
use Drupal\performance_budget\Helper\KpiHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\performance_budget\Entity\WebPageTestKpiListBuilder
 *
 * @group performance_budget
 */
class HistoricalReportControllerTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $kpi_helper = new KpiHelper();
    $kpi_helper->setStringTranslation($this->getStringTranslationStub());
    $state = $this->getMockBuilder('\Drupal\Core\State\StateInterface')
      ->getMock();
    $date_formatter = $this->getMockBuilder('\Drupal\Core\Datetime\DateFormatterInterface')
      ->getMock();
    $date_formatter->expects($this->any())
      ->method('format')
      ->will($this->returnCallback(function ($value, $format) {
        return "{$value}:{$format}";
      }));

    $this->controller = new HistoricalReportController($state, $kpi_helper, $date_formatter);
    $this->controller->setStringTranslation($this->getStringTranslationStub());
    $this->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests HistoricalReportController::testSortAndUnsetRequests().
   */
  public function testSortAndUnsetRequestsSortsAndReturnsRequests() {
    $run_data = [
      '#requests' => [
        'firstView' => [
          'https://www.google.com' => [
            'all_ms' => 100,
            'ct' => 5,
          ],
          'https://www.rackspace.com' => [
            'all_ms' => 40,
            'ct' => 5,
          ],
          'https://www.zombo.com' => [
            'all_ms' => 90,
            'ct' => 1,
          ],
          'https://www.apple.com' => [
            'all_ms' => 12,
            'ct' => 3,
          ],
        ],
        'repeatView' => [
          'https://www.google.com' => [
            'all_ms' => 10,
            'ct' => 1,
          ],
          'https://www.rackspace.com' => [
            'all_ms' => 4,
            'ct' => 1,
          ],
          'https://www.zombo.com' => [
            'all_ms' => 9,
            'ct' => 1,
          ],
          'https://www.apple.com' => [
            'all_ms' => 7,
            'ct' => 1,
          ],
        ],
      ],
      'foo' => [
        'bar' => 'baz',
      ],
    ];

    // Config sorted results.
    $expected = [
      'firstView' => [
        'https://www.apple.com' => [
          'all_ms' => 12,
          'ct' => 3,
        ],
        'https://www.rackspace.com' => [
          'all_ms' => 40,
          'ct' => 5,
        ],
        'https://www.google.com' => [
          'all_ms' => 100,
          'ct' => 5,
        ],
        'https://www.zombo.com' => [
          'all_ms' => 90,
          'ct' => 1,
        ],
      ],
      'repeatView' => [
        'https://www.rackspace.com' => [
          'all_ms' => 4,
          'ct' => 1,
        ],
        'https://www.apple.com' => [
          'all_ms' => 7,
          'ct' => 1,
        ],
        'https://www.zombo.com' => [
          'all_ms' => 9,
          'ct' => 1,
        ],
        'https://www.google.com' => [
          'all_ms' => 10,
          'ct' => 1,
        ],
      ],
    ];
    $this->assertEquals($expected, $this->controller->sortAndUnsetRequests($run_data));

    // Confirm #request was removed from $run_data.
    $expected = [
      'foo' => [
        'bar' => 'baz',
      ],
    ];
    $this->assertEquals($expected, $run_data);
  }

  /**
   * Tests HistoricalReportController::testSortAndUnsetRequests().
   */
  public function testSortAndUnsetRequestsDoesntModifyRequestlessArray() {
    $run_data = [
      'foo' => [
        'bar' => 'baz',
      ],
    ];

    // There is no run data so we should get empty list back.
    $this->assertEquals([], $this->controller->sortAndUnsetRequests($run_data));

    // Confirm $run_data unchanged.
    $expected = [
      'foo' => [
        'bar' => 'baz',
      ],
    ];
    $this->assertEquals($expected, $run_data);
  }

  /**
   * Tests HistoricalReportController::getRequestSummaryTable().
   */
  public function testGetRequestSummaryTable() {
    $requests = [
      'firstView' => ['la la la la'],
      'repeatView' => [
        'https://www.googleanalytics.com/ga/track' => [
          'first' => 1566915083,
          'last' => 1567174283,
          'ct' => 5,
          'all_ms' => 400,
        ],
        'https://www.googleanalytics.com/some/other/path' => [
          'first' => 1566915083,
          'last' => 1567174283,
          'ct' => 5,
          'all_ms' => 10000,
        ],
        'https://www.someothertracker.com/this/is/something/else' => [
          'first' => 1566915083,
          'last' => 1567174283,
          'ct' => 5,
          'all_ms' => 2000,
        ],
      ],
    ];
    $view = 'repeatView';
    $expected_html = '<table><thead><tr>';
    $expected_html .= '<th>Requested URL</th>';
    $expected_html .= '<th>First Time Captured</th>';
    $expected_html .= '<th>Last Time Captured</th>';
    $expected_html .= '<th>Total Requests Made</th>';
    $expected_html .= '<th>Average Load Time</th>';
    $expected_html .= '</tr></thead><tbody><tr>';
    $expected_html .= "<td class='pb-requestTableUrl'>https://www.googleanalytics.com/ga/track</td>";
    $expected_html .= "<td class='pb-requestTableFirst'>1566915083:short</td>";
    $expected_html .= "<td class='pb-requestTableLast'>1567174283:short</td>";
    $expected_html .= "<td class='pb-requestTableCount'>5</td>";
    $expected_html .= "<td class='pb-requestTableAverage'>0.080s</td>";
    $expected_html .= '</tr><tr>';
    $expected_html .= "<td class='pb-requestTableUrl'>https://www.googleanalytics.com/some/other/path</td>";
    $expected_html .= "<td class='pb-requestTableFirst'>1566915083:short</td>";
    $expected_html .= "<td class='pb-requestTableLast'>1567174283:short</td>";
    $expected_html .= "<td class='pb-requestTableCount'>5</td>";
    $expected_html .= "<td class='pb-requestTableAverage'>2.000s</td>";
    $expected_html .= '</tr><tr>';
    $expected_html .= "<td class='pb-requestTableUrl'>https://www.someothertracker.com/this/is/something/else</td>";
    $expected_html .= "<td class='pb-requestTableFirst'>1566915083:short</td>";
    $expected_html .= "<td class='pb-requestTableLast'>1567174283:short</td>";
    $expected_html .= "<td class='pb-requestTableCount'>5</td>";
    $expected_html .= "<td class='pb-requestTableAverage'>0.400s</td>";
    $expected_html .= '</tr></tbody></table>';
    $expected = [
      '#type' => 'details',
      '#title' => 'Repeat View - Request Summary',
      '#markup' => $expected_html,
    ];
    $this->assertEquals($expected, $this->controller->getRequestSummaryTable($requests, $view));
  }

}
