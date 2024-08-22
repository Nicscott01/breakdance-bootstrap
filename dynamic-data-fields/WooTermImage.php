<?php
namespace BricBreakdance;

use Breakdance\DynamicData\ImageField;
use Breakdance\DynamicData\ImageData;

class WooTermImage extends ImageField
{


    /**
     * @inheritDoc
     */
    public function label()
    {
        return 'Term Image';
    }

    /**
     * @inheritDoc
     */
    public function category()
    {
        return 'WooCommerce';
    }

    /**
     * @inheritDoc
     */
    public function slug()
    {
        return 'woo_term_image';
    }

    /**
     * @param array $attributes
     */
    public function handler($attributes): ImageData {


        error_log( json_encode( $attributes ) );

        $imageData = new ImageData;

        //Get the current tax query
        if ( is_tax() ) {

            $term = get_queried_object();

            $term_image = get_term_meta( $term->term_id, 'thumbnail_id', true );

            if ( !empty( $term_image ) ) {

                $attachmentId = $term_image;

                $imageData = ImageData::fromAttachmentId($attachmentId);
                //return $imageData;

            } 
        } else {

            //Try the fallback
            if ( isset( $attributes['fallback_image']) && !empty( $attributes['fallback_image'] ) ) {

                $imageData = ImageData::fromAttachmentId( $attributes['fallback_image'] );

            } 

        }


        // build from attachment data
       /* $attachmentData = wp_prepare_attachment_for_js($attachmentId);
        $imageData = new ImageData;
        $imageData->id = (string) $attachmentData['id'];
        $imageData->filename = $attachmentData['filename'];
        $imageData->alt = $attachmentData['alt'];
        $imageData->caption = $attachmentData['caption'];
        $imageData->url = $attachmentData['url'];
        $imageData->type = $attachmentData['type'];
        $imageData->mime = $attachmentData['mime'];
        $imageData->sizes = $attachmentData['sizes'];
*/
        // or using the helper
       // $imageData = ImageData::fromAttachmentId($attachmentId);
        
        return $imageData;
    }

}