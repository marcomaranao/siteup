<?php

namespace Drupal\Tests\performance_budget\Unit\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\performance_budget\Form\AggregateRunDataForm;

/**
 * @coversDefaultClass \Drupal\performance_budget\Form\AggregateRunDataForm
 *
 * @group performance_budget
 */
class AggregateRunDataFormTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * Mock State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Mock time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Mock date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->state = $this->getMockBuilder('\Drupal\Core\State\StateInterface')
      ->getMock();
    $this->time = $this->getMockBuilder('\Drupal\Component\Datetime\TimeInterface')
      ->getMock();
    $this->dateFormatter = $this->getMockBuilder('\Drupal\Core\Datetime\DateFormatterInterface')
      ->getMock();
    $this->dateFormatter->expects($this->any())
      ->method('format')
      ->will($this->onConsecutiveCalls('2019-07-13', '2019-07-30'));

    $this->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests WebPageTestKpi::buildForm() sets default values.
   */
  public function testBuildFormSetsAppropriateDefaults() {
    $form = new AggregateRunDataForm($this->state, $this->time, $this->dateFormatter);
    $form->setStringTranslation($this->getStringTranslationStub());

    $form_state = new FormState();
    $expected = [
      'instructions' => [
        '#markup' => $this->t('Press the button below to process all captured webpagetest.org data for this job.'),
      ],
      'date_range' => [
        '#type' => 'select',
        '#title' => $this->t('Date range:'),
        '#description' => $this->t('Specify the date range you want to aggregate KPIs on. Drupal will remember this setting on subsequent reports.'),
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
        '#default_value' => 'all',
        '#required' => TRUE,
      ],
      'date_range_start' => [
        '#type' => 'date',
        '#title' => $this->t('From:'),
        '#default_value' => '2019-07-13',
        '#states' => [
          'visible' => [
            ['select[name="date_range"]' => ['value' => 'custom']],
          ],
          'required' => [
            ['select[name="date_range"]' => ['value' => 'custom']],
          ],
        ],
      ],
      'date_range_end' => [
        '#type' => 'date',
        '#title' => $this->t('To:'),
        '#default_value' => '2019-07-30',
        '#states' => [
          'visible' => [
            ['select[name="date_range"]' => ['value' => 'custom']],
          ],
          'required' => [
            ['select[name="date_range"]' => ['value' => 'custom']],
          ],
        ],
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Process data'),
          '#button_type' => 'primary',
        ],
      ],
    ];

    $visibility_states = [
      'visible' => [
        ['select[name="date_range"]' => ['value' => 'custom']],
      ],
      'required' => [
        ['select[name="date_range"]' => ['value' => 'custom']],
      ],
    ];
    $this->assertEquals($expected, $form->buildForm([], $form_state));
  }

  /**
   * Tests WebPageTestKpi::buildForm() sets values from state API.
   */
  public function testBuildFormSetsStateValues() {
    $this->state->expects($this->any())
      ->method('get')
      ->will($this->returnValue([
        'date_range' => 'custom',
        'date_range_start' => '2018-08-13',
        'date_range_end' => '2019-04-04',
      ]));
    $form = new AggregateRunDataForm($this->state, $this->time, $this->dateFormatter);
    $form->setStringTranslation($this->getStringTranslationStub());

    $form_state = new FormState();
    $expected = [
      'instructions' => [
        '#markup' => $this->t('Press the button below to process all captured webpagetest.org data for this job.'),
      ],
      'date_range' => [
        '#type' => 'select',
        '#title' => $this->t('Date range:'),
        '#description' => $this->t('Specify the date range you want to aggregate KPIs on. Drupal will remember this setting on subsequent reports.'),
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
        '#default_value' => 'custom',
        '#required' => TRUE,
      ],
      'date_range_start' => [
        '#type' => 'date',
        '#title' => $this->t('From:'),
        '#default_value' => '2018-08-13',
        '#states' => [
          'visible' => [
            ['select[name="date_range"]' => ['value' => 'custom']],
          ],
          'required' => [
            ['select[name="date_range"]' => ['value' => 'custom']],
          ],
        ],
      ],
      'date_range_end' => [
        '#type' => 'date',
        '#title' => $this->t('To:'),
        '#default_value' => '2019-04-04',
        '#states' => [
          'visible' => [
            ['select[name="date_range"]' => ['value' => 'custom']],
          ],
          'required' => [
            ['select[name="date_range"]' => ['value' => 'custom']],
          ],
        ],
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Process data'),
          '#button_type' => 'primary',
        ],
      ],
    ];

    $visibility_states = [
      'visible' => [
        ['select[name="date_range"]' => ['value' => 'custom']],
      ],
      'required' => [
        ['select[name="date_range"]' => ['value' => 'custom']],
      ],
    ];
    $this->assertEquals($expected, $form->buildForm([], $form_state));
  }

}
