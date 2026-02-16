/**
 * BW Cart Pop-Up JavaScript
 *
 * Gestisce le interazioni del pannello cart pop-up
 *
 * @package BW_Cart_Popup
 * @version 1.0.0
 */

(function ($) {
    'use strict';

    // Oggetto principale
    const BW_CartPopup = {
        // Elementi DOM
        $overlay: null,
        $panel: null,
        $itemsContainer: null,
        $emptyState: null,
        $fullContent: null,
        $footer: null,
        $promoBox: null,
        $promoTrigger: null,
        $promoMessage: null,
        $promoRemoveWrapper: null,
        $promoRemoveLink: null,
        $closeBtn: null,
        $continueBtn: null,
        $cartBadge: null,
        $cartIconContainer: null,
        $floatingTrigger: null,
        $floatingBadge: null,
        $loadingState: null,
        $notification: null,
        checkoutBaseText: '',
        floatingHasItems: false,

        // Stato
        isOpen: false,
        isLoading: false,
        lastAddedButton: null,
        cartItems: [],
        appliedCoupons: [],

        /**
         * Inizializzazione
         */
        init: function () {
            // Inizializza elementi DOM
            this.$overlay = $('#bw-cart-popup-overlay');
            this.$panel = $('#bw-cart-popup-panel');
            this.$itemsContainer = $('.bw-cart-popup-items');
            this.$emptyState = $('.bw-cart-popup-empty-state');
            this.$fullContent = $('.bw-cart-popup-content');
            this.$footer = $('.bw-cart-popup-footer');
            this.$promoBox = $('.bw-cart-popup-promo-box');
            this.$promoTrigger = $('.bw-promo-link');
            this.$promoMessage = $('.bw-promo-message');
            this.$promoRemoveWrapper = $('.bw-promo-remove-wrapper');
            this.$promoRemoveLink = $('.bw-promo-remove-link');
            this.$closeBtn = $('.bw-cart-popup-close');
            this.$continueBtn = $('.bw-cart-popup-continue');
            this.$cartBadge = $('.bw-cart-badge');
            this.$cartIconContainer = $('.bw-cart-popup-header-icon');
            this.$floatingTrigger = $('.bw-cart-floating-trigger');
            this.$floatingBadge = $('.bw-cart-floating-badge');
            this.$loadingState = $('.bw-cart-popup-loading');
            this.$notification = $('.bw-cart-popup-notification');
            this.checkoutBaseText = this.$footer.find('.bw-cart-popup-checkout').data('base-text') || this.$footer.find('.bw-cart-popup-checkout').text().trim();

            // Bind eventi
            this.bindEvents();

            // Garantisce che l'anchor del checkout punti sempre alla URL WooCommerce corretta
            this.ensureCheckoutLink();

            // Registra listener per evento WooCommerce 'added_to_cart'
            // IMPORTANTE: Questo deve essere sempre registrato per supportare sia
            // i pulsanti globali che i widget
            this.listenToAddedToCart();

            // Intercetta pulsanti "Add to Cart" globali se l'opzione slide_animation è attiva
            if (bwCartPopupConfig.settings.active && bwCartPopupConfig.settings.slide_animation) {
                this.interceptAddToCart();
            }

            // Intercetta pulsanti dei widget con data attribute (sempre attivo per i widget)
            this.interceptWidgetAddToCart();

            // Rimuovi automaticamente i link "View cart" di WooCommerce
            this.removeViewCartLinks();

            // Monitora il carrello per aggiornare lo stato dei pulsanti
            this.monitorCartChanges();

            // Aggiorna badge e trigger floating se abilitato
            if (bwCartPopupConfig.settings.show_floating_trigger) {
                this.setupFloatingTriggerWatcher();
                this.loadCartContents(true);
            }

            console.log('BW Cart Pop-Up initialized');
        },

        /**
         * Bind eventi
         */
        bindEvents: function () {
            const self = this;

            // Chiudi pannello al click sull'overlay
            this.$overlay.on('click', function () {
                self.closePanel();
            });

            // Chiudi pannello al click sul pulsante X
            // Usa un listener delegato per coprire eventuali re-render del markup
            $(document).on('click', '.bw-cart-popup-close', function (e) {
                e.preventDefault();
                self.closePanel();
            });

            // Forza sempre il redirect verso la pagina checkout quando si clicca sul pulsante verde
            $(document).on('click', '.bw-cart-popup-checkout', function (e) {
                const checkoutUrl = (bwCartPopupConfig && bwCartPopupConfig.checkoutUrl) || $(this).attr('href');

                if (checkoutUrl) {
                    e.preventDefault();
                    window.location.href = checkoutUrl;
                }
            });

            // Toggle promo code box
            this.$promoTrigger.on('click', function (e) {
                e.preventDefault();
                self.togglePromoBox();
            });

            // Apertura da pulsante flottante
            $(document).on('click', '.bw-cart-floating-trigger', function (e) {
                e.preventDefault();
                self.openPanel();
            });

            // Applica coupon
            $('.bw-promo-apply').on('click', function (e) {
                e.preventDefault();
                self.applyCoupon();
            });

            // Gestione visibilità trigger flottante su scroll
            if (bwCartPopupConfig.settings.show_floating_trigger) {
                $(window).on('scroll.bwCartPopup', function () {
                    self.onFloatingScroll();
                });
            }

            // Enter per applicare coupon
            $('.bw-promo-input').on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.applyCoupon();
                }
            });

            // Floating label promo input (stile checkout/my-account)
            $(document).on('input change keyup blur focus', '.bw-promo-input', function () {
                self.syncPromoFloatingLabel($(this));
            });

            // Rimuovi coupon (Legacy link)
            this.$promoRemoveLink.on('click', function (e) {
                e.preventDefault();
                self.removeCoupon();
            });

            // Rimuovi coupon (Nuovo pulsante dinamico per riga)
            // Delegato al container dei totali o document
            $('.bw-cart-popup-totals').on('click', '.bw-remove-coupon-btn-dynamic', function (e) {
                e.preventDefault();
                var code = $(this).data('code');
                self.removeCoupon(code);
            });

            // Rimuovi prodotto dal carrello
            this.$itemsContainer.on('click', '.bw-cart-item-remove', function (e) {
                e.preventDefault();
                const cartItemKey = $(this).data('cart-item-key');
                self.removeItem(cartItemKey);
            });

            // Controlli quantità (+/-)
            this.$itemsContainer.on('click', '.bw-qty-btn', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const $stepper = $btn.closest('.bw-qty-stepper');
                const cartItemKey = $stepper.data('cart-item-key');
                const delta = parseInt($btn.data('delta'), 10);
                const $value = $stepper.find('.bw-qty-value');
                const currentQty = parseInt($value.text(), 10) || 1;
                const newQty = Math.max(currentQty + delta, 0);

                if (typeof cartItemKey === 'undefined') {
                    return;
                }

                $value.text(newQty);
                self.updateQuantity(cartItemKey, newQty);
            });

            // Chiudi con ESC
            $(document).on('keyup', function (e) {
                if (e.key === 'Escape' && self.isOpen) {
                    self.closePanel();
                }
            });

            // Stato iniziale label
            this.syncPromoFloatingLabel($('.bw-promo-input'));
        },

        /**
         * Aggiorna stato floating label del promo input
         */
        syncPromoFloatingLabel: function ($input) {
            if (!$input || !$input.length) {
                return;
            }

            $input.each(function () {
                const $field = $(this);
                const $wrapper = $field.closest('.bw-coupon-input-wrapper');
                if (!$wrapper.length) {
                    return;
                }

                const hasValue = ($field.val() || '').toString().trim() !== '';
                const $label = $wrapper.find('.bw-floating-label');
                $wrapper.toggleClass('has-value', hasValue);

                if ($label.length) {
                    const shortText = $label.attr('data-short');
                    const fullText = $label.attr('data-full');
                    if (hasValue && shortText) {
                        $label.text(shortText);
                    } else if (!hasValue && fullText) {
                        $label.text(fullText);
                    }
                }
            });
        },

        /**
         * Recupera l'URL di checkout seguendo le API WooCommerce e i fallback HTML
         */
        getCheckoutUrl: function () {
            const config = (typeof bwCartPopupConfig !== 'undefined') ? bwCartPopupConfig : {};
            const $button = $('.bw-cart-popup-checkout');

            const checkoutFromConfig = config.checkoutUrl;
            const checkoutFromData = $button.data('checkout-url');
            const checkoutFromHref = $button.attr('href');
            const cartFallback = config.cartUrl || $button.data('cart-url');

            if (checkoutFromConfig) {
                return checkoutFromConfig;
            }

            if (checkoutFromData) {
                return checkoutFromData;
            }

            if (checkoutFromHref) {
                return checkoutFromHref;
            }

            return cartFallback || null;
        },

        /**
         * Aggiorna l'anchor del checkout con l'URL corretto da WooCommerce
         * FORZA SEMPRE l'uso di wc_get_checkout_url() ignorando qualsiasi valore presente nell'HTML
         */
        ensureCheckoutLink: function () {
            // Usa SEMPRE il checkout URL da WooCommerce passato dal PHP
            const checkoutUrl = (bwCartPopupConfig && bwCartPopupConfig.checkoutUrl) || '/checkout/';

            // FORZA il checkout URL sul pulsante, sovrascrivendo qualsiasi valore errato
            $('.bw-cart-popup-checkout')
                .attr('href', checkoutUrl)
                .data('checkout-url', checkoutUrl);

            console.log('Checkout button forced to:', checkoutUrl);
        },

        /**
         * Registra listener per evento WooCommerce 'added_to_cart'
         * Questo listener viene sempre registrato per garantire che il cart popup
         * si apra quando un prodotto viene aggiunto al carrello
         */
        listenToAddedToCart: function () {
            const self = this;

            // Intercetta l'evento WooCommerce 'added_to_cart' (dopo aggiunta al carrello)
            // Questo evento viene triggerato da WooCommerce per tutti i tipi di Add to Cart
            $(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
                // Controlla se il pulsante che ha triggerato l'evento ha data-open-cart-popup="1"
                // (significa che è un widget con l'opzione attiva)
                const isWidgetWithPopup = $button && $button.attr('data-open-cart-popup') === '1';

                // Salva il riferimento al pulsante per poter cambiare il testo
                if ($button && $button.length) {
                    self.lastAddedButton = $button;

                    // Cambia il testo del pulsante da "Add to Cart" a "Added to cart"
                    self.changeButtonTextToAdded($button);
                }

                // Apri il popup se:
                // - L'opzione slide_animation è attiva (pulsanti globali), OPPURE
                // - Il pulsante è un widget con l'opzione cart popup attiva
                if (bwCartPopupConfig.settings.slide_animation || isWidgetWithPopup) {
                    if (typeof self.closeErrorModal === 'function') {
                        self.closeErrorModal();
                    }
                    self.openPanel();

                    // Mostra la notifica verde "Your item has been added to the cart"
                    self.showAddedNotification();

                    // Aggiorna eventuali href del checkout in caso di markup rimpiazzato da frammenti WooCommerce
                    self.ensureCheckoutLink();

                    console.log('Product added to cart, opening cart popup');
                }

                // Aggiorna badge e trigger flottante anche quando il pannello non si apre
                if (bwCartPopupConfig.settings.show_floating_trigger) {
                    self.loadCartContents(true);
                }
            });
        },

        /**
         * Intercetta i pulsanti "Add to Cart" standard di WooCommerce
         * Questa funzione viene chiamata solo se l'opzione "slide_animation" è attiva
         */
        interceptAddToCart: function () {
            const self = this;

            // 1. Previeni il redirect per pulsanti non-AJAX e convertili in chiamate AJAX
            // Alcuni temi o configurazioni potrebbero avere pulsanti "Add to Cart" come link diretti
            $(document).on('click', 'a.add_to_cart_button', function (e) {
                const $btn = $(this);

                // Se il pulsante ha un href che contiene 'add-to-cart' e non è AJAX
                if ($btn.attr('href') && $btn.attr('href').indexOf('add-to-cart') !== -1) {
                    // Se non è già un pulsante AJAX, previeni il comportamento default e usa AJAX
                    if (!$btn.hasClass('ajax_add_to_cart')) {
                        e.preventDefault();

                        // Ottieni l'ID del prodotto
                        const productId = $btn.data('product_id') || $btn.attr('data-product_id');

                        if (productId) {
                            // Aggiungi prodotto via AJAX (triggerà poi l'evento 'added_to_cart')
                            self.addToCartAjax(productId, 1, $btn);
                        }
                    }
                }
            });

            // 2. Intercetta il link "View Cart" che appare dopo l'aggiunta di un prodotto
            // WooCommerce aggiunge dinamicamente questo link accanto al pulsante "Add to Cart"
            $(document).on('click', '.added_to_cart', function (e) {
                e.preventDefault();
                self.openPanel();
                console.log('View Cart link clicked, opening popup instead');
            });

            // Note: I prodotti variabili (Variable Products) sono già supportati perché
            // WooCommerce triggera l'evento 'added_to_cart' anche per loro dopo la selezione delle varianti
        },

        /**
         * Intercetta i pulsanti "Add to Cart" dei widget con data attribute
         */
        interceptWidgetAddToCart: function () {
            const self = this;

            // Intercetta i pulsanti Add to Cart dei widget che hanno l'opzione cart popup attiva
            $(document).on('click', '.bw-btn-addtocart[data-open-cart-popup="1"]', function (e) {
                const $btn = $(this);
                const href = $btn.attr('href');

                // Se il link contiene 'add-to-cart', previeni il comportamento default
                if (href && href.indexOf('add-to-cart') !== -1) {
                    e.preventDefault();

                    // Estrai parametri dall'URL
                    const urlParams = new URLSearchParams(href.split('?')[1] || '');
                    const productId = urlParams.get('add-to-cart');
                    const variationId = urlParams.get('variation_id') || $btn.attr('data-variation_id') || 0;

                    // Estrai tutti i parametri degli attributi (es. attribute_pa_license)
                    const extraParams = {};

                    // Se c'è una variation_id, aggiungi gli attributi della variation
                    if (variationId) {
                        extraParams.variation_id = variationId;

                        // Cerca tutti gli attributi nell'URL (quelli che iniziano con 'attribute_')
                        for (const [key, value] of urlParams.entries()) {
                            if (key.startsWith('attribute_')) {
                                extraParams[key] = value;
                            }
                        }
                    }

                    if (productId) {
                        // Aggiungi prodotto via AJAX e apri il popup
                        self.addToCartAjax(productId, 1, $btn, extraParams);
                    }
                } else {
                    // Se non è un link add-to-cart diretto (es. prodotto variabile),
                    // lascia che il link funzioni normalmente e vai alla pagina prodotto
                    // Non preveniamo il default in questo caso
                }
            });
        },

        /**
         * Aggiungi prodotto al carrello via AJAX
         * @param {number} productId - ID del prodotto
         * @param {number} quantity - Quantità
         * @param {object} $button - jQuery button element
         * @param {object} extraParams - Parametri extra (variation_id, attributi, ecc.)
         */
        addToCartAjax: function (productId, quantity, $button, extraParams) {
            const self = this;

            // Disabilita il pulsante durante il caricamento
            $button.addClass('loading');

            // Prepara i dati per la chiamata AJAX
            const ajaxData = {
                action: 'bw_cart_popup_add_to_cart',
                nonce: bwCartPopupConfig.nonce,
                product_id: productId,
                quantity: quantity
            };

            // Aggiungi parametri extra se presenti (variation_id, attributi)
            if (extraParams && typeof extraParams === 'object') {
                Object.assign(ajaxData, extraParams);
            }

            $.ajax({
                url: bwCartPopupConfig.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function (response) {
                    const payload = response && response.data ? response.data : response;

                    if (payload && payload.status === 'already_in_cart') {
                        $button.removeClass('loading');
                        self.showAlreadyInCartModal(payload.message, payload.cart_url);
                        return;
                    }

                    if (response && response.success === false) {
                        const message = payload && payload.message ? payload.message : 'Unable to add product to cart. Please try again.';
                        $button.removeClass('loading');
                        self.showErrorModal(message);
                        return;
                    }

                    if (payload && payload.fragments) {
                        $(document.body).trigger('added_to_cart', [payload.fragments, payload.cart_hash, $button]);
                        $button.removeClass('loading');
                        return;
                    }

                    $button.removeClass('loading');
                    self.showErrorModal('Unable to add product to cart. Please try again.');
                },
                error: function (xhr, status, errorThrown) {
                    console.error('Error adding product to cart:', status, errorThrown);
                    $button.removeClass('loading');

                    // Mostra messaggio di errore generico
                    const errorMessage = 'Unable to add product to cart. Please try again.';
                    self.showErrorModal(errorMessage);
                }
            });
        },

        /**
         * Apri il pannello
         */
        openPanel: function () {
            // CORREZIONE: Non aprire il cart popup in Elementor editor o preview
            // Questo previene che il popup si apra quando si clicca Publish/Update
            // Controlliamo sia elementorFrontend che le classi body
            var isElementorEditor = (typeof elementorFrontend !== 'undefined' &&
                typeof elementorFrontend.isEditMode === 'function' &&
                elementorFrontend.isEditMode());

            var hasEditorClass = $('body').hasClass('elementor-editor-active') ||
                $('body').hasClass('elementor-editor-preview');

            if (isElementorEditor || hasEditorClass) {
                console.log('Cart popup non aperto: siamo in Elementor editor/preview');
                return;
            }

            if (this.isOpen || this.isLoading) {
                return;
            }

            this.isOpen = true;
            this.isLoading = true;

            // FORZA il checkout URL corretto prima di aprire il popup
            this.ensureCheckoutLink();

            // Aggiungi classe active per animazione
            this.$overlay.addClass('active');
            this.$panel.addClass('active');

            // Blocca scroll body
            $('body').addClass('bw-cart-popup-no-scroll');

            // Carica contenuto carrello
            this.loadCartContents();

            console.log('Cart popup opened');
        },

        /**
         * Chiudi il pannello
         */
        closePanel: function () {
            if (!this.isOpen) {
                return;
            }

            this.isOpen = false;

            // Rimuovi classe active per animazione
            this.$overlay.removeClass('active');
            this.$panel.removeClass('active');

            // Riabilita scroll body
            $('body').removeClass('bw-cart-popup-no-scroll');

            // Nascondi promo box se aperto
            this.$promoBox.fadeOut(200);

            // Se siamo nella pagina checkout, aggiorna i totali
            if ($('body').hasClass('woocommerce-checkout')) {
                $(document.body).trigger('update_checkout');
            }

            console.log('Cart popup closed');
        },

        /**
         * Mostra il loading state
         */
        showLoading: function () {
            if (window.BWLS) window.BWLS.startProgress();
            this.$loadingState.fadeIn(150);
            this.$emptyState.hide();
            this.$fullContent.hide();
            this.$footer.hide();
        },

        /**
         * Nascondi il loading state
         */
        hideLoading: function () {
            if (window.BWLS) window.BWLS.stopProgress();
            this.$loadingState.fadeOut(150);
        },

        /**
         * Recupera i dati del carrello con opzioni di rendering
         * @param {Object} options
         * @param {boolean} options.render - Se true, aggiorna il DOM con i dati ricevuti
         * @param {boolean} options.skipLoading - Se true, non mostra il loading state
         * @returns {Promise}
         */
        fetchCartData: function (options) {
            const self = this;
            const opts = Object.assign({ render: true, skipLoading: false }, options);

            if (!opts.skipLoading) {
                this.showLoading();
            }

            return $.ajax({
                url: bwCartPopupConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bw_cart_popup_get_contents',
                    nonce: bwCartPopupConfig.nonce
                }
            })
                .done(function (response) {
                    if (response && response.success) {
                        self.cartItems = (response.data && response.data.items) ? response.data.items : [];

                        if (opts.render) {
                            self.renderCartItems(response.data);
                            self.updateTotals(response.data);
                            self.updateCouponDisplay(response.data.applied_coupons || []);
                        }
                    } else {
                        console.error('Failed to load cart contents');
                        // Fallback: if we have items in memory, show them, else show empty
                        if (opts.render) {
                            if (self.cartItems && self.cartItems.length > 0) {
                                self.showFullState();
                            } else {
                                self.showEmptyState();
                            }
                        }
                    }
                })
                .fail(function () {
                    console.error('AJAX error loading cart contents');
                    // Fallback on error
                    if (opts.render) {
                        if (self.cartItems && self.cartItems.length > 0) {
                            self.showFullState();
                        } else {
                            self.showEmptyState();
                        }
                    }
                })
                .always(function () {
                    self.isLoading = false;

                    if (!opts.skipLoading) {
                        // Nascondi loading con un leggero delay per evitare flickering
                        setTimeout(function () {
                            self.hideLoading();

                            // Final safety check: if nothing is visible, show empty state
                            if (opts.render && !self.$fullContent.is(':visible') && !self.$emptyState.is(':visible')) {
                                self.showEmptyState();
                            }
                        }, opts.render ? 200 : 0);
                    }
                });
        },

        /**
         * Carica il contenuto del carrello
         * @param {boolean} skipLoading - Se true, non mostra il loading state
         */
        loadCartContents: function (skipLoading) {
            return this.fetchCartData({ render: true, skipLoading: !!skipLoading });
        },

        /**
         * Garantisce un elenco aggiornato di prodotti nel carrello
         * @param {boolean} forceRefresh - Se true, forza una chiamata AJAX anche se è presente una cache
         * @returns {Promise<Array>}
         */
        ensureCartItems: function (forceRefresh) {
            const self = this;
            const shouldRefresh = forceRefresh || !Array.isArray(self.cartItems);

            if (!shouldRefresh && self.cartItems.length > 0) {
                return $.Deferred().resolve(self.cartItems).promise();
            }

            return self.fetchCartData({ render: false, skipLoading: true })
                .then(function () {
                    return self.cartItems || [];
                }, function () {
                    return [];
                });
        },

        /**
         * Verifica se un prodotto/variazione è già presente nel carrello
         * @param {number} productId
         * @param {number} variationId
         * @returns {boolean}
         */
        hasCartVariation: function (productId, variationId) {
            if (!Array.isArray(this.cartItems)) {
                return false;
            }

            const targetProduct = parseInt(productId, 10);
            const targetVariation = parseInt(variationId, 10);

            return this.cartItems.some(function (item) {
                const itemProductId = parseInt(item.product_id, 10);
                const itemVariationId = parseInt(item.variation_id || 0, 10);

                return itemProductId === targetProduct && itemVariationId === targetVariation;
            });
        },

        /**
         * Renderizza i prodotti nel carrello
         */
        renderCartItems: function (data) {
            this.cartItems = (data && Array.isArray(data.items)) ? data.items : [];

            // Se il carrello è vuoto, mostra il layout vuoto
            if (data.empty || !data.items || data.items.length === 0) {
                this.showEmptyState();
                this.updateBadge(0);
                return;
            }

            // Se il carrello ha prodotti, mostra il layout pieno
            this.showFullState();

            let html = '';
            let totalQuantity = 0;

            data.items.forEach(function (item) {
                const parsedQuantity = parseInt(item.quantity, 10);
                const quantity = Number.isNaN(parsedQuantity) || parsedQuantity < 1 ? 1 : parsedQuantity;
                totalQuantity += quantity;

                const hasDiscount = item.regular_subtotal_raw && item.regular_subtotal_raw > item.subtotal_raw;
                const showBadge = bwCartPopupConfig.settings.show_quantity_badge !== 0 && bwCartPopupConfig.settings.show_quantity_badge !== '0';
                const isSoldIndividually = item.sold_individually === true || item.sold_individually === '1' || item.sold_individually === 1;

                const actionsMarkup = isSoldIndividually
                    ? `<div class="bw-cart-item-actions">
                            <span class="bw-sold-individually-label" aria-label="Sold individually">Sold individually</span>
                            <button class="bw-cart-item-remove bw-cart-item-remove-text" data-cart-item-key="${item.key}" aria-label="Remove item">Remove</button>
                       </div>`
                    : `<div class="bw-cart-item-actions">
                            <div class="bw-qty-stepper bw-qty-stepper-framed" data-cart-item-key="${item.key}">
                                <button class="bw-qty-btn" data-delta="-1" aria-label="Decrease quantity">-</button>
                                <span class="bw-qty-value" aria-live="polite">${quantity}</span>
                                <button class="bw-qty-btn" data-delta="1" aria-label="Increase quantity">+</button>
                            </div>
                            <button class="bw-cart-item-remove bw-cart-item-remove-text" data-cart-item-key="${item.key}" aria-label="Remove item">Remove</button>
                       </div>`;

                html += `
                    <div class="bw-cart-item" data-cart-item-key="${item.key}">
                        <div class="bw-cart-item-main">
                            <div class="bw-cart-item-image">
                                ${item.image}
                                ${showBadge ? `<span class="bw-cart-item-quantity-badge" aria-label="Quantity in cart">${quantity}</span>` : ''}
                            </div>
                            <div class="bw-cart-item-body">
                                <div class="bw-cart-item-header">
                                    <div class="bw-cart-item-info">
                                        <h4 class="bw-cart-item-name">
                                            <a href="${item.permalink}">${item.name}</a>
                                        </h4>
                                        ${actionsMarkup}
                                    </div>
                                    <div class="bw-cart-item-price-block">
                                        ${hasDiscount ? `<span class="bw-cart-item-price-original">${item.regular_subtotal}</span>` : ''}
                                        <span class="bw-cart-item-price-current">${item.subtotal}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            this.$itemsContainer.html(html);
            const badgeCount = typeof data.item_count !== 'undefined' ? data.item_count : totalQuantity;
            this.updateBadge(badgeCount || data.items.length);
        },

        /**
         * Mostra lo stato carrello vuoto
         */
        showEmptyState: function () {
            this.cartItems = [];

            // Nascondi contenuto pieno e footer
            this.$fullContent.hide();
            this.$footer.hide();

            // Nascondi icona carrello e badge quando il carrello è vuoto
            this.$cartIconContainer.addClass('hidden');

            // Mostra stato vuoto
            this.$emptyState.fadeIn(300);
        },

        /**
         * Mostra lo stato carrello pieno
         */
        showFullState: function () {
            // Nascondi stato vuoto
            this.$emptyState.hide();

            // Mostra icona carrello e badge quando il carrello ha prodotti
            this.$cartIconContainer.removeClass('hidden');

            // Mostra contenuto pieno e footer
            this.$fullContent.fadeIn(300);
            this.$footer.fadeIn(300);
        },

        /**
         * Aggiorna i totali
         */
        updateTotals: function (data) {
            const self = this;
            // Subtotal
            $('.bw-cart-popup-subtotal .value').html(data.subtotal).attr('data-price', data.subtotal_raw);

            // Hide default discount row structure as we will rebuild it dynamically
            var $discountContainer = $('.bw-cart-popup-discount');
            $discountContainer.find('.label, .value, .bw-cart-coupon-label').hide();

            // Remove previously added dynamic rows
            $discountContainer.find('.bw-cart-coupon-dynamic-row').remove();

            // Coupons Data (now array of objects {code, amount})
            const coupons = Array.isArray(data.coupons) ? data.coupons : [];

            // Hide generic remove link in promo section
            if (this.$promoRemoveWrapper) {
                this.$promoRemoveWrapper.hide();
            }

            if (coupons.length > 0) {
                $discountContainer.show();
                $discountContainer.css({
                    'display': 'flex',
                    'flex-direction': 'column',
                    'gap': '8px'
                });

                coupons.forEach(function (coupon) {
                    // Handle both object (new) and string (legacy/fallback) formats
                    var code = typeof coupon === 'object' ? coupon.code : coupon;
                    var amount = typeof coupon === 'object' ? coupon.amount : '';

                    // If amount is empty (legacy), we might not show it per row, but user asked for it. 
                    // Backend now ensures amount is sent.

                    var rowHtml = `
                     <div class="bw-cart-coupon-dynamic-row" style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="label" style="display:inline; margin:0; font-weight:600;">Discount</span>
                            <span class="bw-cart-coupon-badge" style="background:#f0f0f1; color:#333; padding:2px 8px; border-radius:4px; font-size:12px; display:flex; align-items:center; gap:4px;">
                                <span class="bw-cart-coupon-icon" style="display:inline-block; width:12px; height:12px; background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z%22></path><line x1=%227%22 y1=%227%22 x2=%227.01%22 y2=%227%22></line></svg>'); background-size:contain; background-repeat:no-repeat; opacity:0.7;"></span>
                                <b>${code}</b>
                            </span>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <a href="#" class="bw-remove-coupon-btn-dynamic" data-code="${code}" style="font-size:11px; color:#999; text-decoration:none; text-transform:uppercase;">[Remove]</a>
                            <span class="value" style="color:#000; font-weight:bold; display:block;">${amount}</span>
                        </div>
                     </div>`;

                    $discountContainer.append(rowHtml);
                });

            } else {
                $discountContainer.hide();
            }

            // VAT
            $('.bw-cart-popup-vat .value').html(data.tax).attr('data-tax', data.tax_raw);

            // Total
            $('.bw-cart-popup-total .value').html(data.total).attr('data-total', data.total_raw);
            this.updateCheckoutCta(data.total);
        },

        /**
         * Toggle promo box
         */
        togglePromoBox: function () {
            if (this.$promoBox.is(':visible')) {
                this.$promoBox.fadeOut(200);
            } else {
                this.$promoBox.fadeIn(200);
                const $promoInput = $('.bw-promo-input');
                $promoInput.focus();
                this.syncPromoFloatingLabel($promoInput);
            }
        },

        /**
         * Applica coupon
         */
        applyCoupon: function () {
            const self = this;
            const couponCode = $('.bw-promo-input').val().trim();

            if (!couponCode) {
                this.showPromoMessage('Please enter a coupon code', 'error');
                return;
            }

            // Disabilita pulsante durante il processo
            const $applyBtn = $('.bw-promo-apply');
            $applyBtn.prop('disabled', true).text('Applying...');

            $.ajax({
                url: bwCartPopupConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bw_cart_popup_apply_coupon',
                    nonce: bwCartPopupConfig.nonce,
                    coupon_code: couponCode
                },
                success: function (response) {
                    if (response.success) {
                        self.showPromoMessage(response.data.message, 'success');
                        self.updateTotals(response.data);
                        self.updateCouponDisplay(response.data.applied_coupons || []);
                        $('.bw-promo-input').val('');
                        self.syncPromoFloatingLabel($('.bw-promo-input'));
                    } else {
                        self.showPromoMessage(response.data.message, 'error');
                    }

                    $applyBtn.prop('disabled', false).text('Apply');
                },
                error: function () {
                    self.showPromoMessage('Error applying coupon', 'error');
                    $applyBtn.prop('disabled', false).text('Apply');
                }
            });
        },

        /**
         * Aggiorna il display del coupon (mostra/nascondi link "Remove coupon")
         */
        updateCouponDisplay: function (appliedCoupons) {
            this.appliedCoupons = appliedCoupons || [];

            if (this.appliedCoupons.length > 0) {
                // Se ci sono coupon applicati, mostra il link "Remove coupon"
                this.$promoRemoveWrapper.fadeIn(200);
            } else {
                // Se non ci sono coupon applicati, nascondi il link "Remove coupon"
                this.$promoRemoveWrapper.fadeOut(200);
            }
        },

        /**
         * Rimuovi coupon
         * @param {string} specificCode - Codice opzionale da rimuovere
         */
        removeCoupon: function (specificCode) {
            const self = this;

            var couponCode = specificCode;

            if (!couponCode) {
                if (this.appliedCoupons && this.appliedCoupons.length > 0) {
                    couponCode = this.appliedCoupons[0];
                }
            }

            // Ottieni il primo coupon applicato (in questo caso assumiamo ci sia solo un coupon)
            if (!couponCode) {
                this.showPromoMessage('No coupon to remove', 'error');
                return;
            }

            $.ajax({
                url: bwCartPopupConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bw_cart_popup_remove_coupon',
                    nonce: bwCartPopupConfig.nonce,
                    coupon_code: couponCode
                },
                success: function (response) {
                    if (response.success) {
                        self.showPromoMessage(response.data.message, 'success');
                        self.updateTotals(response.data);
                        self.updateCouponDisplay(response.data.applied_coupons || []);
                    } else {
                        self.showPromoMessage(response.data.message, 'error');
                    }
                },
                error: function () {
                    self.showPromoMessage('Error removing coupon', 'error');
                }
            });
        },

        /**
         * Mostra messaggio promo code
         */
        showPromoMessage: function (message, type) {
            const self = this;
            const $inputWrapper = $('.bw-promo-input-wrapper');

            this.$promoMessage
                .removeClass('success error')
                .addClass(type)
                .html(message);

            if (type === 'error') {
                // Per errori: nascondi input/pulsante, mostra messaggio full width
                $inputWrapper.fadeOut(300, function () {
                    self.$promoMessage.fadeIn(300);
                });

                // Dopo 2.5 secondi, nascondi messaggio e rimostra input/pulsante
                setTimeout(() => {
                    self.$promoMessage.fadeOut(300, function () {
                        $inputWrapper.fadeIn(300);
                    });
                }, 2500);
            } else {
                // Per successo: mostra messaggio normalmente (sotto input/pulsante)
                this.$promoMessage.fadeIn(200);

                setTimeout(() => {
                    this.$promoMessage.fadeOut(200);
                }, 3000);
            }
        },

        /**
         * Rimuovi prodotto con animazione fluida
         * Mostra "Product removed" per 0.5 secondi mentre aggiorna in background
         */
        removeItem: function (cartItemKey) {
            const self = this;
            const $item = $('.bw-cart-item[data-cart-item-key="' + cartItemKey + '"]');

            if (!$item.length) {
                return;
            }

            // 1. Fade-out del prodotto
            $item.css({
                'opacity': '1',
                'transition': 'opacity 0.3s ease, transform 0.3s ease'
            });

            setTimeout(function () {
                $item.css({
                    'opacity': '0',
                    'transform': 'scale(0.95)'
                });
            }, 10);

            // 2. Dopo il fade-out, sostituisci con il placeholder
            setTimeout(function () {
                const itemHeight = $item.outerHeight();

                // Crea il box placeholder "Product removed" (o "Prodotto rimosso" se in italiano)
                const $placeholder = $('<div class="bw-cart-item-removed-placeholder" style="height: ' + itemHeight + 'px;">' +
                    '<span>Product removed</span>' +
                    '</div>');

                // Sostituisci l'item con il placeholder
                $item.replaceWith($placeholder);

                // 3. Invia la richiesta AJAX per rimuovere il prodotto
                $.ajax({
                    url: bwCartPopupConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'bw_cart_popup_remove_item',
                        nonce: bwCartPopupConfig.nonce,
                        cart_item_key: cartItemKey
                    },
                    success: function (response) {
                        if (response.success) {
                            // Aggiorna anche il mini-cart di WooCommerce
                            $(document.body).trigger('wc_fragment_refresh');

                            // 4. Dopo 0.5 secondi (500ms), fade-out del placeholder e ricarica il contenuto
                            // Durante questi 0.5 secondi, l'aggiornamento avviene in background
                            setTimeout(function () {
                                $placeholder.addClass('fade-out');

                                setTimeout(function () {
                                    $placeholder.remove();
                                    // Ricarica il contenuto del carrello senza mostrare il loading spinner
                                    // (skipLoading = true) per evitare il flash del loading
                                    self.loadCartContents(true);
                                }, 300);
                            }, 500); // Cambiato da 2000ms a 500ms (0.5 secondi)
                        } else {
                            console.error('Failed to remove item');
                            // In caso di errore, ricarica comunque il carrello
                            self.loadCartContents();
                        }
                    },
                    error: function () {
                        console.error('AJAX error removing item');
                        // In caso di errore, ricarica comunque il carrello
                        self.loadCartContents();
                    }
                });
            }, 300);
        },

        /**
         * Aggiorna quantità
         */
        updateQuantity: function (cartItemKey, quantity) {
            const self = this;

            $.ajax({
                url: bwCartPopupConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bw_cart_popup_update_quantity',
                    nonce: bwCartPopupConfig.nonce,
                    cart_item_key: cartItemKey,
                    quantity: quantity
                },
                success: function (response) {
                    if (response.success) {
                        // Ricarica contenuto carrello
                        self.loadCartContents();

                        // Aggiorna anche il mini-cart di WooCommerce se presente
                        $(document.body).trigger('wc_fragment_refresh');
                    } else {
                        console.error('Failed to update quantity');
                    }
                },
                error: function () {
                    console.error('AJAX error updating quantity');
                }
            });
        },

        /**
         * Aggiorna il badge con il numero totale di prodotti
         * Nasconde l'icona carrello + badge quando count = 0
         */
        updateBadge: function (count) {
            if (this.$cartBadge && this.$cartBadge.length) {
                this.$cartBadge.text(count);
            }

            if (this.$floatingBadge && this.$floatingBadge.length) {
                this.$floatingBadge.text(count);
            }

            // Mostra/nascondi l'icona carrello in base al numero di prodotti
            if (this.$cartIconContainer && this.$cartIconContainer.length) {
                if (count > 0) {
                    this.$cartIconContainer.removeClass('hidden');
                } else {
                    this.$cartIconContainer.addClass('hidden');
                }
            }

            // Mantieni sincronizzato anche il badge cart nell'header custom (desktop/mobile).
            this.syncHeaderCartBadge(count);

            this.toggleFloatingTrigger(count);
        },

        /**
         * Sincronizza il contatore cart nell'header navigation.
         */
        syncHeaderCartBadge: function (count) {
            const safeCount = Math.max(0, parseInt(count, 10) || 0);
            const $headerBadges = $('.bw-navshop__cart .bw-navshop__cart-count');

            if (!$headerBadges.length) {
                return;
            }

            $headerBadges.each(function () {
                const $badge = $(this);
                $badge.text(safeCount);

                if (safeCount > 0) {
                    $badge.removeClass('is-empty');
                } else {
                    $badge.addClass('is-empty');
                }
            });
        },

        setupFloatingTriggerWatcher: function () {
            // Inizializza stato iniziale (non visibile finché non si scrolla)
            this.onFloatingScroll();
        },

        onFloatingScroll: function () {
            if (!bwCartPopupConfig.settings.show_floating_trigger || !this.$floatingTrigger || !this.$floatingTrigger.length) {
                return;
            }

            if (!this.floatingHasItems) {
                this.$floatingTrigger.removeClass('is-visible');
                return;
            }

            const scrolled = $(window).scrollTop() > 10;
            if (scrolled) {
                this.$floatingTrigger.addClass('is-visible');
            } else {
                this.$floatingTrigger.removeClass('is-visible');
            }
        },

        toggleFloatingTrigger: function (count) {
            if (!bwCartPopupConfig.settings.show_floating_trigger || !this.$floatingTrigger || !this.$floatingTrigger.length) {
                return;
            }

            this.floatingHasItems = count > 0;

            if (this.floatingHasItems) {
                this.$floatingTrigger.removeClass('hidden');
                this.onFloatingScroll();
            } else {
                this.$floatingTrigger.addClass('hidden').removeClass('is-visible');
            }
        },

        /**
         * Aggiorna il testo del pulsante checkout mostrando il totale
         */
        updateCheckoutCta: function (totalFormatted) {
            const $cta = this.$footer.find('.bw-cart-popup-checkout');
            if (!$cta.length) {
                return;
            }

            const baseText = this.checkoutBaseText || $cta.text().trim();
            if (!baseText) {
                return;
            }

            const totalText = totalFormatted ? $('<div>').html(totalFormatted).text().trim() : '';
            const suffix = totalText ? ` · ${totalText}` : '';
            $cta.text(`${baseText}${suffix}`);
        },

        /**
         * Cambia il testo del pulsante Add to Cart in "Added to cart"
         */
        changeButtonTextToAdded: function ($button) {
            if (!$button || !$button.length) {
                return;
            }

            // Trova l'elemento span con il testo del pulsante
            const $label = $button.find('.overlay-button__label');

            if ($label.length) {
                // Salva il testo originale se non già salvato
                if (!$button.data('original-text')) {
                    $button.data('original-text', $label.text());
                }

                // Cambia il testo a "Added to cart"
                $label.text('Added to cart');
                $button.addClass('added');
            }
        },

        /**
         * Ripristina il testo originale del pulsante Add to Cart
         */
        resetButtonText: function ($button) {
            if (!$button || !$button.length) {
                return;
            }

            const $label = $button.find('.overlay-button__label');
            const originalText = $button.data('original-text');

            if ($label.length && originalText) {
                $label.text(originalText);
                $button.removeClass('added');
            }
        },

        /**
         * Mostra la notifica verde "Your item has been added to the cart"
         */
        showAddedNotification: function () {
            const self = this;

            if (!this.$notification || !this.$notification.length) {
                return;
            }

            // Mostra la notifica con fade-in
            this.$notification.addClass('show');

            // Nascondi la notifica dopo 3 secondi con fade-out
            setTimeout(function () {
                self.$notification.removeClass('show');
            }, 3000);
        },

        /**
         * Rimuove i link "View cart" di WooCommerce automaticamente
         */
        removeViewCartLinks: function () {
            // Rimuovi i link esistenti
            $('.added_to_cart.wc-forward').remove();

            // Monitora il DOM per nuovi link "View cart" aggiunti dinamicamente
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) { // Element node
                            if ($(node).hasClass('added_to_cart') && $(node).hasClass('wc-forward')) {
                                $(node).remove();
                            }
                            // Cerca anche all'interno dei nodi aggiunti
                            $(node).find('.added_to_cart.wc-forward').remove();
                        }
                    });
                });
            });

            // Monitora il body per cambiamenti
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        },

        /**
         * Monitora i cambiamenti del carrello per aggiornare lo stato dei pulsanti
         */
        monitorCartChanges: function () {
            const self = this;

            // Ascolta l'evento WooCommerce quando il carrello viene aggiornato
            $(document.body).on('wc_fragments_refreshed wc_fragment_refresh', function () {
                // Quando il carrello viene aggiornato, controlla se i prodotti sono stati rimossi
                // e ripristina il testo dei pulsanti se necessario
                self.updateAllButtonStates();
            });
        },

        /**
         * Aggiorna lo stato di tutti i pulsanti Add to Cart in base al contenuto del carrello
         */
        updateAllButtonStates: function () {
            const self = this;

            // Per ogni pulsante con il testo "Added to cart", controlla se il prodotto è ancora nel carrello
            $('.bw-btn-addtocart.added, .add_to_cart_button.added').each(function () {
                const $button = $(this);
                const productId = $button.data('product_id') || $button.data('product-id');

                if (productId) {
                    // Se il prodotto non è più nel carrello, ripristina il testo del pulsante
                    // Questa logica può essere espansa per verificare effettivamente il contenuto del carrello
                    // Per ora, resettiamo il pulsante dopo la rimozione
                }
            });
        },

        /**
         * Mostra pop-up dedicato quando il prodotto è già nel carrello
         * @param {string} message - Messaggio da mostrare
         * @param {string} cartUrl - URL del carrello
         */
        showAlreadyInCartModal: function (message, cartUrl, options) {
            const self = this;
            const safeMessage = message || 'This product is already in your cart.';
            const opts = options || {};
            const shopUrl = (bwCartPopupConfig && bwCartPopupConfig.shopUrl) || '/shop/';
            const continueUrl = opts.hasOwnProperty('continueUrl') ? opts.continueUrl : shopUrl;
            const continueLabel = opts.continueLabel || 'Continue shopping';
            const onContinue = typeof opts.onContinue === 'function' ? opts.onContinue : null;

            $('.bw-cart-already-modal-overlay').remove();

            const modalHtml = `
                <div class="bw-cart-already-modal-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.35);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 999999;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                ">
                    <div class="bw-cart-already-modal" style="
                        background: #fff;
                        border-radius: 10px;
                        padding: 36px 32px 28px;
                        max-width: 520px;
                        width: 90%;
                        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                        position: relative;
                        transform: scale(0.9);
                        transition: transform 0.3s ease;
                    ">
                        <div class="bw-cart-already-modal__content" style="text-align: center; margin-bottom: 24px;">
                            <div class="bw-cart-already-modal__message" style="
                                font-size: 16px;
                                line-height: 1.6;
                                color: #333;
                                margin-bottom: 6px;
                            "></div>
                            <div class="bw-cart-already-modal__hint" style="
                                font-size: 14px;
                                color: #666;
                            ">This item is sold individually.</div>
                        </div>
                        <div class="bw-cart-already-modal__actions" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                            <button class="bw-cart-already-modal__btn bw-cart-already-modal__btn--continue" style="
                                background-color: #f4f4f4;
                                color: #000;
                                border: 1px solid #e0e0e0;
                                padding: 12px 26px;
                                border-radius: 6px;
                                font-size: 14px;
                                font-weight: 700;
                                cursor: pointer;
                                transition: all 0.3s ease;
                            ">${continueLabel}</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);

            const $modalOverlay = $('.bw-cart-already-modal-overlay');
            const $modal = $('.bw-cart-already-modal');

            $modal.find('.bw-cart-already-modal__message').text(safeMessage);

            $('body').addClass('bw-cart-popup-no-scroll');

            setTimeout(function () {
                $modalOverlay.css('opacity', '1');
                $modal.css('transform', 'scale(1)');
            }, 10);
            $modalOverlay.on('click', '.bw-cart-already-modal__btn--continue', function (e) {
                e.preventDefault();
                self.closeAlreadyInCartModal();

                if (onContinue) {
                    onContinue();
                    return;
                }

                if (continueUrl) {
                    window.location.href = continueUrl;
                }
            });

            $modalOverlay.on('click', function (e) {
                if ($(e.target).hasClass('bw-cart-already-modal-overlay')) {
                    self.closeAlreadyInCartModal();
                }
            });

            $(document).on('keyup.bwCartAlreadyModal', function (e) {
                if (e.key === 'Escape') {
                    self.closeAlreadyInCartModal();
                }
            });

            const hoverStyles = `
                <style id="bw-cart-already-modal-styles">
                    .bw-cart-already-modal__btn--continue:hover { background-color: #e9e9e9; transform: translateY(-1px); }
                    .bw-cart-already-modal__btn:active { transform: translateY(0); }
                </style>
            `;

            if (!$('#bw-cart-already-modal-styles').length) {
                $('head').append(hoverStyles);
            }
        },

        /**
         * Chiudi il pop-up dedicato già nel carrello
         */
        closeAlreadyInCartModal: function () {
            const $modalOverlay = $('.bw-cart-already-modal-overlay');

            if (!$modalOverlay.length) {
                return;
            }

            $modalOverlay.css('opacity', '0');
            $modalOverlay.find('.bw-cart-already-modal').css('transform', 'scale(0.9)');

            setTimeout(function () {
                $modalOverlay.remove();
                $('body').removeClass('bw-cart-popup-no-scroll');
            }, 300);

            $(document).off('keyup.bwCartAlreadyModal');
        },

        /**
         * Mostra modal di errore con overlay
         * @param {string} errorMessage - Messaggio di errore da mostrare
         */
        showErrorModal: function (errorMessage, options) {
            const self = this;

            // Rimuovi eventuali modal esistenti
            $('.bw-cart-error-modal-overlay').remove();

            // URL della pagina shop WooCommerce
            const opts = options || {};
            const shopUrl = (bwCartPopupConfig && bwCartPopupConfig.shopUrl) || '/shop/';
            const targetUrl = opts.returnUrl || shopUrl;
            const buttonLabel = opts.returnLabel || 'Return to Shop';

            // Crea l'overlay e il modal
            const modalHtml = `
                <div class="bw-cart-error-modal-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 999999;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                ">
                    <div class="bw-cart-error-modal" style="
                        background: #fff;
                        border-radius: 8px;
                        padding: 40px 30px 30px;
                        max-width: 500px;
                        width: 90%;
                        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                        position: relative;
                        transform: scale(0.9);
                        transition: transform 0.3s ease;
                    ">
                        <div class="bw-cart-error-modal__content" style="
                            text-align: center;
                            margin-bottom: 25px;
                        ">
                            <div class="bw-cart-error-modal__icon" style="
                                width: 60px;
                                height: 60px;
                                margin: 0 auto 20px;
                                background-color: #e74c3c;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 32px;
                                color: #fff;
                            ">⚠</div>
                            <div class="bw-cart-error-modal__message" style="
                                font-size: 16px;
                                line-height: 1.6;
                                color: #333;
                                margin-bottom: 10px;
                            ">${errorMessage}</div>
                        </div>
                        <div class="bw-cart-error-modal__actions" style="
                            display: flex;
                            gap: 10px;
                            justify-content: center;
                        ">
                            <button class="bw-cart-error-modal__btn bw-cart-error-modal__btn--shop" style="
                                background-color: #80FD03;
                                color: #000;
                                border: none;
                                padding: 12px 30px;
                                border-radius: 4px;
                                font-size: 14px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: all 0.3s ease;
                            ">${buttonLabel}</button>
                        </div>
                    </div>
                </div>
            `;

            // Aggiungi al body
            $('body').append(modalHtml);

            const $modalOverlay = $('.bw-cart-error-modal-overlay');
            const $modal = $('.bw-cart-error-modal');

            // Blocca scroll body
            $('body').addClass('bw-cart-popup-no-scroll');

            // Animazione fade-in
            setTimeout(function () {
                $modalOverlay.css('opacity', '1');
                $modal.css('transform', 'scale(1)');
            }, 10);

            // Gestisci click sul pulsante "Return to Shop"
            $modalOverlay.on('click', '.bw-cart-error-modal__btn--shop', function (e) {
                e.preventDefault();
                window.location.href = targetUrl;
            });

            // Chiudi modal al click sull'overlay (opzionale)
            $modalOverlay.on('click', function (e) {
                if ($(e.target).hasClass('bw-cart-error-modal-overlay')) {
                    self.closeErrorModal();
                }
            });

            // Chiudi con ESC
            $(document).on('keyup.bwCartErrorModal', function (e) {
                if (e.key === 'Escape') {
                    self.closeErrorModal();
                }
            });

            // Aggiungi effetto hover ai pulsanti
            const hoverStyles = `
                <style id="bw-cart-error-modal-styles">
                    .bw-cart-error-modal__btn--shop:hover {
                        background-color: #6ee002 !important;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(128, 253, 3, 0.3);
                    }
                    .bw-cart-error-modal__btn--shop:active {
                        transform: translateY(0);
                    }
                </style>
            `;

            if (!$('#bw-cart-error-modal-styles').length) {
                $('head').append(hoverStyles);
            }
        },

        /**
         * Chiudi modal di errore
         */
        closeErrorModal: function () {
            const $modalOverlay = $('.bw-cart-error-modal-overlay');

            if (!$modalOverlay.length) {
                return;
            }

            // Animazione fade-out
            $modalOverlay.css('opacity', '0');
            $modalOverlay.find('.bw-cart-error-modal').css('transform', 'scale(0.9)');

            // Rimuovi dopo l'animazione
            setTimeout(function () {
                $modalOverlay.remove();
                $('body').removeClass('bw-cart-popup-no-scroll');
            }, 300);

            // Rimuovi listener ESC
            $(document).off('keyup.bwCartErrorModal');
        }
    };

    // Inizializza al document ready
    $(document).ready(function () {
        BW_CartPopup.init();
    });

    // Esponi globalmente per permettere l'integrazione con altri widget
    window.BW_CartPopup = BW_CartPopup;

})(jQuery);
