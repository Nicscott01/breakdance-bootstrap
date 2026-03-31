<?php

namespace BricBreakdance;

class WooCommerceBlocksButtonHelpers {

    public static function init() {
        add_action( 'wp_footer', array( __CLASS__, 'print_button_class_bridge' ), 25 );
    }

    public static function print_button_class_bridge() {
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) {
            return;
        }

        $buttons = self::get_button_map();

        if ( empty( $buttons ) ) {
            return;
        }

        $payload = array(
            'buttons' => $buttons,
        );

        ?>
        <script>
            (function(config) {
                if (!config || !config.buttons || !document.body) {
                    return;
                }

                var frameId = null;

                var applyButtonClasses = function() {
                    Object.keys(config.buttons).forEach(function(key) {
                        var buttonConfig = config.buttons[key] || {};
                        var selectors = Array.isArray(buttonConfig.selectors) ? buttonConfig.selectors : [];
                        var classes = Array.isArray(buttonConfig.classes) ? buttonConfig.classes : [];

                        if (!selectors.length || !classes.length) {
                            return;
                        }

                        selectors.forEach(function(selector) {
                            document.querySelectorAll(selector).forEach(function(element) {
                                classes.forEach(function(className) {
                                    if (className) {
                                        element.classList.add(className);
                                    }
                                });
                            });
                        });
                    });
                };

                var scheduleApply = function() {
                    if (frameId !== null) {
                        return;
                    }

                    frameId = window.requestAnimationFrame(function() {
                        frameId = null;
                        applyButtonClasses();
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', scheduleApply, { once: true });
                } else {
                    scheduleApply();
                }

                window.addEventListener('pageshow', scheduleApply);

                var observer = new MutationObserver(scheduleApply);

                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class']
                });
            })(<?php echo wp_json_encode( $payload ); ?>);
        </script>
        <?php
    }

    protected static function get_button_map() {
        $button_map = array();

        if ( is_cart() ) {
            $button_map['cart_proceed_to_checkout'] = array(
                'selectors' => array(
                    '.wp-block-woocommerce-cart .wc-block-cart__submit-button',
                ),
                'classes' => self::get_button_classes( 'cart_proceed_to_checkout' ),
            );
        }

        if ( is_checkout() && ! is_order_received_page() ) {
            $button_map['checkout_place_order'] = array(
                'selectors' => array(
                    '.wp-block-woocommerce-checkout .wc-block-components-checkout-place-order-button',
                ),
                'classes' => self::get_button_classes( 'checkout_place_order' ),
            );
        }

        $button_map = apply_filters( 'bric_breakdance_woocommerce_blocks_button_map', $button_map );

        foreach ( $button_map as $key => $button_config ) {
            $button_map[ $key ]['selectors'] = array_values(
                array_filter(
                    array_map( 'strval', (array) ( $button_config['selectors'] ?? array() ) )
                )
            );

            $button_map[ $key ]['classes'] = array_values(
                array_filter(
                    array_map( 'strval', (array) ( $button_config['classes'] ?? array() ) )
                )
            );

            if ( empty( $button_map[ $key ]['selectors'] ) || empty( $button_map[ $key ]['classes'] ) ) {
                unset( $button_map[ $key ] );
            }
        }

        return $button_map;
    }

    protected static function get_button_classes( $button_key ) {
        $classes = array( 'button-atom', 'button-atom--primary' );

        $classes = apply_filters( 'bric_breakdance_woocommerce_blocks_button_classes', $classes, $button_key );
        $classes = apply_filters( "bric_breakdance_woocommerce_blocks_button_classes_{$button_key}", $classes );

        return array_values(
            array_filter(
                array_map( 'strval', (array) $classes )
            )
        );
    }
}

WooCommerceBlocksButtonHelpers::init();
