<?php

namespace Drupal\performance_budget\Exception;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Used when WebPageTest throws an error.
 */
class WebPageTestApiErrorException extends \Exception {

  use StringTranslationTrait;

  /**
   * Instantiates new WebPageTestApiErrorException.
   */
  public function __construct($test_id, $status_text, $status_code) {
    $message = $this->t('WebPageTest @status_code API status for test [@test_id]: @status_text', [
      '@status_code' => $status_code,
      '@status_text' => $status_text,
      '@test_id' => $test_id,
    ]);
    parent::__construct($message, $status_code);
  }

}
