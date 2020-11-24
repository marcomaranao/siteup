<?php

/**
 * @file
 * Post update functions for performance_budget module.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\performance_budget\Plugin\CaptureResponse\WebPageTestCaptureResponse;
use Drupal\web_page_archive\Plugin\CaptureResponse\UriCaptureResponse;

/**
 * Issue 3072499: Convert UriCaptureResponse to WebPageTestCaptureResponse.
 */
function performance_budget_post_update_3072499_convert_capture_responses(&$sandbox) {
  if (!isset($sandbox['total_entities'])) {
    $sandbox['total_entities'] = \Drupal::entityQuery('web_page_archive_run')
      ->condition('capture_utilities', '%pb_wpt_capture%', 'LIKE')
      ->count()
      ->execute();
    // If there are no matching entities, go ahead and exit out of here.
    if ($sandbox['total_entities'] == 0) {
      return;
    }
    $sandbox['progress_entities'] = 0;
    $sandbox['progress_revisions'] = 0;
  }

  // Sandbox limits.
  $entity_limit = 1;
  $revision_limit = 25;

  // Process 1 run entity at a time, as each entity could have any # of runs.
  $query = \Drupal::entityQuery('web_page_archive_run');
  $query->condition('capture_utilities', '%pb_wpt_capture%', 'LIKE');
  $query->range($sandbox['progress_entities'], $entity_limit);
  $entity_ids = $query->execute();

  // Load run entities.
  $run_storage = \Drupal::entityTypeManager()->getStorage('web_page_archive_run');
  $runs = $run_storage->loadMultiple($entity_ids);
  foreach ($runs as $run) {
    // Load next set of revisions.
    $vids = $run_storage->revisionIds($run);
    $vids = array_slice($vids, $sandbox['progress_revisions'], $revision_limit);
    $revisions = $run_storage->loadMultipleRevisions($vids);

    // If there are no more revisions, then we should proceed to the next
    // entity. Otherwise, process this revision.
    if (empty($revisions)) {
      $sandbox['progress_revisions'] = 0;
      $sandbox['progress_entities']++;
      \Drupal::messenger()->addStatus("{$sandbox['progress_entities']}/{$sandbox['total_entities']} entities processed.");
    }
    else {
      foreach ($revisions as $revision) {
        // Unserialize the capture response. If it's UriCaptureResponse, create
        // a new WebPageTestCaptureResponse instance instead.
        $unserialized = unserialize($revision->get('field_captures')->getString());
        if (get_class($unserialized['capture_response']) == UriCaptureResponse::class) {
          $old = $unserialized['capture_response'];
          $unserialized['capture_response'] = new WebPageTestCaptureResponse($old->getContent(), $old->getCaptureUrl());
          $revision->set('field_captures', serialize($unserialized));
          $revision->save();
        }
        $sandbox['progress_revisions']++;
      }
      \Drupal::messenger()->addStatus("[{$sandbox['progress_revisions']} revisions processed]");
    }
  }

  $sandbox['#finished'] = ($sandbox['progress_entities'] / $sandbox['total_entities']);
}

/**
 * Issue 3072581: Installs default KPI group.
 */
function performance_budget_post_update_3072581_install_default_kpi_group() {
  _performance_budget_import_configs(['performance_budget.wpt_kpi.standard_page_load_kpis']);
}

/**
 * Issue 3072581: Installs default KPI group.
 */
function performance_budget_post_update_3072581_attach_kpi_group_existing_jobs() {
  _performance_budget_wpt_capture_update_data_fields([
    'kpi_groups' => [['target_id' => 'standard_page_load_kpis']],
  ]);
}

/**
 * Helper function to reimport a config file.
 */
function _performance_budget_import_configs(array $configs) {
  $path = drupal_get_path('module', 'performance_budget') . '/config/install';
  $source = new FileStorage($path);
  $config_storage = \Drupal::service('config.storage');
  foreach ($configs as $config) {
    $config_storage->write($config, $source->read($config));
  }
}

/**
 * Helper function for updating the data array values in wpt entities.
 */
function _performance_budget_wpt_capture_update_data_fields(array $data = []) {
  $config_factory = \Drupal::configFactory();
  $config_prefix = 'web_page_archive.web_page_archive';
  $keys = $config_factory->listAll($config_prefix);

  foreach ($keys as $key) {
    $wpa_config = $config_factory->getEditable($key);

    $utilities = $wpa_config->get('capture_utilities');
    $changed = FALSE;

    // Search for pb_wpt_capture utilities, and make the desired change.
    foreach ($utilities as $key => $utility) {
      if ($utilities[$key]['id'] == 'pb_wpt_capture') {
        if (!empty($data)) {
          foreach ($data as $data_key => $data_value) {
            $utilities[$key]['data'][$data_key] = $data_value;
          }
          $changed = TRUE;
        }
      }
    }

    // Update config entity if changed.
    if ($changed) {
      $wpa_config->set('capture_utilities', $utilities);
      $wpa_config->save();
    }
  }
}

