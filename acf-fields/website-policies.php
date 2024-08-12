<?php

add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_66b51ee840001',
	'title' => 'Policies Settings',
	'fields' => array(
		array(
			'key' => 'field_66b51ee8b0280',
			'label' => 'Cookie Consent Scripts',
			'name' => 'cookie_consent_scripts',
			'aria-label' => '',
			'type' => 'textarea',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'maxlength' => '',
			'rows' => '',
			'placeholder' => '',
			'new_lines' => '',
		),
		array(
			'key' => 'field_66b52b39e4791',
			'label' => 'Enable Cookie Consent',
			'name' => 'enable_cookie_consent',
			'aria-label' => '',
			'type' => 'true_false',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'message' => 'Enable the Cookie Consent popup',
			'default_value' => 0,
			'ui' => 0,
			'ui_on_text' => '',
			'ui_off_text' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'options_page',
				'operator' => '==',
				'value' => 'settings',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
	'show_in_rest' => 0,
) );
} );

add_action( 'init', function() {
	register_post_type( 'website_policy', array(
	'labels' => array(
		'name' => 'Website Policies',
		'singular_name' => 'Website Policy',
		'menu_name' => 'Website Policies',
		'all_items' => 'All Website Policies',
		'edit_item' => 'Edit Website Policy',
		'view_item' => 'View Website Policy',
		'view_items' => 'View Website Policies',
		'add_new_item' => 'Add New Website Policy',
		'add_new' => 'Add New Website Policy',
		'new_item' => 'New Website Policy',
		'parent_item_colon' => 'Parent Website Policy:',
		'search_items' => 'Search Website Policies',
		'not_found' => 'No website policies found',
		'not_found_in_trash' => 'No website policies found in Trash',
		'archives' => 'Website Policy Archives',
		'attributes' => 'Website Policy Attributes',
		'insert_into_item' => 'Insert into website policy',
		'uploaded_to_this_item' => 'Uploaded to this website policy',
		'filter_items_list' => 'Filter website policies list',
		'filter_by_date' => 'Filter website policies by date',
		'items_list_navigation' => 'Website Policies list navigation',
		'items_list' => 'Website Policies list',
		'item_published' => 'Website Policy published.',
		'item_published_privately' => 'Website Policy published privately.',
		'item_reverted_to_draft' => 'Website Policy reverted to draft.',
		'item_scheduled' => 'Website Policy scheduled.',
		'item_updated' => 'Website Policy updated.',
		'item_link' => 'Website Policy Link',
		'item_link_description' => 'A link to a website policy.',
	),
	'public' => true,
	'show_in_rest' => false,
	'menu_icon' => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1MTIgNTEyIj48cGF0aCBmaWxsPSJjdXJyZW50Q29sb3IiIGQ9Ik0zMTguNiA5LjRjLTEyLjUtMTIuNS0zMi44LTEyLjUtNDUuMyAwbC0xMjAgMTIwYy0xMi41IDEyLjUtMTIuNSAzMi44IDAgNDUuM2wxNiAxNmMxMi41IDEyLjUgMzIuOCAxMi41IDQ1LjMgMGw0LTRMMzI1LjQgMjkzLjRsLTQgNGMtMTIuNSAxMi41LTEyLjUgMzIuOCAwIDQ1LjNsMTYgMTZjMTIuNSAxMi41IDMyLjggMTIuNSA0NS4zIDBsMTIwLTEyMGMxMi41LTEyLjUgMTIuNS0zMi44IDAtNDUuM2wtMTYtMTZjLTEyLjUtMTIuNS0zMi44LTEyLjUtNDUuMyAwbC00IDRMMzMwLjYgNzQuNmw0LTRjMTIuNS0xMi41IDEyLjUtMzIuOCAwLTQ1LjNsLTE2LTE2em0tMTUyIDI4OGMtMTIuNS0xMi41LTMyLjgtMTIuNS00NS4zIDBsLTExMiAxMTJjLTEyLjUgMTIuNS0xMi41IDMyLjggMCA0NS4zbDQ4IDQ4YzEyLjUgMTIuNSAzMi44IDEyLjUgNDUuMyAwbDExMi0xMTJjMTIuNS0xMi41IDEyLjUtMzIuOCAwLTQ1LjNsLTEuNC0xLjRMMjcyIDI4NS4zIDIyNi43IDI0MCAxNjggMjk4LjdsLTEuNC0xLjR6Ii8+PC9zdmc+',
	'supports' => array(
		0 => 'title',
		1 => 'editor',
	),
	'rewrite' => array(
		'slug' => 'website-policies',
		'with_front' => false,
	),
	'delete_with_user' => false,
) );
} );

add_action( 'acf/init', function() {
	acf_add_options_page( array(
	'page_title' => 'Settings',
	'menu_slug' => 'settings',
	'parent_slug' => 'edit.php?post_type=website_policy',
	'position' => '',
	'redirect' => false,
) );
} );

