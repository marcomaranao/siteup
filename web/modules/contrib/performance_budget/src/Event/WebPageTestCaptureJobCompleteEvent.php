<?php

namespace Drupal\performance_budget\Event;

use Drupal\web_page_archive\Event\CaptureJobCompleteEvent;

/**
 * Event that is fired when a capture job is completely finished.
 */
class WebPageTestCaptureJobCompleteEvent extends CaptureJobCompleteEvent {

  const EVENT_NAME = 'pb_wpt_capture_complete';

}