/**
 * Issue 2907871: Add optional support for handling api keys via the key module.
 */
function performance_budget_post_update_2907871_support_key_module() {
  $config_factory = \Drupal::configFactory();
  $config_prefix = 'web_page_archive.web_page_archive';
  $keys = $config_factory->listAll($config_prefix);

  foreach ($keys as $key) {
    $wpa_config = $config_factory->getEditable($key);

    $utilities = $wpa_config->get('capture_utilities');
    $changed = FALSE;

    // Search for pb_wpt_capture utilities, and make the desired change.
    foreach ($utilities as $key => $utility) {
      if ($utilities[$key]['id'] == 'pb_wpt_capture') {
        if (!empty($utilities[$key]['data']['wpt_api'])) {
          $api_key = $utilities[$key]['data']['wpt_api'];
          unset($utilities[$key]['data']['wpt_api']);
          $utilities[$key]['data']['api'] = [
            'storage_method' => 'plaintext',
            'key_plaintext' => $api_key,
          ];
          $changed = TRUE;
        }
      }
    }

    // Update config entity if changed.
    if ($changed) {
      $wpa_config->set('capture_utilities', $utilities);
      $wpa_config->save();
    }
  }
}

/**
 * Issue 3156018: Use file schemes.
 */
function performance_budget_post_update_3156018_use_file_schemes(&$sandbox) {
  $storage = \Drupal::entityTypeManager()->getStorage('web_page_archive_run');
  if (!isset($sandbox['list'])) {
    $sandbox['list'] = array_keys($storage->fullRevisionList());
    $sandbox['total'] = count($sandbox['list']);
    // If there are no items, there is nothing left to do here.
    if ($sandbox['total'] === 0) {
      return;
    }
    $sandbox['progress'] = 0;
  }

  // Get next 10 ids.
  $limit = 10;
  $ids = array_splice($sandbox['list'], 0, $limit);

  if (empty($ids)) {
    $sandbox['progress']++;
  }
  else {
    $scheme = \Drupal::config('system.file')->get('default_scheme');
    $scheme_path = "{$scheme}:/";
    $real_path = \Drupal::service('file_system')->realpath("$scheme_path/");

    $revisions = $storage->loadMultipleRevisions($ids);
    foreach ($revisions as $revision) {
      if ($revision->getConfigEntity()->hasCaptureUtilityInstance('pb_wpt_capture')) {
        $captured = $revision->getCapturedArray();
        foreach ($captured as &$captured_row) {
          $serialized = $captured_row->getString();
          $results = unserialize($serialized);
          $file_location = trim($results['capture_response']->getContent());
          $file_location = str_replace($real_path, $scheme_path, $file_location);
          $results['capture_response'] = new WebPageTestCaptureResponse($file_location, $results['capture_response']->getCaptureUrl());
          $captured_row->setValue(serialize($results));
        }
        $revision->save();
      }
      $sandbox['progress']++;
    }
  }
  \Drupal::messenger()->addStatus("{$sandbox['progress']}/{$sandbox['total']} results processed.");

  $sandbox['#finished'] = ($sandbox['progress'] / $sandbox['total']);
}

/**
 * Issue 3086671: Support private instances.
 */
function performance_budget_post_update_3086671_support_private_instances() {
  $config_factory = \Drupal::configFactory();
  $config_prefix = 'web_page_archive.web_page_archive';
  $keys = $config_factory->listAll($config_prefix);

  foreach ($keys as $key) {
    $wpa_config = $config_factory->getEditable($key);

    $utilities = $wpa_config->get('capture_utilities');
    $changed = FALSE;

    // Search for pb_wpt_capture utilities, and set default api hostname.
    foreach ($utilities as $key => $utility) {
      if ($utilities[$key]['id'] == 'pb_wpt_capture') {
        if (empty($utilities[$key]['data']['api']['hostname'])) {
          $utilities[$key]['data']['api']['hostname'] = 'https://www.webpagetest.org';
          $changed = TRUE;
        }
      }
    }

    // Update config entity if changed.
    if ($changed) {
      $wpa_config->set('capture_utilities', $utilities);
      $wpa_config->save();
    }
  }
}
