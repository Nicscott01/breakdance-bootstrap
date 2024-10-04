<?php
namespace BricBreakdance;



add_action('fluent_crm/confirmation_footer', 'BricBreakdance\auto_login_after_confirmation');

function auto_login_after_confirmation($subscriber)
{
    // Get the subscriber's email
    $email = $subscriber->email;

    // Check if the user exists
    $user = get_user_by('email', $email);

    if ($user) {
        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        // Optional: Redirect to a specific page after login
        wp_redirect(home_url());
        exit();
    } else {
        // Handle case where no user is found (optional)
        error_log('No user found for the confirmed email: ' . $email);
    }
}


/**
 *  Hide the admin bar for logged-in users
 *  
 */

add_action('after_setup_theme', function() {
    if (!is_admin() && current_user_can('subscriber')) {
        show_admin_bar(false);
    }
});


add_action( 'admin_init', function() {
    if (is_admin() && !defined('DOING_AJAX') && current_user_can('subscriber') && !current_user_can('administrator') && !is_super_admin() ) {
        wp_redirect(home_url()); // Redirect to homepage or any other URL
        exit;
    }
});
