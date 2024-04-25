<?php
namespace BricBreakdance;


use Breakdance\DynamicData\StringField;
use Breakdance\DynamicData\StringData;

class CrearePostSlug extends StringField
{


    /**
     * @inheritDoc
     */
    public function label()
    {
        return 'Post Slug';
    }

    /**
     * @inheritDoc
     */
    public function category()
    {
        return 'Post';
    }

    /**
     * @inheritDoc
     */
    public function slug()
    {
        return 'creare_post_slug';
    }

    /**
     * @inheritDoc
     */
    public function handler($attributes): StringData
    {
        global $post;

        $slug = '';

        if ( !empty( $post ) ) {
            $slug = $post->post_name;
        }
       return StringData::fromString( $slug );

    }

}