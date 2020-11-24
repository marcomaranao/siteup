/**
 * @file
 * Provides chart.js functionality for performance budget module.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.performanceBudget = Drupal.performanceBudget || {};
  Drupal.performanceBudget.chartJs = Drupal.performanceBudget.chartJs || {};

  /**
   * Attaches the necessary chartjs functionality just once.
   */
  Drupal.behaviors.performanceBudgetChartJs = {
    attach: function attach(context) {
      if (!Drupal.performanceBudget.chartJs.isAttached) {
        Drupal.performanceBudget.chartJs.createCanvases();
        Drupal.performanceBudget.chartJs.drawKpis();
        Drupal.performanceBudget.chartJs.isAttached = true;
      }
    }
  };

  /**
   * Container for all charts.
   */
  Drupal.performanceBudget.chartJs.charts = [];

  /**
   * Distinct colors for use with charts.
   *
   * @see https://sashat.me/2017/01/11/list-of-20-simple-distinct-colors/
   */
  Drupal.performanceBudget.chartJs.colors = [
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
    'rgba(255, 255, 255, 0.75)',
    'rgba(0, 0, 0, 0.75)',
  ];

  /**
   * Indicates if functionality has been attached to DOM or not.
   */
  Drupal.performanceBudget.chartJs.isAttached = false;

  /**
   * Assembles datasets based on supplied data.
   */
  Drupal.performanceBudget.chartJs.assembleDatasets = function(data) {
    var datasets = [];
    for (var label in data) {
      var dataset = Drupal.performanceBudget.chartJs.getDataset(data, label);

      if (++Drupal.performanceBudget.chartJs.colorIdx >= Drupal.performanceBudget.chartJs.colors.length) {
        Drupal.performanceBudget.chartJs.colorIdx = 0;
      }

      datasets.push(dataset);
    }
    return datasets;
  }

  /**
   * Creates all necessary canvas elements.
   */
  Drupal.performanceBudget.chartJs.createCanvases = function() {
    var chartIdx = 0;
    var wrapperEl = document.getElementById('wpt_kpi_chart');
    for (var group in drupalSettings.pbWptResults) {
      for (var url in drupalSettings.pbWptResults[group]) {
        wrapperEl.innerHTML = wrapperEl.innerHTML += '<canvas id="wpt_kpi_chart_' + chartIdx + '"></canvas>';
        chartIdx++;
      }
    }
  };

  /**
   * Draws all charts based on drupalSettings data.
   */
  Drupal.performanceBudget.chartJs.drawKpis = function() {
    var chartIdx = 0;
    var labels = [];
    for (var group in drupalSettings.pbWptResults) {
      for (var url in drupalSettings.pbWptResults[group]) {
        Drupal.performanceBudget.chartJs.colorIdx = 0;
        var chartId = 'wpt_kpi_chart_' + chartIdx;
        var el = document.getElementById(chartId);
        var ctx = el.getContext('2d');

        // Attach reset zoom click handler.
        el.setAttribute('data-chart-idx', chartIdx);
        el.addEventListener('click', Drupal.performanceBudget.chartJs.clickHandler, false);

        // Transforms the data for chart.js use.
        var data = Drupal.performanceBudget.chartJs.transformUrlData(drupalSettings.pbWptResults[group][url]);
        var datasets = Drupal.performanceBudget.chartJs.assembleDatasets(data);

        // Add chart and increment count.
        Drupal.performanceBudget.chartJs.charts[chartIdx++] = new Chart(ctx, {
          type: 'line',
          data: { datasets: datasets },
          options: Drupal.performanceBudget.chartJs.getChartOptions(group, url),
        });
      }
    }
  };

  /**
   * Retrieves chart options based on specified group and url.
   */
  Drupal.performanceBudget.chartJs.getChartOptions = function(group, url) {
    if (typeof wptRetrieveChartJsOptions !== 'undefined') {
      return wptRetrieveChartJsOptions(group, url);
    }
    return {
      title: {
        display: true,
        text: Drupal.t('{@group}: @url', {'@group': group, '@url': url}),
      },
      scales: {
        xAxes: [{
          type: 'time',
          distribution: 'linear',
          ticks: {
            source: 'data',
            autoSkip: true,
          },
          scaleLabel: {
            display: true,
            labelString: 'Date/Time'
          },
          time: {
            parser: 'MM/DD/YYYY HH:mm',
            tooltipFormat: 'll - h:mma'
          },
        }],
        yAxes: [{
          scaleLabel: {
            display: true,
            labelString: 'Time (seconds)'
          },
        }],
      },
      plugins: {
        zoom: {
          zoom: {
            enabled: true,
            drag: true,
            mode: 'x',
            speed: 0.05,
          },
        },
      },
    };
  };


  /**
   * Retrieves the dataset using the specified data, label and color index.
   */
  Drupal.performanceBudget.chartJs.getDataset = function(data, label) {
    var lineColor = Drupal.performanceBudget.chartJs.colors[Drupal.performanceBudget.chartJs.colorIdx];
    var trendColor = Drupal.performanceBudget.chartJs.colors[Drupal.performanceBudget.chartJs.colorIdx].replace(', 0.75)', ', 0.5)');
    return {
      borderColor: lineColor,
      backgroundColor: lineColor,
      label: label,
      data: data[label],
      type: 'line',
      fill: false,
      lineTension: 0,
      borderWidth: 1.5,
      pointRadius: .5,
      hitRadius: 3,
      trendlineLinear: {
        style: trendColor,
        lineStyle: "dotted",
        width: 2,
      },
    };
  };

  /**
   * Handles chart click events.
   */
  Drupal.performanceBudget.chartJs.clickHandler = function (event) {
    if (event && event.altKey) {
      Drupal.performanceBudget.chartJs.resetZoom(event);
      return true;
    }
    return Drupal.performanceBudget.chartJs.itemClick(event);
  }

  /**
   * Resets zoom-factor on chart if holding alt/option key.
   */
  Drupal.performanceBudget.chartJs.resetZoom = function (event) {
    var chartIdx = event.target.getAttribute('data-chart-idx');
    Drupal.performanceBudget.chartJs.charts[chartIdx].resetZoom();
  }

  /**
   * Detects chart clicks and opens a window if a user "hits" a chart point.
   */
  Drupal.performanceBudget.chartJs.itemClick = function (event) {
    var chartIdx = event.target.getAttribute('data-chart-idx');
    var chart = Drupal.performanceBudget.chartJs.charts[chartIdx];

    var clickElement = chart.getElementAtEvent(event)[0];
    if (clickElement) {
      var vid = chart.data.datasets[clickElement._datasetIndex].data[clickElement._index].vid;
      var url = drupalSettings.wpaRunsBaseUrl.replace('_VID_', vid);
      var newWindow = window.open(url, '_blank');
      newWindow.opener = null;
      return true;
    }
    return false;
  };

  /**
   * Transforms the specified url data into a format chart.js can use.
   */
  Drupal.performanceBudget.chartJs.transformUrlData = function (urlData) {
    var data = {};
    for (var timestamp in urlData) {
      for (var label in urlData[timestamp]) {
        if (typeof data[label] == "undefined") {
          data[label] = [];
        }
        for (var vid in urlData[timestamp][label]) {
          var dataset = {
            x: timestamp * 1000,
            y: urlData[timestamp][label][vid],
            vid: vid,
          }
          data[label].push(dataset);
        }
      }
    }
    return data;
  }

})(Drupal, drupalSettings);
