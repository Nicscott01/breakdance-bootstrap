<?php
/**
 * This code will apply filters to the_content for 
 * adding Breakdance classes to elements when 
 * the bloat eliminator is active.
 * 
 */
namespace BricBreakdance\GutenburgStyles;
use function Breakdance\Data\get_global_option;




add_filter('render_block', function ( $block_content, $block ) {
    if ( $block['blockName'] !== 'core/button' ) {
        return $block_content;
    }

    // Proceed with applying Breakdance Gutenberg styles.
    $bd_options = (array) get_global_option('breakdance_settings_bloat_eliminator');

    if ( in_array('gutenberg-blocks-css', $bd_options)) {

        // Use the new WP_HTML_Tag_Processor
        $processor = new \WP_HTML_Tag_Processor( $block_content );
        // Check the parent element's classes for "bdbutton-secondary"
        $button_style = 'primary';
        $parent_processor = new \WP_HTML_Tag_Processor( $block_content );
        if ( $parent_processor->next_tag() ) {
            $parent_classes = $parent_processor->get_attribute('class') ?? '';
            if ( str_contains( $parent_classes, 'bdbutton-secondary' ) ) {
                $button_style = 'secondary';
            }
        }
       

        if ( $processor->next_tag( array( 'tag_name' => 'a' ) ) ) {
            $existing_classes = $processor->get_attribute( 'class' ) ?? '';
            $new_classes = 'button-atom button-atom--' . $button_style;

            if ( ! str_contains( $existing_classes, 'button-atom' ) ) {
                $processor->set_attribute(
                    'class',
                    trim( $existing_classes . ' ' . $new_classes )
                );
            }

            return $processor->get_updated_html();
        }

    }
    


    return $block_content;

}, 10, 2 );