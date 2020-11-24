<?php

namespace Drupal\performance_budget\Helper;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class KpiHelper.
 */
class KpiHelper implements KpiHelperInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getKpiMap() {
    $speed_index_link = 'https://sites.google.com/a/webpagetest.org/docs/using-webpagetest/metrics/speed-index';
    return [
      'loadTime' => [
        'title' => $this->t('Load Time'),
        'description' => $this->t('The Load Time is measured as the time from the start of the initial navigation until the beginning of the window load event (onload).'),
        'units' => $this->t('milliseconds'),
      ],
      'fullyLoaded' => [
        'title' => $this->t('Fully Loaded'),
        'description' => $this->t('The Fully Loaded time is measured as the time from the start of the initial navigation until there was 2 seconds of no network activity after Document Complete.  This will usually include any activity that is triggered by javascript after the main page loads.'),
        'units' => $this->t('milliseconds'),
      ],
      'TTFB' => [
        'title' => $this->t('TTFB'),
        'description' => $this->t('The First Byte time (often abbreviated as TTFB) is measured as the time from the start of the initial navigation until the first byte of the base page is received by the browser (after following redirects).'),
        'units' => $this->t('milliseconds'),
      ],
      'domElements' => [
        'title' => $this->t('Dom Elements'),
        'description' => $this->t('The DOM Elements metric is the count of the DOM elements on the tested page as measured at the end of the test.'),
        'units' => $this->t('elements'),
      ],
      'SpeedIndex' => [
        'title' => $this->t('Speed Index'),
        'description' => $this->t('The Speed Index is a calculated metric that represents how quickly the page rendered the user-visible content (lower is better).  More information on how it is calculated is available here: <a href=":url" target="_blank">Speed Index</a>', [':url' => $speed_index_link]),
        'units' => $this->t('milliseconds'),
      ],
      'render' => [
        'title' => $this->t('Start Render'),
        'description' => $this->t('The Start Render time is measured as the time from the start of the initial navigation until the first non-white content is painted to the browser display.'),
        'units' => $this->t('milliseconds'),
      ],
      'requests' => [
        'title' => $this->t('Request Count'),
        'description' => $this->t('The Request Count will always contain the total number of requests for the test.'),
        'units' => $this->t('requests'),
      ],
      'responses_200' => [
        'title' => $this->t('200 Response Count'),
        'description' => $this->t('The 200 Response Count will always contain the total number of 200 responses for the test.'),
        'units' => $this->t('responses'),
      ],
      'responses_404' => [
        'title' => $this->t('404 Response Count'),
        'description' => $this->t('The 404 Response Count will always contain the total number of 404 responses for the test.'),
        'units' => $this->t('responses'),
      ],
      'responses_other' => [
        'title' => $this->t('Other Response Count'),
        'description' => $this->t('The Other Response Count will always contain the total number of non-200/404 responses for the test.'),
        'units' => $this->t('responses'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedAverageValue($value) {
    switch ($value) {
      case 'average':
        return $this->t('Average')->render();

      case 'median':
        return $this->t('Median')->render();

      case 'standardDeviation':
        return $this->t('Standard Deviation')->render();

    }
    return $this->t('Unknown Average')->render();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedViewValue($value) {
    switch ($value) {
      case 'firstView':
        return $this->t('First View')->render();

      case 'repeatView':
        return $this->t('Repeat View')->render();

    }
    return $this->t('Unknown View')->render();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedKpiValue($kpi, $value) {
    switch ($kpi) {
      // KPIs that should render as seconds:
      case 'docTime':
      case 'loadTime':
      case 'fullyLoaded':
      case 'render':
      case 'TTFB':
      case 'SpeedIndex':
        return $this->t('@values', ['@value' => $value / 1000])->render();
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacementKey(array $tokens) {
    if (!empty($tokens)) {
      return '@' . strtolower(str_replace(' ', '_', implode('__', $tokens)));
    }

    return FALSE;
  }

}
