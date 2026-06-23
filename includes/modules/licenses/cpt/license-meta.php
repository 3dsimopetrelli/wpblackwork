<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bw_get_license_default_row_labels' ) ) {
	function bw_get_license_default_row_labels() {
		return array(
			__( 'Company size:', 'bw' ),
			__( 'Time period:', 'bw' ),
			__( 'Projects:', 'bw' ),
			__( 'Usage:', 'bw' ),
			__( 'Distribution:', 'bw' ),
			__( 'Promotion:', 'bw' ),
			__( 'Know More', 'bw' ),
		);
	}
}

if ( ! function_exists( 'bw_get_license_allowed_html' ) ) {
	function bw_get_license_allowed_html() {
		$allowed = wp_kses_allowed_html( 'post' );

		if ( ! isset( $allowed['a'] ) || ! is_array( $allowed['a'] ) ) {
			$allowed['a'] = array();
		}

		$allowed['a']['href']   = true;
		$allowed['a']['target'] = true;
		$allowed['a']['rel']    = true;
		$allowed['a']['title']  = true;

		return $allowed;
	}
}

if ( ! function_exists( 'bw_sanitize_license_row_label' ) ) {
	function bw_sanitize_license_row_label( $label ) {
		return sanitize_text_field( (string) $label );
	}
}

if ( ! function_exists( 'bw_sanitize_license_row_value' ) ) {
	function bw_sanitize_license_row_value( $value ) {
		return wp_kses( (string) $value, bw_get_license_allowed_html(), wp_allowed_protocols() );
	}
}

