<?php

function sas_post_pagination($wp_query) {

    /** Stop execution if there's only 1 page */
    if( $wp_query->max_num_pages <= 1 ) {
        return;
    }

    if ( get_query_var('paged') ) { $paged = get_query_var('paged');} 
    elseif ( get_query_var('page') ) { $paged = get_query_var('page');} 
    else { $paged = 1; }
    $max   = intval( $wp_query->max_num_pages );

    /** Add current page to the array */
    if ( $paged >= 1 )
        $links[] = $paged;

    /** Add the pages around the current page to the array */
    if ( $paged >= 3 ) {
        $links[] = $paged - 1;
        $links[] = $paged - 2;
    }

    if ( ( $paged + 2 ) <= $max ) {
        $links[] = $paged + 2;
        $links[] = $paged + 1;
    }

    echo '<ul class="page-numbers">' . "\n";

    /** Previous Post Link */
    if ( get_previous_posts_link() )
        printf( '<li>%s</li>' . "\n", get_previous_posts_link('«') );

    /** Link to first page, plus ellipses if necessary */
    if ( ! in_array( 1, $links ) ) {
        $class = 1 == $paged ? ' current' : '';
        $type = 1 == $paged ? 'span' : 'a';

        printf( '<li><%s href="%s" class="page-numbers %s">%s</%s></li>' . "\n", $type, esc_url( get_pagenum_link( 1 ) ), $class, '1', $type );

        if ( ! in_array( 2, $links ) )
            echo '<li class="sas-pagination-dot-dot"><span>...</span></li>';
    }

    /** Link to current page, plus 2 pages in either direction if necessary */
    sort( $links );
    foreach ( (array) $links as $link ) {
        $class = $paged == $link ? ' current' : '';
        $type = $paged == $link ? 'span' : 'a';
        printf( '<li><%s href="%s" class="page-numbers %s">%s</%s></li>' . "\n", $type, esc_url( get_pagenum_link( $link ) ), $class, $link, $type );
    }

    /** Link to last page, plus ellipses if necessary */
    if ( ! in_array( $max, $links ) ) {
        if ( ! in_array( $max - 1, $links ) )
            echo '<li class="sas-pagination-dot-dot"><span>...</span></li>' . "\n";

        $class = $paged == $max ? ' current' : '';
        $type = $paged == $max ? 'span' : 'a';
        printf( '<li><%s href="%s" class="page-numbers %s">%s</%s></li>' . "\n", $type, esc_url( get_pagenum_link( $max ) ), $class, $max, $type);
    }

    /** Next Post Link */
    if ( get_next_posts_link() )
        printf( '<li>%s</li>' . "\n", get_next_posts_link('»') );


    add_filter('next_posts_link_attributes', 'posts_link_attributes_next');
    add_filter('previous_posts_link_attributes', 'posts_link_attributes_prev');

    echo '</ul>' . "\n";
}

function posts_link_attributes_prev() {
    return 'class="prev-post"';
}

function posts_link_attributes_next() {
    return 'class="next-post"';
}