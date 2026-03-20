<?php
/**
 * Reviews modal template.
 *
 * @var array<string,mixed> $view
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="bw-reviews-modal" data-review-modal hidden>
    <div class="bw-reviews-modal__backdrop" data-review-close></div>
    <div class="bw-reviews-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bw-reviews-modal-title-<?php echo esc_attr( (string) $view['instance_id'] ); ?>">
        <button type="button" class="bw-reviews-modal__close" data-review-close aria-label="<?php esc_attr_e( 'Close', 'bw' ); ?>">×</button>
        <div class="bw-reviews-modal__dialog-scroll">
            <div class="bw-reviews-modal__inner">
                <div class="bw-reviews-modal__top">
                    <p class="bw-reviews-modal__progress" data-review-progress><?php esc_html_e( 'Step 1 of 4', 'bw' ); ?></p>
                    <h2 class="bw-reviews-modal__title" id="bw-reviews-modal-title-<?php echo esc_attr( (string) $view['instance_id'] ); ?>" data-review-modal-title>
                        <?php echo esc_html( (string) $view['modal_title_create'] ); ?>
                    </h2>
                </div>

                <div class="bw-reviews-modal__message" data-review-message hidden></div>

                <div class="bw-reviews-modal__steps">
                    <section class="bw-reviews-step is-active" data-step="rating">
                        <h3 class="bw-reviews-step__title"><?php esc_html_e( 'How would you rate it?', 'bw' ); ?></h3>
                        <div class="bw-reviews-rating-picker" data-rating-picker>
                            <?php for ( $rating = 1; $rating <= 5; $rating++ ) : ?>
                                <button type="button" class="bw-reviews-rating-picker__star" data-rating-value="<?php echo esc_attr( (string) $rating ); ?>">★</button>
                            <?php endfor; ?>
                        </div>
                    </section>

                    <section class="bw-reviews-step" data-step="content" hidden>
                        <h3 class="bw-reviews-step__title"><?php esc_html_e( 'Share your experience', 'bw' ); ?></h3>
                        <label class="bw-reviews-field">
                            <span><?php esc_html_e( 'Review', 'bw' ); ?></span>
                            <textarea rows="7" data-review-field="content"></textarea>
                        </label>
                    </section>

                    <section class="bw-reviews-step" data-step="identity" hidden>
                        <div class="bw-reviews-identity" data-review-identity-guest>
                            <label class="bw-reviews-field">
                                <span><?php esc_html_e( 'First name', 'bw' ); ?></span>
                                <input type="text" data-review-field="first_name" />
                            </label>
                            <label class="bw-reviews-field">
                                <span><?php esc_html_e( 'Last name', 'bw' ); ?></span>
                                <input type="text" data-review-field="last_name" />
                            </label>
                            <label class="bw-reviews-field">
                                <span><?php esc_html_e( 'Email', 'bw' ); ?></span>
                                <input type="email" data-review-field="email" />
                            </label>
                        </div>

                        <div class="bw-reviews-identity bw-reviews-identity--readonly" data-review-identity-user hidden>
                            <p class="bw-reviews-identity__label"><?php esc_html_e( 'Reviewing as', 'bw' ); ?></p>
                            <p class="bw-reviews-identity__name" data-review-user-name></p>
                            <p class="bw-reviews-identity__email" data-review-user-email></p>
                        </div>
                    </section>

                    <section class="bw-reviews-step" data-step="done" hidden>
                        <div class="bw-reviews-step__done">
                            <h3 class="bw-reviews-step__title"><?php esc_html_e( 'Thank you', 'bw' ); ?></h3>
                            <p data-review-done-message></p>
                        </div>
                    </section>
                </div>

                <div class="bw-reviews-modal__actions">
                    <button type="button" class="bw-reviews-modal__button is-secondary" data-review-back hidden>
                        <?php esc_html_e( 'Back', 'bw' ); ?>
                    </button>
                    <button type="button" class="bw-reviews-modal__button is-primary" data-review-next>
                        <?php esc_html_e( 'Next', 'bw' ); ?>
                    </button>
                    <button type="button" class="bw-reviews-modal__button is-primary" data-review-submit hidden>
                        <?php esc_html_e( 'Submit review', 'bw' ); ?>
                    </button>
                    <button type="button" class="bw-reviews-modal__button is-primary" data-review-finish hidden>
                        <?php esc_html_e( 'Close', 'bw' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
