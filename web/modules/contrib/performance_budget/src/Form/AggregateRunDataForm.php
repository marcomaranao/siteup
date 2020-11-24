<?php

namespace Drupal\performance_budget\Form;

use Drupal\performance_budget\Controller\AggregateBatchController;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\web_page_archive\Entity\WebPageArchiveInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Compares web page archive capture runs.
 */
class AggregateRunDataForm extends FormBase {

  /**
   * State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State API service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Datetime service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter service.
   */
  public function __construct(StateInterface $state, TimeInterface $time, DateFormatterInterface $date_formatter) {
    $this->state = $state;
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pb_aggregate_runs';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebPageArchiveInterface $web_page_archive = NULL) {
    $this->webPageArchive = $web_page_archive;
    $form['instructions'] = [
      '#markup' => $this->t('Press the button below to process all captured webpagetest.org data for this job.'),
    ];
    $values = $this->state->get('pb.aggregate.settings');
    $now = $this->time->getRequestTime();

    $form['date_range'] = [
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
      '#default_value' => isset($values['date_range']) ? $values['date_range'] : 'all',
      '#required' => TRUE,
    ];

    $visibility_states = [
      'visible' => [
        ['select[name="date_range"]' => ['value' => 'custom']],
      ],
      'required' => [
        ['select[name="date_range"]' => ['value' => 'custom']],
      ],
    ];
    $form['date_range_start'] = [
      '#type' => 'date',
      '#title' => $this->t('From:'),
      '#default_value' => isset($values['date_range_start']) ? $values['date_range_start'] : $this->dateFormatter->format($now - 365 * 24 * 60 * 60, 'custom', 'Y-m-d'),
      '#states' => $visibility_states,
    ];
    $form['date_range_end'] = [
      '#type' => 'date',
      '#title' => $this->t('To:'),
      '#default_value' => isset($values['date_range_end']) ? $values['date_range_end'] : $this->dateFormatter->format($now, 'custom', 'Y-m-d'),
      '#states' => $visibility_states,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Process data'),
        '#button_type' => 'primary',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $date_range = $form_state->getValue('date_range');
    $date_range_start = $form_state->getValue('date_range_start');
    $date_range_end = $form_state->getValue('date_range_end');
    AggregateBatchController::initializeBatchJob($this->webPageArchive, $date_range, $date_range_start, $date_range_end, FALSE);
    $this->state->set('pb.aggregate.settings', [
      'date_range' => $date_range,
      'date_range_start' => $date_range_start,
      'date_range_end' => $date_range_end,
    ]);
    $form_state->setRedirect('entity.web_page_archive.pb_wpt_history', ['web_page_archive' => $this->webPageArchive->id()]);
  }

}
