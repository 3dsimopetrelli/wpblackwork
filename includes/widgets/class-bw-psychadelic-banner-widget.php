<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BW_Psychadelic_Banner_Widget extends Widget_Base {

	public function get_name() {
		return 'bw-psychadelic-banner';
	}

	public function get_title() {
		return __( 'BW-UI Psychadelic Banner', 'bw' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'blackwork' );
	}

	public function get_style_depends() {
		return array( 'bw-psychadelic-banner-style' );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	private function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'bw' ),
			)
		);

		$this->add_control(
			'labels_list',
			array(
				'label'       => __( 'Labels List', 'bw' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => implode(
					"\n",
					array(
						'Human',
						'Gorilla',
						'Crocodile',
						'Wild Boar',
						'Ostrich Sternum',
						'Plecotus Teeth',
						'Anteater',
						'Beaver',
						'Colubrid',
						'Seal',
					)
				),
				'placeholder' => __( 'One label per line', 'bw' ),
				'description' => __( 'Each non-empty line becomes a pill label in the animated loop.', 'bw' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'center_image',
			array(
				'label' => __( 'Center PNG', 'bw' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$this->add_responsive_control(
			'center_image_position',
			array(
				'label'     => __( 'Center Image Position', 'bw' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Left', 'bw' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center'     => array(
						'title' => __( 'Center', 'bw' ),
						'icon'  => 'eicon-h-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'Right', 'bw' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .bw-psychadelic-banner__image-layer' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'center_image_width',
			array(
				'label'          => __( 'Center Image Width', 'bw' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', '%', 'vw' ),
				'range'          => array(
					'px' => array(
						'min'  => 80,
						'max'  => 1600,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'        => array(
					'size' => 42,
					'unit' => 'vw',
				),
				'tablet_default' => array(
					'size' => 50,
					'unit' => 'vw',
				),
				'mobile_default' => array(
					'size' => 64,
					'unit' => 'vw',
				),
				'selectors'      => array(
					'{{WRAPPER}} .bw-psychadelic-banner__image-shell' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'banner_height',
			array(
				'label'      => __( 'Banner Height', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh', '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 180,
						'max'  => 1400,
						'step' => 1,
					),
					'vh' => array(
						'min'  => 20,
						'max'  => 100,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 72,
					'unit' => 'vh',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'inner_padding',
			array(
				'label'      => __( 'Inner Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'vw', 'vh' ),
				'default'    => array(
					'top'      => 18,
					'right'    => 0,
					'bottom'   => 18,
					'left'     => 0,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner__background' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .bw-psychadelic-banner__image-layer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'row_count',
			array(
				'label'   => __( 'Rows', 'bw' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '5',
				'options' => array(
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
				),
			)
		);

		$this->add_control(
			'animation_enabled',
			array(
				'label'        => __( 'Animation', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'animation_speed',
			array(
				'label'       => __( 'Animation Speed', 'bw' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array(
					'px' => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'     => array(
					'size' => 55,
					'unit' => 'px',
				),
				'description' => __( 'Higher value = faster loop motion.', 'bw' ),
				'condition'   => array(
					'animation_enabled' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_style_controls() {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'background_color',
			array(
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#050505',
				'selectors' => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => 'background-color: {{VALUE}};',
				),
			)
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'label_text_color',
			__( 'Label Text Color', 'bw' ),
			'{{WRAPPER}} .bw-psychadelic-banner',
			'--bw-pb-label-text',
			'#f7f4ef'
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'label_border_color',
			__( 'Label Border Color', 'bw' ),
			'{{WRAPPER}} .bw-psychadelic-banner',
			'--bw-pb-label-border',
			'rgba(255,255,255,0.9)'
		);

		BW_Widget_Helper::add_color_var_control(
			$this,
			'label_background_color',
			__( 'Label Background Color', 'bw' ),
			'{{WRAPPER}} .bw-psychadelic-banner',
			'--bw-pb-label-bg',
			'rgba(0,0,0,0)'
		);

		BW_Widget_Helper::add_typography_group(
			$this,
			'labels_typography',
			'{{WRAPPER}} .bw-psychadelic-banner__label',
			array(
				'exclude' => array( 'font_size' ),
			)
		);

		$this->add_responsive_control(
			'labels_font_vw',
			array(
				'label'      => __( 'Label Font Width (vw)', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'vw' ),
				'range'      => array(
					'vw' => array(
						'min'  => 0,
						'max'  => 20,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'size' => 2.4,
					'unit' => 'vw',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => '--bw-pb-font-vw: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'labels_font_vh',
			array(
				'label'      => __( 'Label Font Height (vh)', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'vh' ),
				'range'      => array(
					'vh' => array(
						'min'  => 0,
						'max'  => 20,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'size' => 0.2,
					'unit' => 'vh',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => '--bw-pb-font-vh: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'labels_font_min',
			array(
				'label'      => __( 'Label Font Min', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array(
					'px'  => array(
						'min'  => 8,
						'max'  => 120,
						'step' => 1,
					),
					'rem' => array(
						'min'  => 0.5,
						'max'  => 8,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'size' => 20,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => '--bw-pb-font-min: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'labels_font_max',
			array(
				'label'      => __( 'Label Font Max', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array(
					'px'  => array(
						'min'  => 8,
						'max'  => 180,
						'step' => 1,
					),
					'rem' => array(
						'min'  => 0.5,
						'max'  => 12,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'size' => 56,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => '--bw-pb-font-max: {{SIZE}}{{UNIT}};',
				),
			)
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'label_padding',
			__( 'Label Padding', 'bw' ),
			'{{WRAPPER}} .bw-psychadelic-banner__label',
			'padding',
			array( 'px', 'em', '%' ),
			array(
				'top'      => 14,
				'right'    => 26,
				'bottom'   => 14,
				'left'     => 26,
				'unit'     => 'px',
				'isLinked' => false,
			)
		);

		$this->add_responsive_control(
			'label_radius',
			array(
				'label'      => __( 'Label Radius', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 120,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'size' => 999,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => '--bw-pb-label-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => __( 'Rows Gap', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 120,
						'step' => 1,
					),
					'vh' => array(
						'min'  => 0,
						'max'  => 20,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'size' => 20,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => '--bw-pb-row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'pill_gap',
			array(
				'label'      => __( 'Labels Gap', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 80,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 0,
						'max'  => 10,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'size' => 18,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-psychadelic-banner' => '--bw-pb-pill-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings        = $this->get_settings_for_display();
		$labels          = $this->get_clean_labels( isset( $settings['labels_list'] ) ? (string) $settings['labels_list'] : '' );
		$image_id        = isset( $settings['center_image']['id'] ) ? absint( $settings['center_image']['id'] ) : 0;
		$image_url       = isset( $settings['center_image']['url'] ) ? esc_url_raw( (string) $settings['center_image']['url'] ) : '';
		$animation_class = ( isset( $settings['animation_enabled'] ) && 'yes' === $settings['animation_enabled'] ) ? 'is-animated' : 'is-static';
		$row_count       = isset( $settings['row_count'] ) ? max( 2, min( 8, absint( $settings['row_count'] ) ) ) : 5;
		$base_duration   = $this->get_animation_base_duration( $settings );

		if ( empty( $labels ) ) {
			$labels = array( 'Psychadelic', 'Banner', 'Blackwork', 'Archive' );
		}

		$rows = $this->build_rows( $labels, $row_count );
		?>
		<div
			class="bw-psychadelic-banner <?php echo esc_attr( $animation_class ); ?>"
			style="--bw-pb-base-duration: <?php echo esc_attr( $base_duration ); ?>s;"
		>
			<div class="bw-psychadelic-banner__background" aria-hidden="true">
				<?php foreach ( $rows as $row_index => $row_labels ) : ?>
					<div class="bw-psychadelic-banner__row <?php echo 0 === $row_index % 2 ? 'is-forward' : 'is-reverse'; ?>">
						<div class="bw-psychadelic-banner__track">
							<?php echo $this->render_labels_group( $row_labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php if ( 'yes' === $settings['animation_enabled'] ) : ?>
								<div class="bw-psychadelic-banner__group" aria-hidden="true">
									<?php echo $this->render_labels_markup( $row_labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $image_id || $image_url ) : ?>
				<div class="bw-psychadelic-banner__image-layer">
					<div class="bw-psychadelic-banner__image-shell">
						<?php
						if ( $image_id ) {
							echo wp_get_attachment_image(
								$image_id,
								'full',
								false,
								array(
									'class'   => 'bw-psychadelic-banner__image',
									'loading' => 'lazy',
								)
							);
						} else {
							?>
							<img
								class="bw-psychadelic-banner__image"
								src="<?php echo esc_url( $image_url ); ?>"
								alt=""
								loading="lazy"
							>
							<?php
						}
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function get_clean_labels( $raw_labels ) {
		$raw_labels = str_replace( ',', "\n", $raw_labels );
		$lines      = preg_split( '/\r\n|\r|\n/', (string) $raw_labels ) ?: array();
		$labels     = array();

		foreach ( $lines as $line ) {
			$clean = trim( wp_strip_all_tags( $line ) );
			if ( '' !== $clean ) {
				$labels[] = $clean;
			}
		}

		return array_values( array_unique( $labels ) );
	}

	private function build_rows( $labels, $row_count ) {
		$rows        = array();
		$label_count = count( $labels );

		for ( $index = 0; $index < $row_count; $index++ ) {
			$row = array();
			for ( $cursor = 0; $cursor < $label_count; $cursor++ ) {
				$row[] = $labels[ ( $cursor + $index ) % $label_count ];
			}
			$rows[] = $row;
		}

		return $rows;
	}

	private function render_labels_group( $labels ) {
		return '<div class="bw-psychadelic-banner__group">' . $this->render_labels_markup( $labels ) . '</div>';
	}

	private function render_labels_markup( $labels ) {
		$markup = '';

		foreach ( $labels as $label ) {
			$markup .= '<span class="bw-psychadelic-banner__label">' . esc_html( $label ) . '</span>';
		}

		return $markup;
	}

	private function get_animation_base_duration( $settings ) {
		$speed = isset( $settings['animation_speed']['size'] ) ? absint( $settings['animation_speed']['size'] ) : 55;
		$speed = max( 10, min( 100, $speed ) );

		// Map a friendly "speed" editor control to marquee duration seconds.
		return max( 10, 54 - ( $speed * 0.4 ) );
	}
}
