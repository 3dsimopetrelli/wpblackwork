/* BW Metabox Admin */
( function ( $ ) {
    'use strict';

    /* ── Product type toggle ──────────────────────────────────────────── */
    function initProductTypeToggle() {
        var select        = document.querySelector( '#bw_product_type' );
        var digitalFields = document.querySelectorAll( '.bw-digital-fields' );
        var physicalFields = document.querySelectorAll( '.bw-physical-fields' );

        if ( ! select ) {
            return;
        }

        function toggleFields() {
            if ( select.value === 'digital' ) {
                digitalFields.forEach( function ( el ) { el.style.display = 'block'; } );
                physicalFields.forEach( function ( el ) { el.style.display = 'none'; } );
            } else {
                digitalFields.forEach( function ( el ) { el.style.display = 'none'; } );
                physicalFields.forEach( function ( el ) { el.style.display = 'block'; } );
            }
        }

        select.addEventListener( 'change', toggleFields );
        toggleFields();
    }

    /* ── Product search (Select2 / SelectWoo) ─────────────────────────── */
    function initProductSearch() {
        var data        = ( typeof bwMetaboxData !== 'undefined' ) ? bwMetaboxData : {};
        var nonce       = data.nonce || '';
        var placeholder = ( data.i18n && data.i18n.searchPlaceholder ) ? data.i18n.searchPlaceholder : '';

        var selectFn = null;
        if ( typeof $.fn.select2 !== 'undefined' ) {
            selectFn = 'select2';
        } else if ( typeof $.fn.selectWoo !== 'undefined' ) {
            selectFn = 'selectWoo';
        }

        if ( ! selectFn ) {
            return;
        }

        $( '#bw_showcase_linked_product' )[ selectFn ]( {
            ajax: {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function ( params ) {
                    return {
                        q:      params.term,
                        action: 'bw_search_products',
                        nonce:  nonce
                    };
                },
                processResults: function ( response ) {
                    return { results: response };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: placeholder,
            allowClear: true
        } );
    }

    /* ── Showcase image media uploader ────────────────────────────────── */
    function initMediaUploader() {
        var data = ( typeof bwMetaboxData !== 'undefined' ) ? bwMetaboxData : {};
        var i18n = data.i18n || {};

        function resetPreview( $container ) {
            $container.find( '#bw_showcase_image' ).val( '' );
            $container.find( '#bw_showcase_image_preview' ).empty().hide();
        }

        $( document ).on( 'click', '.bw-upload-image', function ( e ) {
            e.preventDefault();

            var $container = $( this ).closest( '.postbox' );

            var frame = wp.media( {
                title:    i18n.mediaTitle  || '',
                button:   { text: i18n.mediaButton || '' },
                multiple: false
            } );

            frame.on( 'select', function () {
                var attachment = frame.state().get( 'selection' ).first().toJSON();
                var imageStyle = 'border-radius:6px;width:120px;height:120px;object-fit:cover;';
                var previewUrl = attachment.url;

                if ( attachment.sizes ) {
                    if ( attachment.sizes.thumbnail ) {
                        previewUrl = attachment.sizes.thumbnail.url;
                    } else if ( attachment.sizes.medium ) {
                        previewUrl = attachment.sizes.medium.url;
                    }
                }

                $container.find( '#bw_showcase_image' ).val( attachment.id ? attachment.id : attachment.url );
                $container.find( '#bw_showcase_image_preview' )
                    .html( '<img src="' + previewUrl + '" style="' + imageStyle + '" alt="" />' )
                    .show();
            } );

            frame.open();
        } );

        $( document ).on( 'click', '.bw-remove-image', function ( e ) {
            e.preventDefault();
            resetPreview( $( this ).closest( '.postbox' ) );
        } );
    }

    /* ── Init ──────────────────────────────────────────────────────────── */
    $( document ).ready( function () {
        initProductTypeToggle();
        initProductSearch();
        initMediaUploader();
    } );

} ( jQuery ) );
