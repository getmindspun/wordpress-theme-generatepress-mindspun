<?php
declare(strict_types=1);
namespace mindspun\theme\website;

class EddCustomFields {

    protected $theme;

    public function __construct( Theme $theme ) {
        $this->theme = $theme;

        //add_action( 'edd_purchase_form_user_info_fields', array( $this, 'edd_display_checkout_fields' ) );
        add_action( 'edd_purchase_form_before_register_login', array( $this, 'edd_display_checkout_fields' ) );
        add_filter( 'edd_purchase_form_required_fields', array( $this, 'edd_required_checkout_fields' ) );
        add_action( 'edd_checkout_error_checks', array( $this, 'edd_validate_checkout_fields' ), 10, 2 );
        add_filter( 'edd_payment_meta', array( $this, 'edd_store_custom_fields' ) );
        add_action( 'edd_payment_personal_details_list', array( $this, 'edd_view_order_details' ), 10, 2 );
        add_action( 'edd_receipt_no_files_found_text', array( $this, 'edd_receipt_no_files_found_text' ), 10, 2 );

        add_action( 'edds_create_payment_intent_args', array( $this, 'edds_create_payment_intent_args' ), 10, 2 );

        add_action( 'edd_recurring_create_stripe_subscription_args', array( $this, 'edd_recurring_create_stripe_subscription_args' ), 10, 3 );
        add_action( 'edd_checkout_button_purchase', array( $this, 'edd_checkout_button_purchase' ), 10, 1 );
    }

    /**
     * Display origin text field at checkout
     */
    public function edd_display_checkout_fields() {
        $count = $this->recurring_count();
        if ( $count ) {
            ?>
            <fieldset id="edd_checkout_user_info">
                <legend>Origin</legend>
                <p id="edd-origin-wrap">
                    <label class="edd-label" for="edd-origin">Hostname<span class="edd-required-indicator">*</span></label>
                    <span class="edd-description"> The <i>origin</i> hostname from your hosting provider.  NOT the public domain. </span>
                    <input class="edd-input" type="text" name="edd_origin" id="edd-origin" placeholder="www.example.com">
                </p>
            </fieldset>
            <?php
        }
    }

    /**
     * Make origin required
     */
    public function edd_required_checkout_fields( $required_fields ) {
        $count = $this->recurring_count();
        if ( $count ) {
            $required_fields['edd_origin'] = array(
                'error_id' => 'invalid_origin',
                'error_message' => 'Please enter a valid Origin hostname.',
            );
        }
        return $required_fields;
    }

    /**
     * Set error if origin field is empty
     * TODO: validate against api.
     */
    public function edd_validate_checkout_fields( $valid_data, $data ) {
        $count = $this->recurring_count();
        if ( $count && empty( $data['edd_origin'] ) ) {
            edd_set_error( 'invalid_origin', 'Please enter your origin hostname.' );
        }
    }

    /**
     * Store the custom field data into EDD's payment meta
     */
    public function edd_store_custom_fields( $payment_meta ) {

        if ( 0 !== did_action( 'edd_pre_process_purchase' ) ) {
            // phpcs:ignore
            $payment_meta['origin'] = isset( $_POST['edd_origin'] ) ? sanitize_text_field( $_POST['edd_origin'] ) : '';
        }

        return $payment_meta;
    }

    /**
     * Add the origin to the "View Order Details" page
     */
    public function edd_view_order_details( $payment_meta, $user_info ) {
        $origin = isset( $payment_meta['origin'] ) ? $payment_meta['origin'] : 'none';
        ?>
        <div class="column-container">
            <div class="column">
                <strong>Origin: </strong>
                <?php echo esc_attr( $origin ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Add origin to the Stripe payment intent
     */
    public function edds_create_payment_intent_args( array $intent_args, array $purchase_data ) {
        $intent_args['metadata']['origin'] = $purchase_data['post_data']['edd_origin'];
        return $intent_args;
    }

    /**
     * Gets rid of the 'No downloadable files found.' text
     */
    public function edd_receipt_no_files_found_text( string $text, $item_id ) {
        return null;
    }

    /**
     *  Add origin to subscription.
     */
    public function edd_recurring_create_stripe_subscription_args( $args, $purchase_data, $customer ) {
        $args['metadata']['origin'] = $purchase_data['post_data']['edd_origin'];
        return $args;
    }

    /**
     * Count the number of recurring items in the cart.
     */
    protected function recurring_count() : int {
        $count = 0;

        $contents = edd_get_cart_contents();
        foreach ( $contents as &$item ) {
            if ( array_key_exists( 'recurring', $item['options'] ) ) {
                $count++;
            }
        }
        return $count;
    }

    public function edd_checkout_button_purchase( $args ) {
        $count = $this->recurring_count();
        if ( $count > 1 ) {
            ob_start();
            ?>
                <div class="edd_errors edd-alert edd-alert-error">
                    <p class="edd_error">You may only pay for one recurring subscription at a time.  Please remove extra subscriptions to continue.</p>
                </div>
            <?php
            return ob_get_clean();
        }
        return $args;

    }
}
