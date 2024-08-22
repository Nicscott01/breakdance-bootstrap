<?php
/**
 *  This element is given a download object to base it's data from. It uses 
 *  the given popup template (Global block with our dynamic popup element) to
 *  render the popup title, description
 * 
 * 
 * 
 */



$popup_template_id = $propertiesData['content']['popup']['popup_template'];

$download_id = $propertiesData['content']['popup']['download'];

$download_post = get_post( $download_id );


//var_dump( $propertiesData );
$template = "{{ macros.atomV1ButtonHtmlManual(text, attr, className, design, buttonStyle, buttonId, false) }}";

$popup_data_action_value = json_encode( [
    'type' => 'popup',
    'popupOptions' => [
        'popupId'=> $download_post->ID,
        'popupAction' => 'open'
    ]
    ], JSON_HEX_QUOT );

//Setup the button so Breakdance can auto-load the popup
$popup_data_action_value = htmlspecialchars( $popup_data_action_value, ENT_QUOTES, 'UTF-8' );

echo Breakdance\Render\Twig::getInstance()->runTwig($template, [
    'text' => $propertiesData['content']['popup']['text'],
    'className' => 'bric-popup-button breakdance-link',
    'design' => $propertiesData['design']['button'],
    'buttonStyle' => 'primary/secondary/custom/text',
    'buttonId' => 'gated-content-popup-'. $download_post->ID,
    'attr' => [
        'html' => [
            'attributes' => [
                [
                    'name' => 'data-type',
                    'value' => 'action'
                ],
                [
                    'name' => 'data-action',
                    'value' => $popup_data_action_value
                ]
            ]
        ]
    
    ]
]);


BricBreakdance\DynamicPopup()->queue_popup( $popup_template_id, $download_id );
