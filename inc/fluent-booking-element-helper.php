<?php

namespace BDBS\FluentBookingElementHelper;

add_action( 'breakdance_loaded', 'BDBS\FluentBookingElementHelper\register_handler' );

function register_handler() {

    \Breakdance\AJAX\register_handler(
            'bdbs_get_fluent_booking_calendars',
            'BDBS\FluentBookingElementHelper\handle_get_fluent_booking_calendars',
            'edit',
            true,
            [],
            false
        );
}




function handle_get_fluent_booking_calendars() {

    $calendars = [];

    if ( ! class_exists( '\FluentBooking\App\Models\Calendar' ) ) {
        return $calendars;
    }

    $fluent_booking_calendars = \FluentBooking\App\Services\CalendarService::getCalendarOptionsByTitle();


    if ( !empty( $fluent_booking_calendars ) && is_array( $fluent_booking_calendars ) ) {
        
        foreach ( $fluent_booking_calendars as $calendar ) {

            $owner = $calendar['title'];

            if ( $calendar['options'] && is_array( $calendar['options'] ) ) {

                foreach ( $calendar['options'] as $cal_option ) {

                    $calendars[] = [
                        'text' => $cal_option['title'] . ' - ' . $owner,
                        'value' => (string) $cal_option['id'],
                    ];

                }
            }

        }


    } 



    return $calendars;

}