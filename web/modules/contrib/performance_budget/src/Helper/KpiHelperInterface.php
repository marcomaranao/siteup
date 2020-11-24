<?php

namespace Drupal\performance_budget\Helper;

/**
 * Interface KpiHelperInterface.
 */
interface KpiHelperInterface {

  /**
   * Retrieves a mapping of KPI fields to their respective titles/descriptions.
   *
   * @return array
   *   KPI map array.
   */
  public function getKpiMap();

  /**
   * Formats known average types.
   *
   * @param string $value
   *   The value of the average.
   *
   * @return mixed
   *   A formatted version of the average.
   */
  public function getFormattedAverageValue($value);

  /**
   * Formats known view types.
   *
   * @param string $value
   *   The value of the view.
   *
   * @return mixed
   *   A formatted version of the view.
   */
  public function getFormattedViewValue($value);

  /**
   * Formats known KPI types.
   *
   * @param string $kpi
   *   The type of KPI.
   * @param string $value
   *   The value of the KPI.
   *
   * @return mixed
   *   A formatted version of the KPI.
   */
  public function getFormattedKpiValue($kpi, $value);

  /**
   * Retrieves a replacement key based on the specified token array.
   *
   * @param array $tokens
   *   Token array.
   *
   * @return string|bool
   *   Key value or FALSE if $tokens is empty.
   */
  public function getReplacementKey(array $tokens);

}
