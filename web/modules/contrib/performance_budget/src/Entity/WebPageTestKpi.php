<?php

namespace Drupal\performance_budget\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Web Page Test KPI Group entity.
 *
 * @ConfigEntityType(
 *   id = "wpt_kpi",
 *   label = @Translation("Web Page Test KPI Group"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\performance_budget\Entity\WebPageTestKpiListBuilder",
 *     "form" = {
 *       "add" = "Drupal\performance_budget\Form\WebPageTestKpiForm",
 *       "edit" = "Drupal\performance_budget\Form\WebPageTestKpiForm",
 *       "delete" = "Drupal\performance_budget\Form\WebPageTestKpiDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "wpt_kpi",
 *   admin_permission = "administer web page archive",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "kpis" = "kpis"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/web-page-archive/wpt-kpis/{wpt_kpi}",
 *     "add-form" = "/admin/config/system/web-page-archive/wpt-kpis/add",
 *     "edit-form" = "/admin/config/system/web-page-archive/wpt-kpis/{wpt_kpi}/edit",
 *     "delete-form" = "/admin/config/system/web-page-archive/wpt-kpis/{wpt_kpi}/delete",
 *     "collection" = "/admin/config/system/web-page-archive/wpt-kpis"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "kpis"
 *   }
 * )
 */
class WebPageTestKpi extends ConfigEntityBase implements WebPageTestKpiInterface {

  /**
   * The Web Page Test KPI Group ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Web Page Test KPI Group label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Web Page Test KPI Group kpis.
   *
   * @var array
   */
  protected $kpis;

  /**
   * {@inheritdoc}
   */
  public function getKpis() {
    $kpis = [];
    if (!empty($this->kpis)) {
      foreach ($this->kpis as $average => $average_details) {
        foreach ($average_details as $view => $view_details) {
          foreach ($view_details as $kpi => $is_set) {
            if ($is_set) {
              $kpis[$average][$view][$kpi] = $is_set;
            }
          }
        }
      }
    }
    return $kpis;
  }

}
