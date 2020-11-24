<?php

namespace Drupal\performance_budget\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\performance_budget\Plugin\CaptureUtility\WebPageTestCaptureUtility;
use Drupal\views\Plugin\views\field\Custom;
use Drupal\views\ResultRow;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * A handler to provide a field that for fetching web page test results.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wpt_fetch_result")
 */
class WebPageTestFetchResult extends Custom {

  /**
   * Prerenders the canonical view to add fetch button.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   */
  public static function preRenderView(ViewExecutable $view) {
    if ($view->id() === 'web_page_archive_canonical' && !empty($view->field['nothing'])) {
      $configuration = ['title' => 'wpt_fetch_result'];
      $new_field = Views::pluginManager('field')->createInstance('wpt_fetch_result', $configuration);
      if ($new_field) {
        $options = [
          'id' => 'wpt_fetch_result',
          'exclude' => TRUE,
        ];
        $display_handler = $view->displayHandlers->get('canonical_embed');
        $new_field->init($view, $display_handler, $options);

        // New field must be added before dropbutton, so we just push it to the
        // very top of the list.
        $view->field = ['wpt_fetch_result' => $new_field] + $view->field;
        $view->field['dropbutton']->options['fields']['wpt_fetch_result'] = 'wpt_fetch_result';
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $run = $values->_entity;
    $wpa = $run->getConfigEntity();
    if ($wpa->hasCaptureUtilityInstance('pb_wpt_capture') && !$wpa->getUseCron() && $wpa->getUrlType() == 'url') {
      $urls = $wpa->getUrlList();
      foreach ($urls as $url) {
        if (WebPageTestCaptureUtility::getPendingTestId($url, $run->getRunUuid())) {
          return Link::createFromRoute($this->t('Fetch WebPageTest Results'), 'entity.web_page_archive_run.wpt_fetch_results', ['web_page_archive_run_revision' => $run->getRevisionId()])->toRenderable();
        }
      }
    }

    return parent::render($values);
  }

}
