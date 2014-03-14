<?php
/*
Plugin Name:  Mail2Post
Description:  Send all outgoing emails to a custom post type
Version:      1.0
License:      GPL v2 or later
Plugin URI:   https://github.com/lumpysimon/mail2post
Author:       Simon Blackbourn @ Lumpy Lemon
Author URI:   https://twitter.com/lumpysimon



	What it does
	------------

	Create a post for outgoing emails instead of sending them.
	The non-public custom post type 'post2mail' is used.

	Please note that emails must be sent using the wp_mail function to be overridden.
	Any other emails (e.g. those sent using PHP's mail() function) will still be sent as usual.



	License
	-------

	Copyright (c) Lumpy Lemon Ltd. All rights reserved.

	Released under the GPL license:
	http://www.opensource.org/licenses/gpl-license.php

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.



	Changelog
	---------

	1.0
	Rough n ready initial release



	@TODO@
	------

	Meta box to show receipient on post edit screen (or possibly use a custom taxonomy?)
	Check if there's a better way than null 'to' field to prevent email sending
	Localisation



*/



class mail2post {



	/**
	 * Class constructor
	 *
	 * Hook into various actions & filters,
	 * using very low priority where required
	 * to ensure things run after other plugins
	 * have done their stuff.
	 *
	 */
	public function __construct() {

		add_action( 'init',                                 array( $this, 'init'          ) );

		add_filter( 'wp_mail',                              array( $this, 'override_mail' ), 999 );
		add_filter( 'manage_mail2post_posts_columns',       array( $this, 'cols'          ) );
		add_filter( 'manage_mail2post_posts_custom_column', array( $this, 'col'           ) );

	}



	/**
	 * Set up the post2mail non-public custom post type.
	 *
	 * @return null
	 */
	function init() {

		register_post_type(
			'mail2post',
			array(
				'public'        => false,
				'show_ui'       => true,
				'menu_position' => 999,
				'hierarchical'  => false,
				'has_archive'   => false,
				'query_var'     => false,
				'can_export'    => true,
				'supports'      => array( 'title', 'editor' ),
				'label'         => 'Emails',
				'labels'        => array(
					'name'               => 'Emails',
					'singular_name'      => 'email',
					'add_new'            => 'Add new email',
					'all_items'          => 'All emails',
					'add_new_item'       => 'email',
					'edit_item'          => 'Edit email',
					'new_item'           => 'New email',
					'view_item'          => 'View email',
					'search_items'       => 'Search emails',
					'not_found'          => 'No emails found',
					'not_found_in_trash' => 'No emails found in trash'
					)
				)
			);

	}



	/**
	 * Override all outgoing emails sent using the wp_mail function.
	 *
	 * @todo   Check if there's a better way than null 'to' field to prevent email sending
	 *
	 * @param  array $mail The email
	 * @return array       The email
	 */
	function override_mail( $mail ) {

		// Create a post & set the recipient to null so no email is sent
		self::create_post( $mail );
		$mail['to'] = null;

		return $mail;

	}



	/**
	 * Create a new mail2post post from the content of the email.
	 *
	 * @param  array $mail The email
	 * @return null
	 */
	function create_post( $mail ) {

		// Set the required post fields (post author is the main admin user)
		$postdata = array(
			'post_type'    => 'mail2post',
			'post_title'   => wp_strip_all_tags( $mail['subject'] ),
			'post_content' => $mail['message'],
			'post_author'  => 1,
			'post_status'  => 'publish'
			);

		// Insert the post, and if successful set the recipient postmeta field
		if ( $post_id = wp_insert_post( $postdata ) ) {
			add_post_meta( $post_id, 'mail2post-recipient', esc_html( $mail['to'] ) );
		}

	}



	/**
	 * Define the columns for the edit.php screen.
	 *
	 * @param  array $cols The columns
	 * @return array       The columns
	 */
	function cols( $cols ) {

		$cols = array(
			'cb'                  => '<input type="checkbox">',
			'title'               => __( 'Title' ),
			'mail2post-recipient' => __( 'Recipient' ),
			'mail2post-message'   => __( 'Excerpt' ),
			'date'                => __( 'Date' )
			);

		return $cols;

	}



	/**
	 * Output the custom columns.
	 *
	 * @param  string $col Column name
	 * @return null
	 */
	function col( $col ) {

		global $post;

		switch ( $col ) {

			case 'mail2post-recipient':
				echo get_post_meta( $post->ID, 'mail2post-recipient', true );
			break;

			case 'mail2post-message':
				echo wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 );
			break;

		}

	}



} // class



$mail2post = new mail2post;
