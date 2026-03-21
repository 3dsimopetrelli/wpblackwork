<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the WooCommerce product hover media metabox.
 */
function bw_register_product_slider_hover_metabox() {
	add_meta_box(
		'bw_product_slider_image',
		__( 'Hover Media', 'bw' ),
		'bw_render_product_slider_hover_metabox',
		'product',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_product', 'bw_register_product_slider_hover_metabox' );

/**
 * Render the Hover Media metabox content.
 *
 * @param \WP_Post $post Current post object.
 */
function bw_render_product_slider_hover_metabox( $post ) {
	wp_enqueue_media();

	$image_id   = (int) get_post_meta( $post->ID, '_bw_slider_hover_image', true );
	$image_url  = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
	$video_id   = (int) get_post_meta( $post->ID, '_bw_slider_hover_video', true );
	$video_url  = $video_id ? wp_get_attachment_url( $video_id ) : '';
	$video_type = $video_url ? get_post_mime_type( $video_id ) : 'video/mp4';

	wp_nonce_field( 'bw_slider_hover_media_nonce', 'bw_slider_hover_media_nonce' );
	?>
	<div class="bw-slider-metabox-wrapper">
		<p style="margin-top:0;color:#50575e;">
			<?php esc_html_e( 'Hover Video has priority. If no video is set, the hover image will be used as fallback.', 'bw' ); ?>
		</p>

		<div class="bw-slider-metabox-field" style="margin-bottom:16px;">
			<strong style="display:block;margin-bottom:8px;"><?php esc_html_e( 'Hover Image', 'bw' ); ?></strong>
			<div class="bw-slider-metabox-preview bw-slider-metabox-preview--image" style="margin-bottom:10px;max-width:280px;">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" style="display:block;width:100%;height:auto;max-width:280px;border-radius:6px;" alt="<?php echo esc_attr__( 'Hover image preview', 'bw' ); ?>">
				<?php endif; ?>
			</div>
			<input type="hidden" name="bw_slider_hover_image" id="bw_slider_hover_image" value="<?php echo esc_attr( $image_id ); ?>">
			<button type="button" class="button bw-upload-slider-hover-image"><?php esc_html_e( 'Upload Image', 'bw' ); ?></button>
			<button type="button" class="button bw-remove-slider-hover-image" style="display:<?php echo $image_url ? 'inline-block' : 'none'; ?>;">
				<?php esc_html_e( 'Remove', 'bw' ); ?>
			</button>
		</div>

		<div class="bw-slider-metabox-field">
			<strong style="display:block;margin-bottom:8px;"><?php esc_html_e( 'Hover Video', 'bw' ); ?></strong>
			<div class="bw-slider-metabox-preview bw-slider-metabox-preview--video" style="margin-bottom:10px;max-width:280px;">
				<?php if ( $video_url ) : ?>
					<video
						src="<?php echo esc_url( $video_url ); ?>"
						style="width:100%;height:auto;max-width:280px;border-radius:6px;display:block;background:#000;"
						muted
						playsinline
						preload="metadata"
						controls
					>
						<source src="<?php echo esc_url( $video_url ); ?>" type="<?php echo esc_attr( $video_type ); ?>">
					</video>
				<?php endif; ?>
			</div>
			<input type="hidden" name="bw_slider_hover_video" id="bw_slider_hover_video" value="<?php echo esc_attr( $video_id ); ?>">
			<button type="button" class="button bw-upload-slider-hover-video"><?php esc_html_e( 'Upload Video', 'bw' ); ?></button>
			<button type="button" class="button bw-remove-slider-hover-video" style="display:<?php echo $video_url ? 'inline-block' : 'none'; ?>;">
				<?php esc_html_e( 'Remove', 'bw' ); ?>
			</button>
		</div>
	</div>

	<script>
	jQuery(function($){
		var imageFrame = null;
		var videoFrame = null;

		function renderImagePreview(attachment) {
			var imageHtml = '<img src="' + attachment.url + '" style="display:block;width:100%;height:auto;max-width:280px;border-radius:6px;" alt="<?php echo esc_js( __( 'Hover image preview', 'bw' ) ); ?>">';
			$('.bw-slider-metabox-preview--image').html(imageHtml);
			$('.bw-remove-slider-hover-image').show();
		}

		function renderVideoPreview(attachment) {
			var mimeType = attachment.mime || 'video/mp4';
			var videoHtml = '<video src="' + attachment.url + '" style="width:100%;height:auto;max-width:280px;border-radius:6px;display:block;background:#000;" muted playsinline preload="metadata" controls><source src="' + attachment.url + '" type="' + mimeType + '"></video>';
			$('.bw-slider-metabox-preview--video').html(videoHtml);
			$('.bw-remove-slider-hover-video').show();
		}

		$('.bw-upload-slider-hover-image').on('click', function(e){
			e.preventDefault();

			if (imageFrame) {
				imageFrame.open();
				return;
			}

			imageFrame = wp.media({
				title: '<?php echo esc_js( __( 'Select or Upload Hover Image', 'bw' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Use this image', 'bw' ) ); ?>' },
				library: { type: 'image' },
				multiple: false
			});

			imageFrame.on('select', function(){
				var attachment = imageFrame.state().get('selection').first().toJSON();
				$('#bw_slider_hover_image').val(attachment.id);
				renderImagePreview(attachment);
			});

			imageFrame.open();
		});

		$('.bw-upload-slider-hover-video').on('click', function(e){
			e.preventDefault();

			if (videoFrame) {
				videoFrame.open();
				return;
			}

			videoFrame = wp.media({
				title: '<?php echo esc_js( __( 'Select or Upload Hover Video', 'bw' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Use this video', 'bw' ) ); ?>' },
				library: { type: 'video' },
				multiple: false
			});

			videoFrame.on('select', function(){
				var attachment = videoFrame.state().get('selection').first().toJSON();
				$('#bw_slider_hover_video').val(attachment.id);
				renderVideoPreview(attachment);
			});

			videoFrame.open();
		});

		$('.bw-remove-slider-hover-image').on('click', function(e){
			e.preventDefault();
			$('#bw_slider_hover_image').val('');
			$('.bw-slider-metabox-preview--image').empty();
			$(this).hide();
		});

		$('.bw-remove-slider-hover-video').on('click', function(e){
			e.preventDefault();
			$('#bw_slider_hover_video').val('');
			$('.bw-slider-metabox-preview--video').empty();
			$(this).hide();
		});
	});
	</script>
	<?php
}

