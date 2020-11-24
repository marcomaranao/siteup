<?php

namespace Drupal\performance_budget\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\performance_budget\Controller\AggregateBatchController;
use Drupal\performance_budget\Event\AggregateJobCompleteEvent;
use Drupal\performance_budget\Event\WebPageTestCaptureJobCompleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the web page archive events.
 */
class WebPageTestEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      AggregateJobCompleteEvent::EVENT_NAME => 'aggregateJobComplete',
      WebPageTestCaptureJobCompleteEvent::EVENT_NAME => 'captureComplete',
    ];
  }

  /**
   * React to a capture job completing.
   *
   * @param \Drupal\performance_budget\Event\AggregateJobCompleteEvent $event
   *   Capture job completion event.
   */
  public function aggregateJobComplete(AggregateJobCompleteEvent $event) {
    \Drupal::messenger()->addStatus($this->t('Data aggregation complete for @name job.', ['@name' => $event->webPageArchive->label()]));
  }

  /**
   * React to a capture job completing.
   *
   * @param \Drupal\performance_budget\Event\WebPageTestCaptureJobCompleteEvent $event
   *   Capture job completion event.
   */
  public function captureComplete(WebPageTestCaptureJobCompleteEvent $event) {
    $wpa_run = $event->getRunEntity();
    $wpa = $wpa_run->getConfigEntity();
    $capture_utilities = $wpa->getCaptureUtilities()->getConfiguration();
    foreach ($capture_utilities as $capture_utility) {
      if ($capture_utility['id'] == 'pb_wpt_capture' && !empty($capture_utility['data']['autogen']['enabled'])) {
        $date_range = $capture_utility['data']['autogen']['date_range'];
        $start = !empty($capture_utility['data']['autogen']['date_range_start']) ? $capture_utility['data']['autogen']['date_range_start'] : NULL;
        $end = !empty($capture_utility['data']['autogen']['date_range_end']) ? $capture_utility['data']['autogen']['date_range_end'] : NULL;
        AggregateBatchController::initializeBatchJob($wpa, $date_range, $start, $end, TRUE);
        break;
      }
    }
  }

}
