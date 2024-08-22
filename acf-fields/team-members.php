<?php

add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_663922356667a',
	'title' => 'Team Member Info',
	'fields' => array(
		array(
			'key' => 'field_663922358f855',
			'label' => 'First Name',
			'name' => 'first_name',
			'aria-label' => '',
			'type' => 'text',
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
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_6639223d8f856',
			'label' => 'Last Name',
			'name' => 'last_name',
			'aria-label' => '',
			'type' => 'text',
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
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_663922438f857',
			'label' => 'Title',
			'name' => 'title',
			'aria-label' => '',
			'type' => 'text',
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
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'team_member',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'acf_after_title',
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
	register_taxonomy( 'team_member_types', array(
	0 => 'team_member',
), array(
	'labels' => array(
		'name' => 'Team Member Types',
		'singular_name' => 'Team Member Types',
		'menu_name' => 'Team Member Type',
		'all_items' => 'All Team Member Type',
		'edit_item' => 'Edit Team Member Types',
		'view_item' => 'View Team Member Types',
		'update_item' => 'Update Team Member Types',
		'add_new_item' => 'Add New Team Member Types',
		'new_item_name' => 'New Team Member Types Name',
		'search_items' => 'Search Team Member Type',
		'popular_items' => 'Popular Team Member Type',
		'separate_items_with_commas' => 'Separate team member type with commas',
		'add_or_remove_items' => 'Add or remove team member type',
		'choose_from_most_used' => 'Choose from the most used team member type',
		'not_found' => 'No team member type found',
		'no_terms' => 'No team member type',
		'items_list_navigation' => 'Team Member Type list navigation',
		'items_list' => 'Team Member Type list',
		'back_to_items' => '← Go to team member type',
		'item_link' => 'Team Member Types Link',
		'item_link_description' => 'A link to a team member types',
	),
	'public' => true,
	'show_in_menu' => true,
	'show_in_rest' => false,
	'show_admin_column' => true,
	'rewrite' => array(
		'hierarchical' => true,
	),
	'sort' => true,
) );
} );

add_action( 'init', function() {
	register_post_type( 'team_member', array(
	'labels' => array(
		'name' => 'Team Members',
		'singular_name' => 'Team Member',
		'menu_name' => 'Team Members',
		'all_items' => 'All Team Members',
		'edit_item' => 'Edit Team Member',
		'view_item' => 'View Team Member',
		'view_items' => 'View Team Members',
		'add_new_item' => 'Add New Team Member',
		'add_new' => 'Add New Team Member',
		'new_item' => 'New Team Member',
		'parent_item_colon' => 'Parent Team Member:',
		'search_items' => 'Search Team Members',
		'not_found' => 'No team members found',
		'not_found_in_trash' => 'No team members found in Trash',
		'archives' => 'Team Member Archives',
		'attributes' => 'Team Member Attributes',
		'insert_into_item' => 'Insert into team member',
		'uploaded_to_this_item' => 'Uploaded to this team member',
		'filter_items_list' => 'Filter team members list',
		'filter_by_date' => 'Filter team members by date',
		'items_list_navigation' => 'Team Members list navigation',
		'items_list' => 'Team Members list',
		'item_published' => 'Team Member published.',
		'item_published_privately' => 'Team Member published privately.',
		'item_reverted_to_draft' => 'Team Member reverted to draft.',
		'item_scheduled' => 'Team Member scheduled.',
		'item_updated' => 'Team Member updated.',
		'item_link' => 'Team Member Link',
		'item_link_description' => 'A link to a team member.',
	),
	'public' => true,
	'show_in_rest' => false,
	'supports' => array(
		0 => 'editor',
		1 => 'thumbnail',
	),
	'taxonomies' => array(
		0 => 'team_member_types',
	),
	'rewrite' => false,
	'delete_with_user' => false,
) );
} );

