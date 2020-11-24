<?php

namespace Drupal\performance_budget\Plugin\CaptureUtility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\performance_budget\Event\WebPageTestCaptureJobCompleteEvent;
use Drupal\performance_budget\Exception\WebPageTestApiErrorException;
use Drupal\performance_budget\Exception\WebPageTestApiPendingException;
use Drupal\performance_budget\Plugin\CaptureResponse\WebPageTestCaptureResponse;
use Drupal\web_page_archive\Plugin\ConfigurableCaptureUtilityBase;
use WidgetsBurritos\WebPageTest\WebPageTest;

/**
 * Skeleton capture utility, useful for creating new plugins.
 *
 * @CaptureUtility(
 *   id = "pb_wpt_capture",
 *   label = @Translation("Web page test capture utility", context = "Web Page Archive"),
 *   description = @Translation("Runs url through webpagetest.org.", context = "Web Page Archive")
 * )
 */
class WebPageTestCaptureUtility extends ConfigurableCaptureUtilityBase {

  /**
   * Most recent response.
   *
   * @var string|null
   */
  private $response = NULL;

  /**
   * {@inheritdoc}
   */
  public function capture(array $data = []) {
    // Configuration data is stored in $this->configuration. For example:
    if ($this->configuration['api']['storage_method'] == 'key') {
      $key_provider = $this->getKeyProvider();
      if (!$key_provider->hasKeyRepository()) {
        throw new \Exception('Tried to use key module for authentication, but the module is not installed.');
      }
      $wpt_api = $key_provider->getKey($this->configuration['api']['key_module']);
      if (!isset($wpt_api)) {
        throw new \Exception('Key [@key] is missing.', ['@key' => $this->configuration['api']['key_module']]);
      }
    }
    elseif ($this->configuration['api']['storage_method'] == 'plaintext') {
      $wpt_api = $this->configuration['api']['plaintext'];
    }
    else {
      $wpt_api = NULL;
    }

    $wpt = new WebPageTest($wpt_api, NULL, $this->configuration['api']['hostname']);
    $state_key = $this->getStateKey($data);
    $test_id = $this->state()->get($state_key);

    // TODO: Remove the following block upon 2.0.0 stable release.
    if (!isset($test_id)) {
      $hashed_key = $state_key;
      $state_key = $this->getStateKey($data, FALSE);
      $test_id = $this->state()->get($state_key);
      if (!isset($test_id)) {
        $state_key = $hashed_key;
      }
    }

    $response_content = '';
    if (!isset($test_id)) {
      if ($response = $wpt->runTest($data['url'])) {
        if ($response->statusCode == 200) {
          $this->state()->set($state_key, $response->data->testId);
        }
      }

      $this->response = NULL;
    }
    else {
      if ($response = $wpt->getTestStatus($test_id)) {
        if ($response->statusCode == 200) {
          // Test is complete.
          if ($response = $wpt->getTestResults($test_id)) {
            $file_location = $this->getFileName($data, 'json');
            file_put_contents($file_location, json_encode($response->data));
            $this->response = new WebPageTestCaptureResponse($file_location, $data['url']);

            $replacements = $this->response->getReplacementVariables($data);
            $this->notify('capture_complete_single', $replacements);

            // Notify about any violations.
            $violations = $this->response->getThresholdViolations($data['run_entity']);
            if (!empty($violations)) {
              $violation_messages = [];
              foreach ($violations as $violation) {
                $violation_replacements = [
                  '@kpi' => $violation['kpi'],
                  '@threshold' => $violation['threshold'],
                  '@actual' => $violation['actual'],
                ];
                switch ($violation['type']) {
                  case 'minimum':
                    $violation_messages[] = $this->t('[@kpi] @actual < @threshold', $violation_replacements);
                    break;

                  case 'maximum':
                    $violation_messages[] = $this->t('[@kpi] @actual > @threshold', $violation_replacements);
                    break;

                  default:
                    $violation_messages[] = $this->t('[@kpi] Invalid violation type: @type', ['@type' => $violation['type']]);
                }
              }
              $replacements['@violations'] = implode(PHP_EOL, $violation_messages);
              $this->notify('pb_threshold_violation', $replacements);
            }

            $event = new WebPageTestCaptureJobCompleteEvent($data['run_entity']);
            $event_dispatcher = \Drupal::service('event_dispatcher');
            $event_dispatcher->dispatch($event::EVENT_NAME, $event);
          }
          else {
            throw new \Exception($this->t('WPT test @test_id failed - Could not retrieve test results', ['@test_id' => $test_id]));
          }
          // Cleanup old state key.
          $this->state()->delete($state_key);
        }
        elseif (in_array($response->statusCode, [100, 101, 102])) {
          // Test is still running.
          $this->response = NULL;
          throw new WebPageTestApiPendingException($test_id, $response->statusText, $response->statusCode);
        }
        else {
          throw new WebPageTestApiErrorException($test_id, $response->statusText, $response->statusCode);
        }
      }
    }

    return $this;
  }

