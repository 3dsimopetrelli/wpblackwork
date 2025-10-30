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
            $class_names = $this->get_classes_from_file( $file );

            if ( empty( $class_names ) ) {
                continue;
            }

            foreach ( $class_names as $class_name ) {
                if ( ! class_exists( $class_name ) ) {
                    continue;
                }

                $this->register_widget_with_manager( $widgets_manager, $class_name );
                break;
            }
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

    private function get_classes_from_file( $file ) {
        $basename = basename( $file );

        if ( ! preg_match( '/^class-(.+)-widget\.php$/', $basename, $matches ) ) {
            return [];
        }

        $parts = array_filter( explode( '-', $matches[1] ) );
        $parts = array_map( static function( $part ) {
            return ucfirst( $part );
        }, $parts );

        if ( empty( $parts ) ) {
            return [];
        }

        $class_candidates   = [];
        $base_name          = implode( '_', $parts );
        $class_candidates[] = 'Widget_' . $base_name;

        $parts_without_prefix = $parts;

        if ( ! empty( $parts_without_prefix ) && 'Bw' === $parts_without_prefix[0] ) {
            array_shift( $parts_without_prefix );
        }

        if ( ! empty( $parts_without_prefix ) ) {
            $prefixed_base       = implode( '_', $parts_without_prefix );
            $class_candidates[]  = 'BW_' . $prefixed_base . '_Widget';
        }

        return array_unique( $class_candidates );
    }
}

BW_Widget_Loader::instance();