/**
 * Save the Hover Media metabox values.
 *
 * @param int $post_id Post ID.
 */
function bw_save_product_slider_hover_metabox( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['post_type'] ) || 'product' !== sanitize_key( wp_unslash( $_POST['post_type'] ) ) ) {
		return;
	}

	if ( ! isset( $_POST['bw_slider_hover_media_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bw_slider_hover_media_nonce'] ) ), 'bw_slider_hover_media_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$image_id = isset( $_POST['bw_slider_hover_image'] ) ? absint( wp_unslash( $_POST['bw_slider_hover_image'] ) ) : 0;
	if ( $image_id && wp_attachment_is_image( $image_id ) ) {
		update_post_meta( $post_id, '_bw_slider_hover_image', $image_id );
	} else {
		delete_post_meta( $post_id, '_bw_slider_hover_image' );
	}

	$video_id   = isset( $_POST['bw_slider_hover_video'] ) ? absint( wp_unslash( $_POST['bw_slider_hover_video'] ) ) : 0;
	$video_mime = $video_id ? (string) get_post_mime_type( $video_id ) : '';
	if ( $video_id && 0 === strpos( $video_mime, 'video/' ) ) {
		update_post_meta( $post_id, '_bw_slider_hover_video', $video_id );
	} else {
		delete_post_meta( $post_id, '_bw_slider_hover_video' );
	}
}
add_action( 'save_post_product', 'bw_save_product_slider_hover_metabox' );
