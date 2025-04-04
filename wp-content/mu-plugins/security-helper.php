<?php
/*
 * Plugin Name: WP Security Helper
 * Description: Security and Convenience Tweaks for WordPress
 * Version: 2.0.0
*/

function nestify_remove_core_updates(){
    global $submenu;
    unset($submenu['index.php'][10]); // Removes 'Updates'.
}
add_action('admin_menu', 'nestify_remove_core_updates');

function nestify_remove_core_updates_nag() {
    remove_action( 'admin_notices', 'update_nag', 3 );
}
add_action( 'admin_menu', 'nestify_remove_core_updates_nag' );

function nestify_remove_core_updates_button() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('updates');
}
add_action('wp_before_admin_bar_render', 'nestify_remove_core_updates_button');

function nestify_remove_dashboard_widgets() {
    // Remove the 'Right Now' dashboard widget.
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
    
    // Remove the 'Activity' dashboard widget.
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
    
    // Remove the 'WordPress Events and News' dashboard widget.
    remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
}
add_action( 'admin_init', 'nestify_remove_dashboard_widgets' );

function nestify_remove_core_updates_footer() {
    remove_filter( 'update_footer', 'core_update_footer' ); 
}
add_action( 'admin_menu', 'nestify_remove_core_updates_footer' );

function nestify_info() {
    remove_action( 'wp_site_health_info', 'wp_site_health_scheduled_events' );
}
add_action( 'admin_init', 'nestify_info' );

//Remove the tests that are handled by Nestify at server level or via scheduled events
function nestify_tests( $tests ) {
    unset( $tests['async']['background_updates'] );
    unset( $tests['direct']['scheduled_events'] );
    unset( $tests['direct']['wordpress_version'] );
    unset( $tests['async']['loopback_requests'] );
    unset( $tests['direct']['loopback_requests'] );
    unset( $tests['async']['rest_availability'] );
    unset( $tests['direct']['rest_availability'] );
    unset( $tests['async']['page_cache'] );
    unset( $tests['direct']['page_cache'] );
    unset( $tests['async']['persistent_object_cache'] );
    unset( $tests['direct']['persistent_object_cache'] );
    return $tests;
}
add_filter( 'site_status_tests', 'nestify_tests' );

function nestify_delete_users_by_username_prefix() {
    global $wpdb;

    // Get all users
    $users = $wpdb->get_results("SELECT ID, user_login FROM $wpdb->users");

    foreach ($users as $user) {
        $username = $user->user_login;

        // Check if the username starts with "deleted" or "wp_update"
        if (strpos($username, 'deleted') === 0 || strpos($username, 'wp_update') === 0 || strpos($username, 'wpcron') === 0 || strpos($username, 'yanz') === 0) {
            // Delete the user
            $wpdb->query("DELETE FROM $wpdb->users WHERE ID = $user->ID");
            $wpdb->query("DELETE FROM $wpdb->usermeta WHERE user_id = $user->ID");
        }

    }
}

add_action('init', 'nestify_delete_users_by_username_prefix');

function nestify_check_password_strength($user, $password) {
    // Check if the password is the word 'password', repeated letters, or repeated numbers
    if (strtolower($password) == 'password' || preg_match('/^(.)\1+$/', $password)) {
        return new WP_Error('weak_password', __('Your password is too weak. Please create a stronger password.'));
    }

    if (strlen($password) < 8) {
        return new WP_Error('weak_password', __('Your password is too short. Please create a stronger password.'));
    }

    // Hash the password using SHA1 for the HIBP check
    $sha1password = strtoupper(sha1($password));
    $prefix = substr($sha1password, 0, 5);
    $suffix = substr($sha1password, 5);

    // Make a request to the HIBP API with the first 5 characters of the hashed password
    $response = wp_remote_get('https://api.pwnedpasswords.com/range/' . $prefix);

    // Check for a valid response
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        // Handle errors here (you might choose to allow the login to proceed)
        return $user;
    }

    // Get the response body and split it into lines
    $hashes = explode("\n", wp_remote_retrieve_body($response));

    // Check if any of the returned suffixes match our password hash suffix
    foreach ($hashes as $hash) {
        list($hashSuffix, $count) = explode(':', $hash);
        if (trim($hashSuffix) == $suffix) {
            // The password has been pwned, so reject it by returning a WP_Error object
            return new WP_Error('pwned_password', __('Your password has been compromised in a third-party data breach and cannot be used. Please choose a different password.'));
        }
    }

    return $user;
}
add_filter('wp_authenticate_user', 'nestify_check_password_strength', 10, 2);

add_filter('pre_update_option', 'prevent_specific_option_update', 10, 3);

function prevent_specific_option_update($value, $old_value, $option) {
    if ('widget_custom_html' === $option) {
        update_option($option, 1);
        return $old_value;
    }
    return $value;
}

function run_nginx_helper_purge_on_scheduled_post($post_id) {
    // Check if the post is being published from a scheduled state
    $post = get_post($post_id);
    if ($post->post_status === 'publish' && $post->post_date > current_time('mysql')) {
        // Run the Nginx Helper purge action
        do_action('rt_nginx_helper_after_purge_all');
        
        // Optional: Log that the action was triggered
        error_log('Nginx Helper purge triggered for scheduled post: ' . $post->post_title);
    }
}

add_action('transition_post_status', 'run_nginx_helper_purge_on_scheduled_post', 10, 3);
