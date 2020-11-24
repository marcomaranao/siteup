<?php

namespace Drupal\performance_budget\Exception;

use Drupal\Core\Queue\RequeueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Used when WebPageTest results are still pending.
 *
 * This class notably extends RequeueException. This is important so that any
 * cron-based captures that are still processing data can properly remain in the
 * queue until data is processed.
 *
 * @see \Drupal\web_page_archive\Controller\WebPageArchiveController::batchProcess()
 */
class WebPageTestApiPendingException extends RequeueException {

  use StringTranslationTrait;

  /**
   * Instantiates new WebPageTestApiPendingException.
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
