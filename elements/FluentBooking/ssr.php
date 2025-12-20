<?php



/**
 * Breakdance SSR for Fluent Booking calendar element.
 * We render the plugin shortcode output and, when possible, append a small
 * inline initializer so the Fluent Booking frontend app can initialize inside
 * the Breakdance editor (which often doesn't print enqueued scripts).
 */

$calendar = $propertiesData['content']['data']['calendar'] ?? '';

echo 'This is a calendar;';

/**
 * This calendar will not render in the breakdance builder 
 * becuase it relies on scripts to load it.
 * This line of code is from the Fluent function that handles
 * the shortcode.
 * 
 *         wp_enqueue_script('fluent-booking-public', $assetUrl . 'public/js/app.js', [], FLUENT_BOOKING_ASSETS_VERSION, true);
* 
*   todo: add this script to the breakdance builder so it can render properly.
 */
echo do_shortcode( sprintf( '[fluent_booking id="%s"]', $calendar ) );

// If Fluent Booking classes are available, add a lightweight inline initializer
// that sets the localized vars and injects the app script if it's missing. This
// keeps changes local to the Breakdance element and avoids modifying the plugin.
if (!empty($calendar) && class_exists('\FluentBooking\App\Models\CalendarSlot') && class_exists('\FluentBooking\App\Hooks\Handlers\FrontEndHandler')) {
	try {
		$eventId = intval($calendar);
		$calendarEvent = \FluentBooking\App\Models\CalendarSlot::find($eventId);
		if ($calendarEvent) {
			$handler = new \FluentBooking\App\Hooks\Handlers\FrontEndHandler();
			$assetUrl = \FluentBooking\App\App::getInstance('url.assets');

			// Build the same localized data the plugin uses for this event
			$localizeData = $handler->getCalendarEventVars($calendarEvent->calendar, $calendarEvent);
			$localizeData['disable_author'] = false;

			$varName = 'fcal_public_vars_' . $calendarEvent->calendar->id . '_' . $calendarEvent->id;
			$globalVars = $handler->getGlobalVars();

			// Output a safe inline script only when the window var is not present.
			echo '<script type="text/javascript">(function(){';
			echo 'if(typeof window["' . $varName . '"] === "undefined"){';
			echo 'window["' . $varName . '"] = ' . wp_json_encode($localizeData) . ';';
			echo 'window.fluentCalendarPublicVars = window.fluentCalendarPublicVars || ' . wp_json_encode($globalVars) . ';';
			echo 'if(!document.querySelector(\'script[src="' . $assetUrl . 'public/js/app.js"]\')){var s=document.createElement("script");s.src="' . $assetUrl . 'public/js/app.js";s.async=true;document.head.appendChild(s);}';
			echo '}';
			echo '})();</script>';
		}
	} catch (\Throwable $e) {
		// Don't break the editor if something unexpected happens; fail silently.
	}
}