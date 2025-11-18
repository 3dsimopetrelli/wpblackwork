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
        $closeBtn: null,
        $continueBtn: null,
        $cartBadge: null,
        $loadingState: null,

        // Stato
        isOpen: false,
        isLoading: false,

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
            this.$closeBtn = $('.bw-cart-popup-close');
            this.$continueBtn = $('.bw-cart-popup-continue');
            this.$cartBadge = $('.bw-cart-badge');
            this.$loadingState = $('.bw-cart-popup-loading');

            // Bind eventi
            this.bindEvents();

            // Intercetta pulsanti "Add to Cart" globali se l'opzione è attiva
            if (bwCartPopupConfig.settings.active && bwCartPopupConfig.settings.slide_animation) {
                this.interceptAddToCart();
            }

            // Intercetta pulsanti dei widget con data attribute (sempre attivo per i widget)
            this.interceptWidgetAddToCart();

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
            this.$closeBtn.on('click', function(e) {
                e.preventDefault();
                self.closePanel();
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
         * Intercetta i pulsanti "Add to Cart"
         */
        interceptAddToCart: function() {
            const self = this;

            // Controlla se lo slide-in animation è attivo
            if (!bwCartPopupConfig.settings.slide_animation) {
                return;
            }

            // 1. Intercetta l'evento WooCommerce 'added_to_cart' (dopo aggiunta al carrello)
            // Questo evento viene triggerato da WooCommerce per tutti i tipi di Add to Cart (AJAX, form variabili, ecc.)
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                // Apri il pannello con slide-in animation
                self.openPanel();

                console.log('Product added to cart, opening popup with slide-in animation');
            });

            // 2. Previeni il redirect per pulsanti non-AJAX e convertili in chiamate AJAX
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

            // 3. Intercetta il link "View Cart" che appare dopo l'aggiunta di un prodotto
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

                    // Estrai l'ID del prodotto dall'URL
                    const urlParams = new URLSearchParams(href.split('?')[1] || '');
                    const productId = urlParams.get('add-to-cart');

                    if (productId) {
                        // Aggiungi prodotto via AJAX e apri il popup
                        self.addToCartAjax(productId, 1, $btn);
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
         */
        addToCartAjax: function(productId, quantity, $button) {
            const self = this;

            // Disabilita il pulsante durante il caricamento
            $button.addClass('loading');

            $.ajax({
                url: bwCartPopupConfig.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart'),
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
                    if (response.error && response.product_url) {
                        window.location = response.product_url;
                        return;
                    }

                    // Trigger evento WooCommerce per aggiornare i fragments
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                    $button.removeClass('loading');
                },
                error: function() {
                    console.error('Error adding product to cart');
                    $button.removeClass('loading');
                }
            });
        },

        /**
         * Apri il pannello
         */
        openPanel: function() {
            if (this.isOpen || this.isLoading) {
                return;
            }

            this.isOpen = true;
            this.isLoading = true;

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
         */
        loadCartContents: function() {
            const self = this;

            // Mostra loading state
            this.showLoading();

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
                        self.isLoading = false;

                        // Nascondi loading con un leggero delay per evitare flickering
                        setTimeout(function() {
                            self.hideLoading();
                        }, 200);
                    } else {
                        console.error('Failed to load cart contents');
                        self.isLoading = false;
                        self.hideLoading();
                    }
                },
                error: function() {
                    console.error('AJAX error loading cart contents');
                    self.isLoading = false;
                    self.hideLoading();
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

            // Mostra stato vuoto
            this.$emptyState.fadeIn(300);
        },

        /**
         * Mostra lo stato carrello pieno
         */
        showFullState: function() {
            // Nascondi stato vuoto
            this.$emptyState.hide();

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

            // VAT
            $('.bw-cart-popup-vat .value').html(data.tax).attr('data-tax', data.tax_raw);

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
         * Rimuovi prodotto
         */
        removeItem: function(cartItemKey) {
            const self = this;

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
                        // Ricarica contenuto carrello
                        self.loadCartContents();

                        // Aggiorna anche il mini-cart di WooCommerce se presente
                        $(document.body).trigger('wc_fragment_refresh');
                    } else {
                        console.error('Failed to remove item');
                    }
                },
                error: function() {
                    console.error('AJAX error removing item');
                }
            });
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
         */
        updateBadge: function(count) {
            if (this.$cartBadge && this.$cartBadge.length) {
                this.$cartBadge.text(count);
            }
        }
    };

    // Inizializza al document ready
    $(document).ready(function() {
        BW_CartPopup.init();
    });

    // Esponi globalmente per permettere l'integrazione con altri widget
    window.BW_CartPopup = BW_CartPopup;

})(jQuery);
