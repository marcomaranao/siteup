<?php

namespace Drupal\Tests\performance_budget\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\web_page_archive\Kernel\EntityStorageTestBase;
use Drupal\performance_budget\Form\WebPageTestFetchResultForm;

/**
 * Tests web page test fetch result form functionality.
 *
 * @group performance_budget
 */
class WebPageTestFetchResultFormTest extends EntityStorageTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['performance_budget', 'web_page_archive'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['performance_budget']);
    $this->form = WebPageTestFetchResultForm::create($this->container);
  }

  /**
   * Tests that button is present when job has pb_wpt_capture instance.
   */
  public function testWptJobHasButton() {
    $urls = ['https://www.drupal.org'];
    $wpa = $this->getWpaEntity('My run entity', $urls, 3);
    $wpa->set('use_cron', FALSE);
    $wpa->set('capture_utilities', [
      '1234' => [
        'id' => 'pb_wpt_capture',
        'data' => [
          'kpi_groups' => [],
        ],
      ],
    ]);
    $wpa->save();
    $form_state = new FormState();
    $form = $this->form->buildForm([], $form_state, $wpa->getRunEntity());
    $expected = $this->t('This run may have pending results available from WebPageTest. Click "Fetch Results" to attempt to download the test results.');
    $this->assertEquals($expected, $form['intro']['#markup']);
    $this->assertArrayHasKey('button', $form);
  }

  /**
   * Tests that button is disabled when job doesnt have pb_wpt_capture instance.
   */
  public function testNonWptJobConcealsButton() {
    $urls = ['https://www.drupal.org'];
    $wpa = $this->getWpaEntity('My run entity', $urls, 3);
    $wpa->set('use_cron', FALSE);
    $wpa->save();
    $form_state = new FormState();
    $form = $this->form->buildForm([], $form_state, $wpa->getRunEntity());
    $expected = $this->t('This capture job results could not be loaded. Please try again later.');
    $this->assertEquals($expected, $form['intro']['#markup']);
    $this->assertArrayNotHasKey('button', $form);
  }

  /**
   * Tests that button is disabled when cron is active.
   */
  public function testUseCronConcealsButton() {
    $urls = ['https://www.drupal.org'];
    $wpa = $this->getWpaEntity('My run entity', $urls, 3);
    $wpa->set('use_cron', TRUE);
    $wpa->set('cron_schedule', '* * * * *');
    $wpa->save();
    $form_state = new FormState();
    $form = $this->form->buildForm([], $form_state, $wpa->getRunEntity());
    $expected = $this->t('This capture job uses cron scheduling, so manual result downloading is disabled.');
    $this->assertEquals($expected, $form['intro']['#markup']);
    $this->assertArrayNotHasKey('button', $form);
  }

}
