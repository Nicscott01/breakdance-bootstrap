<?php

use Breakdance\DynamicData\StringField;
use Breakdance\DynamicData\StringData;

class CrearePostId extends StringField
{


    /**
     * @inheritDoc
     */
    public function label()
    {
        return 'Post ID';
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
        return 'creare_post_id';
    }

    /**
     * @inheritDoc
     */
    public function handler($attributes): StringData
    {

       return StringData::fromString(get_the_ID());

    }

}