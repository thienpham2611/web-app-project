/*
author: Boostraptheme
author URL: https://boostraptheme.com
License: Creative Commons Attribution 4.0 Unported
License URL: https://creativecommons.org/licenses/by/4.0/
*/

$(document).ready(function () {

    'use strict';

    // =======================================================
    // CONFIG (QUAN TRỌNG)
    // =======================================================
    const API_BASE  = '/web-app-project/backend/api';
    const FRONTEND_BASE = '/web-app-project/frontend';

    // ------------------------------------------------------- //
    // For demo purposes only
    // ------------------------------------------------------- //
    if ($.cookie("theme_csspath")) {
        $('link#theme-stylesheet').attr("href", $.cookie("theme_csspath"));
    }

    $("#colour").change(function () {

        if ($(this).val() !== '') {

            var theme_csspath = 'css/style.' + $(this).val() + '.css';

            $('link#theme-stylesheet').attr("href", theme_csspath);

            $.cookie("theme_csspath", theme_csspath, {
                expires: 365,
                path: document.URL.substr(0, document.URL.lastIndexOf('/'))
            });
        }

        return false;
    });

    // ------------------------------------------------------- //
    // Search Box
    // ------------------------------------------------------- //
    $('#search').on('click', function (e) {
        e.preventDefault();
        $('.search-box').fadeIn();
    });

    $('.dismiss').on('click', function () {
        $('.search-box').fadeOut();
    });

    // ------------------------------------------------------- //
    // Card Close
    // ------------------------------------------------------- //
    $('.card-close a.remove').on('click', function (e) {
        e.preventDefault();
        $(this).parents('.card').fadeOut();
    });

    // ------------------------------------------------------- //
    // Dropdown fade
    // ------------------------------------------------------- //
    $('.dropdown').on('show.bs.dropdown', function () {
        $(this).find('.dropdown-menu').first().stop(true, true).fadeIn();
    });

    $('.dropdown').on('hide.bs.dropdown', function () {
        $(this).find('.dropdown-menu').first().stop(true, true).fadeOut();
    });

    // ------------------------------------------------------- //
    // Sidebar toggle
    // ------------------------------------------------------- //
    $('#toggle-btn').on('click', function (e) {
        e.preventDefault();

        $(this).toggleClass('active');
        $('.side-navbar').toggleClass('shrinked');
        $('.content-inner').toggleClass('active');

        if ($(window).outerWidth() > 1183) {
            if ($(this).hasClass('active')) {
                $('.navbar-header .brand-small').hide();
                $('.navbar-header .brand-big').show();
            } else {
                $('.navbar-header .brand-small').show();
                $('.navbar-header .brand-big').hide();
            }
        }

        if ($(window).outerWidth() < 1183) {
            $('.navbar-header .brand-small').show();
        }
    });

    // ------------------------------------------------------- //
    // Input animation
    // ------------------------------------------------------- //
    $('input.input-material').on('focus', function () {
        $(this).siblings('.label-material').addClass('active');
    });

    $('input.input-material').on('blur', function () {
        if ($(this).val() !== '') {
            $(this).siblings('.label-material').addClass('active');
        } else {
            $(this).siblings('.label-material').removeClass('active');
        }
    });

    // ------------------------------------------------------- //
    // External links
    // ------------------------------------------------------- //
    $('.external').on('click', function (e) {
        e.preventDefault();
        window.open($(this).attr("href"));
    });

    // =======================================================
    // LOGOUT (ĐÃ SỬA – CHẠY CHẮC 100%)
    // =======================================================
    $('#logout-btn').on('click', function (e) {
        e.preventDefault();

        if (!confirm('Bạn có chắc muốn đăng xuất?')) return;

        $.ajax({
            url: API_BASE + '/logout.php',
            method: 'POST',
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    // quay về trang login / index frontend
                    window.location.href = FRONTEND_BASE + '/index.html';
                } else {
                    alert(res.message || 'Logout failed');
                }
            },
            error: function () {
                alert('Không kết nối được server');
            }
        });
    });

});

// =======================================================
// FULLSCREEN
// =======================================================
function toggleFullScreen(elem) {

    if (
        !document.fullscreenElement &&
        !document.mozFullScreenElement &&
        !document.webkitFullscreenElement &&
        !document.msFullscreenElement
    ) {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) {
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
}
