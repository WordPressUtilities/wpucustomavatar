<?php
defined('ABSPATH') || die;

/*
Plugin Name: WPU Custom Avatar
Description: Override gravatar with a custom image.
Version: 0.7.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wpucustomavatar
Requires at least: 6.2
Requires PHP: 8.0
Network: Optional
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class wpuCustomAvatar {

    private $meta_id;
    private $meta_details_id;

    public function __construct() {

        $this->meta_id = apply_filters('wpucustomavatar_metaname', 'user_custom_avatar');
        $this->meta_details_id = apply_filters('wpucustomavatar_metadetailsname', 'user_custom_avatar_details');

        /* Retrieve custom avatar */
        add_filter('get_avatar_data', array(&$this,
            'get_avatar_data'
        ), 1, 2);

        /* Hide default avatar field */
        add_filter('admin_notices', array(&$this,
            'hide_default_avatar_field'
        ));
        /* Add user metas fields */
        add_filter('wpu_usermetas_sections', array(&$this,
            'set_usermetas_sections'
        ), 10, 3);
        add_filter('wpu_usermetas_fields', array(&$this,
            'set_usermetas_fields'
        ), 10, 3);

        /* Save avatar image details */
        add_filter('profile_update', array(&$this,
            'save_user_avatar'
        ), 10, 2);
    }

    /* Getter */
    public function get_avatar_data($args, $id_or_email) {
        $user = $this->get_user($id_or_email);

        if (!$user) {
            return $args;
        }

        /* Get user avatar */
        $user_img = get_user_meta($user->data->ID, $this->meta_id, 1);
        $avatar_details = array();
        if (is_numeric($user_img)) {
            $avatar_details = wp_get_attachment_image_src($user_img, array($args['width'], $args['height']));
        }
        if (!is_array($avatar_details)) {
            $avatar_details = get_user_meta($user->data->ID, $this->meta_details_id, 1);
        }
        if (is_array($avatar_details) && isset($avatar_details[0]) && isset($avatar_details[1]) && isset($avatar_details[2])) {
            $args['url'] = $avatar_details[0];
            $args['width'] = $avatar_details[1];
            $args['height'] = $avatar_details[2];
        }

        return $args;
    }

    /* Get an user from  */
    public function get_user($id_or_email) {
        $user = false;

        /* Get user details */
        if (is_numeric($id_or_email)) {
            $id = (int) $id_or_email;
            $user = get_user_by('id', $id);
        } elseif (is_object($id_or_email)) {
            if (!empty($id_or_email->user_id)) {
                $id = (int) $id_or_email->user_id;
                $user = get_user_by('id', $id);
            }
        } else {
            $user = get_user_by('email', $id_or_email);
        }

        if (!is_object($user) || !isset($user->data->ID)) {
            $user = false;
        }

        return $user;

    }

    /* Admin */
    public function hide_default_avatar_field() {
        $screen = get_current_screen();

        /* Not profile */
        if (!is_object($screen) || ($screen->base != 'profile' && $screen->base != 'user-edit')) {
            return false;
        }
        global $user_id;
        if (!is_numeric($user_id)) {
            return false;
        }

        /* Get user custom avatar */
        $user_img = get_user_meta($user_id, $this->meta_id, 1);
        if (!is_numeric($user_img)) {
            return false;
        }

        /* Disable avatar preview in content */
        add_filter('option_show_avatars', '__return_false');
    }

    /* Fields */
    public function set_usermetas_sections($sections) {
        $sections['wpu_custom_avatar'] = array(
            'name' => __('Profile Picture')
        );
        return $sections;
    }

    public function set_usermetas_fields($fields) {
        $fields[$this->meta_id] = array(
            'name' => __('Profile Picture'),
            'type' => 'image',
            'section' => 'wpu_custom_avatar'
        );
        return $fields;
    }

    /* Save avatar image details */
    public function save_user_avatar($user_id) {
        if (!isset($_POST[$this->meta_id]) || !is_numeric($_POST[$this->meta_id])) {
            return false;
        }

        $image = wp_get_attachment_image_src((int) $_POST[$this->meta_id], 'thumbnail');
        if (!is_array($image)) {
            return false;
        }

        update_user_meta($user_id, $this->meta_details_id, $image);
    }

}

add_action('plugins_loaded', 'wpucustomavatar_init', 5, 0);
function wpucustomavatar_init() {
    $wpuCustomAvatar = new wpuCustomAvatar();
}
