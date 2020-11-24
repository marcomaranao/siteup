<?php

namespace Drupal\performance_budget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\performance_budget\Event\AggregateJobCompleteEvent;
use Drupal\web_page_archive\Entity\WebPageArchiveInterface;

/**
 * Class AggregateBatchController.
 */
class AggregateBatchController extends ControllerBase {

  /**
   * Retrieves a queue for the specified WPA entity.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $wpa
   *   The web page archive config entity.
   * @param bool $empty
   *   Indicates whether or not we should purge the queue.
   */
  public static function getQueue(WebPageArchiveInterface $wpa, $empty = FALSE) {
    $queue_name = static::getQueueName($wpa);
    $queue = \Drupal::service('queue')->get($queue_name);
    if ($empty) {
      $queue->deleteQueue();
      $queue->createQueue();
      \Drupal::state()->set($queue_name, []);
    }
    return $queue;
  }

  /**
   * Retrieve the name of a queue for the specified WPA entity.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $wpa
   *   The web page archive config entity.
   *
   * @return string
   *   Name of the queue.
   */
  public static function getQueueName(WebPageArchiveInterface $wpa) {
    $run = $wpa->getRunEntity();
    return "pb_aggregate_runs:{$run->uuid()}";
  }

  /**
   * Enqueues the job run data for the specified config entity.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $wpa
   *   The web page archive config entity.
   * @param int $start_time
   *   Starting timestamp.
   * @param int $end_time
   *   Ending timestamp.
   */
  public static function enqueueJobRunData(WebPageArchiveInterface $wpa, $start_time, $end_time) {
    $run_storage = \Drupal::entityTypeManager()->getStorage('web_page_archive_run');

    // Get a brand new queue for this particular run.
    $queue_name = static::getQueueName($wpa);
    $queue = static::getQueue($wpa, TRUE);

    $run = $wpa->getRunEntity();
    if (isset($start_time) && isset($end_time)) {
      $revisions = $run_storage->revisionIdsInRange($run, $start_time, $end_time);
    }
    else {
      $revisions = $run_storage->revisionIds($run);
    }

    foreach ($revisions as $vid) {
      $item = [
        'queue_name' => $queue_name,
        'vid' => $vid,
      ];
      $queue->createItem($item);
    }
  }

  /**
   * Adds up to $items_to_process number of items from the queue to a batch.
   *
   * If $items_to_process < 0 attempt to add entire queue to batch.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $web_page_archive
   *   The web page archive config entity.
   * @param int $items_to_process
   *   The number of items to process.
   */
  public static function setBatch(WebPageArchiveInterface $web_page_archive, $items_to_process = -1) {
    $queue = static::getQueue($web_page_archive);
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('pb_aggregate_runs_queue_worker');

    // Create capture job batch.
    $batch = [
      'title' => \t('Process all web page test data with batch'),
      'operations' => [],
      'finished' => [static::class, 'batchFinished'],
    ];

    // If negative, or if count is too high, set count to queue size.
    if ($items_to_process < 0 || $items_to_process > $queue->numberOfItems()) {
      $items_to_process = $queue->numberOfItems();
    }

    // Create batch operations.
    for ($i = 0; $i < $items_to_process; $i++) {
      $batch['operations'][] = [
        [static::class, 'batchProcess'],
        [$web_page_archive],
      ];
    }

    // Adds the batch sets.
    batch_set($batch);
  }

  /**
   * Common batch processing callback for all operations.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $web_page_archive
   *   The web page archive config entity.
   * @param mixed $context
   *   Contains context for the current batch job.
   */
  public static function batchProcess(WebPageArchiveInterface $web_page_archive, &$context = NULL) {
    if (empty($context['results']['entity'])) {
      $context['results']['entity'] = $web_page_archive;
    }

    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('pb_aggregate_runs_queue_worker');
    $queue = static::getQueue($web_page_archive);

    if ($item = $queue->claimItem()) {
      try {
        $processed = $queue_worker->processItem($item->data);
        if (!isset($processed)) {
          throw new RequeueException(t('Still Running'));
        }
        $queue->deleteItem($item);
        return TRUE;
      }
      catch (RequeueException $e) {
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        watchdog_exception($e);
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and remove it from
        // the queue to prevent queues from getting stuck.
        $queue->deleteItem($item);
        watchdog_exception('performance_budget', $e);
      }

      return FALSE;
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Indicates whether or not the job succeeeded.
   * @param array $results
   *   Contains results from the batch job.
   * @param array $operations
   *   List of operations run on the batch.
   */
  public static function batchFinished($success, array $results, array $operations) {
    if ($success) {
      // Dispatch an event.
      if (isset($results['entity'])) {
        $event = new AggregateJobCompleteEvent($results['entity']);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch($event::EVENT_NAME, $event);
      }
    }
    else {
      $error_operation = reset($operations);
      $values = [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      ];
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments : @args', $values));
    }
  }

  /**
   * Triggers a new batch job.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $web_page_archive
   *   Web page archive config entity.
   * @param string $date_range
   *   String representing the desired date range.
   * @param string $date_range_start
   *   String representing the desired start of the date range (if applicable).
   * @param string $date_range_end
   *   String representing the desired end of the date range (if applicable).
   * @param string $process_queue
   *   Indicates whether or not to explicitly process the queue.
   */
  public static function initializeBatchJob(WebPageArchiveInterface $web_page_archive, $date_range, $date_range_start = '', $date_range_end = '', $process_queue = FALSE) {
    $now = \Drupal::service('datetime.time')->getRequestTime();

    switch ($date_range) {
      case 'custom':
        if (empty($date_range_start) || empty($date_range_end)) {
          throw new \Exception('Date range start/end are required');
        }
        $start_time = strtotime("{$date_range_start} 00:00:00");
        $end_time = strtotime("{$date_range_end} 23:59:59");
        break;

      case 'week':
        $start_time = strtotime('-1 week');
        $end_time = $now;
        break;

      case 'month':
        $start_time = strtotime('-1 month');
        $end_time = $now;
        break;

      case '3month':
        $start_time = strtotime('-3 month');
        $end_time = $now;
        break;

      case '6month':
        $start_time = strtotime('-6 month');
        $end_time = $now;
        break;

      case 'year':
        $start_time = strtotime('-1 year');
        $end_time = $now;
        break;

      case '2year':
        $start_time = strtotime('-2 year');
        $end_time = $now;

      case 'all':
      default:
        $start_time = NULL;
        $end_time = NULL;
    }

    static::enqueueJobRunData($web_page_archive, $start_time, $end_time);
    static::setBatch($web_page_archive);

    // If triggered via form, the queue will automatically be processed. If
    // this is triggered as the result of a job completion, however, we will
    // need to process the queue, as it won't happen automatically.
    if ($process_queue) {
      static::batchProcessFullQueue($web_page_archive);
    }
  }

  /**
   * Processes the full batch queue.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $web_page_archive
   *   Web page archive entity object.
   */
  protected static function batchProcessFullQueue(WebPageArchiveInterface $web_page_archive) {
    $queue = static::getQueue($web_page_archive);
    $context = [];
    for ($i = 0; $i < $queue->numberOfItems(); $i++) {
      static::batchProcess($web_page_archive, $context);
    }
    $results = !empty($context['results']) ? $context['results'] : [];
    static::batchFinished(TRUE, $results, []);
  }

}
