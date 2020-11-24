<?php

namespace Drupal\performance_budget\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\performance_budget\Helper\KpiHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Web Page Test KPI Group entities.
 */
class WebPageTestKpiListBuilder extends ConfigEntityListBuilder {

  /**
   * KPI Helper service.
   *
   * @var \Drupal\performance_budget\Helper\KpiHelperInterface
   */
  protected $kpiHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, KpiHelperInterface $kpi_helper) {
    parent::__construct($entity_type, $storage);
    $this->kpiHelper = $kpi_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $storage = $container->get('entity_type.manager')->getStorage($entity_type->id());
    $kpi_helper = $container->get('performance_budget.helper.kpi');
    return new static($entity_type, $storage, $kpi_helper);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Web Page Test KPI Group');
    $header['id'] = $this->t('Machine name');
    $header['kpis'] = $this->t('KPIs');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $kpis = [];
    $html = '<dl>';
    $replacements = [];
    $replacementIdx = 0;
    foreach ($entity->getKpis() as $average => $average_details) {
      foreach ($average_details as $view => $view_details) {
        foreach ($view_details as $kpi => $kpi_details) {
          if (strpos($kpi, '_threshold') !== FALSE) {
            $threshold = [];
            if (!empty($kpi_details['has_minimum']) && isset($kpi_details['minimum'])) {
              $minimum = $kpi_details['minimum'];
              $threshold[] = $this->t('- Minimum value: @minimum', ['@minimum' => $minimum]);
            }
            if (!empty($kpi_details['has_maximum']) && isset($kpi_details['maximum'])) {
              $maximum = $kpi_details['maximum'];
              $threshold[] = $this->t('- Maximum value: @maximum', ['@maximum' => $maximum]);
            }
            if (!empty($threshold)) {
              $threshold_str = implode('<br>', $threshold);
              $html .= "<dt>{$threshold_str}</dt>";
            }
          }
          else {
            $html .= "<dt>@kpi{$replacementIdx}</dt>";
            $tokens = [
              $this->kpiHelper->getFormattedAverageValue($average),
              $this->kpiHelper->getFormattedViewValue($view),
              isset($this->kpiHelper->getKpiMap()[$kpi]['title']) ? $this->kpiHelper->getKpiMap()[$kpi]['title'] : $kpi,
            ];
            $replacements["@kpi{$replacementIdx}"] = implode(' : ', $tokens);
            $replacementIdx++;
          }
        }
      }
    }
    $html .= '</dl>';
    $row['kpis'] = new FormattableMarkup($html, $replacements);
    return $row + parent::buildRow($entity);
  }

}
