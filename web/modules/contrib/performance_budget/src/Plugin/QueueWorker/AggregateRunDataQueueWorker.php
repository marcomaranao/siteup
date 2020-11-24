<?php

namespace Drupal\performance_budget\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides functionality for processing the aggregator run data queue.
 *
 * @QueueWorker(
 *   id = "pb_aggregate_runs_queue_worker",
 *   title = @Translation("Performance Budget aggregator run data queue"),
 * )
 */
class AggregateRunDataQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new AggregateRunDataQueueWorker.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LockBackendInterface $lock, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->lock = $lock;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('lock'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($this->lock->acquire($data['queue_name'])) {
      $run_storage = $this->entityTypeManager->getStorage('web_page_archive_run');
      $state_array = $this->state->get($data['queue_name']);
      $run = $run_storage->loadRevision($data['vid']);
      foreach ($run->getCapturedArray() as $capture) {
        $unserialized = unserialize($capture->getString());
        if (get_class($unserialized['capture_response']) !== '__PHP_Incomplete_Class') {
          $kpis = $unserialized['capture_response']->parseKpis($run);
          $url = $unserialized['capture_response']->getCaptureUrl();
          $timestamp = $run->getRevisionCreationTime();
          foreach ($kpis as $group => $group_details) {
            if ($group == '#requests') {
              foreach ($group_details as $request_url => $request_details) {
                $parsed = parse_url($request_url);
                $host_url = "{$parsed['scheme']}://{$parsed['host']}";
                foreach ($request_details as $view => $view_details) {
                  if (!isset($state_array[$group][$view][$host_url])) {
                    $state_array[$group][$view][$host_url] = [
                      'ct' => 0,
                      'all_ms' => 0,
                    ];
                  }
                  $state_array[$group][$view][$host_url]['ct']++;
                  $state_array[$group][$view][$host_url]['all_ms'] += $view_details['all_ms'];
                  if (!isset($state_array[$group][$view][$host_url]['first']) || $timestamp < $state_array[$group][$view][$host_url]['first']) {
                    $state_array[$group][$view][$host_url]['first'] = $timestamp;
                  }
                  if (!isset($state_array[$group][$view][$host_url]['last']) || $timestamp > $state_array[$group][$view][$host_url]['last']) {
                    $state_array[$group][$view][$host_url]['last'] = $timestamp;
                  }
                }
              }
            }
            elseif ($group != '#threshold_violations') {
              foreach ($group_details as $average => $average_details) {
                foreach ($average_details as $view => $view_details) {
                  foreach ($view_details as $kpi => $value) {
                    $key = "{$average} - {$view} - {$kpi}";
                    $state_array[$group][$url][$timestamp][$key][$data['vid']] = floatval($value);
                  }
                }
              }
            }
          }
        }
      }
      $this->state->set($data['queue_name'], $state_array);
      $this->lock->release($data['queue_name']);
    }
    return TRUE;
  }

}
