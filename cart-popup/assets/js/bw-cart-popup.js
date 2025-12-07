/**
 * BW Cart Pop-Up JavaScript
 *
 * Gestisce le interazioni del pannello cart pop-up
 *
 * @package BW_Cart_Popup
 * @version 1.0.0
 */

(function($) {
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
        $loadingState: null,
        $notification: null,

        // Stato
        isOpen: false,
        isLoading: false,
        lastAddedButton: null,
        appliedCoupons: [],

        /**
         * Inizializzazione
         */
        init: function() {
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
            this.$loadingState = $('.bw-cart-popup-loading');
            this.$notification = $('.bw-cart-popup-notification');

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

            console.log('BW Cart Pop-Up initialized');
        },

        /**
         * Bind eventi
         */
        bindEvents: function() {
            const self = this;

            // Chiudi pannello al click sull'overlay
            this.$overlay.on('click', function() {
                self.closePanel();
            });

            // Chiudi pannello al click sul pulsante X
            // Usa un listener delegato per coprire eventuali re-render del markup
            $(document).on('click', '.bw-cart-popup-close', function(e) {
                e.preventDefault();
                self.closePanel();
            });

            // Forza sempre il redirect verso la pagina checkout quando si clicca sul pulsante verde
            $(document).on('click', '.bw-cart-popup-checkout', function(e) {
                const checkoutUrl = (bwCartPopupConfig && bwCartPopupConfig.checkoutUrl) || $(this).attr('href');

                if (checkoutUrl) {
                    e.preventDefault();
                    window.location.href = checkoutUrl;
                }
            });

            // Toggle promo code box
            this.$promoTrigger.on('click', function(e) {
                e.preventDefault();
                self.togglePromoBox();
            });

            // Applica coupon
            $('.bw-promo-apply').on('click', function(e) {
                e.preventDefault();
                self.applyCoupon();
            });

            // Enter per applicare coupon
            $('.bw-promo-input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.applyCoupon();
                }
            });

            // Rimuovi coupon
            this.$promoRemoveLink.on('click', function(e) {
                e.preventDefault();
                self.removeCoupon();
            });

            // Rimuovi prodotto dal carrello
            this.$itemsContainer.on('click', '.bw-cart-item-remove', function(e) {
                e.preventDefault();
                const cartItemKey = $(this).data('cart-item-key');
                self.removeItem(cartItemKey);
            });

            // Chiudi con ESC
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape' && self.isOpen) {
                    self.closePanel();
                }
            });
        },

        /**
         * Recupera l'URL di checkout seguendo le API WooCommerce e i fallback HTML
         */
        getCheckoutUrl: function() {
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
        ensureCheckoutLink: function() {
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
        listenToAddedToCart: function() {
            const self = this;

            // Intercetta l'evento WooCommerce 'added_to_cart' (dopo aggiunta al carrello)
            // Questo evento viene triggerato da WooCommerce per tutti i tipi di Add to Cart
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
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
                    self.openPanel();

                    // Mostra la notifica verde "Your item has been added to the cart"
                    self.showAddedNotification();

                    // Aggiorna eventuali href del checkout in caso di markup rimpiazzato da frammenti WooCommerce
                    self.ensureCheckoutLink();

                    console.log('Product added to cart, opening cart popup');
                }
            });
        },

        /**
         * Intercetta i pulsanti "Add to Cart" standard di WooCommerce
         * Questa funzione viene chiamata solo se l'opzione "slide_animation" è attiva
         */
        interceptAddToCart: function() {
            const self = this;

            // 1. Previeni il redirect per pulsanti non-AJAX e convertili in chiamate AJAX
            // Alcuni temi o configurazioni potrebbero avere pulsanti "Add to Cart" come link diretti
            $(document).on('click', 'a.add_to_cart_button', function(e) {
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
            $(document).on('click', '.added_to_cart', function(e) {
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
        interceptWidgetAddToCart: function() {
            const self = this;

            // Intercetta i pulsanti Add to Cart dei widget che hanno l'opzione cart popup attiva
            $(document).on('click', '.bw-btn-addtocart[data-open-cart-popup="1"]', function(e) {
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
        addToCartAjax: function(productId, quantity, $button, extraParams) {
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
                success: function(response) {
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
                error: function(xhr, status, errorThrown) {
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
        openPanel: function() {
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
        closePanel: function() {
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

            console.log('Cart popup closed');
        },

        /**
         * Mostra il loading state
         */
        showLoading: function() {
            this.$loadingState.fadeIn(150);
            this.$emptyState.hide();
            this.$fullContent.hide();
            this.$footer.hide();
        },

        /**
         * Nascondi il loading state
         */
        hideLoading: function() {
            this.$loadingState.fadeOut(150);
        },

        /**
         * Carica il contenuto del carrello
         * @param {boolean} skipLoading - Se true, non mostra il loading state
         */
        loadCartContents: function(skipLoading) {
            const self = this;

            // Mostra loading state solo se non è richiesto di saltarlo
            if (!skipLoading) {
                this.showLoading();
            }

            $.ajax({
                url: bwCartPopupConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bw_cart_popup_get_contents',
                    nonce: bwCartPopupConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderCartItems(response.data);
                        self.updateTotals(response.data);
                        self.updateCouponDisplay(response.data.applied_coupons || []);
                        self.isLoading = false;

                        // Nascondi loading con un leggero delay per evitare flickering
                        if (!skipLoading) {
                            setTimeout(function() {
                                self.hideLoading();
                            }, 200);
                        }
                    } else {
                        console.error('Failed to load cart contents');
                        self.isLoading = false;
                        if (!skipLoading) {
                            self.hideLoading();
                        }
                    }
                },
                error: function() {
                    console.error('AJAX error loading cart contents');
                    self.isLoading = false;
                    if (!skipLoading) {
                        self.hideLoading();
                    }
                }
            });
        },

        /**
         * Renderizza i prodotti nel carrello
         */
        renderCartItems: function(data) {
            // Se il carrello è vuoto, mostra il layout vuoto
            if (data.empty || !data.items || data.items.length === 0) {
                this.showEmptyState();
                this.updateBadge(0);
                return;
            }

            // Se il carrello ha prodotti, mostra il layout pieno
            this.showFullState();

            let html = '';

            data.items.forEach(function(item) {
                html += `
                    <div class="bw-cart-item" data-cart-item-key="${item.key}">
                        <div class="bw-cart-item-image">
                            ${item.image}
                        </div>
                        <div class="bw-cart-item-details">
                            <h4 class="bw-cart-item-name">
                                <a href="${item.permalink}">${item.name}</a>
                            </h4>
                            <div class="bw-cart-item-price">
                                ${item.price}
                            </div>
                        </div>
                        <button class="bw-cart-item-remove" data-cart-item-key="${item.key}" aria-label="Remove item">
                            <span>&times;</span>
                        </button>
                    </div>
                `;
            });

            this.$itemsContainer.html(html);
            this.updateBadge(data.items.length);
        },

        /**
         * Mostra lo stato carrello vuoto
         */
        showEmptyState: function() {
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
        showFullState: function() {
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
        updateTotals: function(data) {
            // Subtotal
            $('.bw-cart-popup-subtotal .value').html(data.subtotal).attr('data-price', data.subtotal_raw);

            // Discount
            if (data.discount_raw > 0) {
                $('.bw-cart-popup-discount').show();
                $('.bw-cart-popup-discount .value').html('-' + data.discount).attr('data-discount', data.discount_raw);
            } else {
                $('.bw-cart-popup-discount').hide();
            }

            // Total
            $('.bw-cart-popup-total .value').html(data.total).attr('data-total', data.total_raw);
        },

        /**
         * Toggle promo box
         */
        togglePromoBox: function() {
            if (this.$promoBox.is(':visible')) {
                this.$promoBox.fadeOut(200);
            } else {
                this.$promoBox.fadeIn(200);
                $('.bw-promo-input').focus();
            }
        },

        /**
         * Applica coupon
         */
        applyCoupon: function() {
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
                success: function(response) {
                    if (response.success) {
                        self.showPromoMessage(response.data.message, 'success');
                        self.updateTotals(response.data);
                        self.updateCouponDisplay(response.data.applied_coupons || []);
                        $('.bw-promo-input').val('');
                    } else {
                        self.showPromoMessage(response.data.message, 'error');
                    }

                    $applyBtn.prop('disabled', false).text('Apply');
                },
                error: function() {
                    self.showPromoMessage('Error applying coupon', 'error');
                    $applyBtn.prop('disabled', false).text('Apply');
                }
            });
        },

        /**
         * Aggiorna il display del coupon (mostra/nascondi link "Remove coupon")
         */
        updateCouponDisplay: function(appliedCoupons) {
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
         */
        removeCoupon: function() {
            const self = this;

            // Ottieni il primo coupon applicato (in questo caso assumiamo ci sia solo un coupon)
            if (!this.appliedCoupons || this.appliedCoupons.length === 0) {
                this.showPromoMessage('No coupon to remove', 'error');
                return;
            }

            const couponCode = this.appliedCoupons[0];

            $.ajax({
                url: bwCartPopupConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bw_cart_popup_remove_coupon',
                    nonce: bwCartPopupConfig.nonce,
                    coupon_code: couponCode
                },
                success: function(response) {
                    if (response.success) {
                        self.showPromoMessage(response.data.message, 'success');
                        self.updateTotals(response.data);
                        self.updateCouponDisplay(response.data.applied_coupons || []);
                    } else {
                        self.showPromoMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showPromoMessage('Error removing coupon', 'error');
                }
            });
        },

        /**
         * Mostra messaggio promo code
         */
        showPromoMessage: function(message, type) {
            const self = this;
            const $inputWrapper = $('.bw-promo-input-wrapper');

            this.$promoMessage
                .removeClass('success error')
                .addClass(type)
                .html(message);

            if (type === 'error') {
                // Per errori: nascondi input/pulsante, mostra messaggio full width
                $inputWrapper.fadeOut(300, function() {
                    self.$promoMessage.fadeIn(300);
                });

                // Dopo 2.5 secondi, nascondi messaggio e rimostra input/pulsante
                setTimeout(() => {
                    self.$promoMessage.fadeOut(300, function() {
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
        removeItem: function(cartItemKey) {
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

            setTimeout(function() {
                $item.css({
                    'opacity': '0',
                    'transform': 'scale(0.95)'
                });
            }, 10);

            // 2. Dopo il fade-out, sostituisci con il placeholder
            setTimeout(function() {
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
                    success: function(response) {
                        if (response.success) {
                            // Aggiorna anche il mini-cart di WooCommerce
                            $(document.body).trigger('wc_fragment_refresh');

                            // 4. Dopo 0.5 secondi (500ms), fade-out del placeholder e ricarica il contenuto
                            // Durante questi 0.5 secondi, l'aggiornamento avviene in background
                            setTimeout(function() {
                                $placeholder.addClass('fade-out');

                                setTimeout(function() {
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
                    error: function() {
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
        updateQuantity: function(cartItemKey, quantity) {
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
                success: function(response) {
                    if (response.success) {
                        // Ricarica contenuto carrello
                        self.loadCartContents();

                        // Aggiorna anche il mini-cart di WooCommerce se presente
                        $(document.body).trigger('wc_fragment_refresh');
                    } else {
                        console.error('Failed to update quantity');
                    }
                },
                error: function() {
                    console.error('AJAX error updating quantity');
                }
            });
        },

        /**
         * Aggiorna il badge con il numero di prodotti
         * Nasconde l'icona carrello + badge quando count = 0
         */
        updateBadge: function(count) {
            if (this.$cartBadge && this.$cartBadge.length) {
                this.$cartBadge.text(count);
            }

            // Mostra/nascondi l'icona carrello in base al numero di prodotti
            if (this.$cartIconContainer && this.$cartIconContainer.length) {
                if (count > 0) {
                    this.$cartIconContainer.removeClass('hidden');
                } else {
                    this.$cartIconContainer.addClass('hidden');
                }
            }
        },

        /**
         * Cambia il testo del pulsante Add to Cart in "Added to cart"
         */
        changeButtonTextToAdded: function($button) {
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
        resetButtonText: function($button) {
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
        showAddedNotification: function() {
            const self = this;

            if (!this.$notification || !this.$notification.length) {
                return;
            }

            // Mostra la notifica con fade-in
            this.$notification.addClass('show');

            // Nascondi la notifica dopo 3 secondi con fade-out
            setTimeout(function() {
                self.$notification.removeClass('show');
            }, 3000);
        },

        /**
         * Rimuove i link "View cart" di WooCommerce automaticamente
         */
        removeViewCartLinks: function() {
            // Rimuovi i link esistenti
            $('.added_to_cart.wc-forward').remove();

            // Monitora il DOM per nuovi link "View cart" aggiunti dinamicamente
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
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
        monitorCartChanges: function() {
            const self = this;

            // Ascolta l'evento WooCommerce quando il carrello viene aggiornato
            $(document.body).on('wc_fragments_refreshed wc_fragment_refresh', function() {
                // Quando il carrello viene aggiornato, controlla se i prodotti sono stati rimossi
                // e ripristina il testo dei pulsanti se necessario
                self.updateAllButtonStates();
            });
        },

        /**
         * Aggiorna lo stato di tutti i pulsanti Add to Cart in base al contenuto del carrello
         */
        updateAllButtonStates: function() {
            const self = this;

            // Per ogni pulsante con il testo "Added to cart", controlla se il prodotto è ancora nel carrello
            $('.bw-btn-addtocart.added, .add_to_cart_button.added').each(function() {
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
        showAlreadyInCartModal: function(message, cartUrl) {
            const self = this;
            const safeMessage = message || 'This product is already in your cart.';
            const targetCartUrl = cartUrl || (bwCartPopupConfig && bwCartPopupConfig.cartUrl) || '/cart/';
            const shopUrl = (bwCartPopupConfig && bwCartPopupConfig.shopUrl) || '/shop/';

            $('.bw-cart-already-modal-overlay').remove();

            const modalHtml = `
                <div class="bw-cart-already-modal-overlay" style="
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
                            <div class="bw-cart-already-modal__icon" style="
                                width: 64px;
                                height: 64px;
                                margin: 0 auto 18px;
                                background-color: #f8f8f8;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 30px;
                                color: #000;
                            ">🛒</div>
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
                            <button class="bw-cart-already-modal__btn bw-cart-already-modal__btn--cart" style="
                                background-color: #111;
                                color: #fff;
                                border: none;
                                padding: 12px 26px;
                                border-radius: 6px;
                                font-size: 14px;
                                font-weight: 700;
                                cursor: pointer;
                                transition: all 0.3s ease;
                            ">Vai al carrello</button>
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
                            ">Continua lo shopping</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);

            const $modalOverlay = $('.bw-cart-already-modal-overlay');
            const $modal = $('.bw-cart-already-modal');

            $modal.find('.bw-cart-already-modal__message').text(safeMessage);

            $('body').addClass('bw-cart-popup-no-scroll');

            setTimeout(function() {
                $modalOverlay.css('opacity', '1');
                $modal.css('transform', 'scale(1)');
            }, 10);

            $modalOverlay.on('click', '.bw-cart-already-modal__btn--cart', function(e) {
                e.preventDefault();
                window.location.href = targetCartUrl;
            });

            $modalOverlay.on('click', '.bw-cart-already-modal__btn--continue', function(e) {
                e.preventDefault();
                self.closeAlreadyInCartModal();
                window.location.href = shopUrl;
            });

            $modalOverlay.on('click', function(e) {
                if ($(e.target).hasClass('bw-cart-already-modal-overlay')) {
                    self.closeAlreadyInCartModal();
                }
            });

            $(document).on('keyup.bwCartAlreadyModal', function(e) {
                if (e.key === 'Escape') {
                    self.closeAlreadyInCartModal();
                }
            });

            const hoverStyles = `
                <style id="bw-cart-already-modal-styles">
                    .bw-cart-already-modal__btn--cart:hover { background-color: #000; color: #fff; transform: translateY(-1px); }
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
        closeAlreadyInCartModal: function() {
            const $modalOverlay = $('.bw-cart-already-modal-overlay');

            if (!$modalOverlay.length) {
                return;
            }

            $modalOverlay.css('opacity', '0');
            $modalOverlay.find('.bw-cart-already-modal').css('transform', 'scale(0.9)');

            setTimeout(function() {
                $modalOverlay.remove();
                $('body').removeClass('bw-cart-popup-no-scroll');
            }, 300);

            $(document).off('keyup.bwCartAlreadyModal');
        },

        /**
         * Mostra modal di errore con overlay
         * @param {string} errorMessage - Messaggio di errore da mostrare
         */
        showErrorModal: function(errorMessage) {
            const self = this;

            // Rimuovi eventuali modal esistenti
            $('.bw-cart-error-modal-overlay').remove();

            // URL della pagina shop WooCommerce
            const shopUrl = (bwCartPopupConfig && bwCartPopupConfig.shopUrl) || '/shop/';

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
                            ">Return to Shop</button>
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
            setTimeout(function() {
                $modalOverlay.css('opacity', '1');
                $modal.css('transform', 'scale(1)');
            }, 10);

            // Gestisci click sul pulsante "Return to Shop"
            $modalOverlay.on('click', '.bw-cart-error-modal__btn--shop', function(e) {
                e.preventDefault();
                window.location.href = shopUrl;
            });

            // Chiudi modal al click sull'overlay (opzionale)
            $modalOverlay.on('click', function(e) {
                if ($(e.target).hasClass('bw-cart-error-modal-overlay')) {
                    self.closeErrorModal();
                }
            });

            // Chiudi con ESC
            $(document).on('keyup.bwCartErrorModal', function(e) {
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
        closeErrorModal: function() {
            const $modalOverlay = $('.bw-cart-error-modal-overlay');

            if (!$modalOverlay.length) {
                return;
            }

            // Animazione fade-out
            $modalOverlay.css('opacity', '0');
            $modalOverlay.find('.bw-cart-error-modal').css('transform', 'scale(0.9)');

            // Rimuovi dopo l'animazione
            setTimeout(function() {
                $modalOverlay.remove();
                $('body').removeClass('bw-cart-popup-no-scroll');
            }, 300);

            // Rimuovi listener ESC
            $(document).off('keyup.bwCartErrorModal');
        }
    };

    // Inizializza al document ready
    $(document).ready(function() {
        BW_CartPopup.init();
    });

    // Esponi globalmente per permettere l'integrazione con altri widget
    window.BW_CartPopup = BW_CartPopup;

})(jQuery);
