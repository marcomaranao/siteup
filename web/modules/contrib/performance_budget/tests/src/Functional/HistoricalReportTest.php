<?php

namespace Drupal\Tests\performance_budget\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\performance_budget\Plugin\CaptureResponse\WebPageTestCaptureResponse;
use Drupal\performance_budget\Event\WebPageTestCaptureJobCompleteEvent;

/**
 * Tests web page archive.
 *
 * @group performance_budget
 */
class HistoricalReportTest extends BrowserTestBase {

  const FIXTURE_PATH = __DIR__ . '/../../fixtures';

  /**
   * {@inheritdoc}
   */
  public $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Authorized Admin User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorizedAdminUser;

  /**
   * Authorized View User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorizedReadOnlyUser;

  /**
   * Unauthorized User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $unauthorizedUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'node',
    'performance_budget',
    'web_page_archive',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->authorizedAdminUser = $this->drupalCreateUser([
      'administer web page archive',
      'view web page archive results',
    ]);

    $this->placeBlock('local_actions_block', ['region' => 'content']);

    $this->wpaStorage = $this->container->get('entity_type.manager')->getStorage('web_page_archive');
  }

  /**
   * Tests the achecker capture utility end-to-end.
   */
  public function testHistoricalReportWorksAsExpected() {
    $assert = $this->assertSession();
    $start_time = 1565234212;
    $this->drupalLogin($this->authorizedAdminUser);

    // Create KPI Groups.
    $this->drupalGet('admin/config/system/web-page-archive/wpt-kpis');
    $assert->pageTextNotContains('Standard Deviation : First View : Fully Loaded');
    $assert->pageTextNotContains('Median : First View : TTFB');
    $this->clickLink('Add Web Page Test KPI Group');
    $this->drupalPostForm(
      NULL,
      [
        'label' => 'KPI Group 1',
        'id' => 'kpi_group_1',
        'kpis[standardDeviation][firstView][fullyLoaded]' => 1,
        'kpis[median][firstView][TTFB_threshold][has_minimum]' => 0,
        'kpis[median][firstView][TTFB_threshold][minimum]' => 50,
        'kpis[median][firstView][TTFB_threshold][has_maximum]' => 0,
        'kpis[median][firstView][TTFB_threshold][maximum]' => 75,
        'kpis[median][firstView][TTFB]' => 1,
        'kpis[median][firstView][TTFB_threshold][has_minimum]' => 1,
        'kpis[median][firstView][TTFB_threshold][minimum]' => 25,
        'kpis[median][firstView][TTFB_threshold][has_maximum]' => 1,
        'kpis[median][firstView][TTFB_threshold][maximum]' => 100,
      ],
      'Save'
    );
    $assert->pageTextContains('Standard Deviation : First View : Fully Loaded');
    $assert->pageTextContains('Median : First View : TTFB');
    $assert->pageTextContains('Minimum value: 25');
    $assert->pageTextContains('Maximum value: 100');
    $assert->pageTextNotContains('Average : First View : Speed Index');
    $assert->pageTextNotContains('Average : Repeat View : Speed Index');
    $assert->pageTextNotContains('Minimum value: 50');
    $assert->pageTextNotContains('Maximum value: 75');
    $this->clickLink('Add Web Page Test KPI Group');
    $this->drupalPostForm(
      NULL,
      [
        'label' => 'KPI Group 2',
        'id' => 'kpi_group_2',
        'kpis[average][firstView][SpeedIndex]' => 1,
        'kpis[average][repeatView][SpeedIndex]' => 1,
      ],
      'Save'
    );
    $assert->pageTextContains('Average : First View : Speed Index');
    $assert->pageTextContains('Average : Repeat View : Speed Index');

    // Add one more KPI group with a really long name.
    $this->clickLink('Add Web Page Test KPI Group');

    // Create WPT jobs.
    $this->drupalGet('admin/config/system/web-page-archive/jobs/add');
    $this->drupalPostForm(
      NULL,
      [
        'label' => 'Test Archive',
        'id' => 'test_archive',
        'timeout' => 500,
        'use_cron' => 1,
        'use_robots' => 0,
        'user_agent' => 'MonkeyBot',
        'cron_schedule' => '* * * * *',
        'url_type' => 'url',
        'urls' => 'http://localhost',
      ],
      'Create new archive'
    );
    $assert->pageTextContains('Created the Test Archive Web page archive entity.');

    // Add a screenshot capture utility.
    $this->drupalPostForm(NULL, ['new' => 'pb_wpt_capture'], 'Add');
    $this->drupalPostForm(NULL, [
      'data[api][hostname]' => 'http://localhost:4000',
      'data[api][storage_method]' => 'plaintext',
      'data[api][key_plaintext]' => 'SomeApiKeyHere',
      'data[kpi_groups]' => 'KPI Group 1 (kpi_group_1), KPI Group 2 (kpi_group_2), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1), KPI Group 1 (kpi_group_1)',
      'data[chartjs_option]' => '{ height: 1000 }',
    ], 'Add capture utility');

    // Simulate job runs by creating a few revisions.
    $wpa = $this->wpaStorage->load('test_archive');
    $run = $wpa->getRunEntity();
    for ($i = 0; $i < 3; $i++) {
      $timestamp = $start_time - 86400 * $i;
      $captured_result['timestamp'] = $timestamp;
      $captured_result['vid'] = $i + 1;
      $run->setNewRevision(TRUE);
      $run->setRevisionCreationTime($timestamp);
      $field_captures = $run->get('field_captures');
      for ($j = 0; $j < 2; $j++) {
        $file = static::FIXTURE_PATH . '/https-www-drupal-org.json';
        $url = "https:///www.drupal.org/?j={$j}";
        $capture = [
          'uuid' => 'ea174347-fc38-4cb2-9fe7-8cc7413b251a',
          'run_uuid' => 'dc8789cc-c827-4bab-8c48-adcda54c61d5',
          'timestamp' => $timestamp,
          'status' => 'complete',
          'capture_url' => $url,
          'capture_response' => new WebPageTestCaptureResponse($file, $url),
          'capture_size' => 1636852,
          'vid' => $i + 1,
          'delta' => $j,
          'langcode' => 'en',
        ];
        $field_captures->appendItem(serialize($capture));
      }
      $run->setCaptureUtilities($wpa->getCaptureUtilityMap());
      $run->set('success_ct', 1);
      $run->set('capture_size', 1636852);
      $run->save();
    }

    // Go check the test_archive job and process the historical report.
    $this->drupalGet('admin/config/system/web-page-archive/jobs/test_archive');
    $this->clickLink('View Historical Report');
    $this->assertUrl('admin/config/system/web-page-archive/jobs/test_archive/wpt-history/process');
    $this->drupalPostForm(NULL, ['date_range' => 'all'], 'Process data');
    $this->assertUrl('admin/config/system/web-page-archive/jobs/test_archive/wpt-history');

    // Confirm javascript settings and libraries are all present.
    $base_url = Url::fromRoute('<front>')->toString();
    $encoded_path = json_encode("{$base_url}admin/config/system/web-page-archive/runs/_VID_");
    $assert->responseContains('"wpaRunsBaseUrl":' . $encoded_path);
    $assert->responseContains('"pbWptResults":{"KPI Group 1":{"https:\/\/\/www.drupal.org\/?j=0":{"1565234212":{"Median - First View - TTFB":{"2":0.271},"Standard Deviation - First View - Fully Loaded":{"2":0}},"1565147812":{"Median - First View - TTFB":{"3":0.271},"Standard Deviation - First View - Fully Loaded":{"3":0}},"1565061412":{"Median - First View - TTFB":{"4":0.271},"Standard Deviation - First View - Fully Loaded":{"4":0}}},"https:\/\/\/www.drupal.org\/?j=1":{"1565234212":{"Median - First View - TTFB":{"2":0.271},"Standard Deviation - First View - Fully Loaded":{"2":0}},"1565147812":{"Median - First View - TTFB":{"3":0.271},"Standard Deviation - First View - Fully Loaded":{"3":0}},"1565061412":{"Median - First View - TTFB":{"4":0.271},"Standard Deviation - First View - Fully Loaded":{"4":0}}}},"KPI Group 2":{"https:\/\/\/www.drupal.org\/?j=0":{"1565234212":{"Average - First View - Speed Index":{"2":1.804},"Average - Repeat View - Speed Index":{"2":0.996}},"1565147812":{"Average - First View - Speed Index":{"3":1.804},"Average - Repeat View - Speed Index":{"3":0.996}},"1565061412":{"Average - First View - Speed Index":{"4":1.804},"Average - Repeat View - Speed Index":{"4":0.996}}},"https:\/\/\/www.drupal.org\/?j=1":{"1565234212":{"Average - First View - Speed Index":{"2":1.804},"Average - Repeat View - Speed Index":{"2":0.996}},"1565147812":{"Average - First View - Speed Index":{"3":1.804},"Average - Repeat View - Speed Index":{"3":0.996}},"1565061412":{"Average - First View - Speed Index":{"4":1.804},"Average - Repeat View - Speed Index":{"4":0.996}}}}}');
    $assert->responseContains('<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>');
    $assert->responseContains('<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-trendline@0.1.2/src/chartjs-plugin-trendline.min.js"></script>');
    $assert->responseContains('<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@0.7.3"></script>');
    $assert->responseContains('<div class="wpt-kpi-chart" id="wpt_kpi_chart"></div>');
    $assert->responseMatches('|<script src=".*/modules/contrib/performance_budget/js/chartjs.js?.*"></script>|');
    $assert->responseMatches('|<script>\s*function wptRetrieveChartJsOptions\(group, url\) {\s*return { height: 1000 };\s*}\s*</script>|s');

    // Confirm request data shows up.
    $assert->responseContains("<tr><td class='pb-requestTableUrl'>https://fonts.googleapis.com</td><td class='pb-requestTableFirst'>08/06/2019 - 13:16</td><td class='pb-requestTableLast'>08/08/2019 - 13:16</td><td class='pb-requestTableCount'>12</td><td class='pb-requestTableAverage'>0.211s</td></tr>");
    $assert->responseContains("<tr><td class='pb-requestTableUrl'>https://pixel-geo.prfct.co</td><td class='pb-requestTableFirst'>08/06/2019 - 13:16</td><td class='pb-requestTableLast'>08/08/2019 - 13:16</td><td class='pb-requestTableCount'>24</td><td class='pb-requestTableAverage'>0.118s</td></tr>");
    $assert->responseContains('08/06/2019');

    // Confirm event fired.
    $assert->pageTextContains('Data aggregation complete for Test Archive job.');

    // Let's attempt to filter by date range.
    $this->drupalGet('admin/config/system/web-page-archive/jobs/test_archive/wpt-history/process');
    $this->drupalPostForm(NULL, [
      'date_range' => 'custom',
      'date_range_start' => '2019-08-07',
      'date_range_end' => '2019-08-10',
    ], 'Process data');
    $assert->responseContains('"pbWptResults":{"KPI Group 1":{"https:\/\/\/www.drupal.org\/?j=0":{"1565234212":{"Median - First View - TTFB":{"2":0.271},"Standard Deviation - First View - Fully Loaded":{"2":0}},"1565147812":{"Median - First View - TTFB":{"3":0.271},"Standard Deviation - First View - Fully Loaded":{"3":0}}},"https:\/\/\/www.drupal.org\/?j=1":{"1565234212":{"Median - First View - TTFB":{"2":0.271},"Standard Deviation - First View - Fully Loaded":{"2":0}},"1565147812":{"Median - First View - TTFB":{"3":0.271},"Standard Deviation - First View - Fully Loaded":{"3":0}}}},"KPI Group 2":{"https:\/\/\/www.drupal.org\/?j=0":{"1565234212":{"Average - First View - Speed Index":{"2":1.804},"Average - Repeat View - Speed Index":{"2":0.996}},"1565147812":{"Average - First View - Speed Index":{"3":1.804},"Average - Repeat View - Speed Index":{"3":0.996}}},"https:\/\/\/www.drupal.org\/?j=1":{"1565234212":{"Average - First View - Speed Index":{"2":1.804},"Average - Repeat View - Speed Index":{"2":0.996}},"1565147812":{"Average - First View - Speed Index":{"3":1.804},"Average - Repeat View - Speed Index":{"3":0.996}}}}}');
    $assert->responseNotContains('08/06/2019');

    // Let's turn on automatic historical data processing.
    $this->drupalGet('admin/config/system/web-page-archive/jobs/test_archive/edit');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'data[autogen][enabled]' => 1,
      'data[autogen][date_range]' => 'custom',
      'data[autogen][date_range_start]' => '2019-08-08',
      'data[autogen][date_range_end]' => '2019-08-10',
    ], 'Update capture utility');

    // Confirm field values are remembered.
    $this->clickLink('Edit');
    $this->assertFieldChecked('data[autogen][enabled]');
    $this->assertFieldByName('data[autogen][date_range]', 'custom');
    $this->assertFieldByName('data[autogen][date_range_start]', '2019-08-08');
    $this->assertFieldByName('data[autogen][date_range_end]', '2019-08-10');

    // Trigger the CaptureJobCompleteEvent.
    $event = new WebPageTestCaptureJobCompleteEvent($run);
    $this->container->get('event_dispatcher')->dispatch($event::EVENT_NAME, $event);

    // Go to history page add confirm date range is changed.
    $this->drupalGet('admin/config/system/web-page-archive/jobs/test_archive/wpt-history');
    $assert->responseNotContains('08/07/2019');
    $assert->responseContains('08/08/2019');
    $assert->responseContains('"pbWptResults":{"KPI Group 1":{"https:\/\/\/www.drupal.org\/?j=0":{"1565234212":{"Median - First View - TTFB":{"2":0.271},"Standard Deviation - First View - Fully Loaded":{"2":0}}},"https:\/\/\/www.drupal.org\/?j=1":{"1565234212":{"Median - First View - TTFB":{"2":0.271},"Standard Deviation - First View - Fully Loaded":{"2":0}}}},"KPI Group 2":{"https:\/\/\/www.drupal.org\/?j=0":{"1565234212":{"Average - First View - Speed Index":{"2":1.804},"Average - Repeat View - Speed Index":{"2":0.996}}},"https:\/\/\/www.drupal.org\/?j=1":{"1565234212":{"Average - First View - Speed Index":{"2":1.804},"Average - Repeat View - Speed Index":{"2":0.996}}}}}');
  }

}
