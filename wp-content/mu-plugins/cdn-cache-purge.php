<?php
/*
 * Plugin Name: CDN Cache Helper
 * Description: Clear global CDN cache on content update
 * Version: 2.0.0
*/

if ( ! defined( 'CDN_SITE_ID' ) ) {
    define( 'CDN_SITE_ID', 1 );
}

define( 'RT_WP_NGINX_HELPER_REDIS_HOSTNAME', '127.0.0.1' );
define( 'RT_WP_NGINX_HELPER_REDIS_PORT', '6379' );
define( 'RT_WP_NGINX_HELPER_REDIS_PREFIX', CDN_SITE_ID.':' );
require_once WPMU_PLUGIN_DIR . '/nginx-helper/nginx-helper.php';

function custom_remove_nginx_submenu() {
    remove_submenu_page('options-general.php', 'nginx');
}
add_action('admin_menu', 'custom_remove_nginx_submenu', 1000);

function log_purge_action($url = 'all') {    
    static $files = array();
    static $shutdown_hook_registered = false; 

    if ($url === '') {
        //$files[] = 'Purge all';
    } else {
        $files[] = $url;
    }

    if (!$shutdown_hook_registered) {
        add_action('shutdown', function() use (&$files) {
        $data = array('url' => $_SERVER['HTTP_HOST'], 'files' => $files);

        $curl_url = 'https://my.nestify.io/cdn/purge/' . CDN_SITE_ID . '/purge';
        $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $curl_url,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                    ],
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_RETURNTRANSFER => true,
                    // Timeout the request after 5 seconds
                    CURLOPT_TIMEOUT => 5,
                ]);

                try {
                    $result = curl_exec($ch);
                    curl_close($ch);
                    $data['result'] = $result;
                    /*$log_entry = sprintf("[%s] Purged URL: %s\n", date('Y-m-d H:i:s'), json_encode($data));
                    $log_file = WP_CONTENT_DIR . '/nginx-helper-purge-log.txt';
                    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
		    */
                } catch (\Exception $e) {
                    error_log(' Exception: ' . $e->getMessage());
                }

        }, 10, 0);
        $shutdown_hook_registered = true;
    }
}

add_action('rt_nginx_helper_purge_url', 'log_purge_action', 1000, 1);
add_action('rt_nginx_helper_after_purge_all', 'log_purge_action', 1000, 1);
