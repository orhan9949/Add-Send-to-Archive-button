<?php
/*
Plugin Name: Archive Post Status
Plugin URI: --
Author: Orkhan Chichitov
Author URI: --
Description: The Archive Post Status Plugin for WordPress
Version: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Archive Post Status.
 */
class Archive_Post_Status {

    /**
     * Constructor.
     */
    public function __construct() {

        add_action( 'init', [ $this, 'register_post_status_archive' ] );
        add_filter( 'post_row_actions', [ $this, 'button_send_post_to_archive' ], 10, 2 );
        add_action( 'admin_init', [ $this, 'update_post_status' ] );
        add_filter( 'cloudflare_purge_url_actions', [ $this, 'purge_cloudflare_url' ], 10, 2);
    }

    /**
     * Register post status archive
     *
     * @return void
     */
    public function register_post_status_archive() {

        register_post_status( 'archive', array(
            'label'                     => __( 'Archived' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Archived <span class="count">(%s)</span>', __( 'Archived' ) .' <span class="count">(%s)</span>' ),
        ) );

    }

    /**
     * Button send post to archive
     *
     * @param $actions
     * @param $post
     * @return mixed
     */
    public function button_send_post_to_archive( $actions, $post ) {

        if ( ! current_user_can( 'edit_others_posts' ) ) {
            return $actions;
        }

        if ( $post->post_status !== 'archive' ) {

            $url = admin_url( 'post.php?post_id='.$post->ID.'&action_post_status=archive' );

            $actions['archive'] = sprintf( '<a href="%s">'. __( 'Send to Archive' ) .'</a>', esc_url( $url ) );

        } else {

            $url = admin_url( 'post.php?post_id='.$post->ID.'&action_post_status=draft' );

            $actions['archive'] = sprintf( '<a href="%s">'. __( 'Restore' ) .'</a>', esc_url( $url ) );

        }

        return $actions;

    }

    /**
     * Send to archive post
     *
     * @return void
     */
    public function update_post_status() {

        if ( ! current_user_can( 'edit_others_posts' ) ) {
            return;
        }

        if ( in_array( $_GET['action_post_status'], [ 'archive', 'draft' ] ) ) {

            $post_id = $_GET['post_id'];

            global $wpdb;

            $wpdb->update(
                'wp_posts',
                [ 'post_status' => $_GET['action_post_status'] ],
                [ 'ID' => $post_id ]
            );

            wp_cache_delete( $post_id, 'posts' );

            do_action( 'archive_status_change', $post_id );

            if ( ! function_exists( 'spinupwp_purge_post' ) ) {

                spinupwp_purge_post( $post_id );

            }

            header('Location: '.admin_url( 'post.php?edit.php?post_type=post' ) );

        }

    }

    /**
     * Purge cloudflare url
     *
     * @param $action_items
     * @return mixed
     */
    public function purge_cloudflare_url($action_items ) {

        $action_items[] = 'archive_status_change';

        return $action_items;

    }

}

new Archive_Post_Status;
