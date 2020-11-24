/**
 * Javascript for Upanup widget.
 */

(function ($, Drupal, debounce) {
  Drupal.behaviors.upanupWidgetRecurrences = {
    attach: function attach(context, settings) {
      var $recurrenceOptionDropdowns = $(context).find('.upanup-date-recur-widget-upanup-widget .upanup-date-recur-widget-upanup-widget-recurrence-option').once('upanup-date-recur-widget-upanup-widget-recurrence-option');
      $recurrenceOptionDropdowns.each(function () {
        $(this).change(function() {
          var value = $(this).val();
          if ('custom_open' === value) {
            /* Traverse and find the outer container, then find the recurring
            rules button. */
            var $recurrenceOpen = $(this).closest('.upanup-date-recur-widget-upanup-widget').find('.upanup-date-recur-widget-upanup-widget-recurrence-open');
            $recurrenceOpen.click();
          }
          if ('custom' !== value) {
            /** Delete custom option if de-selected */
            $(this).find('option[value="custom"]').remove();
          }
        });
      });

      // Click the reload button when the value changes, with debounce.
      var $startDates = $(context).find('.upanup-date-recur-widget-upanup-widget .upanup-date-recur-widget-upanup-widget-start-date').once('upanup-date-recur-widget-upanup-widget-start-date');
      $startDates.each(function () {
        $(this).on('change', debounce(function () {
          $(this).closest('.upanup-date-recur-widget-upanup-widget').find('.upanup-date-recur-widget-upanup-widget-reload-recurrence-options').click();

          /* Set the end date to start date if start date is greater than end
          date or end date is empty/invalid */
          var $startMatches = $(this).val().match(/^(\d{4})\-(\d{2})\-(\d{2})$/);
          if ($startMatches !== null) {
            var $startDate = new Date($startMatches[1], $startMatches[2] - 1, $startMatches[3]);

            var $endDateElement = $(this).closest('.upanup-date-recur-widget-upanup-widget').find('.upanup-date-recur-widget-upanup-widget-start-end');
            var $endDate = null;
            var $endMatches = $($endDateElement).val().match(/^(\d{4})\-(\d{2})\-(\d{2})$/);
            if ($endMatches !== null) {
              $endDate = new Date($endMatches[1], $endMatches[2] - 1, $endMatches[3]);
            }
            if ($endDate === null || ($endDate.getTime()) < $startDate.getTime()) {
              $endDateElement.val($(this).val());
            }
          }
        }, 1000));
      });

      // Remove the hidden label classes
      $('[data-drupal-selector="edit-ends-date"] .form-item--no-label').removeClass('form-item--no-label');
      $('[data-drupal-selector="edit-ends-date"] .form-item__label.visually-hidden').removeClass('visually-hidden');
      // Set default date/time in modal
      var defaultEndDate = $('.upanup-date-recur-widget-upanup-widget-day__end input[type="date"]').val();
      var defaultEndTime = $('.upanup-date-recur-widget-upanup-widget-day__end input[type="time"]').val();
      if(!$('[data-drupal-selector="edit-ends-date-date"]').val()) {
        $('[data-drupal-selector="edit-ends-date-date"]').val(defaultEndDate);
      }
      if(!$('[data-drupal-selector="edit-ends-date-time"]').val()) {
        $('[data-drupal-selector="edit-ends-date-time"]').val(defaultEndTime);
      }
    }
  };
})(jQuery, Drupal, Drupal.debounce);
