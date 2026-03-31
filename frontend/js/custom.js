/* 
author: Boostraptheme
author URL: https://boostraptheme.com
License: Creative Commons Attribution 4.0 Unported
License URL: https://creativecommons.org/licenses/by/4.0/
*/ 

// ====================================================
// ANIMATION
// ====================================================
$(function () {
    if (typeof WOW === "function") {
        new WOW().init();
    }
});

// ====================================================
// NAVIGATION
// ====================================================
(function($) {
"use strict";

// Smooth scroll
$('a.js-scroll-trigger[href*="#"]:not([href="#"])').click(function() {
    var target = $(this.hash);
    target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
    if (target.length) {
        $('html, body').animate({
            scrollTop: target.offset().top
        }, 1000);
        return false;
    }
});

// Collapse navbar on click
$('.js-scroll-trigger').click(function() {
    $('.navbar-collapse').collapse('hide');
});

// Search bar toggle
$('.search').on("click", function () {
    if ($('.search-btn').hasClass('fa-search')) {
        $('.search-open').fadeIn(300);
        $('.search-btn').removeClass('fa-search').addClass('fa-times');
    } else {
        $('.search-open').fadeOut(300);
        $('.search-btn').addClass('fa-search').removeClass('fa-times');
    }
});

// Fixed navbar (affix)
var toggleAffix = function(affixElement, scrollElement, wrapper) {
    var height = affixElement.outerHeight(),
        top = wrapper.offset().top;

    if (scrollElement.scrollTop() >= top){
        wrapper.height(height);
        affixElement.addClass("affix");
    } else {
        affixElement.removeClass("affix");
        wrapper.height('auto');
    }
};

$('[data-toggle="affix"]').each(function() {
    var ele = $(this),
        wrapper = $('<div></div>');
    ele.before(wrapper);
    $(window).on('scroll resize', function() {
        toggleAffix(ele, $(this), wrapper);
    });
    toggleAffix(ele, $(window), wrapper);
});

// Hover dropdown
$('ul.navbar-nav li.dropdown').hover(
    function() {
        $(this).find('.dropdown-menu').stop(true, true).delay(200).fadeIn(300);
    },
    function() {
        $(this).find('.dropdown-menu').stop(true, true).delay(200).fadeOut(300);
    }
);

})(jQuery);

// ====================================================
// AUTO WRITER
// ====================================================
var TxtType = function(el, toRotate, period) {
    this.toRotate = toRotate;
    this.el = el;
    this.loopNum = 0;
    this.period = parseInt(period, 10) || 2000;
    this.txt = '';
    this.isDeleting = false;
    this.tick();
};

TxtType.prototype.tick = function() {
    var i = this.loopNum % this.toRotate.length;
    var fullTxt = this.toRotate[i];

    this.txt = this.isDeleting
        ? fullTxt.substring(0, this.txt.length - 1)
        : fullTxt.substring(0, this.txt.length + 1);

    this.el.innerHTML = '<span class="wrap">' + this.txt + '</span>';

    var delta = 200 - Math.random() * 100;
    if (this.isDeleting) delta /= 2;

    if (!this.isDeleting && this.txt === fullTxt) {
        delta = this.period;
        this.isDeleting = true;
    } 
    else if (this.isDeleting && this.txt === '') {
        this.isDeleting = false;
        this.loopNum++;
        delta = 500;
    }

    setTimeout(() => this.tick(), delta);
};

window.onload = function() {
    var elements = document.getElementsByClassName('typewrite');
    for (var i = 0; i < elements.length; i++) {
        var toRotate = elements[i].getAttribute('data-type');
        var period = elements[i].getAttribute('data-period');
        if (toRotate) {
            new TxtType(elements[i], JSON.parse(toRotate), period);
        }
    }
};

// ====================================================
// HOME / UI COMPONENTS
// ====================================================
$('.carousel').carousel();

// Blog hover
$(document).ready(function() {
    $('.thumbnail-blogs').hover(
        function(){ $(this).find('.caption').slideDown(250); },
        function(){ $(this).find('.caption').slideUp(200); }
    );
});

// Thoughts / clients carousel (nếu có owlCarousel)
if (typeof $.fn.owlCarousel === "function") {

    $("#clients-list").owlCarousel({
        items: 6,
        autoplay: true,
        smartSpeed: 700,
        loop: true,
        dots: false,
        responsive: {
            0:{items:1},
            480:{items:3},
            768:{items:5},
            992:{items:6}
        }
    });

    $('#thought-desc').owlCarousel({
        items: 1,
        autoplay: true,
        smartSpeed: 700,
        loop: true
    });

    $('#customers-testimonials').owlCarousel({
        items: 1,
        autoplay: true,
        smartSpeed: 700,
        loop: true
    });
}

// ====================================================
// BACK TO TOP
// ====================================================
$(window).scroll(function () {
    if ($(this).scrollTop() < 50) {
        $("nav").removeClass("vesco-top-nav");
        $("#back-to-top").fadeOut();
    } else {
        $("nav").addClass("vesco-top-nav");
        $("#back-to-top").fadeIn();
    }
});

