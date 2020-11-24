(function ($) {
  /*
  *	function keyNavMenus - provides a method to setup mutliple
  * keyboard based navigation keys on any menu by providing
  * keycodes to the options object of the function. Keycodes can
  * be either a single integer or an array of integers. Shift+Tab
  * always closes menus and focuses parent.
  *
  * Sample: $('#menu a').keyNavMenus()
  */
  $.fn.keyNavMenus = function( options ) {
    var settings = $.extend({
      // These are the defaults.
      open: 40, // DOWN
      close: 38, // UP
      next: 39, // RIGHT
      prev: 37, // LEFT
      click: [ 32, 13 ], // SPACE / ENTER
      openClass: 'is-open',
      visibleClass: 'is-visible',
      triggerClass: '.submenu-trigger',
      triggerOpenClass:'is-open',
      triggerOpenLabel:'Expand this section of the menu',
      triggerClosedLabel:'Collapse this section of the menu',
      addClass: true,
      autoNavigate: false,
      tabClose: true,

    }, options );

    return this.each( function() {
      // Store a copy of $( this )
      var $this = $( this );

      $this.bind('keydown', function (event) {
        var key = event.which;
        // VARIOUS CHECKS AGAINST MAPPED KEYS FOR ACTIONS
        if ($.inArray(key, settings.open) != -1 || key === settings.open) {
          event.preventDefault();
          keyNavSectionOpen( $this );
        }
        if ($.inArray(key, settings.close) != -1 || key === settings.close) {
          event.preventDefault();
          keyNavSectionClose( $this );
        }
        if ($.inArray(key, settings.next) != -1 || key === settings.next) {
          event.preventDefault();
          keyNavItemNext( $this );
        }
        if ($.inArray(key, settings.prev) != -1 || key === settings.prev) {
          event.preventDefault();
          keyNavItemPrev( $this );
        }
        if ($.inArray(key, settings.click) != -1 || key === settings.click) {
          event.preventDefault();
          keyNavItemClick( $this );
        }
        // DEFAULT TO CLOSE WITH SHIFT+TAB
        if (event.shiftKey && event.which === 9 && settings.tabClose) {
          keyNavSectionClose( $this );
        }
      });
    });

    /*
     *	function keyNavSectionClose - Closes the parent section menu of the
     * element that the function is called on. Can be used just like any
     * other jQuery function, if no menu is found nothing happens.
     */
    function keyNavSectionClose( element ) {
      return element.each(function () {
        element.parent('li').removeClass(settings.openClass).delay(200).removeClass(settings.visibleClass);
        element.siblings(settings.triggerClass).removeClass(settings.triggerOpenClass).attr('aria-expanded',false).attr('aria-label',settings.triggerOpenLabel);
        element.siblings('.menu').slideUp().find('> a').focus();
      });
    }

    /*
     *	function keyNavSectionOpen - Opens the parent section menu of the
     * element that the function is called on. Can be used just like any
     * other jQuery function, if no menu is found nothing happens.
     */
    function keyNavSectionOpen( element ) {
      return element.each(function () {
        element.parent('li').addClass(settings.visibleClass).delay(200).addClass(settings.openClass);
        element.siblings(settings.triggerClass).addClass(settings.triggerOpenClass).attr('aria-expanded',true).attr('aria-label',settings.triggerClosedLabel);
        element.siblings('.menu').slideDown().find(' > li:first-child > a').focus();
      });
    }

    /*
     *	function keyNavItemNext - Closes the parent section menu of the
     * element that the function is called on. Can be used just like any
     * other jQuery function, if no menu is found nothing happens.
     */
    function keyNavItemNext( element ) {
      return element.each(function () {
        element.closest('li').next().children('a').focus();
      });
    }

    /*
     *	function keyNavItemPrev - Closes the parent section menu of the
     * element that the function is called on. Can be used just like any
     * other jQuery function, if no menu is found nothing happens.
     */
    function keyNavItemPrev( element ) {
      return element.each(function () {
        if (element.closest('li').prev('li').length > 0) {
          element.closest('li').prev().children('a').focus();
        } else {
          element.closest('ul').closest('li').removeClass(settings.openClass).delay(200).removeClass(settings.visibleClass).find('> a').focus();
          element.closest('ul').closest('li').find('> a').siblings(settings.triggerClass).removeClass(settings.triggerOpenClass).attr('aria-expanded',false).attr('aria-label',settings.triggerOpenLabel);
        }
      });
    }

    /*
     *	function keyNavItemClick - Performs the click event on whatever
     * element that the function is called on. Can be used just like any
     * other jQuery function, if no menu is found nothing happens.
     */
    function keyNavItemClick( element ) {
      return element.each(function () {
        this.click();
      });
    }
  }
}(jQuery));