  /**
   * Retrieves unique state key for data array.
   */
  public static function getStateKey(array $data = [], $hashed = TRUE) {
    $url = $hashed ? md5($data['url']) : $data['url'];
    return "pb_wpt_capture:{$data['run_uuid']}:{$url}";
  }

  /**
   * Retrieves a test ID for the specified url/run_uuid combination.
   */
  public static function getPendingTestId($url, $run_uuid) {
    $state_key = static::getStateKey(['url' => $url, 'run_uuid' => $run_uuid]);
    return \Drupal::state()->get($state_key);
  }

  /**
   * Retrieves unique state key for data array.
   */
  private function state() {
    return \Drupal::state();
  }

  /**
   * Retrieves config factory service.
   */
  private function configFactory() {
    return \Drupal::configFactory();
  }

  /**
   * Retrieves the storage for wpt_kpi entities.
   */
  private function getKpiStorage() {
    return \Drupal::entityTypeManager()->getStorage('wpt_kpi');
  }

  /**
   * Retrieves the kpi helper service.
   */
  private function getKpiHelper() {
    return \Drupal::service('performance_budget.helper.kpi');
  }

  /**
   * Retrieves the key provider service.
   */
  private function getKeyProvider() {
    return \Drupal::service('performance_budget.key_provider');
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = $this->configFactory()->get("web_page_archive.{$this->getPluginId()}.settings");
    $ret = [
      'api' => [
        'hostname' => $config->get('defaults.api.hostname') ?: 'http://www.webpagetest.org',
        'storage_method' => $config->get('defaults.api.storage_method') ?: 'plaintext',
        'key_module' => $config->get('defaults.api.key_module') ?: NULL,
        'key_plaintext' => $config->get('defaults.api.key_plaintext') ?: '',
      ],
      'autogen' => $config->get('defaults.autogen') ?: [
        'enabled' => FALSE,
        'date_range' => '',
        'date_range_start' => '',
        'date_range_end' => '',
      ],
      'kpi_groups' => $config->get('defaults.kpi_groups'),
      'chartjs_option' => $config->get('defaults.chartjs_option') ?: $this->getExampleChartJsOptions(),
    ];
    $this->injectNotificationDefaultValues($ret, $config->get('defaults') ?: []);
    return $ret;
  }

