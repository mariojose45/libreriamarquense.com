jQuery(function ($) {
    'use strict';
	
	// Redirigir al seleccionar categoría en el header
	$('#select-categoria-header').on('change', function() {
		var categoriaId = $(this).val();
		if (categoriaId) {
			// Redirigir a tienda.php con el idcategoria
			window.location.href = 'tienda.php?categoria=' + categoriaId;
		} else {
			// Si selecciona "Todas las categorías", ir a tienda sin parámetros
			window.location.href = 'tienda.php';
		}
	});
	
	// Header Sticky
	$(window).on('scroll',function() {
		if ($(this).scrollTop() > 120){  
			$('.navbar-area').addClass("is-sticky");
		}
		else{
			$('.navbar-area').removeClass("is-sticky");
		}
	});

	// Mean Menu
	jQuery('.mean-menu').meanmenu({
		meanScreenWidth: "991"
	});
	
	// Others Option For Responsive JS
	$(".others-option-for-responsive .dot-menu").on("click", function(){
		$(".others-option-for-responsive .container .container").toggleClass("active");
	});
	
	// Button Hover JS
	$('.default-btn')
	.on('mouseenter', function(e) {
		var parentOffset = $(this).offset(),
		relX = e.pageX - parentOffset.left,
		relY = e.pageY - parentOffset.top;
		$(this).find('span').css({top:relY, left:relX})
	})
	.on('mouseout', function(e) {
		var parentOffset = $(this).offset(),
		relX = e.pageX - parentOffset.left,
		relY = e.pageY - parentOffset.top;
		$(this).find('span').css({top:relY, left:relX})
	});

	// Nice Select JS
	$('select').niceSelect();
	
	// Cerrar dropdown de categorías al hacer clic fuera
	$(document).on('click', function(e) {
		if (!$(e.target).closest('.nice-select').length) {
			$('.nice-select').removeClass('open');
		}
	});
	
	// Asegurar que el dropdown se cierre después de seleccionar
	$('#select-categoria-header').on('change', function() {
		// Cerrar el dropdown antes de redirigir
		$('.nice-select').removeClass('open');
	});
	
	// Home Slides
	$('.home-slides').owlCarousel({
		loop: true,
		nav: true,
		dots: false,
		autoplayHoverPause: true,
		items: 1,
		smartSpeed: 100,
		autoplay: false,
		navText: [
			"<i class='flaticon-left-arrow'></i>",
			"<i class='flaticon-right-arrow'></i>"
		],
	});
	$(".home-slides").on("translate.owl.carousel", function(){
		$(".main-slider-content b").removeClass("animate__animated animate__fadeInUp").css("opacity", "0");
		$(".main-slider-content h1").removeClass("animate__animated animate__fadeInUp").css("opacity", "0");
		$(".main-slider-content p").removeClass("animate__animated animate__fadeInUp").css("opacity", "0");
		$(".main-slider-content a").removeClass("animate__animated animate__fadeInUp").css("opacity", "0");
	});
	$(".home-slides").on("translated.owl.carousel", function(){
		$(".main-slider-content b").addClass("animate__animated animate__fadeInUp").css("opacity", "1");
		$(".main-slider-content h1").addClass("animate__animated animate__fadeInUp").css("opacity", "1");
		$(".main-slider-content p").addClass("animate__animated animate__fadeInUp").css("opacity", "1");
		$(".main-slider-content a").addClass("animate__animated animate__fadeInUp").css("opacity", "1");
	});
	
	// Home Slides Two - Slider manual tipo stacked cards
	const sliderStack = document.querySelector('.home-slides-two.slider-stack');
	const nextBtnStack = document.querySelector('.slider-stack-next');
	const prevBtnStack = document.querySelector('.slider-stack-prev');

	if (sliderStack && nextBtnStack && prevBtnStack) {
		const moveNext = () => {
			const items = sliderStack.querySelectorAll('.main-slider-item-box');
			if (items.length) {
				sliderStack.appendChild(items[0]);
			}
		};

		const movePrev = () => {
			const items = sliderStack.querySelectorAll('.main-slider-item-box');
			if (items.length) {
				sliderStack.prepend(items[items.length - 1]);
			}
		};

		nextBtnStack.addEventListener('click', moveNext);
		prevBtnStack.addEventListener('click', movePrev);

		let autoSlider = setInterval(moveNext, 5500);

		const sliderArea = document.querySelector('.main-slider-area');
		if (sliderArea) {
			sliderArea.addEventListener('mouseenter', () => clearInterval(autoSlider));
			sliderArea.addEventListener('mouseleave', () => {
				autoSlider = setInterval(moveNext, 5500);
			});
		}
	}

	// Products Details Image Slides
	$('.products-details-image-slides').slick({
		dots: true,
		speed: 500,
		fade: false,
		slide: 'li',
		slidesToShow: 1,
		autoplay: true,
		autoplaySpeed: 4000,
		prevArrow: false,
		nextArrow: false,
		responsive: [{
			breakpoint: 800,
			settings: {
				arrows: false,
				centerMode: false,
				centerPadding: '40px',
				variableWidth: false,
				slidesToShow: 1,
				dots: true
			},
			breakpoint: 1200,
			settings: {
				arrows: false,
				centerMode: false,
				centerPadding: '40px',
				variableWidth: false,
				slidesToShow: 1,
				dots: true
			}
		}],
		customPaging: function (slider, i) {
			return '<button class="tab">' + $('.slick-thumbs li:nth-child(' + (i + 1) + ')').html() + '</button>';
		}
	});
	
	// Testimonial Slides
	$('.testimonial-slides').owlCarousel({
		loop: true,
		nav: true,
		dots: false,
		autoplayHoverPause: true,
		items: 1,
		smartSpeed: 100,
		autoplay: false,
		navText: [
			"<i class='flaticon-left-arrow'></i>",
			"<i class='flaticon-right-arrow'></i>"
		],
	});

	// Main Products Image Slides
	$('.slider-for').slick({
		slidesToShow: 1,
		slidesToScroll: 1,
		arrows: false,
		fade: true,
		asNavFor: '.slider-nav'
	});
	$('.slider-nav').slick({
		slidesToShow: 3,
		slidesToScroll: 1,
		asNavFor: '.slider-for',
		dots: false,
		arrows: false,
		focusOnSelect: true,
		verticalSwiping: true,
		vertical: true
	});
	$('a[data-slide]').on('click', function(e) {
		e.preventDefault();
		var slideno = $(this).data('slide');
		$('.slider-nav').slick('slickGoTo', slideno - 1);
	});
	
	// Tabs
	$('.tab ul.tabs').addClass('active').find('> li:eq(0)').addClass('current');
	$('.tab ul.tabs li a').on('click', function (g) {
		var tab = $(this).closest('.tab'), 
		index = $(this).closest('li').index();
		tab.find('ul.tabs > li').removeClass('current');
		$(this).closest('li').addClass('current');
		tab.find('.tab_content').find('div.tabs_item').not('div.tabs_item:eq(' + index + ')').slideUp();
		tab.find('.tab_content').find('div.tabs_item:eq(' + index + ')').slideDown();
		g.preventDefault();
	});

	// Count Time 
	function makeTimer() {
		var endTime = new Date("September 20, 2029 17:00:00 PDT");			
		var endTime = (Date.parse(endTime)) / 1000;
		var now = new Date();
		var now = (Date.parse(now) / 1000);
		var timeLeft = endTime - now;
		var days = Math.floor(timeLeft / 86400); 
		var hours = Math.floor((timeLeft - (days * 86400)) / 3600);
		var minutes = Math.floor((timeLeft - (days * 86400) - (hours * 3600 )) / 60);
		var seconds = Math.floor((timeLeft - (days * 86400) - (hours * 3600) - (minutes * 60)));
		if (hours < "10") { hours = "0" + hours; }
		if (minutes < "10") { minutes = "0" + minutes; }
		if (seconds < "10") { seconds = "0" + seconds; }
		$("#days").html(days + "<span>Days</span>");
		$("#hours").html(hours + "<span>Hours</span>");
		$("#minutes").html(minutes + "<span>Minutes</span>");
		$("#seconds").html(seconds + "<span>Seconds</span>");
	}
	setInterval(function() { makeTimer(); }, 0);

	// Products Filter Options
	$(".icon-view-two").on("click", function(e){
		e.preventDefault();
		document.getElementById("products-collections-filter").classList.add('products-col-two')
		document.getElementById("products-collections-filter").classList.remove('products-col-one', 'products-col-three', 'products-col-four', 'products-row-view');
	});
	$(".icon-view-three").on("click", function(e){
		e.preventDefault();
		document.getElementById("products-collections-filter").classList.add('products-col-three')
		document.getElementById("products-collections-filter").classList.remove('products-col-one', 'products-col-two', 'products-col-four', 'products-row-view');
	});
	$('.products-filter-options .view-column a').on('click', function(){
		$('.view-column a').removeClass("active");
		$(this).addClass("active");
	});
	
	// Range Slider - Solo inicializar si no está en tienda.php (donde se inicializa dinámicamente)
	if ($("#range-slider").length && !$("#range-slider").hasClass("ui-slider")) {
		$( "#range-slider" ).slider({
			range: true,
			min: 50,
			max: 400,
			values: [50, 400],
			slide: function( event, ui ) {
				$( "#price-amount" ).val( "Q" + ui.values[ 0 ] + " - Q" + ui.values[ 1 ] );
			}
		});
		$( "#price-amount" ).val( "Q" + $( "#range-slider" ).slider( "values", 0 ) +
		" - Q" + $( "#range-slider" ).slider( "values", 1 ) );
	}  

	// Popup Video
	$('.popup-youtube').magnificPopup({
		disableOn: 320,
		type: 'iframe',
		mainClass: 'mfp-fade',
		removalDelay: 160,
		preloader: false,
		fixedContentPos: false
	});

	// FAQ Accordion
	$('.accordion').find('.accordion-title').on('click', function(){
		$(this).toggleClass('active');
		$(this).next().slideToggle('fast')
		$('.accordion-content').not($(this).next()).slideUp('fast');
		$('.accordion-title').not($(this)).removeClass('active');		
	});

	// Odometer JS
	$('.odometer').appear(function(e) {
		var odo = $(".odometer");
		odo.each(function() {
			var countNumber = $(this).attr("data-count");
			$(this).html(countNumber);
		});
	});
	
	// Subscribe form
	$(".newsletter-form").validator().on("submit", function (event) {
		if (event.isDefaultPrevented()) {
			formErrorSub();
			submitMSGSub(false, "Please enter your email correctly.");
		} 
		else {
			event.preventDefault();
		}
	});
	function callbackFunction (resp) {
		if (resp.result === "success") {
			formSuccessSub();
		}
		else {
			formErrorSub();
		}
	}
	function formSuccessSub(){
		$(".newsletter-form")[0].reset();
		submitMSGSub(true, "Thank you for subscribing!");
		setTimeout(function() {
			$("#validator-newsletter").addClass('hide');
		}, 4000)
	}
	function formErrorSub(){
		$(".newsletter-form").addClass("animated shake");
		setTimeout(function() {
			$(".newsletter-form").removeClass("animated shake");
		}, 1000)
	}
	function submitMSGSub(valid, msg){
		if(valid){
			var msgClasses = "validation-success";
		} 
		else {
			var msgClasses = "validation-danger";
		}
		$("#validator-newsletter").removeClass().addClass(msgClasses).text(msg);
	}

	// AJAX MailChimp
	$(".newsletter-form").ajaxChimp({
		url: "https://envytheme.us20.list-manage.com/subscribe/post?u=60e1ffe2e8a68ce1204cd39a5&amp;id=42d6d188d9", 
		callback: callbackFunction
	});

	// Input Plus & Minus Number JS
	$('.input-counter').each(function() {
		var spinner = jQuery(this),
		input = spinner.find('input[type="text"]'),
		btnUp = spinner.find('.plus-btn'),
		btnDown = spinner.find('.minus-btn'),
		min = parseInt(input.attr('min'), 10),
		max = parseInt(input.attr('max'), 10);

		if (isNaN(min)) {
			min = 1;
		}

		if (isNaN(max)) {
			max = 999;
		}
		
		btnUp.on('click', function() {
			var oldValue = parseInt(input.val(), 10);
			var newVal = isNaN(oldValue) ? min : Math.min(max, oldValue + 1);
			spinner.find("input").val(newVal);
			spinner.find("input").trigger("change");
		});
		btnDown.on('click', function() {
			var oldValue = parseInt(input.val(), 10);
			var newVal = isNaN(oldValue) ? min : Math.max(min, oldValue - 1);
			spinner.find("input").val(newVal);
			spinner.find("input").trigger("change");
		});
	});
	
	// Go to Top JS
	$(window).on('scroll', function() {
		var scrolled = $(window).scrollTop();
		if (scrolled > 600) $('.go-top').addClass('active');
		if (scrolled < 600) $('.go-top').removeClass('active');
	});  
	$('.go-top').on('click', function() {
		$("html, body").animate({ scrollTop: "0" },  500);
	});
	
	// Preloader
	jQuery(window).on('load', function() {
		$('.preloader').fadeOut();
	});

}(jQuery));

