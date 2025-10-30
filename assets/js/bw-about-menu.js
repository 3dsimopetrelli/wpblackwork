(function() {
    'use strict';

    const SPOTLIGHT_OPACITY_ACTIVE = 1;

    const getDomElement = (scope) => {
        if (!scope) {
            return null;
        }

        if (scope instanceof Element) {
            return scope;
        }

        if (scope[0] instanceof Element) {
            return scope[0];
        }

        if (scope.$el && scope.$el[0] instanceof Element) {
            return scope.$el[0];
        }

        if (scope.el instanceof Element) {
            return scope.el;
        }

        return null;
    };

    const initAboutMenu = function(scope) {
        const root = getDomElement(scope);

        if (!root) {
            return;
        }

        const container = root.querySelector('.bw-about-menu');
        const list = root.querySelector('.bw-about-menu__list');

        if (!container || !list) {
            return;
        }

        const items = Array.from(list.querySelectorAll('.menu-item > a, .menu-item > .bw-about-menu__link'));

        if (!items.length) {
            return;
        }

        const updateSpotlight = (target) => {
            if (!target) {
                list.style.setProperty('--spotlight-opacity', '0');
                return;
            }

            const itemRect = target.getBoundingClientRect();
            const listRect = list.getBoundingClientRect();
            const offset = itemRect.left - listRect.left;

            list.style.setProperty('--spotlight-x', `${offset}px`);
            list.style.setProperty('--spotlight-width', `${itemRect.width}px`);
            list.style.setProperty('--spotlight-opacity', `${SPOTLIGHT_OPACITY_ACTIVE}`);
        };

        const getInitialTarget = () => {
            const current = list.querySelector('.current-menu-item > a, .current-menu-item > .bw-about-menu__link');
            if (current) {
                return current;
            }

            return items[0];
        };

        let activeTarget = getInitialTarget();
        updateSpotlight(activeTarget);

        const handleEnter = (event) => {
            activeTarget = event.currentTarget;
            updateSpotlight(activeTarget);
        };

        const handleLeave = () => {
            updateSpotlight(activeTarget);
        };

        const handleFocus = (event) => {
            activeTarget = event.currentTarget;
            updateSpotlight(activeTarget);
        };

        const handleBlur = () => {
            window.requestAnimationFrame(() => {
                const focused = root.querySelector('.menu-item > a:focus, .menu-item > .bw-about-menu__link:focus');
                if (!focused) {
                    updateSpotlight(activeTarget);
                }
            });
        };

        items.forEach((item) => {
            if (!item.classList.contains('bw-about-menu__link')) {
                item.classList.add('bw-about-menu__link');
            }
            item.addEventListener('mouseenter', handleEnter);
            item.addEventListener('focus', handleFocus);
            item.addEventListener('blur', handleBlur);
        });

        list.addEventListener('mouseleave', handleLeave);

        const handleResize = () => {
            if (activeTarget && document.body.contains(activeTarget)) {
                updateSpotlight(activeTarget);
            }
        };

        let resizeObserver = null;

        if ('ResizeObserver' in window) {
            resizeObserver = new ResizeObserver(handleResize);
            resizeObserver.observe(list);
        }

        window.addEventListener('resize', handleResize);

        root.addEventListener('bw-about-menu-destroy', () => {
            items.forEach((item) => {
                item.removeEventListener('mouseenter', handleEnter);
                item.removeEventListener('focus', handleFocus);
                item.removeEventListener('blur', handleBlur);
            });
            list.removeEventListener('mouseleave', handleLeave);
            if (resizeObserver) {
                resizeObserver.disconnect();
            }
            window.removeEventListener('resize', handleResize);
        }, { once: true });
    };

    const destroyScope = (scope) => {
        const root = getDomElement(scope);
        if (!root) {
            return;
        }

        const event = new CustomEvent('bw-about-menu-destroy');
        root.dispatchEvent(event);
    };

    const initOnElements = (elements) => {
        elements.forEach((element) => {
            destroyScope(element);
            initAboutMenu(element);
        });
    };

    const onDocumentReady = () => {
        const elements = document.querySelectorAll('.elementor-widget-bw-about-menu');
        if (elements.length) {
            initOnElements(elements);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onDocumentReady);
    } else {
        onDocumentReady();
    }

    if (window.elementorFrontend) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/bw-about-menu.default', (scope) => {
            initAboutMenu(scope);
        });
    }
})();
