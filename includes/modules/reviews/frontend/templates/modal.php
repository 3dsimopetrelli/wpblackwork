<?php
/**
 * Reviews modal singleton template.
 *
 * @var array<string,mixed> $view
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="bw-reviews-modal" data-review-modal hidden>
    <div class="bw-reviews-modal__backdrop" data-review-close></div>
    <div class="bw-reviews-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bw-reviews-modal-title" tabindex="-1" data-review-modal-dialog>
        <button type="button" class="bw-reviews-modal__close" data-review-close aria-label="<?php esc_attr_e( 'Close', 'bw' ); ?>">×</button>
        <div class="bw-reviews-modal__dialog-scroll">
            <div class="bw-reviews-modal__inner">
                <div class="bw-reviews-modal__top">
                    <p class="bw-reviews-modal__progress" data-review-progress><?php esc_html_e( 'Step 1 of 4', 'bw' ); ?></p>
                    <h2 class="bw-reviews-modal__title" id="bw-reviews-modal-title" data-review-modal-title>
                        <?php echo esc_html( (string) $view['modal_title_create'] ); ?>
                    </h2>
                    <p class="bw-reviews-modal__subtitle" data-review-modal-subtitle hidden></p>
                </div>

                <div class="bw-reviews-modal__message" data-review-message hidden></div>

                <div class="bw-reviews-modal__steps">
                    <section class="bw-reviews-step is-active" data-step="rating">
                        <h3 class="bw-reviews-step__title"><?php esc_html_e( 'How would you rate this item?', 'bw' ); ?></h3>
                        <div class="bw-reviews-rating-picker" data-rating-picker>
                            <span class="bw-reviews-rating-picker__label"><?php echo esc_html( $view['config']['strings']['dislike'] ); ?></span>
                            <div class="bw-reviews-rating-picker__stars">
                                <?php for ( $rating = 1; $rating <= 5; $rating++ ) : ?>
                                    <button type="button" class="bw-reviews-rating-picker__star" data-rating-value="<?php echo esc_attr( (string) $rating ); ?>" aria-label="<?php echo esc_attr( sprintf( __( '%d stars', 'bw' ), $rating ) ); ?>">
                                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                            <path d="M12 2.75 14.936 8.701 21.5 9.655 16.75 14.286 17.871 20.825 12 17.738 6.129 20.825 7.25 14.286 2.5 9.655 9.064 8.701 12 2.75Z"/>
                                        </svg>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <span class="bw-reviews-rating-picker__label is-right"><?php echo esc_html( $view['config']['strings']['love'] ); ?></span>
                        </div>
                    </section>

                    <section class="bw-reviews-step" data-step="content" hidden>
                        <h3 class="bw-reviews-step__title"><?php esc_html_e( 'Tell us more!', 'bw' ); ?></h3>
                        <label class="bw-reviews-field bw-reviews-field--textarea">
                            <textarea rows="7" data-review-field="content" placeholder="<?php esc_attr_e( 'Share your experience', 'bw' ); ?>"></textarea>
                        </label>
                    </section>

                    <section class="bw-reviews-step" data-step="identity" hidden>
                        <h3 class="bw-reviews-step__title"><?php esc_html_e( 'About you', 'bw' ); ?></h3>

                        <div class="bw-reviews-identity" data-review-identity-guest>
                            <div class="bw-reviews-identity__grid">
                                <label class="bw-reviews-field">
                                    <span><?php esc_html_e( 'First name', 'bw' ); ?> *</span>
                                    <input type="text" data-review-field="first_name" />
                                </label>
                                <label class="bw-reviews-field">
                                    <span><?php esc_html_e( 'Last name', 'bw' ); ?></span>
                                    <input type="text" data-review-field="last_name" />
                                </label>
                            </div>
                            <label class="bw-reviews-field">
                                <span><?php esc_html_e( 'Email', 'bw' ); ?> *</span>
                                <input type="email" data-review-field="email" />
                            </label>
                        </div>

                        <div class="bw-reviews-identity bw-reviews-identity--readonly" data-review-identity-user hidden>
                            <p class="bw-reviews-identity__label"><?php esc_html_e( 'Reviewing as', 'bw' ); ?></p>
                            <p class="bw-reviews-identity__name" data-review-user-name></p>
                            <p class="bw-reviews-identity__email" data-review-user-email></p>
                        </div>

                        <label class="bw-reviews-consent">
                            <input type="checkbox" data-review-field="privacy_ack" value="1" />
                            <span class="bw-reviews-consent__text">
                                <?php esc_html_e( 'By submitting, I acknowledge the', 'bw' ); ?>
                                <a href="<?php echo esc_url( (string) $view['terms_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Terms of Service', 'bw' ); ?></a>
                                <?php esc_html_e( 'and', 'bw' ); ?>
                                <a href="<?php echo esc_url( (string) $view['privacy_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacy Policy', 'bw' ); ?></a>
                                <?php esc_html_e( ', and that my review will be publicly posted and shared online.', 'bw' ); ?>
                            </span>
                        </label>
                    </section>

                    <section class="bw-reviews-step" data-step="done" hidden>
                        <div class="bw-reviews-step__done">
                            <h3 class="bw-reviews-step__title" data-review-done-title><?php esc_html_e( 'Thank you', 'bw' ); ?></h3>
                            <p data-review-done-message></p>
                        </div>
                    </section>
                </div>

                <div class="bw-reviews-modal__footer">
                    <button type="button" class="bw-reviews-modal__back" data-review-back hidden>
                        <?php esc_html_e( 'Back', 'bw' ); ?>
                    </button>
                    <div class="bw-reviews-modal__progress-bars" data-review-progress-bars>
                        <span class="is-active"></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="bw-reviews-modal__actions">
                        <button type="button" class="bw-reviews-modal__button is-primary" data-review-next disabled>
                            <?php esc_html_e( 'Next', 'bw' ); ?>
                        </button>
                        <button type="button" class="bw-reviews-modal__button is-primary" data-review-submit hidden disabled>
                            <?php esc_html_e( 'Done', 'bw' ); ?>
                        </button>
                        <button type="button" class="bw-reviews-modal__button is-primary" data-review-finish hidden>
                            <?php echo esc_html( $view['config']['strings']['continue'] ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
