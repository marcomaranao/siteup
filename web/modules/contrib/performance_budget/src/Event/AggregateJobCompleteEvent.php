<?php

namespace Drupal\performance_budget\Event;

use Drupal\web_page_archive\Entity\WebPageArchiveInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when data aggregation is completed.
 */
class AggregateJobCompleteEvent extends Event {

  const EVENT_NAME = 'pb_aggregate_runs_complete';

  /**
   * The completed job.
   *
   * @var \Drupal\web_page_archive\Entity\WebPageArchiveInterface
   */
  public $webPageArchive;

  /**
   * Constructs the object.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $wpa
   *   The config job that was just aggregated.
   */
  public function __construct(WebPageArchiveInterface $wpa) {
    $this->webPageArchive = $wpa;
  }

}
