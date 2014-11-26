(function($){
	'use strict';
	var $localNav = $('#localNavigation');
	var previousScrollTop = window.scrollY;
	var localNavTop = $localNav.offset().top;

	function localNavScroll(event) {
		var state = window.scrollY;
		if (state > previousScrollTop) {
			if ($localNav.hasClass('fixed')) {
				$localNav.animate({top: '0px'}, 250, function() {
					$localNav.removeClass('fixed');
				});
			}
		} else if (state < previousScrollTop) {
			if (!$localNav.hasClass('fixed')) {
				$localNav.addClass('fixed');
				if ($localNav.css('top') === '57px') {
					$localNav.css('top', 0);
				}
				$localNav.animate({top: '57px'}, 250);
			}
		}
		if (state < localNavTop) {
			if ($localNav.hasClass('fixed')) {
				$localNav.removeClass('fixed');
			}
		}
		previousScrollTop = state;
	}

	function localNavScrollV2(event) {
		var state = window.scrollY;
		if (state > previousScrollTop) {
			if ($localNav.hasClass('fixed')) {
				$localNav.animate({top: '0px'}, 250, function() {
					$localNav.removeClass('fixed');
				});
			}
		} else if (state < previousScrollTop) {
			if (!$localNav.hasClass('fixed')) {
				$localNav.addClass('fixed');
				if ($localNav.css('top') === '57px') {
					$localNav.css('top', 0);
				}
				$localNav.animate({top: '57px'}, 250);
			}
		}
		if (state < localNavTop) {
			if ($localNav.hasClass('fixed')) {
				$localNav.removeClass('fixed');
			}
		}
		previousScrollTop = state;
	}

	function throttle(fn, threshold) {
	var last, deferTimer;

		return function() {
			var context = this;
			var now = +new Date();
			var args = arguments;
			if (last && now < last + threshold) {
				clearTimeout(deferTimer);
				deferTimer = setTimeout(function() {
					last = now;
					fn.apply(context, args);
				}, threshold);
			} else {
				last = now;
				fn.apply(context, args);
			}
		};
	}

	$(window).on('scroll', throttle(localNavScroll, 250));


})(jQuery);
