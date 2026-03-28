<?php
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BW_Trust_Box_Widget extends Widget_Base {
	private function render_editor_notice( $message ) {
		echo '<div class="elementor-alert elementor-alert-warning">';
		echo esc_html( $message );
		echo '</div>';
	}

	public function get_name() {
		return 'bw-trust-box';
	}

	public function get_title() {
		return __( 'BW Trust Box', 'bw' );
	}

	public function get_icon() {
		return 'eicon-review';
	}

	public function get_categories() {
		return [ 'blackwork' ];
	}

	public function get_style_depends() {
		if ( ! wp_style_is( 'bw-trust-box-style', 'registered' ) && function_exists( 'bw_register_trust_box_widget_assets' ) ) {
			bw_register_trust_box_widget_assets();
		}

		return [ 'bw-embla-core-css', 'bw-trust-box-style' ];
	}

	public function get_script_depends() {
		if ( ! wp_script_is( 'bw-trust-box-script', 'registered' ) && function_exists( 'bw_register_trust_box_widget_assets' ) ) {
			bw_register_trust_box_widget_assets();
		}

		return [ 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-trust-box-script' ];
	}

	protected function register_controls() {
		$this->register_content_controls();
	}

	private function register_content_controls() {
		$this->start_controls_section(
			'section_trust_review_content',
			[
				'label' => __( 'Review Trust', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'trust_review_content_notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => wp_kses_post( __( 'The review slider and fixed review box are managed globally in <strong>Blackwork Site -> Reviews Settings -> Trust Content</strong>. These toggles let you enable or disable those global trust blocks per widget instance when needed.', 'bw' ) ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'show_global_review_slider',
			[
				'label'        => __( 'Show Review Slider', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_global_review_box',
			[
				'label'        => __( 'Show Fixed Review Box', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'review_slider_transition',
			[
				'label'     => __( 'Review Slider Effect', 'bw' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'slide',
				'options'   => [
					'slide' => __( 'Slide', 'bw' ),
					'fade'  => __( 'Fade', 'bw' ),
				],
				'condition' => [
					'show_global_review_slider' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_digital_product_info_content',
			[
				'label' => __( 'Digital Product Info', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_digital_product_info',
			[
				'label'        => __( 'Enable Information Box', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$info_repeater = new Repeater();

		$info_repeater->add_control(
			'info_icon',
			[
				'label' => __( 'Icon', 'bw' ),
				'type'  => Controls_Manager::ICONS,
			]
		);

		$info_repeater->add_control(
			'info_title',
			[
				'label'       => __( 'Title', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Instant Download', 'bw' ),
				'label_block' => true,
			]
		);

		$info_repeater->add_control(
			'info_description',
			[
				'label'       => __( 'Description', 'bw' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'rows'        => 3,
				'placeholder' => __( 'Optional short description', 'bw' ),
			]
		);

		$this->add_control(
			'digital_product_info_items',
			[
				'label'       => __( 'Information Items', 'bw' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $info_repeater->get_controls(),
				'default'     => [
					[
						'info_icon'        => [ 'value' => 'bw-instant-download-file-down', 'library' => 'bw-custom' ],
						'info_title'       => __( 'Instant Download', 'bw' ),
						'info_description' => __( 'Get immediate access to your files after checkout.', 'bw' ),
					],
					[
						'info_icon'        => [ 'value' => 'bw-premium-quality-gem', 'library' => 'bw-custom' ],
						'info_title'       => __( 'Premium Quality', 'bw' ),
						'info_description' => __( 'Professionally crafted assets built for production use.', 'bw' ),
					],
					[
						'info_icon'        => [ 'value' => 'bw-lifetime-support-messages-square', 'library' => 'bw-custom' ],
						'info_title'       => __( 'Lifetime Support', 'bw' ),
						'info_description' => __( 'Keep a reliable reference point for using the product over time.', 'bw' ),
					],
				],
				'title_field' => '{{{ info_title }}}',
				'condition'   => [
					'show_digital_product_info' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_faq_button_content',
			[
				'label' => __( 'FAQ Button', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_faq_button',
			[
				'label'        => __( 'Enable FAQ Button', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'faq_button_label',
			[
				'label'       => __( 'FAQ Label', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Frequently Asked Questions', 'bw' ),
				'label_block' => true,
				'condition'   => [
					'show_faq_button' => 'yes',
				],
			]
		);

		$this->add_control(
			'faq_button_link',
			[
				'label'         => __( 'FAQ Link', 'bw' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => __( 'https://your-site.com/faq', 'bw' ),
				'show_external' => true,
				'default'       => [
					'url'         => '',
					'is_external' => false,
					'nofollow'    => false,
				],
				'condition'     => [
					'show_faq_button' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Get trust settings from Reviews Settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_reviews_trust_settings() {
		if ( ! class_exists( 'BW_Reviews_Settings' ) || ! method_exists( 'BW_Reviews_Settings', 'get_trust_settings' ) ) {
			return [];
		}

		$settings = BW_Reviews_Settings::get_trust_settings();

		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Normalize global review-slider items.
	 *
	 * @param array<string,mixed> $trust_settings Trust settings.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function get_review_slider_items( $trust_settings ) {
		$items = [];
		$rows  = isset( $trust_settings['slider_reviews'] ) && is_array( $trust_settings['slider_reviews'] )
			? $trust_settings['slider_reviews']
			: [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$text   = isset( $row['text'] ) ? trim( sanitize_textarea_field( (string) $row['text'] ) ) : '';
			$author = isset( $row['author'] ) ? trim( sanitize_text_field( (string) $row['author'] ) ) : '';

			if ( '' === $text || '' === $author ) {
				continue;
			}

			$items[] = [
				'text'   => $text,
				'author' => $author,
			];

			if ( count( $items ) >= 6 ) {
				break;
			}
		}

		return $items;
	}

	/**
	 * Resolve fixed review-box content.
	 *
	 * @param array<string,mixed> $trust_settings Trust settings.
	 *
	 * @return string
	 */
	private function get_review_box_content( $trust_settings ) {
		$content = isset( $trust_settings['review_box_content'] ) ? (string) $trust_settings['review_box_content'] : '';

		return '' !== trim( wp_strip_all_tags( $content ) ) ? wp_kses_post( $content ) : '';
	}

	/**
	 * Normalize widget-level information-box items.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_digital_product_info_items( $settings ) {
		$items = [];
		$rows  = isset( $settings['digital_product_info_items'] ) && is_array( $settings['digital_product_info_items'] )
			? $settings['digital_product_info_items']
			: [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$title       = isset( $row['info_title'] ) ? trim( sanitize_text_field( (string) $row['info_title'] ) ) : '';
			$description = isset( $row['info_description'] ) ? trim( sanitize_textarea_field( (string) $row['info_description'] ) ) : '';
			$icon        = isset( $row['info_icon'] ) && is_array( $row['info_icon'] ) ? $row['info_icon'] : [];

			if ( '' === $title && '' === $description && empty( $icon['value'] ) ) {
				continue;
			}

			$items[] = [
				'title'       => $title,
				'description' => $description,
				'icon'        => $icon,
			];
		}

		return $items;
	}

	/**
	 * Render widget info-card icon markup.
	 *
	 * @param array<string,mixed> $item Trust item payload.
	 *
	 * @return string
	 */
	private function render_digital_product_info_icon( $item ) {
		$icon       = isset( $item['icon'] ) && is_array( $item['icon'] ) ? $item['icon'] : [];
		$icon_value = isset( $icon['value'] ) ? (string) $icon['value'] : '';
		$title      = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';

		if ( 'bw-instant-download-file-down' === $icon_value || 'fas fa-download' === $icon_value ) {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>';
		}

		if ( 'bw-premium-quality-gem' === $icon_value || 'fas fa-gem' === $icon_value ) {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.5 3 8 9l4 13 4-13-2.5-6"/><path d="M17 3a2 2 0 0 1 1.6.8l3 4a2 2 0 0 1 .013 2.382l-7.99 10.986a2 2 0 0 1-3.247 0l-7.99-10.986A2 2 0 0 1 2.4 7.8l2.998-3.997A2 2 0 0 1 7 3z"/><path d="M2 9h20"/></svg>';
		}

		if ( 'bw-lifetime-support-messages-square' === $icon_value || 'fas fa-life-ring' === $icon_value ) {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/><path d="M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1"/></svg>';
		}

		if ( empty( $icon_value ) ) {
			if ( false !== stripos( $title, 'instant download' ) ) {
				return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>';
			}

			if ( false !== stripos( $title, 'premium quality' ) ) {
				return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.5 3 8 9l4 13 4-13-2.5-6"/><path d="M17 3a2 2 0 0 1 1.6.8l3 4a2 2 0 0 1 .013 2.382l-7.99 10.986a2 2 0 0 1-3.247 0l-7.99-10.986A2 2 0 0 1 2.4 7.8l2.998-3.997A2 2 0 0 1 7 3z"/><path d="M2 9h20"/></svg>';
			}

			if ( false !== stripos( $title, 'lifetime support' ) ) {
				return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/><path d="M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1"/></svg>';
			}

			return '';
		}

		ob_start();
		Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );

		return (string) ob_get_clean();
	}

	protected function render() {
		$settings       = $this->get_settings_for_display();
		$is_editor_mode = class_exists( '\Elementor\Plugin' )
			&& \Elementor\Plugin::$instance->editor
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();

		$trust_settings      = $this->get_reviews_trust_settings();
		$review_slider_items = $this->get_review_slider_items( $trust_settings );
		$review_box_content  = $this->get_review_box_content( $trust_settings );
		$show_review_slider  = ! empty( $trust_settings['enable_review_slider'] ) && ! empty( $review_slider_items ) && isset( $settings['show_global_review_slider'] ) && 'yes' === $settings['show_global_review_slider'];
		$show_review_box     = ! empty( $trust_settings['enable_review_box'] ) && '' !== $review_box_content && isset( $settings['show_global_review_box'] ) && 'yes' === $settings['show_global_review_box'];
		$review_slider_transition = isset( $settings['review_slider_transition'] ) && in_array( $settings['review_slider_transition'], [ 'slide', 'fade' ], true )
			? $settings['review_slider_transition']
			: 'slide';
		$info_box_items      = ( isset( $settings['show_digital_product_info'] ) && 'yes' === $settings['show_digital_product_info'] )
			? $this->get_digital_product_info_items( $settings )
			: [];
		$show_info_box       = ! empty( $info_box_items );
		$faq_button_enabled  = isset( $settings['show_faq_button'] ) && 'yes' === $settings['show_faq_button'];
		$faq_button_label    = isset( $settings['faq_button_label'] ) && '' !== trim( (string) $settings['faq_button_label'] )
			? (string) $settings['faq_button_label']
			: __( 'Frequently Asked Questions', 'bw' );
		$faq_button_link     = isset( $settings['faq_button_link'] ) && is_array( $settings['faq_button_link'] )
			? $settings['faq_button_link']
			: [];
		$faq_button_url      = isset( $faq_button_link['url'] ) ? esc_url( $faq_button_link['url'] ) : '';
		$show_faq_button     = ( $faq_button_enabled && '' !== $faq_button_url ) || ( $faq_button_enabled && '' === $faq_button_url && $is_editor_mode );

		if ( ! $show_review_slider && ! $show_review_box && ! $show_info_box && ! $show_faq_button ) {
			if ( $is_editor_mode ) {
				$this->render_editor_notice( __( 'BW Trust Box: Nothing to render yet. Enable trust content in Reviews Settings or in this widget.', 'bw' ) );
			}
			return;
		}
		?>
		<div class="bw-trust-box" data-bw-trust-box="yes">
			<div class="bw-trust-box__stack">
				<?php if ( $show_review_slider ) : ?>
					<?php $is_single_review_slide = count( $review_slider_items ) < 2; ?>
					<section class="bw-trust-box__card bw-trust-box__review-slider<?php echo $is_single_review_slide ? ' is-single-slide' : ''; ?>" data-bw-trust-review-slider data-bw-trust-review-slider-effect="<?php echo esc_attr( $review_slider_transition ); ?>">
						<?php if ( ! $is_single_review_slide ) : ?>
							<button class="bw-trust-box__review-slider-arrow bw-trust-box__review-slider-arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Previous review', 'bw' ); ?>">
								<svg viewBox="0 0 20 20" focusable="false" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</button>
						<?php endif; ?>

						<div class="bw-embla-viewport bw-trust-box__review-slider-viewport">
							<div class="bw-embla-container bw-trust-box__review-slider-container">
								<?php foreach ( $review_slider_items as $index => $review_item ) : ?>
									<article class="bw-embla-slide bw-trust-box__review-slide<?php echo 0 === $index ? ' is-active' : ''; ?>">
										<div class="bw-trust-box__review-slide-stars" aria-hidden="true">★★★★★</div>
										<blockquote class="bw-trust-box__review-slide-text">"<?php echo esc_html( $review_item['text'] ); ?>"</blockquote>
										<div class="bw-trust-box__review-slide-author"><?php echo esc_html( $review_item['author'] ); ?></div>
									</article>
								<?php endforeach; ?>
							</div>
						</div>

						<?php if ( ! $is_single_review_slide ) : ?>
							<button class="bw-trust-box__review-slider-arrow bw-trust-box__review-slider-arrow--next" type="button" aria-label="<?php esc_attr_e( 'Next review', 'bw' ); ?>">
								<svg viewBox="0 0 20 20" focusable="false" aria-hidden="true"><path d="M7.5 4.5 13 10l-5.5 5.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</button>
						<?php endif; ?>
					</section>
				<?php endif; ?>

				<?php if ( $show_review_box ) : ?>
					<section class="bw-trust-box__card bw-trust-box__review-box">
						<div class="bw-trust-box__review-box-stars" aria-hidden="true">★★★★★</div>
						<div class="bw-trust-box__review-box-content"><?php echo $review_box_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized via wp_kses_post ?></div>
					</section>
				<?php endif; ?>

				<?php if ( $show_info_box ) : ?>
					<section class="bw-trust-box__info-grid">
						<?php foreach ( $info_box_items as $info_item ) : ?>
							<article class="bw-trust-box__card bw-trust-box__info-card">
								<?php if ( ! empty( $info_item['icon']['value'] ) ) : ?>
									<div class="bw-trust-box__info-card-icon" aria-hidden="true">
										<?php echo $this->render_digital_product_info_icon( $info_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								<?php endif; ?>
								<?php if ( '' !== $info_item['title'] ) : ?>
									<h3 class="bw-trust-box__info-card-title"><?php echo esc_html( $info_item['title'] ); ?></h3>
								<?php endif; ?>
								<?php if ( '' !== $info_item['description'] ) : ?>
									<p class="bw-trust-box__info-card-description"><?php echo esc_html( $info_item['description'] ); ?></p>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>

				<?php if ( $show_faq_button ) : ?>
					<?php
					$faq_target = ! empty( $faq_button_link['is_external'] ) ? ' target="_blank"' : '';
					$faq_rel    = [];
					if ( ! empty( $faq_button_link['is_external'] ) ) {
						$faq_rel[] = 'noopener';
						$faq_rel[] = 'noreferrer';
					}
					if ( ! empty( $faq_button_link['nofollow'] ) ) {
						$faq_rel[] = 'nofollow';
					}
					?>
					<div class="bw-trust-box__faq-wrapper">
						<?php if ( '' !== $faq_button_url ) : ?>
							<a class="bw-trust-box__faq-button" href="<?php echo esc_url( $faq_button_url ); ?>"<?php echo $faq_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo ! empty( $faq_rel ) ? ' rel="' . esc_attr( implode( ' ', array_unique( $faq_rel ) ) ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<span class="bw-trust-box__faq-button-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
								</span>
								<span class="bw-trust-box__faq-button-label"><?php echo esc_html( $faq_button_label ); ?></span>
							</a>
						<?php else : ?>
							<div class="bw-trust-box__faq-button bw-trust-box__faq-button--preview" aria-disabled="true">
								<span class="bw-trust-box__faq-button-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
								</span>
								<span class="bw-trust-box__faq-button-label"><?php echo esc_html( $faq_button_label ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
