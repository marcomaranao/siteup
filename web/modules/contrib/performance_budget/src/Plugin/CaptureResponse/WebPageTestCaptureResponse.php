<?php

namespace Drupal\performance_budget\Plugin\CaptureResponse;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\performance_budget\Helper\KpiHelperInterface;
use Drupal\web_page_archive\Plugin\CaptureResponse\UriCaptureResponse;
use Drupal\web_page_archive\Entity\WebPageArchiveRunInterface;

/**
 * Web page test capture response.
 */
class WebPageTestCaptureResponse extends UriCaptureResponse {

  /**
   * KPI Storage.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $kpiStorage;

  /**
   * WPA Run Storage.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $runStorage;

  /**
   * KPI helper service.
   *
   * @var Drupal\performance_budget\Helper\KpiHelper
   */
  protected $kpiHelper;

  /**
   * {@inheritdoc}
   */
  public static function getId() {
    return 'pb_wpt_capture_response';
  }

  /**
   * Retrieves the KPI storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The wpt_kpi entity storage.
   */
  protected function getKpiStorage() {
    if (!isset($this->kpiStorage)) {
      $this->kpiStorage = \Drupal::entityTypeManager()->getStorage('wpt_kpi');
    }
    return $this->kpiStorage;
  }

  /**
   * Sets the KPI storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The wpt_kpi entity storage.
   */
  public function setKpiStorage(EntityStorageInterface $storage) {
    $this->kpiStorage = $storage;
    return $this;
  }

  /**
   * Retrieves the web page archive run storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The web_page_archive_run entity storage.
   */
  protected function getRunStorage() {
    if (!isset($this->runStorage)) {
      $this->runStorage = \Drupal::entityTypeManager()->getStorage('web_page_archive_run');
    }
    return $this->runStorage;
  }

  /**
   * Sets the web page archive run storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The web_page_archive_run entity storage.
   */
  public function setRunStorage(EntityStorageInterface $storage) {
    $this->runStorage = $storage;
    return $this;
  }

  /**
   * Retrieves the KPI helper service.
   *
   * @return \Drupal\performance_budget\Helper\KpiHelperInterface
   *   The kpi helper service.
   */
  protected function getKpiHelper() {
    if (!isset($this->kpiHelper)) {
      $this->kpiHelper = \Drupal::service('performance_budget.helper.kpi');
    }
    return $this->kpiHelper;
  }

  /**
   * Sets the KPI helper service.
   *
   * @param \Drupal\performance_budget\Helper\KpiHelperInterface $kpi_helper
   *   The kpi helper service.
   */
  public function setKpiHelper(KpiHelperInterface $kpi_helper) {
    $this->kpiHelper = $kpi_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function renderable(array $options = []) {
    return (isset($options['mode']) && $options['mode'] == 'full') ?
      $this->renderFull($options) : $this->renderPreview($options);
  }

  /**
   * Renders preview mode.
   *
   * @param array $options
   *   Render options.
   *
   * @return array
   *   Render array.
   */
  public function renderPreview(array $options) {
    $contents = $this->retrieveFileContents();
    $run = $this->getRunStorage()->loadRevision($options['vid']);
    $route_params = [
      'web_page_archive_run_revision' => $options['vid'],
      'delta' => $options['delta'],
    ];
    $render = [
      '#theme' => 'pb-wpt-preview',
      '#url' => $this->captureUrl,
      '#from' => isset($contents['from']) ? strip_tags($contents['from']) : $this->t('Unknown')->render(),
      '#kpis' => $this->parseKpis($run, $contents, FALSE),
    ];

    $render['#view_button'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('entity.web_page_archive.modal', $route_params),
      '#title' => $this->t('View Detailed Report')->render(),
      '#attributes' => ['class' => ['button', 'pb-displayFullButton']],
    ];

    return $render;
  }

  /**
   * Renders full mode.
   *
   * @param array $options
   *   Render options.
   *
   * @return array
   *   Render array.
   */
  public function renderFull(array $options) {
    $contents = $this->retrieveFileContents();
    $render = [];
    $request_keys = [
      'all_start',
      'all_end',
      'all_ms',
      'connect_start',
      'connect_end',
      'connect_ms',
      'dns_start',
      'dns_end',
      'dns_ms',
      'download_start',
      'download_end',
      'download_ms',
      'load_start',
      'load_end',
      'load_ms',
      'ssl_start',
      'ssl_end',
      'ssl_ms',
      'ttfb_start',
      'ttfb_end',
      'ttfb_ms',
    ];
    $table_keys = [
      'start' => $this->t('Start'),
      'end' => $this->t('End'),
      'ms' => $this->t('Time'),
    ];
    $metrics = [
      'dns' => $this->t('DNS'),
      'connect' => $this->t('Connect'),
      'ssl' => $this->t('SSL'),
      'ttfb' => $this->t('TTFB'),
      'load' => $this->t('Load'),
      'download' => $this->t('Download'),
      'all' => $this->t('Total'),
    ];
    $requests = [];
    foreach ($contents['runs'] as $run_details) {
      foreach ($run_details as $view => $view_details) {
        foreach ($view_details['requests'] as $request) {
          $request_details = array_filter($request, function ($key) use ($request_keys) {
            return in_array($key, $request_keys);
          }, ARRAY_FILTER_USE_KEY);
          $requests[$view][$request['full_url']] = $request_details;
        }
        uasort($requests[$view], function ($a, $b) {
          return $a['load_start'] > $b['load_start'];
        });
      }
    }

    $linkCt = 0;
    $elements = [];
    foreach ($requests as $view => $view_details) {
      $view_elements = [];
      $tables = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => 'details-wrapper',
        ],
      ];
      $table_idx = 0;
      foreach ($view_details as $url => $request) {
        $tables[$table_idx] = [
          '#type' => 'table',
          '#caption' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => ['pb-requestTableCaption'],
            ],
            '#value' => $url,
          ],
          '#header' => [''],
          '#attributes' => [
            'class' => ['pb-requestTable'],
          ],
        ];

