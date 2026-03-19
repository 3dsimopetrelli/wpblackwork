/**
 * BWEmblaCore — wrapper condiviso per Embla Carousel v8
 * Vanilla JS, zero jQuery.
 * Riusabile da qualsiasi widget BW che usa Embla.
 */
(function (global) {
    'use strict';

    /**
     * @param {HTMLElement} viewport   — elemento con overflow:hidden passato a EmblaCarousel()
     * @param {Object}      emblaOpts  — opzioni EmblaCarousel (loop, align, containScroll, …)
     * @param {Object}      [features] — funzionalità aggiuntive BW
     * @param {HTMLElement} [features.prevBtn]        — bottone "prev"
     * @param {HTMLElement} [features.nextBtn]        — bottone "next"
     * @param {HTMLElement} [features.dotsContainer]  — container dove iniettare i dots
     * @param {string}      [features.dotsPosition]   — 'left'|'center'|'right' (default: 'center')
     * @param {Object|false}[features.autoplay]       — opzioni EmblaCarouselAutoplay, o false
     * @param {Function}    [features.onSelect]       — callback(index) al cambio slide
     */
    function BWEmblaCore(viewport, emblaOpts, features) {
        this._viewport  = viewport;
        this._opts      = emblaOpts  || {};
        this._feat      = features   || {};
        this._embla     = null;
        this._dotsNodes = [];
        this._dotsList  = null;
        this._plugins   = [];
        this._autoplayPlugin = null;

        // Listener references per rimozione precisa
        this._onSelect   = this._onSelect.bind(this);
        this._onReInit   = this._onReInit.bind(this);
    }

    /* ─────────────────────────────────────────────────────────
       API Pubblica
    ───────────────────────────────────────────────────────── */

    /**
     * Inizializza Embla e tutte le feature.
     * @returns {Object|null} emblaApi oppure null se EmblaCarousel non è disponibile
     */
    BWEmblaCore.prototype.init = function () {
        if (typeof EmblaCarousel === 'undefined') {
            if (window.console) {
                console.warn('BWEmblaCore: EmblaCarousel non trovato.');
            }
            return null;
        }

        // Costruisci plugin
        if (this._feat.autoplay && typeof EmblaCarouselAutoplay !== 'undefined') {
            this._autoplayPlugin = EmblaCarouselAutoplay(this._feat.autoplay);
            this._plugins.push(this._autoplayPlugin);
        }

        this._embla = EmblaCarousel(this._viewport, this._opts, this._plugins);

        // Setup funzionalità
        if (this._feat.prevBtn || this._feat.nextBtn) {
            this._setupArrows();
        }
        if (this._feat.dotsContainer) {
            this._setupDots();
        }
        if (this._feat.autoplay && this._feat.autoplay.stopOnMouseEnter === false) {
            this._setupAutoplayHover();
        }

        // Caricamento immagini fade-in
        BWEmblaCore.initImageLoading(this._viewport);

        // Event listeners Embla
        this._embla.on('select', this._onSelect);
        this._embla.on('reInit', this._onReInit);

        // Stato iniziale
        this._onSelect();

        return this._embla;
    };

    /**
     * Ritorna l'istanza emblaApi (null se non inizializzato)
     */
    BWEmblaCore.prototype.api = function () {
        return this._embla;
    };

    /**
     * Scroll to slide by index
     */
    BWEmblaCore.prototype.scrollTo = function (index, jump) {
        if (this._embla) {
            this._embla.scrollTo(index, jump || false);
        }
    };

    /**
     * Distrugge Embla e rimuove dots generati
     */
    BWEmblaCore.prototype.destroy = function () {
        if (this._embla) {
            this._embla.off('select', this._onSelect);
            this._embla.off('reInit', this._onReInit);
            this._embla.destroy();
            this._embla = null;
        }
        if (this._dotsList && this._dotsList.parentNode) {
            this._dotsList.parentNode.removeChild(this._dotsList);
            this._dotsList  = null;
            this._dotsNodes = [];
        }
    };

    /* ─────────────────────────────────────────────────────────
       Metodi Privati
    ───────────────────────────────────────────────────────── */

    BWEmblaCore.prototype._setupArrows = function () {
        var self = this;
        var prev = this._feat.prevBtn;
        var next = this._feat.nextBtn;

        if (prev) {
            prev.addEventListener('click', function (e) {
                e.preventDefault();
                if (self._embla) self._embla.scrollPrev();
            });
        }
        if (next) {
            next.addEventListener('click', function (e) {
                e.preventDefault();
                if (self._embla) self._embla.scrollNext();
            });
        }
        this._updateArrowState();
    };

    BWEmblaCore.prototype._setupDots = function () {
        var self    = this;
        var api     = this._embla;
        var snaps   = api.scrollSnapList();
        var pos     = this._feat.dotsPosition || 'center';
        var container = this._feat.dotsContainer;

        // Crea la lista dots
        var ul = document.createElement('ul');
        ul.className = 'bw-ps-dots-list bw-ps-dots-' + pos;

        snaps.forEach(function (_, i) {
            var li  = document.createElement('li');
            var btn = document.createElement('button');
            btn.setAttribute('type', 'button');
            btn.setAttribute('aria-label', 'Slide ' + (i + 1));
            btn.addEventListener('click', function () {
                if (self._embla) self._embla.scrollTo(i);
                // Pausa autoplay al click manuale
                if (self._autoplayPlugin) {
                    self._autoplayPlugin.stop();
                }
            });
            li.appendChild(btn);
            ul.appendChild(li);
            self._dotsNodes.push(li);
        });

        container.appendChild(ul);
        this._dotsList = ul;
        this._updateDots();
    };

    BWEmblaCore.prototype._setupAutoplayHover = function () {
        var self = this;
        var ap   = this._autoplayPlugin;
        if (!ap) return;

        this._viewport.addEventListener('mouseenter', function () {
            ap.stop();
        });
        this._viewport.addEventListener('mouseleave', function () {
            ap.play();
        });
    };

    BWEmblaCore.prototype._onSelect = function () {
        this._updateDots();
        this._updateArrowState();
        if (typeof this._feat.onSelect === 'function' && this._embla) {
            this._feat.onSelect(this._embla.selectedScrollSnap());
        }
    };

    BWEmblaCore.prototype._onReInit = function () {
        // Aggiorna dots se i snap sono cambiati (es. resize)
        this._updateDots();
        this._updateArrowState();
    };

    BWEmblaCore.prototype._updateDots = function () {
        if (!this._embla || this._dotsNodes.length === 0) return;
        var selected = this._embla.selectedScrollSnap();
        this._dotsNodes.forEach(function (li, i) {
            if (i === selected) {
                li.classList.add('is-active');
            } else {
                li.classList.remove('is-active');
            }
        });
    };

    BWEmblaCore.prototype._updateArrowState = function () {
        if (!this._embla) return;
        var prev = this._feat.prevBtn;
        var next = this._feat.nextBtn;
        if (prev) {
            prev.disabled = !this._embla.canScrollPrev();
            prev.style.opacity = this._embla.canScrollPrev() ? '' : '0.3';
        }
        if (next) {
            next.disabled = !this._embla.canScrollNext();
            next.style.opacity = this._embla.canScrollNext() ? '' : '0.3';
        }
    };

    /* ─────────────────────────────────────────────────────────
       Metodi Statici
    ───────────────────────────────────────────────────────── */

    /**
     * Applicare fade-in sulle immagini in un container.
     * Prima immagine: già eager in PHP, qui gestiamo solo il callback.
     * Immagini lazy: fade-in appena caricate.
     *
     * @param {HTMLElement} container
     */
    BWEmblaCore.initImageLoading = function (container) {
        if (!container) return;
        var images = container.querySelectorAll('img');

        images.forEach(function (img) {
            if (img.complete && img.naturalWidth > 0) {
                // Use rAF so the browser first paints opacity:0 (from CSS),
                // then transitions to opacity:1 — avoids the sync-add-class race.
                requestAnimationFrame(function () { img.classList.add('is-loaded'); });
                return;
            }
            var onLoad = function () {
                img.classList.add('is-loaded');
                img.removeEventListener('load',  onLoad);
                img.removeEventListener('error', onError);
            };
            var onError = function () {
                // Mostra comunque l'immagine (anche se rotta) per non bloccare il layout
                img.classList.add('is-loaded');
                img.removeEventListener('load',  onLoad);
                img.removeEventListener('error', onError);
            };
            img.addEventListener('load',  onLoad);
            img.addEventListener('error', onError);
        });
    };

    /* ─────────────────────────────────────────────────────────
       Esposizione globale
    ───────────────────────────────────────────────────────── */
    global.BWEmblaCore = BWEmblaCore;

}(window));
