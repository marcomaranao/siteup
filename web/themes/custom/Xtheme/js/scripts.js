/* TABLE OF CONTENTS
1. Global functions
2. Menu
	2.1 Menu trigger
	2.2 Keyboard nav
	2.3 Mobile menu trigger
3. Components
	3.1 Accordions
	3.2 Scrollable tables
	3.3 Hidden sidebar
	3.4 Tiny Slider
	3.5 Categorized tiles
4. Window resize
	4.1 Scrollable tables
	4.2 Cookie Consent banner
	4.3 Mobile menu
5. Storage
	5.1 Alerts banner
	5.2 Categorized tiles
6. Ajax
*/

(function ($) {

	/* [ 1. Global functions ] ----------*/

	function delayAdd(elem,className,transition) {
		setTimeout(
			function removeAddFunc() {
				elem.addClass(className);
			},
		transition);
	}
	function delayRemove(elem,className,transition) {
		setTimeout(
			function removeDelayFunc() {
				elem.removeClass(className);
			},
		transition);
	}

	/* [ 2. Menu ] ----------*/

	/* [ 2.1 Menu trigger ] ----------*/
	$('.a-menu-link-wrapper__trigger, .a-section-menu-link-wrapper__trigger').on('click',function(){
		$(this).attr('aria-expanded',function(index,attr){
			return attr == 'false' ? 'true' : 'false';
		}).attr('aria-label',function(index,attr){
			if(attr.indexOf('Expand the') > -1) {
				return attr.replace('Expand the', 'Collapse the')
			} else {
				return attr.replace('Collapse the', 'Expand the')
			}
		});
		$(this).parent().siblings('.m-menu').slideToggle();
		$(this).parent().parent().siblings().find('.a-menu-link-wrapper__trigger, .a-section-menu-link-wrapper__trigger').attr('aria-expanded',false).attr('aria-label',function(index,attr){
			if(attr.indexOf('Collapse the') > -1) {
				return attr.replace('Collapse the', 'Expand the')
			}
		}).parent().siblings('.m-menu').slideUp();
	});
	$(document).on('focusin',function(){
		setTimeout(function(){
			if($(window).width() > 1024 &! $('*:focus').parents('.m-menu-wrapper--main').length) {
				$('.m-menu-wrapper--main nav > .m-menu .m-menu').slideUp();
				$('.m-menu-wrapper--main nav .a-menu-link-wrapper__trigger').attr('aria-expanded',false).attr('aria-label',function(index,attr){
					if(attr.indexOf('Collapse the') > -1) {
						return attr.replace('Collapse the', 'Expand the')
					}
				});
			}
		},200);
	});

	/* [ 2.2 Keyboard nav ] ----------*/
	$('.t-header__nav .menu-block-wrapper a').keyNavMenus();

	/* [2.3 Mobile menu trigger ] ----------*/
	$('.a-menu-trigger').on('click',function(){
		if($(window).width() <= 1024) {
			$(this).attr('aria-expanded',function(index,attr){
				return attr == 'true' ? 'false' : 'true';
			});
			$('#'+$(this).attr('aria-controls')).slideToggle();
			if($(this).attr('aria-expanded') == 'true') {
				$('.m-menu-wrapper--main .m-menu__item--active-trail').each(function(){
					$(this).find('> .m-menu__item-link-wrapper > .a-menu-link-wrapper__trigger').attr('aria-expanded',true).attr('aria-label',function(index,attr){
						return attr.replace('Expand the','Collapse the');
					}).parent().siblings('.m-menu').show();
				});
			}
		}
	});
 
	/* [ 3. Components ] ----------*/

	/* [ 3.1 Accordions ] ----------*/
	$('.field--name-field-accordion > .field__item').each(function(){
		var itemIndex = $(this).index();
		$(this).find('*[data-acc-id]').each(function(){
			var currentID = $(this).attr('id');
			$(this).attr('id','accordion__group-'+itemIndex+'-'+currentID);
		});
	});
	$('.o-accordion-group > li').each(function(){
		var itemIndex = $(this).index();
		$(this).find('*[data-acc-id]').each(function(){
			var currentID = $(this).attr('id');
			$(this).attr('id',currentID+itemIndex);
		});
	});
	$('.m-accordion__trigger').on('click',function(){
		$(this).toggleClass('is-open').attr('aria-expanded',function(index,attr){
			return attr == 'true' ? 'false' : 'true';
		});
		$(this).siblings('.m-accordion__content').slideToggle();
		$(this).parents('.o-accordion-group__list-row').siblings().find('.m-accordion__trigger').removeClass('is-open').attr('aria-expanded',false).siblings().slideUp();
	});
	if($('.o-accordion-group__list-row').length) {
		$('.o-accordion-group__list-row').each(function(){
			if(!$(this).find('.m-accordion__trigger').length){
				$(this).hide();
			}
		});
	}
	if($('.field--name-field-accordion > .field__item').length) {
		$('.field--name-field-accordion > .field__item').each(function(){
			if(!$(this).find('.m-accordion__trigger').length){
				$(this).hide();
			}
		});
	}

	/* [ 3.2 Scrollable tables ] ----------*/
	$('.t-content table').each(function() {
    var element = $(this);
    // Create the wrapper element
    var scrollWrapper = $('<div />', {
      'class': 'scrollable',
        'html': '<div />' // The inner div is needed for styling
      }).insertBefore(element);
    // Store a reference to the wrapper element
    element.data('scrollWrapper', scrollWrapper);
    // Move the scrollable element inside the wrapper element
    element.appendTo(scrollWrapper.find('div'));
	});

	/* [ 3.3 Hidden sidebar ] ----------*/
	if($('.t-sidebar .region-sidebar > *').length < 0 || $('.t-sidebar.t-sidebar--no-nav .region-sidebar > *:not(.block-menu)').length < 0) {
		$('.t-sidebar').addClass('t-sidebar--hidden');
		$('.t-content').addClass('t-content--fullwidth');
	}

	/* [ 3.4 Tiny Slider ] ----------*/
	if($('.m-carousel--multiple').length) {
		var carousel = tns({
			container: '.m-carousel--multiple .m-carousel__slide-wrapper',
			nav: true,
			navContainer: '.m-carousel--multiple .a-carousel-nav',
			controls: true,
			prevButton: '.m-carousel--multiple .a-carousel-control--previous',
			nextButton: '.m-carousel--multiple .a-carousel-control--next',
			autoHeight:true
		});
		var carousel_controls_position;
		setTimeout(function(){
			carousel_controls_position = ($('.m-carousel--multiple .tns-slide-active img').height() - $('.m-carousel--multiple .m-carousel__controls').outerHeight());
			$('.m-carousel--multiple .m-carousel__controls').attr('style','top:'+carousel_controls_position+'px').addClass('m-carousel__controls--visible');
		},200);
		carousel.events.on('indexChanged',function(){
			setTimeout(function(){
				carousel_controls_position = ($('.m-carousel--multiple .tns-slide-active img').height() - $('.m-carousel--multiple .m-carousel__controls').outerHeight());
				$('.m-carousel--multiple .m-carousel__controls').attr('style','top:'+carousel_controls_position+'px');
			},200);
		});
	}
	if($('.o-alerts-banner__carousel-slides .views-row').length > 1) {
		$('.o-alerts-banner__carousel-slides .views-row').each(function(){
			$('.o-alerts-banner .a-carousel-nav').append('<li class="a-carousel-nav__item"><button class="a-carousel-nav__item-button"></button></li>');
		});
		$('.o-alerts-banner .a-carousel-controls').removeClass('a-carousel-controls--hidden');
		var alerts_carousel = tns({
			container:'.o-alerts-banner__carousel-slides .view-content',
			nav: true,
			navContainer: '.o-alerts-banner .a-carousel-nav',
			controls: true,
			prevButton: '.o-alerts-banner .a-carousel-control--previous',
			nextButton: '.o-alerts-banner .a-carousel-control--next',
			autoHeight:true
		});
	}

	/* [ 3.5 Categorized tiles ] ----------*/
	// This is covered in section 5.2 with the use of sessionStorage.
	// For some implementations, such as a user selecting their 'profile', it makes sense to remember that choice.
	// For others, it doesn't make sense to store that value.
	// This should be assessed based on the recommended use of the categorized tiles field on a project-by-project basis.
	if($('.a-category-list__button').length) {
		$('.a-category-list__item:first > .a-category-list__button').addClass('a-category-list__button--selected');
		$('.o-categorized-tiles__categories--mobile').val($('.o-categorized-tiles__categories--mobile option:first').val());
		$('.o-categorized-tiles__content .o-categorized-tiles__section:first').addClass('o-categorized-tiles__section--active');
	}
	$('.a-category-list__button').on('click',function(){
		$(this).addClass('a-category-list__button--selected').parent().siblings().find('.a-category-list__button').removeClass('a-category-list__button--selected');
		$('.o-categorized-tiles__categories--mobile').val($(this).attr('data-category'));
		$('.o-categorized-tiles__section[data-category="'+$(this).attr('data-category')+'"]').addClass('o-categorized-tiles__section--active').siblings().removeClass('o-categorized-tiles__section--active');
	});
	$('.o-categorized-tiles__categories--mobile').on('change',function(){
		$('.a-category-list__button[data-category="'+$(this).val()+'"]').addClass('a-category-list__button--selected').parent().siblings().find('.a-category-list__button').removeClass('a-category-list__button--selected');
		$('.o-categorized-tiles__section[data-category="'+$(this).val()+'"]').addClass('o-categorized-tiles__section--active').siblings().removeClass('o-categorized-tiles__section--active');
	});

	/* [ 4. Window resize ] ----------*/

	var resizeTimer;
	function resizeFunction() {
		/* [ 4.1 Scrollable tables ] ----------*/
		if($('.scrollable').length) {
			$('.scrollable').each(function(){
				if ($(this).find('> div > table').not('table.sticky-header').outerWidth() > $(this).find('> div > table').not('table.sticky-header').parent().outerWidth()) {
					$(this).find('> div > table').not('table.sticky-header').data('scrollWrapper').addClass('has-scroll');
				} else {
					$(this).find('> div > table').not('table.sticky-header').data('scrollWrapper').removeClass('has-scroll');
				}
			});
		}
		/* [ 4.2 Cookie Consent banner ] ----------*/
		if($('.cc_banner-wrapper').length) {
			$('body').css('padding-bottom',$('.cc_banner-wrapper').outerHeight());
		}
		/* [ 4.3 Mobile menu ] ----------*/
		if($(window).width() > 1024) {
			$('.a-menu-trigger').attr('aria-expanded',false)
			$('.m-menu-wrapper').show().attr('style','');
		}
		/* [ 4.4 Tiny Slider ] ----------*/
		if($('.m-carousel--multiple').length) {
			setTimeout(function(){
				carousel_controls_position = ($('.m-carousel--multiple .tns-slide-active img').height() - $('.m-carousel--multiple .a-carousel-controls').outerHeight());
				$('.m-carousel--multiple .a-carousel-controls').attr('style','top:'+carousel_controls_position+'px').addClass('a-carousel-controls--visible');
			},200);
		}
	};
	$(window).on('load resize',function() {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(resizeFunction, 250);
	});
	resizeFunction();

	/* [ 5. Storage ] ----------*/

	if ('sessionStorage' in window && typeof sessionStorage == 'object') {
		$(document).ready(function() {
			/* [ 5.1 Alerts banner ] ----------*/
			if($('.o-alerts-banner').length) {
				if(sessionStorage['alerts'] == 'collapsed') {
					$('.o-alerts-banner__carousel-wrapper').hide().attr('aria-hidden',true);
					$('.a-alerts-trigger').attr({
						title:'Expand the Alerts carousel',
						'aria-expanded':false
					}).text('Expand');
					if($('.o-alerts-banner .views-row').length > 1) {
						$('.o-alerts-banner .a-carousel-controls').addClass('a-carousel-controls--hidden');
					}
				} else {
					sessionStorage['alerts'] = 'expanded';
					$('.o-alerts-banner__carousel-wrapper').show().attr('aria-hidden',true);
					$('.a-alerts-trigger').attr({
						title:'Collapse the Alerts carousel',
						'aria-expanded':true
					}).text('Collapse');
					if($('.o-alerts-banner .views-row').length > 1) {
						$('.o-alerts-banner .a-carousel-controls').removeClass('a-carousel-controls--hidden');
					}
				}
				$('.a-alerts-trigger').on('click',function(){
					$(this).attr({
						'title':function(index,attr) {
							return attr == 'Expand the Alerts carousel' ? 'Collapse the Alerts carousel' : 'Expand the Alerts carousel';
						},
						'aria-expanded':function(index,attr) {
							return attr == 'true' ? 'false' : 'true';
						}
					}).text(function(index,text){
						return text == 'Collapse' ? 'Expand' : 'Collapse';
					});
					$('.o-alerts-banner__carousel-wrapper').slideToggle().attr('aria-hidden',function(index,attr){
						return attr == 'true' ? 'false' : 'true';
					});
					if($('.o-alerts-banner .views-row').length > 1) {
						$('.o-alerts-banner .a-carousel-controls').toggleClass('a-carousel-controls--hidden');
					}
					if($(this).attr('aria-expanded') == 'true') {
						sessionStorage['alerts'] = 'expanded';
					} else {
						sessionStorage['alerts'] = 'collapsed';
					}
				});
			}

			/* [ 5.2 Categorized tiles ] ----------*/
			// This is covered in section 3.5 without the use of sessionStorage.
			// For some implementations, such as a user selecting their 'profile', it makes sense to remember that choice.
			// For others, it doesn't make sense to store that value.
			// This should be assessed based on the recommended use of the categorized tiles field on a project-by-project basis.
			// if($('.a-category-list__button').length) {
			// 	if(sessionStorage['categorized tiles'] == null || sessionStorage['categorized tiles'] == '') {
			// 		sessionStorage['categorized tiles'] = $('.a-category-list__item:first > .a-category-list__button').attr('data-category');
			// 	}
			// 	$('.a-category-list__button[data-category="'+sessionStorage['categorized tiles']+'"]').addClass('a-category-list__button--selected');
			// 	$('.o-categorized-tiles__categories--mobile').val(sessionStorage['categorized tiles']);
			// 	$('.o-categorized-tiles__section[data-category="'+sessionStorage['categorized tiles']+'"]').addClass('o-categorized-tiles__section--active');
			// }
			// $('.a-category-list__button').on('click',function(){
			// 	sessionStorage['categorized tiles'] = $(this).attr('data-category');
			// 	$(this).addClass('a-category-list__button--selected').parent().siblings().find('.a-category-list__button').removeClass('a-category-list__button--selected');
			// 	$('.o-categorized-tiles__categories--mobile').val(sessionStorage['categorized tiles']);
			// 	$('.o-categorized-tiles__section[data-category="'+sessionStorage['categorized tiles']+'"]').addClass('o-categorized-tiles__section--active').siblings().removeClass('o-categorized-tiles__section--active');
			// });
			// $('.o-categorized-tiles__categories--mobile').on('change',function(){
			// 	sessionStorage['categorized tiles'] = $(this).val();
			// 	$('.a-category-list__button[data-category="'+sessionStorage['categorized tiles']+'"]').addClass('a-category-list__button--selected').parent().siblings().find('.a-category-list__button').removeClass('a-category-list__button--selected');
			// 	$('.o-categorized-tiles__section[data-category="'+sessionStorage['categorized tiles']+'"]').addClass('o-categorized-tiles__section--active').siblings().removeClass('o-categorized-tiles__section--active');
			// });
		});
	};
 
})(jQuery);
/* [ 6. Ajax ] ----------*/
// jQuery(document).ready(function ($) {
//   $(document).ajaxComplete(function () {
// 	});
// });