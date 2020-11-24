/**
 * @file
 * Provides javascript test coverage for chart.js functionality.
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
    browser
      .drupalUninstall();
  },
  'Visit capture utilities settings and ensure chart functions as expected': (browser) => {
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
      .drupalRelativeURL('/admin/config/system/web-page-archive/wpt/utilities/eed58042-b791-4621-ae97-2182a7b2ce0a')
      .waitForElementVisible('#wpt_kpi_chart_preview', browserTimeout)
      // Assert attachment.
      .execute(function() {
        return Drupal.performanceBudget.chartJsValidation.isAttached;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, true);
      })
      // Assert chart title. This confirms chart actually loaded.
      .execute(function () {
        return Drupal.performanceBudget.chartJsValidation.chart.titleBlock.options.text;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, '{Key Performance Indicators}: https://www.drupal.org');
      })
      // Test chart is enabled.
      .execute(function() {
        return Drupal.performanceBudget.chartJsValidation.chartContainer.classList;
      }, [], function (result) {
        browser.assert.strictEqual(result.value.includes('pb-chartContainer-disabled'), false);
      })
      // Empty and add invalid input to option field.
      .clearValue('#edit-data-chartjs-option')
      .setValue('#edit-data-chartjs-option', 'this is garbage')
      // Test chart is disabled.
      .execute(function() {
        return Drupal.performanceBudget.chartJsValidation.chartContainer.classList;
      }, [], function (result) {
        browser.assert.strictEqual(result.value.includes('pb-chartContainer-disabled'), true);
      })
      // Empty and add custom input to option field.
      .clearValue('#edit-data-chartjs-option')
      .setValue('#edit-data-chartjs-option', "{ title: { display: true, text: 'my new title' } }")
      // Test chart is enabled.
      .execute(function() {
        return Drupal.performanceBudget.chartJsValidation.chartContainer.classList;
      }, [], function (result) {
        browser.assert.strictEqual(result.value.includes('pb-chartContainer-disabled'), false);
      })
      // Assert chart title. This confirms chart actually loaded.
      .execute(function () {
        return Drupal.performanceBudget.chartJsValidation.chart.titleBlock.options.text;
      }, [], function (result) {
        browser.assert.strictEqual(result.value, 'my new title');
      })
      // Test resetting zoom runs if alt key is pressed.
      .execute(function () {
        return Drupal.performanceBudget.chartJsValidation.resetZoom({ altKey: true });
      }, [], function (result) {
        browser.assert.strictEqual(result.value, true);
      })
      // Test resetting zoom doesn't run if alt key is not pressed.
      .execute(function () {
        return Drupal.performanceBudget.chartJsValidation.resetZoom({ altKey: false });
      }, [], function (result) {
        browser.assert.strictEqual(result.value, false);
      })
      // Test resetting zoom doesn't run if alt key is not specified.
      .execute(function () {
        return Drupal.performanceBudget.chartJsValidation.resetZoom(null);
      }, [], function (result) {
        browser.assert.strictEqual(result.value, false);
      })
      .end();
  },

};
