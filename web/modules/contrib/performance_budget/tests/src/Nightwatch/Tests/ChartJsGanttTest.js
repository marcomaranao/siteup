/**
 * @file
 * Provides javascript test coverage for gantt chart.js functionality.
 */

const browserTimeout = 5000;

module.exports = {
  '@tags': ['performance_budget'],
  before: function(browser) {
    browser.drupalInstall({
      setupFile: 'modules/contrib/performance_budget/tests/src/TestSite/TestSiteInstallTestScript.php',
    });
  },
  after: function(browser) {
    browser.drupalUninstall();
  },
  'Visit individual run and ensure gantt charts and request data are available.': (browser) => {
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
      .drupalRelativeURL('/admin/config/system/web-page-archive/runs/1')
      .assert.containsText('body', 'Load Time: 7.507s')
      .assert.containsText('body', 'Fully Loaded: 8.054s')
      .assert.containsText('body', 'TTFB: 0.271s')
      .assert.containsText('body', 'Start Render: 1.1s')
      .click('.pb-displayFullButton')
      .assert.containsText('body', 'First View - Gantt Chart')
      .assert.containsText('body', 'First View - Requests')
      .assert.containsText('body', 'Repeat View - Gantt Chart')
      .assert.containsText('body', 'Repeat View - Requests')

      // Assert attachment.
      .execute(function() {
        return Drupal.performanceBudget.chartJsGantt.isAttached;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, true);
      })

      // Test first gantt chart visibility and data settings.
      .waitForElementNotVisible('#wpt_kpi_chart_firstView', browserTimeout)
      .click('.pb-requestGanttChartLink0 summary')
      .waitForElementVisible('#wpt_kpi_chart_firstView', browserTimeout)
      .execute(function () {
        return Drupal.performanceBudget.chartJsGantt.charts.firstView.config.type;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, 'gantt');
      })
      .execute(function () {
        return Drupal.performanceBudget.chartJsGantt.charts.firstView.data.datasets.length;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, 446);
      })
      .click('.pb-requestGanttChartLink0 summary')
      .waitForElementNotVisible('#wpt_kpi_chart_firstView', browserTimeout)

      // Test second gantt chart visibility and data settings.
      .waitForElementNotVisible('#wpt_kpi_chart_repeatView', browserTimeout)
      .click('.pb-requestGanttChartLink1 summary')
      .waitForElementVisible('#wpt_kpi_chart_repeatView', browserTimeout)
      .execute(function () {
        return Drupal.performanceBudget.chartJsGantt.charts.repeatView.config.type;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, 'gantt');
      })
      .execute(function () {
        return Drupal.performanceBudget.chartJsGantt.charts.repeatView.data.datasets.length;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, 168);
      })
      .click('.pb-requestGanttChartLink1 summary')
      .waitForElementNotVisible('#wpt_kpi_chart_repeatView', browserTimeout);

    // Test first request table.
    browser.expect.element('body').text.to.not.contain('0.297s');
    browser.click('.pb-requestLink0 summary');
    browser.expect.element('body').text.to.not.contain('2.972s');
    browser.expect.element('body').text.to.contain('0.297s');
    browser.click('.pb-requestLink0 summary');
    browser.expect.element('body').text.to.not.contain('0.297s');

    // Test second request table.
    browser.expect.element('body').text.to.not.contain('2.972s');
    browser.click('.pb-requestLink1 summary');
    browser.expect.element('body').text.to.contain('2.972s');
    browser.expect.element('body').text.to.not.contain('0.297s');
    browser.click('.pb-requestLink1 summary');
    browser.expect.element('body').text.to.not.contain('2.972s');

    browser
      // Test getDatasets() on firstView data.
      .execute(function () {
        return Drupal.performanceBudget.chartJsGantt.getDatasets('firstView');
      }, [], function (result) {
        browser.assert.deepEqual(result.value[0], {
          backgroundColor: 'rgba(230, 25, 75, 0.75)',
          borderWidth: 0,
          data: [ { x: { from: 0.02, to: 0.07 }, y: 720 } ],
          height: 5,
          label: 'dns (0.05s from 0.02s to 0.07s): https://www.drupal.org/',
          padding: 10,
          pointRadius: 0,
        });
        browser.assert.deepEqual(result.value[239], {
          backgroundColor: 'rgba(255, 225, 25, 0.75)',
          borderWidth: 0,
          data: [ { x: { from: 5.746, to: 5.747 }, y: 282 } ],
          height: 5,
          label: 'download (0.001s from 5.746s to 5.747s): https://www.drupal.org/sites/all/themes/bluecheese/images/icon-arrow-left.svg',
          padding: 10,
          pointRadius: 0,
        });
      })
      // Test getDatasets() on repeatView data.
      .execute(function () {
        return Drupal.performanceBudget.chartJsGantt.getDatasets('repeatView');
      }, [], function (result) {
        console.log(result.value[10]);
        console.log(result.value[111]);
        browser.assert.deepEqual(result.value[10], {
          backgroundColor: 'rgba(70, 240, 240, 0.75)',
          borderWidth: 0,
          data: [ { x: { from: 0.365, to: 0.396 }, y: 264 } ],
          height: 5,
          label: 'ttfb (0.031s from 0.365s to 0.396s): https://www.drupal.org/sites/all/themes/bluecheese/images/icon-w-user.svg',
          padding: 10,
          pointRadius: 0,
        });
        browser.assert.deepEqual(result.value[111], {
          backgroundColor: 'rgba(230, 25, 75, 0.75)',
          borderWidth: 0,
          data: [ { x: { from: 1.845, to: 1.872 }, y: 72 } ],
          height: 5,
          label: 'dns (0.027s from 1.845s to 1.872s): https://pixel-geo.prfct.co/tagjs?a_id=32173&amp;source=js_tag',
          padding: 10,
          pointRadius: 0,
        });
      })
      .end();
  },
};
