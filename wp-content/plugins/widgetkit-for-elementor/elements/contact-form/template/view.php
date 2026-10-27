<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

    $contact_form = $this->get_settings();
    /*
     * Defaults for the keys this template reads unconditionally.
     *
     * get_settings() only returns what the control stack produced, so a widget
     * saved before a control existed — or one placed with no settings at all —
     * has no key here, and reading it is an "Undefined array key" warning on
     * every render. It is a warning rather than an error, so the page still
     * renders and nobody notices until they read the log.
     */
    $contact_form = array_merge(
        array(
            'button_align'      => '',
            'button_width_type' => '',
            'contact_form_list' => '',
        ),
        is_array( $contact_form ) ? $contact_form : array()
    );
    $id = $this->get_id();
    use Elementor\Icons_Manager;
?>

        <div class="wk-contact-form <?php echo esc_attr(($contact_form['button_align'] == 'center')) ? 'button-center':'';?> <?php echo esc_attr(($contact_form['button_align'] == 'right')) ? 'button-right':'';?> <?php echo esc_attr(($contact_form['button_width_type'] == 'custom')) ? 'wk-submit-button-custom':'';?>">
            <?php if ($contact_form['contact_form_list'] == 'contact-7'):?>
                <?php if (function_exists( 'wpcf7' ) ):?>
                    <?php echo do_shortcode( '[contact-form-7 id="' . $contact_form['choose_form_7'] . '" ]' ); ?>
                <?php else: ?>
                    <p>Contact Form 7</strong> is not installed/activated on your site. Please install and activate <strong>Contact Form 7</strong> first.</p>
                <?php endif; ?>

            <?php elseif($contact_form['contact_form_list'] == 'weforms'): ?>
                <?php if (class_exists( 'WeForms' ) ):?>
                    <p><?php echo do_shortcode( '[weforms id="' . $contact_form['choose_weforms'] . '" ]' ); ?></p>
                <?php else: ?>
                    <p>WeForms</strong> is not installed/activated on your site. Please install and activate <strong>WeForms</strong> first.</p>
                <?php endif; ?>

            <?php elseif($contact_form['contact_form_list'] == 'wpforms'): ?>
                <?php if (class_exists( '\WPForms\WPForms') ):?>
                    <?php echo do_shortcode( '[wpforms id="' . $contact_form['choose_wpforms'] . '" ]' ); ?>
                <?php else: ?>
                    <p>Wpforms</strong> is not installed/activated on your site. Please install and activate <strong>Wpforms</strong> first.</p>
                <?php endif; ?>
            <?php else: ?>
                <?php echo do_shortcode( '[contact-form-7 id="' . $contact_form['choose_form_7'] . '" ]' ); ?>
            <?php endif;?>
        </div>

        <script type="text/javascript">
            jQuery(function($){
                if(!$('body').hasClass('wk-contact-form')){
                    $('body').addClass('wk-contact-form');
                }
            });

        </script>



