/* BlackWork Modular Loading System (BWLS) */
(function ($) {
    'use strict';

    /**
     * Start global top bar loader
     */
    function startTopBar() {
        let $bar = $('.bw-top-bar-loader');
        if (!$bar.length) {
            $bar = $('<div class="bw-top-bar-loader"></div>').appendTo('body');
        }

        $bar.stop().show().css('width', '0%');
        $bar.animate({ width: '70%' }, 800);
    }

    /**
     * Complete global top bar loader
     */
    function completeTopBar() {
        const $bar = $('.bw-top-bar-loader');
        if (!$bar.length) return;

        $bar.stop().animate({ width: '100%' }, 200, function () {
            $bar.fadeOut(300);
        });
    }

    /**
     * Inject Checkout Order Summary Skeleton
     */
    function injectCheckoutSkeleton() {
        const $target = $('#order_review_inner');
        if (!$target.length) return;

        // Prevent layout jump by preserving height
        const currentHeight = $target.height();
        $target.css({
            'min-height': currentHeight + 'px',
            'position': 'relative'
        });

        // Check if skeleton already exists
        if ($target.find('.bw-checkout-skeleton').length) return;

        const skeletonHtml = `
            <div class="bw-checkout-skeleton">
                <div class="bw-skeleton-item">
                    <div class="bw-shimmer bw-skeleton-thumb"></div>
                    <div class="bw-skeleton-content">
                        <div class="bw-shimmer-text" style="width: 60%"></div>
                        <div class="bw-shimmer-text" style="width: 40%"></div>
                    </div>
                </div>
                <div class="bw-skeleton-item">
                    <div class="bw-shimmer bw-skeleton-thumb"></div>
                    <div class="bw-skeleton-content">
                        <div class="bw-shimmer-text" style="width: 50%"></div>
                        <div class="bw-shimmer-text" style="width: 30%"></div>
                    </div>
                </div>
                <div class="bw-skeleton-total">
                    <div class="bw-shimmer-text" style="width: 100%"></div>
                </div>
            </div>
        `;

        $target.append(skeletonHtml);
        $target.find('.bw-checkout-skeleton').hide().fadeIn(200);
    }

    /**
     * Remove Checkout Skeleton
     */
    function removeCheckoutSkeleton() {
        const $target = $('#order_review_inner');
        $target.find('.bw-checkout-skeleton').fadeOut(200, function () {
            $(this).remove();
            $target.css('min-height', '');
        });
    }

    $(document).ready(function () {
        // 1. Initial Page Progress
        startTopBar();

        // Complete when everything is loaded
        $(window).on('load', function () {
            completeTopBar();
            $('body').addClass('bw-page-ready');
            setTimeout(() => {
                document.documentElement.classList.remove('bw-page-loading');
            }, 600);
        });

        // Fallback for page-ready
        setTimeout(function () {
            if (!$('body').hasClass('bw-page-ready')) {
                $('body').addClass('bw-page-ready');
                completeTopBar();
                document.documentElement.classList.remove('bw-page-loading');
            }
        }, 3000);

        // 2. Catch WooCommerce AJAX events
        $(document.body).on('update_checkout', function () {
            startTopBar();
            // Inject skeleton for specific "refresh" actions
            injectCheckoutSkeleton();
        });

        $(document.body).on('updated_checkout', function () {
            completeTopBar();
            removeCheckoutSkeleton();
        });

        $(document.body).on('adding_to_cart', function () {
            startTopBar();
        });

        $(document.body).on('added_to_cart', function () {
            completeTopBar();
        });

        // 3. Expose global helper for other widgets
        window.BWLS = {
            startLoading: function ($container) {
                $container.addClass('bw-is-loading');
                if (!$container.find('.bw-glass-overlay').length) {
                    $container.append('<div class="bw-glass-overlay"><div class="bw-gradient-spinner"></div></div>');
                }
            },
            stopLoading: function ($container) {
                $container.removeClass('bw-is-loading');
            },
            startProgress: startTopBar,
            stopProgress: completeTopBar,
            injectSkeleton: function ($container, type) {
                if (type === 'checkout') injectCheckoutSkeleton();
            },
            removeSkeleton: function () {
                removeCheckoutSkeleton();
            }
        };
    });

    // Handle the page fade class
    document.documentElement.classList.add('bw-page-loading');

})(jQuery);
