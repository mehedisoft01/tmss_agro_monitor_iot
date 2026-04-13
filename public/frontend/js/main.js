(function ($) {
    "use strict";

    /*--
        Commons Variables
    -----------------------------------*/
	var $window = $(window),
		$body = $('body'),
		$mainWrapper = $('.main-wrapper');

	/*Custom script to call Background Image form html data attribute*/
    $('[data-bg-image]').each(function () {
        var $this = $(this),
            $image = $this.data('bg-image');
        $this.css('background-image', 'url(' + $image + ')');

    });

	/*--
		Header Search
    -----------------------------------*/
	function headerSearch() {
		$('.header-search-toggle').on('click', function () {
			var $this = $(this),
				$target = $this.data('target');
			$($target).addClass('open');
		});
		$('.fullscreen-search-close').on('click', function () {
			var $this = $(this),
				$target = $this.data('target');
			$($target).removeClass('open');
		});
	}
	headerSearch();

	/*--
		Mobile OffCanvas Open
    -----------------------------------*/
	function mobileOffCanvasToggle() {
		var $offCanvas = $('#offcanvas'),
			$offCanvasOverlay = $('.offcanvas-overlay');
		$('.mobile-menu-toggle').on('click', function () {
			$offCanvas.addClass('open');
			$offCanvasOverlay.fadeIn();
		});
		$('.offcanvas-close, .offcanvas-overlay').on('click', function () {
			$offCanvas.removeClass('open');
			$offCanvasOverlay.fadeOut();
		});
	}
	mobileOffCanvasToggle();

	/*--
        Off Canvas Menu
    -----------------------------------*/
	function mobileOffCanvasMenu() {
		var $offCanvasNav = $('.offcanvas-menu'),
			$offCanvasNavSubMenu = $offCanvasNav.find('.sub-menu');

		/*Add Toggle Button With Off Canvas Sub Menu*/
		$offCanvasNavSubMenu.parent().prepend('<span class="menu-expand"><i></i></span>');

		/*Category Sub Menu Toggle*/
		$offCanvasNav.on('click', 'li a, li .menu-expand', function (e) {
			var $this = $(this);
			if ($this.parent().attr('class').match(/\b(menu-item-has-children|has-children|has-sub-menu)\b/) || ($this.attr('href') === '#' || $this.hasClass('menu-expand'))) {
				e.preventDefault();
				if ($this.siblings('ul:visible').length) {
					$this.parent('li').removeClass('active');
					$this.siblings('ul').slideUp();
					$this.parent('li').find('li').removeClass('active');
					$this.parent('li').find('ul:visible').slideUp();
				} else {
					$this.parent('li').addClass('active');
					$this.closest('li').siblings('li').removeClass('active').find('li').removeClass('active');
					$this.closest('li').siblings('li').find('ul:visible').slideUp();
					$this.siblings('ul').slideDown();
				}
			}
		});
	}
	mobileOffCanvasMenu();

	/*--
		Slide Slider Activation
    -----------------------------------*/

    // Hero Slider
    $('.hero-slider').slick({
        //autoplay: true,
		autoplaySpeed: 5000,
		fade: true,
        prevArrow: '<button class="slick-prev"><i class="ion-ios-arrow-left"></i></button>',
        nextArrow: '<button class="slick-next"><i class="ion-ios-arrow-right"></i></button>',
		responsive: [
			{
				breakpoint: 768,
				settings: {
					arrows: false
				}
			}
		]
    }).on('afterChange', function (event, slick, currentSlide) {
		new WOW().init();
	});

    // News Media Slider
    $('.news-media-slider').slick({
        autoplay: true,
        prevArrow: '<button class="slick-prev"><i class="fa fa-chevron-left"></i></button>',
        nextArrow: '<button class="slick-next"><i class="fa fa-chevron-right"></i></button>'
    });

	// Single Product Gallery
    $('.single-product-gallery').slick({
        arrows: false,
		swipe: false,
		touchMove: false,
		asNavFor: '.single-product-thumb'
    });

	// Single Product Thumb
    $('.single-product-thumb').slick({
        arrows: false,
		asNavFor: '.single-product-gallery',
		slidesToShow: 4,
		slidesToScroll: 1,
		focusOnSelect: true
    });

	// Product Slider 4 Column
    $('.product-slider').slick({
        autoplay: true,
        arrows: false,
		slidesToShow: 4,
		slidesToScroll: 1,
		responsive: [
			{
				breakpoint: 992,
				settings: {
					slidesToShow: 3
				}
			},
			{
				breakpoint: 768,
				settings: {
					slidesToShow: 2
				}
			},
			{
				breakpoint: 480,
				settings: {
					slidesToShow: 1
				}
			}
		]
    });

	// Testimonial Slider
    $('.testimonial-slider').slick({
        autoplay: true,
        arrows: false,
		slidesToShow: 2,
		slidesToScroll: 1,
		responsive: [
			{
				breakpoint: 992,
				settings: {
					slidesToShow: 1
				}
			}
		]
    });
    $('.testimonial-slider-2').slick({
        autoplay: true,
        arrows: false,
		slidesToShow: 3,
		slidesToScroll: 1,
		responsive: [
			{
				breakpoint: 992,
				settings: {
					slidesToShow: 2
				}
			},
			{
				breakpoint: 768,
				settings: {
					slidesToShow: 1
				}
			}
		]
    });

	// Partner Slider
    $('.partner-slider').slick({
        autoplay: true,
        arrows: false,
		slidesToShow: 6,
		slidesToScroll: 1,
		responsive: [
			{
				breakpoint: 992,
				settings: {
					slidesToShow: 4
				}
			},
			{
				breakpoint: 768,
				settings: {
					slidesToShow: 3
				}
			},
			{
				breakpoint: 480,
				settings: {
					slidesToShow: 2
				}
			}
		]
    });

	/*--
		Counter Activation
    -----------------------------------*/
	$('.counter').counterUp({
		'time': 2000
	});

	/*--
		MagnificPopup Activation
    -----------------------------------*/

	// Single Image Popup
	$('.single-popup').magnificPopup({
		type: 'image',
		closeOnContentClick: true,
		mainClass: 'mfp-img-mobile',
		image: {
			verticalFit: true
		}

	});

	// Gallery Image Popup
	$('.popup-gallery').magnificPopup({
		delegate: 'a',
		type: 'image',
		tLoading: 'Loading image #%curr%...',
		mainClass: 'mfp-img-mobile',
		gallery: {
			enabled: true,
			navigateByImgClick: true,
			preload: [0, 1] // Will preload 0 - before current, and 1 after the current image
		},
		image: {
			tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
			titleSrc: function (item) {
				return item.el.attr('title') + '<small> By: ' + item.el.attr('data-author') + '</small>';
			}
		}
	});

	// Single Video Popup
	$('.video-popup').magnificPopup({
		type: 'iframe',
		closeOnContentClick: true,
		mainClass: 'mfp-img-mobile'
	});

	/*--
		WOW Activation
    -----------------------------------*/
    new WOW().init();

	/*--
		Pricing Slider Activation
    -----------------------------------*/
    $('#price-range-slider').ionRangeSlider({
		type: 'double',
        skin: 'round',
		hide_min_max: true,
		min: 0,
		max: 500,
		from: 50,
		to: 440
    });

	/*--
		Single Product Zoom Actiovation
    -----------------------------------*/
	$('.product-zoom').zoom();

    /*--
        MailChimp
    -----------------------------------*/
    $('#mc-form').ajaxChimp({
        language: 'en',
        callback: mailChimpResponse,
        // ADD YOUR MAILCHIMP URL BELOW HERE!
        url: 'http://devitems.us11.list-manage.com/subscribe/post?u=6bbb9b6f5827bd842d9640c82&amp;id=05d85f18ef'

    });
    function mailChimpResponse(resp) {
        if (resp.result === 'success') {
            $('.mailchimp-success').html('' + resp.msg).fadeIn(900);
            $('.mailchimp-error').fadeOut(400);
        } else if(resp.result === 'error') {
            $('.mailchimp-error').html('' + resp.msg).fadeIn(900);
        }
    }

    /*--
        Scroll To Top
    -----------------------------------*/
	(function(){
		$body.append('<a class="scrolltotop" href="#top"><i class="fa fa-angle-up"></i></a>');
		$window.on('scroll', function(){
			if ($window.scrollTop() >= 300) {
				$('.scrolltotop').addClass('show');
			} else {
				$('.scrolltotop').removeClass('show');
			}
		})
		$body.on('click', 'a[href="#top"]', function(e) {
			e.preventDefault();
			$("html, body").animate({ scrollTop: 0}, 'slow');
		});
	})();

    /*--
        Ajax Contact Form
    -----------------------------------*/
    // $(function() {
    //     // Get the form.
    //     var form = $('#contact-form, #evaluation-form');
    //     // Get the messages div.
    //     var formMessages = $('.form-message');
    //     // Set up an event listener for the contact form.
    //     $(form).submit(function(e) {
    //         // Stop the browser from submitting the form.
    //         e.preventDefault();
    //         // Serialize the form data.
    //         var formData = $(form).serialize();
    //         // Submit the form using AJAX.
    //         $.ajax({
    //             type: 'POST',
    //             url: $(form).attr('action'),
    //             data: formData
    //         })
    //         .done(function(response) {
    //             // Make sure that the formMessages div has the 'success' class.
    //             formMessages.removeClass('error text-danger d-none').addClass('success text-success d-block');
    //             // Set the message text.
    //             formMessages.text(response);
    //             // Clear the form.
    //             $(form).find('input:not([type=submit]), textarea').val('');
    //         })
    //         .fail(function(data) {
    //             // Make sure that the formMessages div has the 'error' class.
    //             formMessages.removeClass('success text-success d-none').addClass('error text-danger d-block');
    //             // Set the message text.
    //             if (data.responseText !== '') {
    //                 formMessages.text(data.responseText);
    //             } else {
    //                 formMessages.text('Oops! An error occured and your message could not be sent.');
    //             }
    //         });
    //     });
    // });

	/*--
		Contact Map Activation
    -----------------------------------*/
	if ($('#contact-map').length) {
		var myCenter = new google.maps.LatLng(42.0258352, -93.6613958);
		function initialize() {
			var mapProp = {
					center: myCenter,
					scrollwheel: false,
					zoom: 10,
					mapTypeId: google.maps.MapTypeId.ROADMAP
				},
				map = new google.maps.Map(document.getElementById("contact-map"), mapProp),
				marker = new google.maps.Marker({
					position: myCenter,
					icon: 'assets/images/icons/marker.png',
					animation: google.maps.Animation.BOUNCE,
					map: map
				}),

				styles = [
					{
						"featureType": "water",
						"elementType": "geometry",
						"stylers": [
							{
								"color": "#e9e9e9"
							},
							{
								"lightness": 17
							}
						]
					},
					{
						"featureType": "landscape",
						"elementType": "geometry",
						"stylers": [
							{
								"color": "#f5f5f5"
							},
							{
								"lightness": 20
							}
						]
					},
					{
						"featureType": "road.highway",
						"elementType": "geometry.fill",
						"stylers": [
							{
								"color": "#ffffff"
							},
							{
								"lightness": 17
							}
						]
					},
					{
						"featureType": "road.highway",
						"elementType": "geometry.stroke",
						"stylers": [
							{
								"color": "#ffffff"
							},
							{
								"lightness": 29
							},
							{
								"weight": 0.2
							}
						]
					},
					{
						"featureType": "road.arterial",
						"elementType": "geometry",
						"stylers": [
							{
								"color": "#ffffff"
							},
							{
								"lightness": 18
							}
						]
					},
					{
						"featureType": "road.local",
						"elementType": "geometry",
						"stylers": [
							{
								"color": "#ffffff"
							},
							{
								"lightness": 16
							}
						]
					},
					{
						"featureType": "poi",
						"elementType": "geometry",
						"stylers": [
							{
								"color": "#f5f5f5"
							},
							{
								"lightness": 21
							}
						]
					},
					{
						"featureType": "poi.park",
						"elementType": "geometry",
						"stylers": [
							{
								"color": "#dedede"
							},
							{
								"lightness": 21
							}
						]
					},
					{
						"elementType": "labels.text.stroke",
						"stylers": [
							{
								"visibility": "on"
							},
							{
								"color": "#ffffff"
							},
							{
								"lightness": 16
							}
						]
					},
					{
						"elementType": "labels.text.fill",
						"stylers": [
							{
								"saturation": 36
							},
							{
								"color": "#333333"
							},
							{
								"lightness": 40
							}
						]
					},
					{
						"elementType": "labels.icon",
						"stylers": [
							{
								"visibility": "off"
							}
						]
					},
					{
						"featureType": "transit",
						"elementType": "geometry",
						"stylers": [
							{
								"color": "#f2f2f2"
							},
							{
								"lightness": 19
							}
						]
					},
					{
						"featureType": "administrative",
						"elementType": "geometry.fill",
						"stylers": [
							{
								"color": "#fefefe"
							},
							{
								"lightness": 20
							}
						]
					},
					{
						"featureType": "administrative",
						"elementType": "geometry.stroke",
						"stylers": [
							{
								"color": "#fefefe"
							},
							{
								"lightness": 17
							},
							{
								"weight": 1.2
							}
						]
					}
				];
			map.setOptions({styles: styles});
			marker.setMap(map);
		}
		google.maps.event.addDomListener(window, 'load', initialize);
	}


})(jQuery);
