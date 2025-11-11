(function() {
    'use strict';

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

        const menu = root.querySelector('.bw-about-menu');
        const list = root.querySelector('.bw-about-menu__list');

        if (!menu || !list) {
            return;
        }

        const menuItems = Array.from(menu.querySelectorAll('.menu-item'));

        if (!menuItems.length) {
            return;
        }

        const parseSize = (value) => {
            const parsed = parseFloat(value);

            if (Number.isNaN(parsed)) {
                return 0;
            }

            return parsed;
        };

        const updateMenuWidth = (callback) => {
            window.requestAnimationFrame(() => {
                if (typeof callback === 'function') {
                    callback();
                }
            });
        };

        const readSpotlightSize = () => {
            const menuStyle = window.getComputedStyle(menu);
            const fromVar = menuStyle.getPropertyValue('--spotlight-size').trim();

            if (fromVar) {
                const parsedFromVar = parseFloat(fromVar);

                if (!Number.isNaN(parsedFromVar) && parsedFromVar > 0) {
                    return parsedFromVar;
                }
            }

            const pseudoStyles = window.getComputedStyle(menu, '::before');
            const widthValue = pseudoStyles.getPropertyValue('width').trim();
            const parsedWidth = parseFloat(widthValue);

            if (!Number.isNaN(parsedWidth) && parsedWidth > 0) {
                return parsedWidth;
            }

            return 120;
        };

        const updateSpotlight = (menuItem) => {
            if (!menuItem) {
                return;
            }

            const itemRect = menuItem.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            const spotlightWidth = readSpotlightSize();
            const centeredOffset = itemRect.left - menuRect.left + (itemRect.width / 2) - (spotlightWidth / 2);
            const menuWidth = Math.max(menu.clientWidth, menuRect.width);
            const maxOffset = Math.max(0, menuWidth - spotlightWidth);
            const clampedOffset = Math.max(0, Math.min(centeredOffset, maxOffset));

            menu.style.setProperty('--spotlight-x', `${clampedOffset}px`);
        };

        const getInitialItem = () => {
            const current = list.querySelector('.current-menu-item');

            if (current instanceof HTMLElement) {
                return current;
            }

            return menuItems[0];
        };

        let activeItem = getInitialItem();
        updateMenuWidth(() => {
            updateSpotlight(activeItem);
        });

        const handleEnter = (event) => {
            const targetItem = event.currentTarget.closest('.menu-item');

            if (!targetItem) {
                return;
            }

            activeItem = targetItem;
            updateSpotlight(activeItem);
        };

        const handleLeave = () => {
            updateSpotlight(activeItem);
        };

        const handleFocus = (event) => {
            const targetItem = event.currentTarget.closest('.menu-item');

            if (!targetItem) {
                return;
            }

            activeItem = targetItem;
            updateSpotlight(activeItem);
        };

        const handleBlur = () => {
            window.requestAnimationFrame(() => {
                const focused = root.querySelector('.menu-item > a:focus, .menu-item > .bw-about-menu__link:focus');
                if (!focused) {
                    updateSpotlight(activeItem);
                }
            });
        };

        const interactiveItems = menuItems.reduce((accumulator, menuItem) => {
            const link = menuItem.querySelector('a, .bw-about-menu__link');

            if (link instanceof HTMLElement) {
                if (!link.classList.contains('bw-about-menu__link')) {
                    link.classList.add('bw-about-menu__link');
                }

                link.addEventListener('mouseenter', handleEnter);
                link.addEventListener('focus', handleFocus);
                link.addEventListener('blur', handleBlur);
                accumulator.push(link);
            }

            return accumulator;
        }, []);

        if (!interactiveItems.length) {
            root.addEventListener('bw-about-menu-destroy', () => {
                menu.style.removeProperty('--spotlight-x');
            }, { once: true });
            return;
        }

        list.addEventListener('mouseleave', handleLeave);

        const handleResize = () => {
            updateMenuWidth(() => {
                if (activeItem && document.body.contains(activeItem)) {
                    updateSpotlight(activeItem);
                }
            });
        };

        let resizeObserver = null;

        if ('ResizeObserver' in window) {
            resizeObserver = new ResizeObserver(handleResize);
            resizeObserver.observe(menu);
            resizeObserver.observe(list);
        }

        let mutationObserver = null;

        if ('MutationObserver' in window) {
            mutationObserver = new MutationObserver(() => {
                updateMenuWidth(() => {
                    if (activeItem && document.body.contains(activeItem)) {
                        updateSpotlight(activeItem);
                    }
                });
            });

            mutationObserver.observe(root, { attributes: true, attributeFilter: ['style', 'class'] });
            mutationObserver.observe(menu, { attributes: true, attributeFilter: ['style', 'class'] });
        }

        window.addEventListener('resize', handleResize);

        root.addEventListener('bw-about-menu-destroy', () => {
            interactiveItems.forEach((item) => {
                item.removeEventListener('mouseenter', handleEnter);
                item.removeEventListener('focus', handleFocus);
                item.removeEventListener('blur', handleBlur);
            });
            list.removeEventListener('mouseleave', handleLeave);
            if (resizeObserver) {
                resizeObserver.disconnect();
            }
            if (mutationObserver) {
                mutationObserver.disconnect();
            }
            window.removeEventListener('resize', handleResize);
            menu.style.removeProperty('--spotlight-x');
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
