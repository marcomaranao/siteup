<?php

namespace Drupal\performance_budget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\performance_budget\Helper\KpiHelperInterface;
use Drupal\web_page_archive\Entity\WebPageArchiveInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AggregateBatchController.
 */
class HistoricalReportController extends ControllerBase {

  /**
   * The KPI Helper service.
   *
   * @var Drupal\performance_budget\Helper\KpiHelperInterface
   */
  private $kpiHelper;

  /**
   * Constructs a new AcheckerHistoryController.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\performance_budget\Helper\KpiHelperInterface $kpi_helper
   *   The kpi helper service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(StateInterface $state, KpiHelperInterface $kpi_helper, DateFormatterInterface $date_formatter) {
    $this->state = $state;
    $this->kpiHelper = $kpi_helper;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('performance_budget.helper.kpi'),
      $container->get('date.formatter')
    );
  }

  /**
   * Takes a request array and sorts it based on highest average load times.
   *
   * Also unsets the #requests key from the source array.
   *
   * @param array $run_data
   *   Array containing the current historical run data.
   *
   * @return array
   *   A sorted request array.
   */
  public function sortAndUnsetRequests(array &$run_data) {
    if (!isset($run_data['#requests'])) {
      return [];
    }
    $requests = $run_data['#requests'];
    foreach ($requests as $view => $view_details) {
      uasort($requests[$view], function ($a, $b) {
        return $a['all_ms'] / $a['ct'] < $b['all_ms'] / $b['ct'];
      });
    }
    unset($run_data['#requests']);
    return $requests;
  }

  /**
   * Gets the request summary table.
   *
   * @return array
   *   Render array containing request summary table.
   */
  public function getRequestSummaryTable(array $requests, $view) {
    $table_html = '<table><thead><tr>';
    $headers = [
      $this->t('Requested URL'),
      $this->t('First Time Captured'),
      $this->t('Last Time Captured'),
      $this->t('Total Requests Made'),
      $this->t('Average Load Time'),
    ];
    foreach ($headers as $header) {
      $table_html .= "<th>{$header}</th>";
    }
    $table_html .= '</tr></thead><tbody>';
    if (!empty($requests[$view])) {
      foreach ($requests[$view] as $url => $details) {
        $table_html .= '<tr>';
        $table_html .= "<td class='pb-requestTableUrl'>{$url}</td>";
        $first = $this->dateFormatter->format($details['first'], 'short');
        $last = $this->dateFormatter->format($details['last'], 'short');
        $table_html .= "<td class='pb-requestTableFirst'>{$first}</td>";
        $table_html .= "<td class='pb-requestTableLast'>{$last}</td>";
        $count = number_format($details['ct']);
        $table_html .= "<td class='pb-requestTableCount'>{$count}</td>";
        $average = number_format(($details['all_ms'] / $details['ct']) / 1000, 3);
        $average_str = $this->t('@averages', ['@average' => $average]);
        $table_html .= "<td class='pb-requestTableAverage'>{$average_str}</td>";
        $table_html .= '</tr>';
      }
    }
    else {
      $pending_results_message = $this->t('The latest performance results are still aggregating. Please try again later.');
      $table_html .= "<tr><td colspan='5'>{$pending_results_message}</td></tr>";
    }
    $table_html .= '</tbody></table>';
    return [
      '#type' => 'details',
      '#title' => $this->t('@view - Request Summary', ['@view' => $this->kpiHelper->getFormattedViewValue($view)])->render(),
      '#markup' => $table_html,
    ];
  }

  /**
   * Displays the body for the historical report.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $web_page_archive
   *   The web page archive config entity.
   *
   * @return array
   *   Render array for the historical report.
   */
  public function content(WebPageArchiveInterface $web_page_archive) {
    $queue_name = AggregateBatchController::getQueueName($web_page_archive);

    // If this is our first time viewing the report, go to the process form.
    $state_array = $this->state->get($queue_name);
    if (!isset($state_array)) {
      return $this->redirect('entity.web_page_archive.pb_wpt_history.process', ['web_page_archive' => $web_page_archive->id()]);
    }

    $requests = $this->sortAndUnsetRequests($state_array);
    $ret = [
      '#theme' => 'pb-wpt-history',
      '#first_view_request_summary' => $this->getRequestSummaryTable($requests, 'firstView'),
      '#repeat_view_request_summary' => $this->getRequestSummaryTable($requests, 'repeatView'),
      '#attached' => [
        'drupalSettings' => [
          'wpaRunsBaseUrl' => Url::fromRoute('view.web_page_archive_individual.individual_run_page', ['arg_0' => '_VID_'])->toString(),
          'pbWptResults' => $state_array,
        ],
      ],
    ];

    // Look for custom chart.js options.
    $capture_config = $web_page_archive->getCaptureUtilities()->getConfiguration();
    foreach ($capture_config as $utility_config) {
      if ($utility_config['id'] == 'pb_wpt_capture' && !empty($utility_config['data']['chartjs_option'])) {
        $ret['#chartjs_option'] = $utility_config['data']['chartjs_option'];
        break;
      }
    }

    return $ret;
  }

  /**
   * Displays the title for the historical report.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $web_page_archive
   *   The web page archive config entity.
   *
   * @return string
   *   Title of the historical report.
   */
  public function title(WebPageArchiveInterface $web_page_archive) {
    return $this->t('@job Historical Report', ['@job' => $web_page_archive->label()]);
  }

}
