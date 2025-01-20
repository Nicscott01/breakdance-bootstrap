<?php

namespace BricBreakdance;


function insert_after_key( $array, $target_key, $new_key, $new_value ) {
	$result = array();

	foreach ( $array as $key => $value ) {
		$result[ $key ] = $value;
		if ( $key === $target_key ) {
			$result[ $new_key ] = $new_value;
		}
	}

	return $result;
}




if ( ! function_exists( 'dlog' ) ) {

    function dlog( $thing, $name = '' ) {

        error_log( $name . ': ' . print_r( $thing, 1 ) );
    }

}