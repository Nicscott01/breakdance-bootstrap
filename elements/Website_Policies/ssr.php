<?php

$propertiesData;

//var_dump( $propertiesData );

$policies = $propertiesData['content']['data']['policies'];

if ( !empty( $policies ) ) {

    echo '<ul class="policies">';

    foreach( $policies as $policy ) {


        $id = $policy['policy'];

        printf( '<li class="link"><a href="%s">%s</a></li>', get_permalink( $id ), get_the_title( $id ));
    }

    //Get the policy settings link
    if ( !empty( $propertiesData['content']['data']['settings_link'] ) ) {

        $label = !empty( $propertiesData['content']['data']['settings_label'] ) ? $propertiesData['content']['data']['settings_label'] : 'Privacy Settings';

        printf( '<li class="link settings"><a href="%s">%s</a></li>', $propertiesData['content']['data']['settings_link'], $label );

    }

    echo '</ul>';
}