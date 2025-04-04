$(document).ready(function () {
    var tmNav = $('#tm-nav ul');
    var menuToggle = $('#menu-toggle');

    // Ensure the menu is always visible on PC but hidden initially on mobile
    function ensureMenuVisibility() {
        if (window.innerWidth < 768) {
            tmNav.hide(); // Hide menu initially on mobile
        } else {
            tmNav.show(); // Show menu on PC
        }
    }

    ensureMenuVisibility();
    $(window).on('resize', ensureMenuVisibility);

    // Toggle menu on mobile when clicking the toggler
    menuToggle.click(function () {
        tmNav.slideToggle();
    });

    // Hide menu when clicking a nav item (only for mobile)
    tmNav.find('li a').click(function () {
        if (window.innerWidth < 768) {
            tmNav.slideUp();
        }
    });

    // Detect scroll direction for navbar styling
    $(window).scroll(function () {
        var tmNavBar = $('#tm-nav');
        var distanceFromTop = $(document).scrollTop();

        if (distanceFromTop === 0) {
            tmNavBar.removeClass('scrolled-down').addClass('top');
        } else {
            tmNavBar.removeClass('top').addClass('scrolled-down');
        }
    });

    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function (e) {
        e.preventDefault();
        $('html, body').animate({
            scrollTop: $($(this).attr('href')).offset().top
        }, 500);
    });

    // Initialize parallax effect
    $('.parallax-window').each(function () {
        var isMobile = window.innerWidth <= 768;
        if (!isMobile) {
            $(this).parallax({ speed: 0.5 });
        } else {
            var $this = $(this);
            $(window).on('scroll', function () {
                var scrollTop = $(window).scrollTop();
                var offset = $this.offset().top;
                var height = $this.outerHeight();
                if (scrollTop + $(window).height() > offset && scrollTop < offset + height) {
                    var translateValue = (scrollTop - offset) * 0.5;
                    $this.css('background-position', 'center ' + translateValue + 'px');
                }
            });
        }
    });
});