  /**
   * Retrieves example chart.js options. Also default value for new jobs.
   *
   * @return string
   *   String containing example javascript object.
   */
  private function getExampleChartJsOptions() {
    return "{
      title: {
        display: true,
        text: Drupal.t('{@group}: @url', {'@group': group, '@url': url}),
      },
      scales: {
        xAxes: [{
          type: 'time',
          distribution: 'linear',
          ticks: {
            source: 'data',
            autoSkip: true,
          },
          scaleLabel: {
            display: true,
            labelString: 'Date/Time'
          },
          time: {
            parser: 'MM/DD/YYYY HH:mm',
            tooltipFormat: 'll - h:mma'
          },
        }],
        yAxes: [{
          scaleLabel: {
            display: true,
            labelString: 'Time (seconds)'
          },
        }],
      },
      plugins: {
        zoom: {
          zoom: {
            enabled: true,
            drag: true,
            mode: 'x',
            speed: 0.05,
          },
        },
      },
    }";
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $key_provider = $this->getKeyProvider();
    $key_module_enabled = $key_provider->hasKeyRepository();
    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('webpagetest.org API Key'),
      '#tree' => TRUE,
    ];
    $form['api']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Hostname'),
      '#description' => $this->t('The protocol and hostname on which to make API calls (e.g. https://www.webpagetest.org).'),
      '#default_value' => isset($this->configuration['api']['hostname']) ? $this->configuration['api']['hostname'] : $this->defaultConfiguration()['api']['storage_method'],
      '#required' => TRUE,
    ];
    $form['api']['storage_method'] = [
      '#type' => 'select',
      '#title' => $this->t('webpagetest API key storage method'),
      '#description' => $this->t('The method for mapping a job with an API key. It is highly recommended to use the key module for better key storage security.'),
      '#options' => [
        'none' => $this->t('None'),
        'plaintext' => $this->t('Plain text'),
        'key' => $this->t('Key module'),
      ],
      '#default_value' => isset($this->configuration['api']['storage_method']) ? $this->configuration['api']['storage_method'] : $this->defaultConfiguration()['api']['storage_method'],
      '#required' => TRUE,
    ];

    $keys = [];
    if ($key_module_enabled) {
      foreach ($key_provider->getKeys() as $key) {
        $keys[$key->id()] = $key->label();
      }
      asort($keys);
    }

    $form['api']['key_module'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a key'),
      '#description' => $key_module_enabled ? $this->t('Select a valid key from the key module.') : $this->t('You must install the key module to use this API key storage method.'),
      '#options' => $keys,
      '#default_value' => isset($this->configuration['api']['key_module']) ? $this->configuration['api']['key_module'] : $this->defaultConfiguration()['api']['key_module'],
      '#states' => [
        'required' => [
          ':input[name="data[api][storage_method]"]' => ['value' => 'key'],
        ],
        'visible' => [
          ':input[name="data[api][storage_method]"]' => ['value' => 'key'],
        ],
        'optional' => [
          ':input[name="data[api][storage_method]"]' => [['value' => 'plaintext'], ['value' => 'none']],
        ],
        'invisible' => [
          ':input[name="data[api][storage_method]"]' => [['value' => 'plaintext'], ['value' => 'none']],
        ],
      ],
    ];

    $form['api']['key_plaintext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('webpagetest.org API key'),
      '#description' => $this->t('Enter your webpagetest.org API Key. http://www.webpagetest.org/getkey.php'),
      '#default_value' => isset($this->configuration['api']['key_plaintext']) ? $this->configuration['api']['key_plaintext'] : $this->defaultConfiguration()['api']['key_plaintext'],
      '#states' => [
        'required' => [
          ':input[name="data[api][storage_method]"]' => ['value' => 'plaintext'],
        ],
        'visible' => [
          ':input[name="data[api][storage_method]"]' => ['value' => 'plaintext'],
        ],
        'optional' => [
          ':input[name="data[api][storage_method]"]' => [['value' => 'key'], ['value' => 'none']],
        ],
        'invisible' => [
          ':input[name="data[api][storage_method]"]' => [['value' => 'key'], ['value' => 'none']],
        ],
      ],
    ];

    $form['autogen'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Autogenerate historical report settings'),
      '#tree' => TRUE,
      'enabled' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled?'),
        '#description' => $this->t('Use this checkbox to force the regeneration of the historical report (i.e.  the visual trend graph) upon every capture.'),
        '#default_value' => isset($this->configuration['autogen']['enabled']) ? $this->configuration['autogen']['enabled'] : $this->defaultConfiguration()['autogen']['enabled'],
      ],
      'date_range' => [
        '#type' => 'select',
        '#title' => $this->t('Autogeneration date range'),
        '#description' => $this->t('Use this field to determine the time frame you wish to generate a chart for on the historical report.'),
        '#options' => [
          'all' => $this->t('All time'),
          'week' => $this->t('Past week'),
          'month' => $this->t('Past month'),
          '3month' => $this->t('Past 3 months'),
          '6month' => $this->t('Past 6 months'),
          'year' => $this->t('Past year'),
          '2year' => $this->t('Past 2 years'),
          'custom' => $this->t('Custom date range'),
        ],
        '#default_value' => isset($this->configuration['autogen']['date_range']) ? $this->configuration['autogen']['date_range'] : $this->defaultConfiguration()['autogen']['date_range'],
        '#states' => [
          'visible' => [
            ':input[name="data[autogen][enabled]"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="data[autogen][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'date_range_start' => [
        '#type' => 'date',
        '#title' => $this->t('From:'),
        '#default_value' => isset($this->configuration['autogen']['date_range_start']) ? $this->configuration['autogen']['date_range_start'] : '',
        '#states' => [
          'visible' => [
            ['select[name="data[autogen][date_range]"]' => ['value' => 'custom']],
          ],
          'required' => [
            ['select[name="data[autogen][date_range]"]' => ['value' => 'custom']],
          ],
        ],
      ],
      'date_range_end' => [
        '#type' => 'date',
        '#title' => $this->t('To:'),
        '#default_value' => isset($this->configuration['autogen']['date_range_end']) ? $this->configuration['autogen']['date_range_end'] : '',
        '#states' => [
          'visible' => [
            ['select[name="data[autogen][date_range]"]' => ['value' => 'custom']],
          ],
          'required' => [
            ['select[name="data[autogen][date_range]"]' => ['value' => 'custom']],
          ],
        ],
      ],
    ];

    $kpi_add_link = Link::fromTextAndUrl($this->t('Create a new KPI group.'), Url::fromRoute('entity.wpt_kpi.add_form'));

    // Flatten group ids.
    $kpi_group_ids = isset($this->configuration['kpi_groups']) ? array_map(function ($value) {
      return $value['target_id'];
    }, $this->configuration['kpi_groups']) : [];
    $kpi_groups = !empty($kpi_group_ids) ? $this->getKpiStorage()->loadMultiple($kpi_group_ids) : NULL;

    $form['kpi_groups'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'wpt_kpi',
      '#title' => $this->t('KPI Groups'),
      '#description' => $this->t('Select which KPI groups to apply to this job. @link', ['@link' => $kpi_add_link->toString()]),
      '#tags' => TRUE,
      '#default_value' => $kpi_groups,
      '#maxlength' => NULL,
      '#required' => TRUE,
    ];
    $chartjs_url = 'https://www.chartjs.org/docs/latest/';
    $form['chartjs_option'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Use this field to define the chart options as a javascript object. You are allowed to use existing javascript functions, such as <code>Drupal.t()</code>, the <code>url</code> variables is available to represent the captured URL, and the <em>group</em> variable is available to represent the KPI group. See <a href="@url" target="_blank" rel="noopener noreferrer">Chart.js documentation</a> for more information about available options.', ['@url' => $chartjs_url]),
      '#default_value' => isset($this->configuration['chartjs_option']) ? $this->configuration['chartjs_option'] : $this->defaultConfiguration()['chartjs_option'],
      '#required' => TRUE,
      '#attached' => [
        'library' => [
          'performance_budget/chartjs-validate',
        ],
      ],
    ];
    $form['chartjs_example'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#prefix' => $this->t('For example:'),
      '#value' => $this->getExampleChartJsOptions(),
    ];
    $form['chartjs_display'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#prefix' => $this->t('Sample chart using above settings:'),
      '#attributes' => [
        'id' => 'wpt_kpi_chart_preview',
        'style' => [
          'border: 1px dotted #c8c8c8;',
          'display: block;',
        ],
      ],
      '#value' => '',
    ];

    $this->injectNotificationFields($form, $this->configuration);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $keys_to_save = [
      'chartjs_option' => TRUE,
      'kpi_groups' => FALSE,
    ];
    foreach ($keys_to_save as $key => $trim) {
      $this->configuration[$key] = $trim ? trim($form_state->getValue($key)) : $form_state->getValue($key);
    }
    $this->configuration['api'] = $form_state->getValue('api');
    // To avoid accidental key leakage, empty out alternative method values.
    if ($this->configuration['api']['storage_method'] === 'plaintext') {
      $this->configuration['api']['key_module'] = '';
    }
    else {
      $this->configuration['api']['key_plaintext'] = '';
    }
    $this->configuration['autogen'] = $form_state->getValue('autogen');

    $this->injectNotificationConfig($this->configuration, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationContexts() {
    $contexts = parent::getNotificationContexts();
    unset($contexts['capture_complete_all']);
    $contexts['pb_threshold_violation'] = [
      'label' => $this->t('Performance Budget Threshold Violation'),
      'description' => $this->t('This context occurs when a threshold violation is detected during a capture.'),
    ];
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacementListByContext($context) {
    $ret = [
      '@wpa_id' => $this->t('Web page archive configuration entity ID'),
      '@wpa_label' => $this->t('Web page archive configuration entity label'),
      '@wpa_run_id' => $this->t('Web page archive run entity ID'),
      '@wpa_run_label' => $this->t('Web page archive run entity label'),
      '@wpa_run_url' => $this->t('URL to this individual run'),
    ];

    switch ($context) {
      case 'capture_complete_single':
      case 'pb_threshold_violation':
        $ret['@url'] = $this->t('URL of the current run');
        if ($context == 'pb_threshold_violation') {
          $ret['@violations'] = $this->t('List of violations');
        }
        $kpi_helper = $this->getKpiHelper();
        $kpi_groups = $this->getKpiStorage()->loadMultiple();
        foreach ($kpi_groups as $kpi_group_id => $kpi_group) {
          foreach ($kpi_group->getKpis() as $average => $average_details) {
            foreach ($average_details as $view => $view_details) {
              foreach ($view_details as $metric => $active) {
                // Skip threshold metrics.
                if (strpos($metric, '_threshold') !== FALSE) {
                  continue;
                }
                if ($active) {
                  $tokens = [
                    'pb_wpt',
                    $kpi_group->label(),
                    $kpi_helper->getFormattedAverageValue($average),
                    $kpi_helper->getFormattedViewValue($view),
                    $kpi_helper->getKpiMap()[$metric]['title'],
                  ];
                  $key = $kpi_helper->getReplacementKey($tokens);
                  $replacements = [
                    '@group' => $tokens[1],
                    '@average' => $tokens[2],
                    '@view' => $tokens[3],
                    '@metric' => $tokens[4],
                  ];
                  if ($key) {
                    $ret[$key] = $this->t('KPI Group Results [@group : @average : @view : @metric]', $replacements);
                  }
                }
              }
            }
          }
        }
        break;
    }
    return $ret;
  }

}