// ============================================================
// FUNCIÓN GLOBAL PARA BUSCAR PRODUCTOS
// ============================================================
function normalizarTerminoBusqueda(valor) {
    return (valor || '').toString().replace(/\s+/g, ' ').trim();
}

function buscarProductos(event) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    const searchInput = document.getElementById('search');
    const categoriaSelect = document.getElementById('select-categoria-header');
    const termino = normalizarTerminoBusqueda(searchInput ? searchInput.value : '');
    const categoriaId = categoriaSelect ? categoriaSelect.value : '';
    
    if (!termino) {
        if (categoriaId) {
            window.location.href = 'tienda.php?categoria=' + encodeURIComponent(categoriaId);
            return;
        }

        alert('Por favor ingresa un término de búsqueda');
        return;
    }
    
    // Redirigir a tienda.php con el parámetro de búsqueda.
    window.location.href = 'tienda.php?buscar=' + encodeURIComponent(termino);
}

// Permitir búsqueda con Enter y restaurar valor de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        // Restaurar valor de búsqueda si existe en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const buscar = urlParams.get('buscar');
        if (buscar) {
            searchInput.value = buscar;
        }
        
        // Permitir búsqueda con Enter
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                buscarProductos(e);
            }
        });
    }
});

// function to set a given theme/color-scheme
function setTheme(themeName) {
    localStorage.setItem('ejon_theme', themeName);
    document.documentElement.className = themeName;
}
// function to toggle between light and dark theme
function toggleTheme() {
    if (localStorage.getItem('ejon_theme') === 'theme-dark') {
        setTheme('theme-light');
    } else {
        setTheme('theme-dark');
    }
}
// Immediately invoked function to set the theme on initial load
(function () {
    const slider = document.getElementById('slider');
    if (localStorage.getItem('ejon_theme') === 'theme-dark') {
        setTheme('theme-dark');
        if (slider) slider.checked = false;
    } else {
        setTheme('theme-light');
        if (slider) slider.checked = true;
    }
})();
