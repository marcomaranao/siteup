<?php

namespace Drupal\performance_budget\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\performance_budget\Helper\KpiHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WebPageTestKpiForm.
 */
class WebPageTestKpiForm extends EntityForm {

  /**
   * KPI Helper service.
   *
   * @var \Drupal\performance_budget\Helper\KpiHelperInterface
   */
  protected $kpiHelper;

  /**
   * Constructs a new WebPageTestKpiForm.
   *
   * @param \Drupal\performance_budget\Helper\KpiHelperInterface $kpi_helper
   *   KPI Helper service.
   */
  public function __construct(KpiHelperInterface $kpi_helper) {
    $this->kpiHelper = $kpi_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('performance_budget.helper.kpi'));
  }

  /**
   * Retrieves all of the average KPI fields.
   *
   * @return array
   *   Render array containing the average KPI fields.
   */
  protected function getAverageKpiFields($average, $view) {
    $form = [];
    $defaults = $this->entity->getKpis();
    $kpis = $this->kpiHelper->getKpiMap();
    foreach ($kpis as $kpi => $details) {
      $has_kpi = !empty($defaults[$average][$view][$kpi]) ? $defaults[$average][$view][$kpi] : 0;
      $form[$kpi] = [
        '#type' => 'checkbox',
        '#title' => $details['title'],
        '#description' => $details['description'],
        '#default_value' => $has_kpi,
      ];

      // Grab threshold defaults.
      $has_minimum = !empty($defaults[$average][$view]["{$kpi}_threshold"]['has_minimum']);
      $has_maximum = !empty($defaults[$average][$view]["{$kpi}_threshold"]['has_maximum']);
      $minimum = isset($defaults[$average][$view]["{$kpi}_threshold"]['minimum']) ? $defaults[$average][$view]["{$kpi}_threshold"]['minimum'] : NULL;
      $maximum = isset($defaults[$average][$view]["{$kpi}_threshold"]['maximum']) ? $defaults[$average][$view]["{$kpi}_threshold"]['maximum'] : NULL;

      $form["{$kpi}_threshold"] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Thresholds'),
        '#states' => [
          'visible' => [
            ":input[name='kpis[{$average}][{$view}][{$kpi}]']" => ['checked' => TRUE],
          ],
        ],
        'has_minimum' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Has minimum value?'),
          '#description' => $this->t('Check this box if you want to trigger notifications when values below the minimum are detected.'),
          '#default_value' => $has_minimum,
        ],
        'minimum' => [
          '#type' => 'number',
          '#title' => $this->t('Minimum value (@units)', ['@units' => $details['units']]),
          '#description' => $this->t('Any detected values lower than this value would trigger a notification.'),
          '#default_value' => $minimum,
          '#states' => [
            'visible' => [
              ":input[name='kpis[{$average}][{$view}][{$kpi}_threshold][has_minimum]']" => ['checked' => TRUE],
            ],
            'required' => [
              ":input[name='kpis[{$average}][{$view}][{$kpi}_threshold][has_minimum]']" => ['checked' => TRUE],
            ],
          ],
        ],
        'has_maximum' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Has maximum value?'),
          '#description' => $this->t('Check this box if you want to trigger notifications when values above the maximum are detected.'),
          '#default_value' => $has_maximum,
        ],
        'maximum' => [
          '#type' => 'number',
          '#title' => $this->t('Maximum value (@units)', ['@units' => $details['units']]),
          '#description' => $this->t('Any detected values higher than this value would trigger a notification.'),
          '#default_value' => $maximum,
          '#states' => [
            'visible' => [
              ":input[name='kpis[{$average}][{$view}][{$kpi}_threshold][has_maximum]']" => ['checked' => TRUE],
            ],
            'required' => [
              ":input[name='kpis[{$average}][{$view}][{$kpi}_threshold][has_maximum]']" => ['checked' => TRUE],
            ],
          ],
        ],
      ];

    }
    return $form;
  }

  /**
   * Retrieves all of the average view group fields.
   *
   * @return array
   *   Render array containing the average view groups.
   */
  protected function getAverageViewGroups($average) {
    $form = [];
    $defaults = $this->entity->getKpis();
    $views = [
      'firstView' => [
        'title' => $this->t('First View'),
        'description' => $this->t('Cache is cleared and page is loaded.'),
      ],
      'repeatView' => [
        'title' => $this->t('Repeat View'),
        'description' => $this->t('Browser is closed, reopened and the page is tested again.'),
      ],
    ];
    foreach ($views as $view => $details) {
      $form[$view] = [
        '#type' => 'details',
        '#title' => $details['title'],
        '#description' => $details['description'],
        '#open' => isset($defaults[$average][$view]),
        '#tree' => TRUE,
      ];
      $form[$view] += $this->getAverageKpiFields($average, $view);
    }
    return $form;
  }

  /**
   * Retrieves all of the average type group fields.
   *
   * @return array
   *   Render array containing the average type groups.
   */
  protected function getAverageTypeGroups() {
    $form = [];
    $defaults = $this->entity->getKpis();
    $averages = [
      'average' => [
        'title' => $this->t('Average'),
        'description' => $this->t('The average value of the given metric across all of the runs for the given test.'),
      ],
      'median' => [
        'title' => $this->t('Median'),
        'description' => $this->t('The median value of the given metric across all of the runs for the given test.'),
      ],
      'standardDeviation' => [
        'title' => $this->t('Standard Deviation'),
        'description' => $this->t('The standard deviation in values of the given metric across all of the runs for the given test.'),
      ],
    ];

    foreach ($averages as $average => $details) {
      $form[$average] = [
        '#type' => 'details',
        '#title' => $details['title'],
        '#description' => $details['description'],
        '#open' => isset($defaults[$average]),
        '#tree' => TRUE,
      ];
      $form[$average] += $this->getAverageViewGroups($average);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $wpt_kpi = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wpt_kpi->label(),
      '#description' => $this->t('Label for the Web Page Test KPI Group.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wpt_kpi->id(),
      '#machine_name' => [
        'exists' => '\Drupal\performance_budget\Entity\WebPageTestKpi::load',
      ],
      '#disabled' => !$wpt_kpi->isNew(),
    ];
    $form['kpis'] = [
      '#type' => 'details',
      '#title' => $this->t('Key Performance Indicators'),
      '#description' => $this->t('Select which KPIs you are interested in tracking in this group.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['kpis'] += $this->getAverageTypeGroups();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wpt_kpi = $this->entity;
    $status = $wpt_kpi->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Web Page Test KPI Group.', [
          '%label' => $wpt_kpi->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Web Page Test KPI Group.', [
          '%label' => $wpt_kpi->label(),
        ]));
    }
    $form_state->setRedirectUrl($wpt_kpi->toUrl('collection'));
  }

}
