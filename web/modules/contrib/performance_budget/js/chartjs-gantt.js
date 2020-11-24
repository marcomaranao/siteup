/**
 * @file
 * Provides chart.js functionality for performance budget module.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.performanceBudget = Drupal.performanceBudget || {};
  Drupal.performanceBudget.chartJsGantt = Drupal.performanceBudget.chartJsGantt || {};

  /**
   * Attaches the necessary chartjs functionality just once.
   */
  Drupal.behaviors.performanceBudgetChartJsGantt = {
    attach: function attach(context) {
      if (!Drupal.performanceBudget.chartJsGantt.isAttached) {
        Drupal.performanceBudget.chartJsGantt.drawRequestGanttCharts();
        Drupal.performanceBudget.chartJsGantt.isAttached = true;
      }
    }
  };

  /**
   * Container for all charts.
   */
  Drupal.performanceBudget.chartJsGantt.charts = [];

  /**
   * Distinct colors for use with charts.
   *
   * @see https://sashat.me/2017/01/11/list-of-20-simple-distinct-colors/
   */
  Drupal.performanceBudget.chartJsGantt.colors = [
    'rgba(230, 25, 75, 0.75)',
    'rgba(60, 180, 75, 0.75)',
    'rgba(0, 130, 200, 0.75)',
    'rgba(245, 130, 48, 0.75)',
    'rgba(70, 240, 240, 0.75)',
    'rgba(255, 225, 25, 0.75)',
    'rgba(240, 50, 230, 0.75)',
    'rgba(250, 190, 190, 0.75)',
    'rgba(0, 128, 128, 0.75)',
    'rgba(230, 190, 255, 0.75)',
    'rgba(170, 110, 40, 0.75)',
    'rgba(255, 250, 200, 0.75)',
    'rgba(128, 0, 0, 0.75)',
    'rgba(170, 255, 195, 0.75)',
    'rgba(0, 0, 128, 0.75)',
    'rgba(128, 128, 128, 0.75)',
    'rgba(0, 0, 0, 0.75)',
  ];

  /**
   * Indicates if functionality has been attached to DOM or not.
   */
  Drupal.performanceBudget.chartJsGantt.isAttached = false;

  /**
   * Retrieves the datasets for the gantt charts.
   */
  Drupal.performanceBudget.chartJsGantt.getDatasets = function(view) {
    var data = [];
    var factor = Drupal.performanceBudget.chartJsGantt.ganttChartScaleFactor;
    var y = Object.keys(drupalSettings.pbWptRequests[view]).length * factor;
    for (var url in drupalSettings.pbWptRequests[view]) {
      var metrics = [
        'dns',
        'connect',
        'ssl',
        'load',
        'ttfb',
        'download',
      ];
      for (var i=0 ; i < metrics.length; i++) {
        var color = Drupal.performanceBudget.chartJsGantt.colors[i];
        var start = metrics[i] + '_start';
        var end = metrics[i] + '_end';
        var ms = metrics[i] + '_ms';
        if (drupalSettings.pbWptRequests[view][url][start] >= 0 && drupalSettings.pbWptRequests[view][url][end] >= 0) {
          var dataset = {
            label: Drupal.t('@metric (@times from @starts to @ends): @url', {
              '@metric': metrics[i],
              '@time': drupalSettings.pbWptRequests[view][url][ms]/1000,
              '@start': drupalSettings.pbWptRequests[view][url][start]/1000,
              '@end': drupalSettings.pbWptRequests[view][url][end]/1000,
              '@url': url,
            }),
            backgroundColor: color,
            height: 5,
            borderWidth: 0,
            pointRadius: 0,
            padding: 10,
            data: [
              {
                x: {
                  from: drupalSettings.pbWptRequests[view][url][start]/1000,
                  to: drupalSettings.pbWptRequests[view][url][end]/1000,
                },
                y: y,
              },
            ],
          };
          data.push(dataset);
        }
      }

      y -= factor;
    }
    return data;
  }

  Drupal.performanceBudget.chartJsGantt.ganttChartScaleFactor = 6;

  /**
   * Draws the request gantt chart.
   */
  Drupal.performanceBudget.chartJsGantt.drawRequestGanttCharts = function() {
    for (var chartIdx in drupalSettings.pbWptRequests) {
      var chartId = 'wpt_kpi_chart_' + chartIdx;
      var el = document.getElementById(chartId);
      var ctx = el.getContext('2d');
      var factor = Drupal.performanceBudget.chartJsGantt.ganttChartScaleFactor;
      var y = Object.keys(drupalSettings.pbWptRequests[chartIdx]).length * factor;
      el.height = y;
      Drupal.performanceBudget.chartJsGantt.charts[chartIdx] = new Chart(ctx, {
        type: 'gantt',
        data: {
          datasets: Drupal.performanceBudget.chartJsGantt.getDatasets(chartIdx),
        },
        options: Drupal.performanceBudget.chartJsGantt.getChartOptions(chartIdx),
      });
    }
  }

  /**
   * Retrieves chart options based on specified group and url.
   */
  Drupal.performanceBudget.chartJsGantt.getChartOptions = function(view, group, url) {
    if (typeof wptRetrieveChartJsOptions !== 'undefined') {
      return wptRetrieveChartJsOptions(group, url);
    }
    var factor = Drupal.performanceBudget.chartJsGantt.ganttChartScaleFactor;
    var y = Object.keys(drupalSettings.pbWptRequests[view]).length * factor;
    return {
      responsive: true,
      maintainAspectRatio: true,
      legend: {
        display: false,
      },
      tooltips: {
        callbacks: {
          labelColor: function(tooltipItem, chart) {
            return {
              borderColor: chart.data.datasets[tooltipItem.datasetIndex].backgroundColor,
              backgroundColor: chart.data.datasets[tooltipItem.datasetIndex].backgroundColor,
            };
          },
          label: function(tooltipItem, chart) {
            var stringLimit = 128;
            var label = chart.datasets[tooltipItem.datasetIndex].label;
            if (label.length > stringLimit) {
              label = chart.datasets[tooltipItem.datasetIndex].label.substring(0, stringLimit) + '...';
            }
            return label;
          },
          labelTextColor: function(tooltipItem, chart) {
            return '#ffffff';
          },
        },
      },
      scales: {
        xAxes: [{
          scaleLabel: {
            display: true,
            labelString: Drupal.t('Time (seconds)'),
          },
          position: 'bottom',
          ticks : {
            beginAtzero: true,
            stepSize: 0.5
          }
        }],
        yAxes : [{
          gridLines: {
            offsetGridLines: true,
          },
          ticks : {
            display: false,
            callback: function(value, index, values) {
              return index;
            },
            stepSize: factor,
            max: y + factor,
          },
        }],
      },
    };
  };
})(Drupal, drupalSettings);
