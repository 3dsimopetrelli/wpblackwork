<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Widget_Loader {
    private static $instance = null;

    /**
     * Flag per evitare registrazioni multiple.
     *
     * @var bool
     */
    private $widgets_registered = false;

    private function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
    }

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register_widgets( $widgets_manager = null ) {
        if ( $this->widgets_registered ) {
            return;
        }

        if ( ! did_action( 'elementor/loaded' ) && null === $widgets_manager ) {
            return;
        }

        if ( null === $widgets_manager && class_exists( '\Elementor\Plugin' ) ) {
            $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        }

        if ( ! $widgets_manager ) {
            return;
        }

        $files = glob( __DIR__ . '/widgets/class-bw-*-widget.php' ) ?: [];
        foreach ( $files as $file ) {
            require_once $file;
            $class_name = $this->get_class_from_file( $file );

            if ( ! $class_name || ! class_exists( $class_name ) ) {
                continue;
            }

            $this->register_widget_with_manager( $widgets_manager, $class_name );
        }

        $this->widgets_registered = true;
    }

    private function register_widget_with_manager( $widgets_manager, $class_name ) {
        $widget_instance = new $class_name();

        if ( method_exists( $widgets_manager, 'register' ) ) {
            $widgets_manager->register( $widget_instance );
            return;
        }

        if ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
            $widgets_manager->register_widget_type( $widget_instance );
        }
    }

    private function get_class_from_file( $file ) {
        $basename = basename( $file );

        if ( ! preg_match( '/^class-(.+)-widget\.php$/', $basename, $matches ) ) {
            return '';
        }

        $parts = array_filter( explode( '-', $matches[1] ) );
        $parts = array_map( static function( $part ) {
            return ucfirst( $part );
        }, $parts );

        if ( empty( $parts ) ) {
            return '';
        }

        return 'Widget_' . implode( '_', $parts );
    }
}

BW_Widget_Loader::instance();
