<?php

namespace Drupal\performance_budget\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Web Page Test KPI Group entities.
 */
interface WebPageTestKpiInterface extends ConfigEntityInterface {

  /**
   * Retrieves KPIs from configuration.
   *
   * @return array
   *   Multi-dimensional array containing KPI results.
   */
  public function getKpis();

}