        foreach ($metrics as $metric => $metric_label) {
          $tables[$table_idx]['#header'][] = $metric_label;
          $row = 0;
          foreach ($table_keys as $key => $key_label) {
            // Insert row header.
            if (!isset($tables[$table_idx][$row])) {
              $tables[$table_idx][$row][] = [
                '#type' => 'processed_text',
                '#text' => $key_label,
              ];
            }
            $result = $request["{$metric}_{$key}"] > -1 ? $this->t('@results', ['@result' => $request["{$metric}_{$key}"] / 1000]) : '';
            $classes = $metric == 'all' ? ['pb-requestTableTotal'] : [];
            $tables[$table_idx][$row][] = [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#attributes' => ['class' => $classes],
              '#value' => $result,
            ];
            $row++;
          }
        }
        $table_idx++;
      }

      // Generate gantt chart.
      $view_elements[] = [
        '#type' => 'details',
        '#title' => $this->t('@view - Gantt Chart', ['@view' => $this->getKpiHelper()->getFormattedViewValue($view)]),
        'chart' => [
          '#type' => 'html_tag',
          '#tag' => 'canvas',
          '#attributes' => [
            'id' => "wpt_kpi_chart_{$view}",
          ],
        ],
        '#open' => FALSE,
        '#attributes' => [
          'class' => ['pb-requestGanttChartLink', "pb-requestGanttChartLink{$linkCt}"],
        ],
      ];

      // Generate request details table.
      $view_elements[] = [
        '#type' => 'details',
        '#title' => $this->t('@view - Requests', ['@view' => $this->getKpiHelper()->getFormattedViewValue($view)]),
        'table' => $tables,
        '#open' => FALSE,
        '#attributes' => [
          'class' => ['pb-requestLink', "pb-requestLink{$linkCt}"],
        ],
      ];
      $linkCt++;
      $elements = array_merge($elements, $view_elements);
    }

    $render = [
      '#theme' => 'pb-wpt-full',
      '#tables' => $elements,
      '#attached' => [
        'drupalSettings' => [
          'pbWptRequests' => $requests,
        ],
      ],
    ];
    return $render;
  }

  /**
   * Parses content array for key performance indicators.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveRunInterface $run
   *   Web page archive run entity.
   * @param array $content
   *   Content array generated by $this->retrieveFileContents().
   * @param bool $include_requests
   *   Indicates whether or not to include request data.
   *
   * @return array
   *   List of KPI results.
   */
  public function parseKpis(WebPageArchiveRunInterface $run, array $content = NULL, $include_requests = TRUE) {
    $ret = [];
    if (!isset($content)) {
      $content = $this->retrieveFileContents();
    }

    // Store request summary information.
    if ($include_requests && !empty($content['runs'])) {
      foreach ($content['runs'] as $run_details) {
        foreach ($run_details as $view => $view_details) {
          foreach ($view_details['requests'] as $request) {
            $ret['#requests'][$request['full_url']][$view]['all_ms'] = $request['all_ms'];
          }
        }
      }
    }

    $capture_utilities = $run->getConfigEntity()->getCaptureUtilities()->getConfiguration();
    $kpi_group_ids = [];
    foreach ($capture_utilities as $capture_utility) {
      if ($capture_utility['id'] == 'pb_wpt_capture') {
        foreach ($capture_utility['data']['kpi_groups'] as $group) {
          if (!empty($group['target_id'])) {
            $kpi_group_ids[] = $group['target_id'];
          }
        }
      }
    }

    $kpi_groups = $this->getKpiStorage()->loadMultiple($kpi_group_ids);
    if (empty($kpi_groups)) {
      throw new \Exception('Missing KPI Group');
    }

    $kpi_map = $this->getKpiHelper()->getKpiMap();
    foreach ($kpi_groups as $kpi_group) {
      $kpis = $kpi_group->getKpis();
      foreach ($kpis as $average => $average_details) {
        foreach ($average_details as $view => $view_details) {
          foreach ($view_details as $kpi => $kpi_details) {
            if (strpos($kpi, '_threshold') !== FALSE) {
              $kpi = str_replace('_threshold', '', $kpi);
              $actual = isset($content[$average][$view][$kpi]) ? $content[$average][$view][$kpi] : FALSE;
              $formatted_average = $this->getKpiHelper()->getFormattedAverageValue($average);
              $formatted_view = $this->getKpiHelper()->getFormattedViewValue($view);
              $formatted_actual = $this->getKpiHelper()->getFormattedKpiValue($kpi, $actual);

              // Verify we have a value and corresponding kpi is enabled.
              if ($actual !== FALSE && !empty($kpis[$average][$view][$kpi])) {
                // Check minimum.
                if (!empty($kpi_details['has_minimum']) && isset($kpi_details['minimum']) && $actual < $kpi_details['minimum']) {
                  $formatted_minimum = $this->getKpiHelper()->getFormattedKpiValue($kpi, $kpi_details['minimum']);
                  $tokens = [
                    $formatted_average,
                    $formatted_view,
                    $kpi_map[$kpi]['title']->render(),
                  ];
                  $ret['#threshold_violations'][] = [
                    'type' => 'minimum',
                    'kpi' => implode(' : ', $tokens),
                    'threshold' => $formatted_minimum,
                    'actual' => $formatted_actual,
                  ];
                }

                // Check maximum.
                if (!empty($kpi_details['has_maximum']) && isset($kpi_details['maximum']) && $actual > $kpi_details['maximum']) {
                  $formatted_maximum = $this->getKpiHelper()->getFormattedKpiValue($kpi, $kpi_details['maximum']);
                  $tokens = [
                    $formatted_average,
                    $formatted_view,
                    $kpi_map[$kpi]['title']->render(),
                  ];
                  $ret['#threshold_violations'][] = [
                    'type' => 'maximum',
                    'kpi' => implode(' : ', $tokens),
                    'threshold' => $formatted_maximum,
                    'actual' => $formatted_actual,
                  ];
                }
              }
            }
            else {
              $formatted_average = $this->getKpiHelper()->getFormattedAverageValue($average);
              $formatted_view = $this->getKpiHelper()->getFormattedViewValue($view);
              if (isset($content[$average][$view][$kpi])) {
                $ret[$kpi_group->label()][$formatted_average][$formatted_view][$kpi_map[$kpi]['title']->render()] = $this->getKpiHelper()->getFormattedKpiValue($kpi, $content[$average][$view][$kpi]);
              }
            }
          }
        }
      }
    }
    return $ret;
  }

  /**
   * Retrieves file contents.
   */
  protected function retrieveFileContents() {
    if (!empty($this->content) && file_exists($this->content)) {
      return Json::decode(file_get_contents($this->content));
    }
    return [];
  }

  /**
   * Retrieves a list of replacement variables based on specified data.
   *
   * @param array $data
   *   Data array.
   *
   * @return array
   *   Returns a replacement array.
   */
  public function getReplacementVariables(array $data) {
    // Create @wpa variables.
    $replacements['@url'] = $this->captureUrl;
    $replacements['@wpa_id'] = $data['web_page_archive']->id();
    $replacements['@wpa_label'] = $data['web_page_archive']->label();
    $replacements['@wpa_run_id'] = $data['run_entity']->getRevisionId();
    $replacements['@wpa_run_label'] = $data['run_entity']->label();
    $url = Url::fromRoute('view.web_page_archive_individual.individual_run_page', ['arg_0' => $replacements['@wpa_run_id']]);
    $url->setAbsolute(TRUE);
    $replacements['@wpa_run_url'] = $url->toString();

    // Generate replacements based on KPI groups.
    $kpis = $this->parseKpis($data['run_entity']);
    foreach (Element::children($kpis) as $kpi_group) {
      foreach ($kpis[$kpi_group] as $average => $average_details) {
        foreach ($average_details as $view => $view_details) {
          foreach ($view_details as $metric => $value) {
            $tokens = ['pb_wpt', $kpi_group, $average, $view, $metric];
            $key = $this->kpiHelper->getReplacementKey($tokens);
            if ($key) {
              $replacements[$key] = $value;
            }
          }
        }
      }
    }

    return $replacements;
  }

  /**
   * Retrieves threshold violations for the specified run entity.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveRunInterface $run_entity
   *   The run entity.
   *
   * @return array
   *   List of threshold violations.
   */
  public function getThresholdViolations(WebPageArchiveRunInterface $run_entity) {
    $kpis = $this->parseKpis($run_entity);
    return isset($kpis['#threshold_violations']) ? $kpis['#threshold_violations'] : [];
  }

}