if ( ! function_exists( 'bw_sanitize_license_rows' ) ) {
	function bw_sanitize_license_rows( $raw_rows ) {
		$raw_rows = is_array( $raw_rows ) ? array_values( $raw_rows ) : array();
		$rows     = array();

		foreach ( $raw_rows as $index => $row ) {
			$row   = is_array( $row ) ? $row : array();
			$label = bw_sanitize_license_row_label( isset( $row['label'] ) ? $row['label'] : '' );
			$value = bw_sanitize_license_row_value( isset( $row['value'] ) ? $row['value'] : '' );

			if ( $index < 7 ) {
				$rows[] = array(
					'label' => $label,
					'value' => $value,
				);
				continue;
			}

			if ( '' === trim( wp_strip_all_tags( $label ) ) && '' === trim( wp_strip_all_tags( $value ) ) ) {
				continue;
			}

			$rows[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $rows;
	}
}

if ( ! function_exists( 'bw_get_license_editor_rows' ) ) {
	function bw_get_license_editor_rows( $license_id ) {
		$saved_rows = get_post_meta( $license_id, '_bw_license_rows', true );

		if ( ! is_array( $saved_rows ) || empty( $saved_rows ) ) {
			$rows = array();

			foreach ( bw_get_license_default_row_labels() as $label ) {
				$rows[] = array(
					'label' => $label,
					'value' => '',
				);
			}
		} else {
			$rows = array();

			foreach ( $saved_rows as $row ) {
				$row    = is_array( $row ) ? $row : array();
				$rows[] = array(
					'label' => bw_sanitize_license_row_label( isset( $row['label'] ) ? $row['label'] : '' ),
					'value' => bw_sanitize_license_row_value( isset( $row['value'] ) ? $row['value'] : '' ),
				);
			}
		}

		$target_count = max( 10, count( $rows ) + 2 );
		while ( count( $rows ) < $target_count ) {
			$rows[] = array(
				'label' => '',
				'value' => '',
			);
		}

		return $rows;
	}
}

if ( ! function_exists( 'bw_get_license_rows' ) ) {
	function bw_get_license_rows( $license_id ) {
		$rows = get_post_meta( $license_id, '_bw_license_rows', true );
		$rows = is_array( $rows ) ? $rows : array();
		$out  = array();

		foreach ( $rows as $row ) {
			$row   = is_array( $row ) ? $row : array();
			$label = bw_sanitize_license_row_label( isset( $row['label'] ) ? $row['label'] : '' );
			$value = bw_sanitize_license_row_value( isset( $row['value'] ) ? $row['value'] : '' );

			if ( '' === trim( wp_strip_all_tags( $label ) ) && '' === trim( wp_strip_all_tags( $value ) ) ) {
				continue;
			}

			$out[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'bw_get_license_options' ) ) {
	function bw_get_license_options() {
		$posts   = get_posts(
			array(
				'post_type'      => 'bw_license',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);
		$options = array();

		foreach ( $posts as $post_id ) {
			$options[ $post_id ] = get_the_title( $post_id );
		}

		return $options;
	}
}

if ( ! function_exists( 'bw_get_variation_selected_license_id' ) ) {
	function bw_get_variation_selected_license_id( $variation_id ) {
		return absint( get_post_meta( $variation_id, '_bw_variation_license_id', true ) );
	}
}

if ( ! function_exists( 'bw_build_license_table_html_from_rows' ) ) {
	function bw_build_license_table_html_from_rows( $rows ) {
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return '';
		}

		$markup = '<div class="bw-license-table-wrapper"><table class="bw-license-table bw-license-table--clean"><tbody>';

		foreach ( $rows as $row ) {
			$row   = is_array( $row ) ? $row : array();
			$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
			$value = isset( $row['value'] ) ? trim( (string) $row['value'] ) : '';

			if ( '' === $label && '' === trim( wp_strip_all_tags( $value ) ) ) {
				continue;
			}

			$markup .= sprintf(
				'<tr><td class="bw-license-table__cell bw-license-table__cell--label">%1$s</td><td class="bw-license-table__cell bw-license-table__cell--value">%2$s</td></tr>',
				esc_html( $label ),
				$value
			);
		}

		$markup .= '</tbody></table></div>';

		return $markup;
	}
}

if ( ! function_exists( 'bw_get_variation_license_rows_from_legacy_meta' ) ) {
	function bw_get_variation_license_rows_from_legacy_meta( $variation_id ) {
		$col1 = get_post_meta( $variation_id, '_bw_variation_license_col1', true );
		$col2 = get_post_meta( $variation_id, '_bw_variation_license_col2', true );
		$col1 = is_array( $col1 ) ? array_values( $col1 ) : array();
		$col2 = is_array( $col2 ) ? array_values( $col2 ) : array();
		$rows = array();

		for ( $index = 0; $index < 10; $index++ ) {
			$label = isset( $col1[ $index ] ) ? bw_sanitize_license_row_label( $col1[ $index ] ) : '';
			$value = isset( $col2[ $index ] ) ? bw_sanitize_license_row_value( $col2[ $index ] ) : '';

			if ( '' === trim( wp_strip_all_tags( $label ) ) && '' === trim( wp_strip_all_tags( $value ) ) ) {
				continue;
			}

			$rows[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $rows;
	}
}

if ( ! function_exists( 'bw_get_variation_license_table_html' ) ) {
	function bw_get_variation_license_table_html( $variation_id ) {
		$license_id = bw_get_variation_selected_license_id( $variation_id );

		if ( $license_id > 0 ) {
			$license_post = get_post( $license_id );

			if (
				$license_post
				&& 'bw_license' === $license_post->post_type
				&& 'publish' === $license_post->post_status
			) {
				$license_rows = bw_get_license_rows( $license_id );
				$license_html = bw_build_license_table_html_from_rows( $license_rows );

				if ( '' !== $license_html ) {
					return $license_html;
				}
			}
		}

		return bw_build_license_table_html_from_rows( bw_get_variation_license_rows_from_legacy_meta( $variation_id ) );
	}
}

if ( ! function_exists( 'bw_register_license_terms_metabox' ) ) {
	function bw_register_license_terms_metabox() {
		add_meta_box(
			'bw_license_terms_metabox',
			__( 'License Terms', 'bw' ),
			'bw_render_license_terms_metabox',
			'bw_license',
			'normal',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'bw_register_license_terms_metabox' );

if ( ! function_exists( 'bw_render_license_terms_metabox' ) ) {
	function bw_render_license_terms_metabox( $post ) {
		$rows = bw_get_license_editor_rows( $post->ID );

		wp_nonce_field( 'bw_save_license_terms_metabox', 'bw_license_terms_nonce' );
		?>
		<p><?php esc_html_e( 'Only non-empty rows are displayed on the product.', 'bw' ); ?></p>
		<div class="bw-license-terms-editor">
			<div class="bw-license-terms-editor__header">
				<span><?php esc_html_e( 'Column 1', 'bw' ); ?></span>
				<span><?php esc_html_e( 'Column 2', 'bw' ); ?></span>
				<span class="bw-license-terms-editor__actions-column" aria-hidden="true"></span>
			</div>
			<div class="bw-license-terms-editor__rows" data-bw-license-rows="1">
				<?php foreach ( $rows as $index => $row ) : ?>
					<?php $is_extra_row = $index >= 7; ?>
					<div class="bw-license-terms-editor__row" data-row-index="<?php echo esc_attr( $index ); ?>">
						<input
							type="text"
							name="bw_license_rows[<?php echo esc_attr( $index ); ?>][label]"
							value="<?php echo esc_attr( $row['label'] ); ?>"
							class="widefat"
							placeholder="<?php echo esc_attr( $is_extra_row ? __( 'Custom label', 'bw' ) : __( 'Row label', 'bw' ) ); ?>"
						/>
						<textarea
							name="bw_license_rows[<?php echo esc_attr( $index ); ?>][value]"
							class="widefat bw-license-terms-editor__textarea"
							rows="3"
							placeholder="<?php echo esc_attr__( 'Safe HTML value', 'bw' ); ?>"
						><?php echo esc_textarea( $row['value'] ); ?></textarea>
						<div class="bw-license-terms-editor__row-actions">
							<?php if ( $is_extra_row ) : ?>
								<button type="button" class="button-link-delete bw-license-terms-editor__remove-row"><?php esc_html_e( 'Remove', 'bw' ); ?></button>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" class="button" data-bw-license-add-row="1"><?php esc_html_e( 'Add Row', 'bw' ); ?></button>
			</p>
		</div>
		<script>
		(function() {
			const rowsRoot = document.querySelector('[data-bw-license-rows="1"]');
			const addButton = document.querySelector('[data-bw-license-add-row="1"]');

			if (!rowsRoot || !addButton) {
				return;
			}

			function nextIndex() {
				return rowsRoot.querySelectorAll('.bw-license-terms-editor__row').length;
			}

			function rowMarkup(index) {
				return `
					<div class="bw-license-terms-editor__row" data-row-index="${index}">
						<input
							type="text"
							name="bw_license_rows[${index}][label]"
							value=""
							class="widefat"
							placeholder="<?php echo esc_attr__( 'Custom label', 'bw' ); ?>"
						/>
						<textarea
							name="bw_license_rows[${index}][value]"
							class="widefat bw-license-terms-editor__textarea"
							rows="3"
							placeholder="<?php echo esc_attr__( 'Safe HTML value', 'bw' ); ?>"
						></textarea>
						<div class="bw-license-terms-editor__row-actions">
							<button type="button" class="button-link-delete bw-license-terms-editor__remove-row"><?php esc_html_e( 'Remove', 'bw' ); ?></button>
						</div>
					</div>
				`;
			}

			addButton.addEventListener('click', function() {
				rowsRoot.insertAdjacentHTML('beforeend', rowMarkup(nextIndex()));
			});

			rowsRoot.addEventListener('click', function(event) {
				const removeButton = event.target.closest('.bw-license-terms-editor__remove-row');
				if (!removeButton) {
					return;
				}

				event.preventDefault();
				const row = removeButton.closest('.bw-license-terms-editor__row');
				if (row) {
					row.remove();
				}
			});
		})();
		</script>
		<?php
	}
}

if ( ! function_exists( 'bw_save_license_terms_metabox' ) ) {
	function bw_save_license_terms_metabox( $post_id ) {
		if ( ! isset( $_POST['bw_license_terms_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bw_license_terms_nonce'] ) ), 'bw_save_license_terms_metabox' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw_rows = isset( $_POST['bw_license_rows'] ) && is_array( $_POST['bw_license_rows'] )
			? wp_unslash( $_POST['bw_license_rows'] )
			: array();

		update_post_meta( $post_id, '_bw_license_rows', bw_sanitize_license_rows( $raw_rows ) );
	}
}
add_action( 'save_post_bw_license', 'bw_save_license_terms_metabox' );

if ( ! function_exists( 'bw_license_terms_metabox_admin_css' ) ) {
	function bw_license_terms_metabox_admin_css() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'bw_license' !== $screen->post_type ) {
			return;
		}
		?>
		<style>
			.bw-license-terms-editor__header,
			.bw-license-terms-editor__row {
				display: grid;
				grid-template-columns: minmax(200px, 1fr) minmax(260px, 2fr) auto;
				gap: 12px;
				align-items: start;
			}
			.bw-license-terms-editor__header {
				margin-bottom: 8px;
				font-weight: 600;
			}
			.bw-license-terms-editor__rows {
				display: grid;
				gap: 10px;
			}
			.bw-license-terms-editor__textarea {
				min-height: 88px;
				resize: vertical;
			}
			.bw-license-terms-editor__row-actions {
				padding-top: 6px;
				min-width: 68px;
			}
			@media (max-width: 960px) {
				.bw-license-terms-editor__header,
				.bw-license-terms-editor__row {
					grid-template-columns: 1fr;
				}
				.bw-license-terms-editor__actions-column {
					display: none;
				}
				.bw-license-terms-editor__row-actions {
					padding-top: 0;
				}
			}
		</style>
		<?php
	}
}
add_action( 'admin_head', 'bw_license_terms_metabox_admin_css' );
