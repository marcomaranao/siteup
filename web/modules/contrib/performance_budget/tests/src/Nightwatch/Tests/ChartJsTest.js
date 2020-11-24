/**
 * @file
 * Provides javascript test coverage for chart.js functionality.
 */

const browserTimeout = 5000;
const chartDrawTime = 1000;

module.exports = {
  '@tags': ['performance_budget'],
  before: function(browser) {
    browser.drupalInstall({
      setupFile: 'modules/contrib/performance_budget/tests/src/TestSite/TestSiteInstallTestScript.php',
    });
  },
  after: function(browser) {
    browser
      .drupalUninstall();
  },
  'Visit a historical report and ensure chart functions as expected': (browser) => {
    browser
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: [
          'administer web page archive',
          'view web page archive results',
        ],
      })
      .drupalLogin({ name: 'user', password: '123' })
      .drupalRelativeURL('/admin/config/system/web-page-archive/jobs/wpt/wpt-history/process')
      .click('input[type=submit]')
      .waitForElementVisible('#wpt_kpi_chart_0', browserTimeout)
      // Assert attachment.
      .execute(function() {
        return Drupal.performanceBudget.chartJs.isAttached;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, true);
      })
      // Assert chart title. This confirms chart actually loaded.
      .execute(function () {
        return Drupal.performanceBudget.chartJs.charts[0].titleBlock.options.text;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, '{Standard Page Load KPIs}: https://www.drupal.org');
      })
      // Test getChartOptions().
      .execute(function () {
        return Drupal.performanceBudget.chartJs.getChartOptions('foo', 'bar');
      }, [], function (result) {
        browser.assert.strictEqual(result.value.title.text, '{foo}: bar');
      })
      // Test data transformation.
      .execute(function() {
        const group = 'Standard Page Load KPIs';
        const url = 'https://www.drupal.org';
        return Drupal.performanceBudget.chartJs.transformUrlData(drupalSettings.pbWptResults[group][url]);
      }, [], function (result) {
        const expected = {
          'Average - First View - Fully Loaded': [
            { x: 1564800317000, y: 8.054, vid: 1 },
            { x: 1564800492000, y: 8.236, vid: 2 },
          ],
          'Average - First View - Load Time': [
            { x: 1564800317000, y: 7.507, vid: 1 },
            { x: 1564800492000, y: 7.628, vid: 2 },
          ],
          'Average - First View - Start Render': [
            { x: 1564800317000, y: 1.1, vid: 1 },
            { x: 1564800492000, y: 1, vid: 2 },
          ],
          'Average - First View - TTFB': [
            { x: 1564800317000, y: 0.271, vid: 1 },
            { x: 1564800492000, y: 0.22, vid: 2 },
          ]
        };
        browser.assert.deepEqual(result.value, expected);
      })
      // Test assembleDatasets().
      .execute(function() {
        const data = {
          'Average - First View - Fully Loaded': [
            { x: 1564800317000, y: 8.054 },
            { x: 1564800492000, y: 8.236 },
          ],
          'Average - First View - Load Time': [
            { x: 1564800317000, y: 7.507 },
            { x: 1564800492000, y: 7.628 },
          ],
          'Average - First View - Start Render': [
            { x: 1564800317000, y: 1.1 },
            { x: 1564800492000, y: 1 },
          ],
          'Average - First View - TTFB': [
            { x: 1564800317000, y: 0.271 },
            { x: 1564800492000, y: 0.22 },
          ],
        };
        return Drupal.performanceBudget.chartJs.assembleDatasets(data);
      }, [], function (result) {
        const expected = [
          {
            backgroundColor: 'rgba(70, 240, 240, 0.75)',
            borderColor: 'rgba(70, 240, 240, 0.75)',
            borderWidth: 1.5,
            data: [
              { x: 1564800317000, y: 8.054 },
              { x: 1564800492000, y: 8.236 },
            ],
            fill: false,
            label: 'Average - First View - Fully Loaded',
            lineTension: 0,
            pointRadius: .5,
            hitRadius: 3,
            trendlineLinear: {
              lineStyle: 'dotted',
              style: 'rgba(70, 240, 240, 0.5)',
              width: 2,
            },
            type: 'line',
          },
          {
            backgroundColor: 'rgba(255, 225, 25, 0.75)',
            borderColor: 'rgba(255, 225, 25, 0.75)',
            borderWidth: 1.5,
            data: [
              { x: 1564800317000, y: 7.507 },
              { x: 1564800492000, y: 7.628 },
            ],
            fill: false,
            label: 'Average - First View - Load Time',
            lineTension: 0,
            pointRadius: .5,
            hitRadius: 3,
            trendlineLinear: {
               lineStyle: 'dotted',
               style: 'rgba(255, 225, 25, 0.5)',
               width: 2,
             },
            type: 'line',
          },
          {
            backgroundColor: 'rgba(240, 50, 230, 0.75)',
            borderColor: 'rgba(240, 50, 230, 0.75)',
            borderWidth: 1.5,
            data: [
              { x: 1564800317000, y: 1.1 },
              { x: 1564800492000, y: 1 },
            ],
            fill: false,
            label: 'Average - First View - Start Render',
            lineTension: 0,
            pointRadius: .5,
            hitRadius: 3,
            trendlineLinear: {
               lineStyle: 'dotted',
               style: 'rgba(240, 50, 230, 0.5)',
               width: 2,
             },
            type: 'line',
          },
          {
            backgroundColor: 'rgba(250, 190, 190, 0.75)',
            borderColor: 'rgba(250, 190, 190, 0.75)',
            borderWidth: 1.5,
            data: [
              { x: 1564800317000, y: 0.271 },
              { x: 1564800492000, y: 0.22 },
            ],
            fill: false,
            label: 'Average - First View - TTFB',
            lineTension: 0,
            pointRadius: .5,
            hitRadius: 3,
            trendlineLinear: {
              lineStyle: 'dotted',
              style: 'rgba(250, 190, 190, 0.5)',
              width: 2,
            },
            type: 'line',
          },
        ];
        browser.assert.deepEqual(result.value, expected);
      })
      // Test resetting zoom runs if alt key is pressed.
      .execute(function () {
        return Drupal.performanceBudget.chartJs.clickHandler({
          altKey: true,
          target: {
            getAttribute: function getAttribute(id) { return 0; },
          },
        });
      }, [], function (result) {
        browser.assert.strictEqual(result.value, true);
      })
      // Ensure buttons are not hittable.
      .execute(function () {
        Drupal.performanceBudget.chartJs.charts[0].data.datasets[0].hitRadius = 0;
        Drupal.performanceBudget.chartJs.charts[0].update();
      })
      // Wait to ensure chart redraw.
      .pause(chartDrawTime)
      // Test unhittable item clicks return false.
      .execute(function () {
        var rect = document.getElementById('wpt_kpi_chart_0').getBoundingClientRect();
        var event = new MouseEvent('click', {
          view: window,
          bubbles: true,
          cancelable: true,
          clientX: rect.x,
          clientY: rect.y,
        });

        Object.defineProperty(event, 'target', {value: document.getElementById('wpt_kpi_chart_0'), enumerable: true});
        return Drupal.performanceBudget.chartJs.clickHandler(event);
      }, [], function (result) {
        browser.assert.strictEqual(result.value, false);
      })
      // Ensure buttons are hittable.
      .execute(function () {
        Drupal.performanceBudget.chartJs.charts[0].data.datasets[0].hitRadius = 10000;
        Drupal.performanceBudget.chartJs.charts[0].update();
      })
      // Wait to ensure chart redraw.
      .pause(chartDrawTime)
      // Test hittable item clicks return true.
      .execute(function () {
        var rect = document.getElementById('wpt_kpi_chart_0').getBoundingClientRect();
        var event = new MouseEvent('click', {
          view: window,
          bubbles: true,
          cancelable: true,
          clientX: rect.x,
          clientY: rect.y,
        });

        Object.defineProperty(event, 'target', {value: document.getElementById('wpt_kpi_chart_0'), enumerable: true});
        return Drupal.performanceBudget.chartJs.clickHandler(event);
      }, [], function (result) {
        browser.assert.strictEqual(result.value, true);
      })
      .end();
  },

};
