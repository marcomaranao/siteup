/**
 * @file
 * Provides chart.js validation functionality for performance budget module.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.performanceBudget = Drupal.performanceBudget || {};
  Drupal.performanceBudget.chartJsValidation = Drupal.performanceBudget.chartJsValidation || {};

  /**
   * Attaches the necessary chartjs functionality just once.
   */
  Drupal.behaviors.performanceBudgetChartJsValidation = {
    attach: function attach(context) {
      if (!Drupal.performanceBudget.chartJsValidation.isAttached) {
        Drupal.performanceBudget.chartJsValidation.validateTextArea = document.querySelector('#edit-data-chartjs-option,#edit-pb-wpt-capture-defaults-chartjs-option');
        Drupal.performanceBudget.chartJsValidation.chartContainer = document.getElementById('wpt_kpi_chart_preview');
        Drupal.performanceBudget.chartJsValidation.editButton = document.getElementById('edit-submit');
        Drupal.performanceBudget.chartJsValidation.validateTextArea.addEventListener('keyup', function () { Drupal.performanceBudget.chartJsValidation.validateJavascriptObject(); }, false);
        Drupal.performanceBudget.chartJsValidation.isAttached = true;

        // Attach reset zoom click handler.
        Drupal.performanceBudget.chartJsValidation.chartContainer.addEventListener('click', Drupal.performanceBudget.chartJsValidation.resetZoom, false);

        // Only draw on load, if not on global settings page.
        if (Drupal.performanceBudget.chartJsValidation.validateTextArea.id !== 'edit-pb-wpt-capture-defaults-chartjs-option') {
          Drupal.performanceBudget.chartJsValidation.validateJavascriptObject();
        }
      }
    }
  };

  /**
   * Sample datasets for chart preview.
   */
  Drupal.performanceBudget.chartJsValidation.getSampleDatasets = function() {
    return [
      {
        data: [
          { x: 1565784000000, y: 6.1 },
          { x: 1565870400000, y: 6.3 },
          { x: 1565956800000, y: 4.1 },
          { x: 1566043200000, y: 3.9 },
          { x: 1566129600000, y: 4.0 },
          { x: 1566216000000, y: 2.9 },
        ],
        label: 'Average - First View - Load Time',
        type: 'line',
        borderColor: 'rgba(230, 25, 75, 0.75)',
        backgroundColor: 'rgba(230, 25, 75, 0.75)',
        fill: false,
        lineTension: 0,
        borderWidth: 1.5,
        pointRadius: 0,
        trendlineLinear: {
          style: 'rgba(230, 25, 75, 0.5)',
          lineStyle: 'dotted',
          width: 2
        }
      },
      {
        data: [
          { x: 1565784000000, y: 2.1 },
          { x: 1565870400000, y: 2.3 },
          { x: 1565956800000, y: 1.9 },
          { x: 1566043200000, y: 2.7 },
          { x: 1566129600000, y: 1.1 },
          { x: 1566216000000, y: 1.4 },
        ],
        label: 'Average - Repeat View - Load Time',
        borderColor: 'rgba(60, 180, 75, 0.75)',
        backgroundColor: 'rgba(60, 180, 75, 0.75)',
        fill: false,
        lineTension: 0,
        borderWidth: 1.5,
        pointRadius: 0,
        trendlineLinear: {
          style: 'rgba(60, 180, 75, 0.5)',
          lineStyle: 'dotted',
          width: 2
        }
      },
    ];
  }

  /**
   * Resets zoom-factor on chart if holding alt/option key.
   *
   * @return boolean
   *   Indicates whether or not the zoom was reset.
   */
  Drupal.performanceBudget.chartJsValidation.resetZoom = function (event) {
    if (event && event.altKey) {
      Drupal.performanceBudget.chartJsValidation.chart.resetZoom();
      return true;
    }
    return false;
  };

  /**
   * Validates the specified javascript object.
   */
  Drupal.performanceBudget.chartJsValidation.validateJavascriptObject = function () {
    try {
      // Attempt to draw chart.
      var ctx = Drupal.performanceBudget.chartJsValidation.chartContainer.getContext('2d');
      var group = 'Key Performance Indicators';
      var url = 'https://www.drupal.org';
      var options = eval('(' + Drupal.performanceBudget.chartJsValidation.validateTextArea.value + ')');
      var datasets = Drupal.performanceBudget.chartJsValidation.getSampleDatasets();
      Drupal.performanceBudget.chartJsValidation.chart = new Chart(ctx, {
        type: 'line',
        data: { datasets: datasets },
        options: options,
      });

      // If we've made it this far, we were successful.
      Drupal.performanceBudget.chartJsValidation.chartContainer.classList.remove('pb-chartContainer-disabled');
      Drupal.performanceBudget.chartJsValidation.validateTextArea.setCustomValidity('');
    }
    catch (e) {
      // Disable save functionality on error.
      Drupal.performanceBudget.chartJsValidation.chartContainer.classList.add('pb-chartContainer-disabled');
      Drupal.performanceBudget.chartJsValidation.validateTextArea.setCustomValidity(Drupal.t('Javascript object is invalid.'));
    }
  };

})(Drupal, drupalSettings);
