<?php
/*
Plugin Name:  Community Impact Directory
Description:  Automatically populates the Community Impact Directory including an audio impact statement visualizer.
Version:      1.0
Author:       Samuel Diamond
*/

if(!defined('ABSPATH')) {
	exit;
}

// -----------------------  Shortcode  ----------------------------------
// Display Parent
function display_parent_function($attributes) {
	$categories = get_terms(array(
		'taxonomy'=>get_option('directory_taxonomy'),
		'hide_empty'=>false
	));
	
	// Set default options
	$a = shortcode_atts( array(
		'parent_category' => -1,
		'display_text' => ''
	), $attributes );
	
	if(isset($_GET['cidparent']) && $_GET['cidparent'] != '') {
		if($a['parent_category'] != -1) {
			if($a['parent_category'] == $_GET['cidparent']) {
				return $a['display_text'];
			}
		} else {
			foreach($categories as $category) {
				if($category->term_id == $_GET['cidparent']) {
					return $category->name;
				}
			}
		}
	}
}
add_shortcode('cid_parent', 'display_parent_function');	

// Directory
function search_results_setup_function($attributes) {
	$output = '';
	$tax_query_terms = array();
		
	if(isset($_GET['cidcategory']) && $_GET['cidcategory'] != ''){
		$cid_categories = explode('-', $_GET['cidcategory']);
		foreach ($cid_categories as $cid_category) {
			$tax_query_terms[] = array(
				'taxonomy' => get_option('directory_taxonomy'),
				'field' => 'term_id',
				'terms' => $cid_category
			);
		}
	} else if(isset($_GET['cidparent']) && $_GET['cidparent'] != '') {
		$tax_query_terms['relation'] = 'OR';
		
		$categories = get_terms(array(
			'taxonomy'=> get_option('directory_taxonomy'),
			'hide_empty'=>false
		));
		
		foreach($categories as $category) {
			if($category->parent != 0) {
				$sub_category = get_term($category->parent);
				if($sub_category->parent == $_GET['cidparent']) {
					$tax_query_terms[] = array(
						'taxonomy' => get_option('directory_taxonomy'),
						'field' => 'term_id',
						'terms' => $category->term_id
					);
				}
			}
		}
	}
	
	$action_icon_query = array();
	
	if(isset($_GET['cidaction']) && $_GET['cidaction'] != ''){
		$action_icon_query[] = array(
			'key' => 'action_icon_'.$_GET['cidaction'],
			'value' => '',
			'compare' => '!='
			);
	}
	
	$args = array(
		'post_type' => get_option('directory_post_type'),
		'tax_query' => $tax_query_terms,
		'meta_query' => $action_icon_query
	);
	
	if(isset($_GET['cidsearch']) && $_GET['cidsearch'] != '') {
		$args['s'] = $_GET['cidsearch'];
	} else {
		$args['order'] = 'ASC';
		$args['orderby'] = 'title';
	}
	
	$query = new WP_Query($args);
	
	if ( $query->have_posts() ) {
		if(isset($_GET['cidsearch']) && $_GET['cidsearch'] != '') {
			$output .= '<h2 class="cid-search-results">Search results for <strong>"'.$_GET['cidsearch'].'"</strong>:</h2>';
		}
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			
			$output .= 
			'<article class="cid-listing">
				<header>
					<h2 class="cid-listing-title">'.
					get_the_title($post_id).
					'</h2>
				</header>
				<div class="cid-external-directory-links">';
					$external_icons = get_option('external_icons'); 
	
					if(is_array($external_icons)){
						$output .= '<ul class="cid-listing-external-icons">';
						foreach($external_icons as $icon) {
							if(is_array($icon)){
								$post_external_link = esc_attr(get_post_meta($post_id, 'external_icon_'.$icon['icon_id'], true));
								if($post_external_link != '') {
									$output .= '<li class="cid-listing-external-icon"><a href="'.$post_external_link.'" target="_blank">'.$icon['icon_name'].'<img src="'.$icon['icon_src'].'"></a></li>';
								}
							}
						}
						$output .= '</ul>';
					}
				$output .= '</div>
				<div class="cid-listing-body">
					<div class="cid-listing-flex">
						<div class="cid-listing-thumbnail">';
							$full_listing = get_post_meta($post_id, 'full_listing', true);
							
							if($full_listing == 'full') {
								$output .= '<a href="'.get_post_permalink($post_id);
								
								if(isset($_GET['cidparent'])) {
									$output .= '?cidparent='.$_GET['cidparent'];
								}
								
								$output .= '">';
							}
							
							$output .= get_the_post_thumbnail($post_id);
							
							$impact_statement_audio = get_post_meta($post_id, 'impact_statement_audio', true);
							if($impact_statement_audio != '') {
								$output .= '<canvas class="cid-visualizer"></canvas>';
							}
							
							if($full_listing == 'full') {
								$output .= '</a>';
							}
						$output .=
						'</div>
						<div class="cid-listing-impact-statement">
							<h3>Impact Statement:</h3>';
							
							if($impact_statement_audio != '') {
								$output .= '<audio class="cid-impact-statement-audio" controls><source type="audio/mpeg" src="'.$impact_statement_audio.'"></audio>';
							}
							
							$output .= '<p class="impact-statement">'.get_post_meta($post_id, 'impact_statement', true);
							
							if($full_listing == 'full') {
								$output .= '<a class="cid-more-info" href="'.get_post_permalink($post_id).'">More Information</a>';
							}
							
							$output .= '</p>'.
						'</div>
					</div>';
					
					$action_icons = get_option('directory_actions'); 
	
					if(is_array($action_icons)){
						$output .= '<ul class="cid-listing-action-icons">';
						foreach($action_icons as $icon) {
							if(is_array($icon)){
								if(!isset($_GET['cidparent']) || (isset($icon['icon_parents']) && is_array($icon['icon_parents']) && in_array($_GET['cidparent'], $icon['icon_parents']))) {
									$post_action_link = esc_attr(get_post_meta($post_id, 'action_icon_'.$icon['icon_id'], true));
									if($post_action_link != '') {
										$output .= '<li class="cid-listing-action-icon"><a href="'.$post_action_link.'" target="_blank"><img src="'.$icon['icon_src'].'">'.$icon['icon_name'].'</a></li>';
									}
								}
							}
						}
						$output .= '</ul>';
					}
				$output .=
				'</div>
			</article>';
		}
		
		wp_reset_postdata();
	} else {
		if(isset($_GET['cidsearch'])) {
			$output .= '<p class="cid-no-results">Your search for <strong>"'.$_GET['cidsearch'].'"</strong> gave no results.  Please try a different search term, another category, or reduce the number of categories selected.</p>';
		} else {
			$output .= '<p class="cid-no-results">The category or combination of categories you\'ve selected returned no results.  Please try a different category or reduce the number of categories selected.</p>';
		}
	}
	
	wp_enqueue_script('visualizer', plugin_dir_url(__FILE__) . 'js/visualizer.js', array('jquery'), '1.1', true);
	
	return $output;
}
add_shortcode('cid_search_results', 'search_results_setup_function');		

// Parent category buttons
function get_parent_categories($categories) {
	
	// Determine the parent categories
	$parent_categories = array();
	if(is_array($categories)) {
		foreach($categories as $category) {
			if($category->parent == 0) {
				$parent_categories[] = $category->term_id;
			}
		}
	}
	
	return $parent_categories;
}

function homepage_parent_buttons_setup_function ($attributes) {
	$categories = get_terms(array(
		'taxonomy'=> get_option('directory_taxonomy'),
		'hide-empty'=>false
		));
		
	$parent_categories = get_parent_categories($categories);
	
	// Set default options
	$a = shortcode_atts( array(
		'parent_category' => -1,
		'button_text' => 'Click here'
	), $attributes );
	
	// Determine next valid parent category if not valid or already assigned
	$parent_category = $a['parent_category'];
	static $assigned_parent_categories = array();
	
	if(!in_array($parent_category, $parent_categories) || in_array($parent_category, $assigned_parent_categories)) {
		// Find which parent categories haven't been used
		$unassigned_parent_categories = array_diff($parent_categories, $assigned_parent_categories);
		
		// Get first unused parent category
		$parent_category = reset($unassigned_parent_categories);
		
		// Do nothing if no valid parent categories remain.
		if(!$parent_category) {
			return;
		}
	}
	
	// Add used parent category to list of assigned categories
	$assigned_parent_categories[] = $parent_category;

	// Add javascript for interactivity
	wp_enqueue_script('homepage_setup', plugin_dir_url(__FILE__) . 'js/homepage_setup.js', array('jquery'), '1.0', true);
	
	return '<div class="parent-category-button" data-parent-category-id="'.$parent_category.'">'.$a['button_text'].'</div>';
}
add_shortcode('cid_homepage_parent_buttons', 'homepage_parent_buttons_setup_function');

// Categories Setup
function cid_categories_function ($attributes) {
	
	$taxonomy = get_option('directory_taxonomy');
	
	$categories = get_terms(array(
		'taxonomy'=> $taxonomy,
		'orderby'=>'name',
		'order'=>'ASC',
		'hide-empty'=>false
		));
		
	foreach($categories as $category) {
		print_r($category->name);
	}
		
	$parent_categories = get_parent_categories($categories);
	
	$output = '';
	
	$search = '';
	if(isset($_GET['cidsearch']) && $_GET['cidsearch'] != '') {
		$search = 'cidsearch='.$_GET['cidsearch'].'&';
	}
	
	
	$cid_categories = array();
	if(isset($_GET['cidcategory'])) {
		$cid_categories = explode('-', $_GET['cidcategory']);
	}
	
	$action = '';
	if(isset($_GET['cidaction']) && $_GET['cidaction'] != '') {
		$action = '&cidaction='.$_GET['cidaction'];
	}

	if(is_array($categories) && is_array($parent_categories)) {

		$output .= '<div>';
		
		foreach($parent_categories as $parent_category) {
			if(!isset($_GET['cidparent']) || $_GET['cidparent'] == $parent_category) {
				$sub_categories = get_term_children($parent_category, $taxonomy);
				
				if(is_array($sub_categories)) {
					$output .= '<div class="category-list" data-parent-category-id="'.$parent_category.'">';
					if(!isset($_GET['cidparent'])) {
						$output .= '<h2 class="cid-homepage-show">'.get_term($parent_category)->name.'</h2>';
					}
					
					$output .= '<a class="cid-clear-filters" href="'.esc_url( home_url( '/' ) ).'search/?'.$search.'cidparent='.$parent_category.$action.'">Clear filters</a>';
					
					foreach($sub_categories as $sub_category) {
						
						$children = get_terms($taxonomy, array('child_of'=>$sub_category, 
																'taxonomy'=>$taxonomy,
																'orderby'=>'name',
																'order'=>'ASC',
																'hide-empty'=>false
																));
						
						$sub_category = get_term($sub_category);
						
						if($sub_category->parent == $parent_category) {
						
							$output .= '<div class="sub-category"><h3>'.$sub_category->name.'</h3>';
							
							if(is_array($children)) {
								
								$output .= '<ul>';
								
								foreach($children as $child) {
									
									$selected = '';
									$checked = '';
									if(in_array($child->term_id, $cid_categories)) {
										$selected = 'selected';
										$checked = 'checked';
										if(sizeof($cid_categories) > 1) {
											$new_categories = $cid_categories;
											unset($new_categories[array_search($child->term_id, $new_categories)]);
											$cid_category =  '&cidcategory='.implode('-', $new_categories);
										} else {
											$cid_category = '';
										}
									}else if(isset($_GET['cidcategory'])){
										$cid_category = '&cidcategory='.$_GET['cidcategory'].'-'.$child->term_id;
									} else {
										$cid_category = '&cidcategory='.$child->term_id;
									}
									$href = esc_url( home_url( '/' ) ).'search/?'.$search.'cidparent='.$parent_category.$cid_category.$action;
									$output .= '<li class="'.$selected.'"><a href="'.$href.'"><input onclick="window.location.href = \''.$href.'\';" type="checkbox" '.$checked.'>'.$child->name.'</a></li>';
								}
								
								$output .= '</ul>';
							}
							$output .= '</div>';
							
						}
					}
					
					$output .= '</div>';
					
				}
			}
		}
		
		$output .= '</div>';
		
	}

	return $output;
}
add_shortcode('cid_categories', 'cid_categories_function');

// Setup Action Icons Filter
function cid_action_icons_function ($attributes) {
	
	$search = '';
	if(isset($_GET['cidsearch']) && $_GET['cidsearch'] != '') {
		$search = 'cidsearch='.$_GET['cidsearch'].'&';
	}
	
	$parent = '';
	$parent_category = -1;
	
	if(isset($attributes['parent_category'])) {
		$parent_category = $attributes['parent_category'];
	} else if(isset($_GET['cidparent']) && $_GET['cidparent'] != '') {
		$parent_category = $_GET['cidparent'];
	}

	if($parent_category != -1) {
		$parent = 'cidparent='.$parent_category.'&';
	}
	
	$category = '';
	if(isset($_GET['cidcategory']) && $_GET['cidcategory'] != '') {
		$category = 'cidcategory='.$_GET['cidcategory'].'&';
	}

	$output = '';
	
	$action_icons = get_option('directory_actions'); 
	
	$icon_ids = array();
	if(isset($attributes['icon_ids'])) {
		$icon_ids = explode(',', $attributes['icon_ids']);
	}
	
	if(is_array($action_icons)){
		$output .= '<ul class="cid-action-icons" data-parent-category-id="'.$parent_category.'">';
		foreach($action_icons as $icon) {
			if(is_array($icon)){
				if((sizeof($icon_ids) == 0 || in_array($icon['icon_id'], $icon_ids)) && ($parent_category == -1 || (isset($icon['icon_parents']) && is_array($icon['icon_parents']) && in_array($parent_category, $icon['icon_parents'])))) {
					$selected = '';
					$action = 'cidaction='.$icon['icon_id'];
					if(isset($_GET['cidaction']) && $icon['icon_id'] == $_GET['cidaction']) {
						$selected = ' selected';
						$action = '';
					}
					$output .= '<li class="cid-action-icon'.$selected.'"><a href="'.esc_url( home_url( '/' ) ).'search/?'.$search.$parent.$category.$action.'"><img src="'.$icon['icon_src'].'">'.$icon['icon_name'].'</a></li>';
				}
			}
		}
		$output .= '</ul>';
	}
	return $output;
}

add_shortcode('cid_action_icons', 'cid_action_icons_function');

// Display Impact Statement
function cid_post_impact_statement_function() {
	return '<div class="cid-post-impact-statement">'.get_post_meta(get_the_ID(), 'impact_statement', true).'</div>';
}

add_shortcode('cid_post_impact_statement', 'cid_post_impact_statement_function');

// Localize the autohide javascript 
function cid_autohide() {
	$cidparent = -1;
	
	if(isset($_GET['cidparent'])) {
		$cidparent = $_GET['cidparent'];
	}

	wp_enqueue_script('autohide_ajax', plugin_dir_url(__FILE__) . 'js/autohide_ajax.js', array('jquery'), '1.0', false);
	wp_localize_script('autohide_ajax', 'autohide_ajax_object', array('cidparent'=>$cidparent));
}

add_shortcode('cid_autohide', 'cid_autohide');

// ---------------- Search Field ------------------------
function search_form_insert_hidden_input($form, $parameter) {
	if(isset($_GET[$parameter])) {
		$cid_hidden_input = '<input name="'.$parameter.'" type="hidden" value="'.$_GET[$parameter].'">';	
		return insert_string($form, $cid_hidden_input, '</form>', false);
	}
	return $form;
}

function insert_string($base_string, $insertion_string, $insertion_point_string, $replace) {
	
	$insertion_point = strpos($base_string, $insertion_point_string);
	
	$length = 0;
	if($replace) {
		$length = strlen($insertion_point_string);
	}
	
	if($insertion_point != false) {
		return substr_replace($base_string, $insertion_string, $insertion_point, $length);
	}
	
	return $base_string;
}


function cid_search_link($form) {
	$parameters = array('cidparent', 'cidcategory', 'cidaction');
	
	// Setup hidden inputs to pass GET information
	foreach($parameters as $parameter) {
		$form = search_form_insert_hidden_input($form, $parameter);
	}
	
	// Customize search field
	$form = insert_string($form, 'name="cidsearch"', 'name="s"', true);
	if(isset($_GET['cidsearch'])) {
		$form = insert_string($form, 'value="'.$_GET['cidsearch'].'"', 'value=""', true);
	}
	
	// Redirect search to page with shortcode
	$form_action = 'action="' . esc_url( home_url( '/' ) );
	$form = insert_string($form, $form_action . 'search/"', $form_action . '"', true);
	
	// Add 'Clear' button
	$parent = '';
	$category = '';
	$action = '';
	
	if(isset($_GET['cidparent'])) {
		$parent = '&cidparent='.$_GET['cidparent'];
	}
	
	if(isset($_GET['cidcategory'])) {
		$category = '&cidcategory='.$_GET['cidcategory'];
	}
	
	if(isset($_GET['cidaction'])) {
		$action = '&cidaction='.$_GET['cidaction'];
	}
	
	$form = insert_string($form, '<a class="cid-search-clear" href="'.esc_url( home_url( '/' ) ).'search/?'.$parent.$category.$action.'">Clear</a>', '</form>', false);
	
	// Add class for javascript control of visibility
	$form = '<div class="cid-search">'.$form.'</div>';
	
	return $form;
}
add_filter('get_search_form', 'cid_search_link', 1000, 1);

//  ----------------------- Navigation ---------------------------------------
function _custom_nav_menu_item( $title, $url, $id ){
  $item = new stdClass();
  $item->ID = $id;
  $item->db_id = $item->ID;
  $item->title = $title;
  $item->url = $url;
  $item->menu_order = $id;
  $item->menu_item_parent = 0;
  $item->type = '';
  $item->object = '';
  $item->object_id = '';
  $item->classes = array('cid-navigation-item');
  $item->target = '';
  $item->attr_title = '';
  $item->description = '';
  $item->xfn = '';
  $item->status = '';
  return $item;
}

function cid_navigation_function($items, $menu) {
	
	$categories = get_terms(array(
		'taxonomy'=> get_option('directory_taxonomy'),
		'hide-empty'=>false
		));
		
	$parent_categories = get_parent_categories($categories);
	
	if(is_array($parent_categories)) {
		foreach($parent_categories as $parent_category) {
			if(!isset($_GET['cidparent']) || $_GET['cidparent'] != $parent_category) {
				$items[] = _custom_nav_menu_item(get_term($parent_category)->name,'', $parent_category);
			}
		}
	}
	
	wp_enqueue_script('navigation_setup', plugin_dir_url(__FILE__) . 'js/navigation_setup.js', array('jquery'), '1.0', true);
		
	return $items;
}
add_filter('wp_get_nav_menu_items', 'cid_navigation_function', 10, 2);

function cid_navigation_attribute_function($atts, $item, $args) {
	$categories = get_terms(array(
		'taxonomy'=> get_option('directory_taxonomy'),
		'hide-empty'=>false
		));
		
	$parent_categories = get_parent_categories($categories);
	
	if(is_array($parent_categories) && in_array($item->ID, $parent_categories)) {

		$atts['data-parent-category-id'] = $item->ID;
		
		$link = esc_url( home_url( '/' ) ).'search/?';
		
		if(isset($_GET['cidsearch']) && $_GET['cidsearch'] != '') {
			$link .= 'cidsearch='.$_GET['cidsearch'].'&';
		}
		
		$link .= 'cidparent='.$item->ID;
		
		$atts['href'] = $link;
		
		if(isset($atts['class'])) { 
			$atts['class'] .= ' cid-homepage-hide';
		} else {
			$atts['class'] = 'cid-homepage-hide';
		}
	}
	
	return $atts;
}
add_filter('nav_menu_link_attributes', 'cid_navigation_attribute_function', 10, 3);

//  ---------------------  Default Directory Logo Thumbnails ---------------------------
function cid_default_logo_function ($value, $post_id, $meta_key) {
	$meta_cache = wp_cache_get($post_id, 'post_meta');
	if($meta_key == '_thumbnail_id' && (!is_array($meta_cache) || !isset($meta_cache['_thumbnail_id']))) {
		return get_option('directory_default_logo');
	}
}

add_filter('get_post_metadata', 'cid_default_logo_function', 10, 3);

//  ---------------------  Custom Fields for editing posts ---------------------------
// Action Icons
function add_icon_metabox() {
	add_meta_box (
		'action_icon_metabox',
		'Action Icon Link Addresses',
		'icon_metabox_callback',
		get_option('directory_post_type'),
		'advanced',
		'default',
		array('icon_type'=>'action_icon')
	);
	add_meta_box (
		'external_icon_metabox',
		'External Icon Link Addresses',
		'icon_metabox_callback',
		get_option('directory_post_type'),
		'advanced',
		'default',
		array('icon_type'=>'external_icon')
	);
}
add_action('add_meta_boxes', 'add_icon_metabox');

function icon_metabox_callback ($post, $metabox) {
	wp_nonce_field(basename(__FILE__), 'action_icon_nonce');
	
	wp_enqueue_style('settings_styles', plugin_dir_url(__FILE__) . 'css/settings_styles.css');
	
	$icons = array();
	$prefix = '';
	
	if($metabox['args']['icon_type'] == 'action_icon') {
		$icons = get_option('directory_actions');
		$prefix = 'action_icon_';
	} else if($metabox['args']['icon_type'] == 'external_icon') {
		$icons = get_option('external_icons');
		$prefix = 'external_icon_';
	}
	
	if(is_array($icons)){
		foreach($icons as $icon) {
			if(is_array($icon)){
				echo '<label>'.$icon['icon_name'].' Link Address: <input type="text" value="'.esc_attr(get_post_meta($post->ID, $prefix.$icon['icon_id'] , true)).'" name="'.$prefix.$icon['icon_id'].'" id="'.$prefix.$icon['icon_id'].'"></label>';
			}
		}
	}
}

// Impact Statement
function add_impact_statement_metabox() {
	add_meta_box (
		'impact_statement_metabox',
		'Impact Statement',
		'impact_statement_metabox_callback',
		get_option('directory_post_type')
	);
}
add_action('add_meta_boxes', 'add_impact_statement_metabox');

function impact_statement_metabox_callback ($post) {
	wp_nonce_field(basename(__FILE__), 'impact_statement_nonce');
	wp_enqueue_media();
	wp_enqueue_script('impact_statement', plugin_dir_url(__FILE__) . 'js/post_impact_statement_audio.js', array('jquery'), '1.0', true);
	$audio_track = get_post_meta($post->ID, 'impact_statement_audio', true);
	
	$checked = '';
	if(get_post_meta($post->ID, 'full_listing', true) == 'full') {
		$checked = 'checked';
	}
	
	echo '<label>Full Listing: <input type="checkbox" id="full-listing" name="full_listing" '.$checked.' value="full"></label>';
	
	echo wp_editor(get_post_meta($post->ID, 'impact_statement', true), 'impact_statement');
	echo '<label>Audio Track: <input type="text" id="impact_statement_audio" name="impact_statement_audio" value="'.$audio_track.'"></label>';
	if($audio_track != '') {
		$audio_track = '<source type="audio/mpeg" src="'.$audio_track.'">';
	}
	echo '<audio controls id="audio_track_player" >'.$audio_track.'</audio>';	
	echo '<input id="select_audio_button" type="button" value="Select Audio Track">';
}

// Save post custom meta box
function save_icon_metabox ($post_id){
	if(!isset($_POST['action_icon_nonce']) 
		|| !wp_verify_nonce($_POST['action_icon_nonce'], basename(__FILE__)) 
		|| (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		|| !current_user_can('edit_post', $post_id)
		) {return;}

	$action_icons = get_option('directory_actions');
		
	if(is_array($action_icons)){
		foreach($action_icons as $icon) {
			if(is_array($icon)){
				$address = sanitize_text_field($_POST['action_icon_'.$icon['icon_id']]);
				update_post_meta($post_id, 'action_icon_'.$icon['icon_id'] , $address);
			}
		}
	}
	
	$external_icons = get_option('external_icons');
		
	if(is_array($external_icons)){
		foreach($external_icons as $icon) {
			if(is_array($icon)){
				$address = sanitize_text_field($_POST['external_icon_'.$icon['icon_id']]);
				update_post_meta($post_id, 'external_icon_'.$icon['icon_id'] , $address);
			}
		}
	}
}
add_action('save_post', 'save_icon_metabox');

function save_statement_metabox ($post_id){
	if(!isset($_POST['impact_statement_nonce']) 
		|| !wp_verify_nonce($_POST['impact_statement_nonce'], basename(__FILE__)) 
		|| (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		|| !current_user_can('edit_post', $post_id)
		) {return;}

		$full_listing = '';
		
		if(isset($_POST['full_listing'])) {
			$full_listing = $_POST['full_listing'];
		}
		
		update_post_meta($post_id, 'full_listing', $full_listing);
		update_post_meta($post_id, 'impact_statement' , $_POST['impact_statement']);
		update_post_meta($post_id, 'impact_statement_audio' , sanitize_text_field($_POST['impact_statement_audio']));
}
add_action('save_post', 'save_statement_metabox');


// --------------------------  Settings --------------------------
// Settings Page setup
function directory_settings_page() {
	// Add the menu item and page
	$capability = 'manage_options';
	$slug = 'cid_settings';
	
	// Page title, menu title, capability, menu slug, callback, icon url, position
	add_menu_page('Post Settings', 'Community Impact Directory', $capability, $slug, 'cid_post_settings_callback' );
	
	// Parent slug, page title, menu title, capability, menu slug, callback
	add_submenu_page($slug, 'Action Icons', 'Action Icons', $capability, $slug.'_action_icons', 'cid_settings_icons_callback' );
	add_submenu_page($slug, 'External Icons', 'External Icons', $capability, $slug.'_external_icons', 'cid_settings_icons_callback' );
}
add_action('admin_menu', 'directory_settings_page');

// Main settings page content
function cid_post_settings_callback() { 
	if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    } 

	?>
	<div class="wrap">
		<h2>Community Impact Directory</h2>
		<?php settings_errors(); ?>
		<form method="post" action="options.php">
			<?php
				settings_fields( 'cid_settings' );
				do_settings_sections( 'cid_settings' );
				submit_button();
			?>
		</form>
	</div>
<?php 

	$ajax_array = array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'page'=>'directory_default_logo');
	wp_enqueue_media();
	wp_enqueue_script('settings_ajax', plugin_dir_url(__FILE__) . 'js/settings_ajax.js', array('jquery'), '1.0', true);
	wp_localize_script('settings_ajax', 'ajax_object', $ajax_array);
	wp_enqueue_style('settings_styles', plugin_dir_url(__FILE__) . 'css/settings_styles.css');
}

// Add and Remove Icon Types subpage content
function cid_settings_icons_callback() {
	if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    } 
	
	global $plugin_page;
	
	if($plugin_page == 'cid_settings_action_icons') {
		$categories = get_terms(array(
			'taxonomy'=> get_option('directory_taxonomy'),
		'hide-empty'=>false
			));
			
		$parent_categories = get_parent_categories($categories);
		$parent_category_names = [];
		if(is_array($parent_categories)) {
			foreach($parent_categories as $parent_category) {
				$parent_category_names[$parent_category] = get_term($parent_category)->name;
			}	
		}
		
		$ajax_array = array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'page'=>'action_icons', 'directory_actions'=>get_option('directory_actions'), 'parent_category_names'=>$parent_category_names);

	} else {
		$ajax_array = array('ajax_url' => admin_url('admin-ajax.php'), 'page'=>'external_icons', 'directory_actions'=>get_option('external_icons'));
	}
	wp_enqueue_media();
	wp_enqueue_script('settings_ajax', plugin_dir_url(__FILE__) . 'js/settings_ajax.js', array('jquery'), '1.0', true);
	wp_localize_script('settings_ajax', 'ajax_object', $ajax_array);
	wp_enqueue_style('settings_styles', plugin_dir_url(__FILE__) . 'css/settings_styles.css');

	?>
	<div class="wrap">
		<h2>Community Impact Directory - Icon Types</h2>
		<div id="settings-saved" class="notice notice-success" style="position: relative;">
			<p>Settings saved.</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
		<form id="action_icon_form" method="post" action="">
			<div id="icon_management">
				<?php 
					settings_fields( $plugin_page );
					do_settings_sections( $plugin_page );
				?>
			</div>
			<label>Display Name: <input type="text" id="add_icon_type" name="add_icon_type"></label>
			<input type="button" id="add_icon_type_button" name="add_icon_type_button" value="Add Icon Type">
			<ul id="recover-icons"></ul>
			<?php submit_button(); ?>
		</form>
	</div>
<?php }


// Setup sections for all pages
function directory_setup_sections() {
	//Tags ID, section title, callback, menu slug
	add_settings_section( 'post_settings_section', 'Community Impact Directory', 'cid_settings_section_callback', 'cid_settings' );
	add_settings_section( 'action_icons_section', 'Action Icons', 'cid_settings_section_callback', 'cid_settings_action_icons');
	add_settings_section( 'external_icons_section', 'External Icons', 'cid_settings_section_callback', 'cid_settings_external_icons');
}
add_action('admin_init', 'directory_setup_sections');	

// Callback for section subheadings
function cid_settings_section_callback( $arguments ) {
	switch( $arguments['id'] ){
		case 'post_settings_section':
			echo '<h3>Setup the directory</h3>';
			break;
		case 'action_icons_section':
			echo '<h3>Setup action icons:</h3>';
			echo '<h4>WARNING: Deleting an icon will remove all associate post data</h4>';
			break;		
		case 'external_icons_section':
			echo '<h3>Setup external icons:</h3>';
			echo '<h4>WARNING: Deleting an icon will remove all associate post data</h4>';
			break;
	}
}

// Setup input fields for main page
function cid_setup_settings_fields() {
	//Community Impact Directory Section
	//Tag ID, Field Label, Callback, Menu Slug, Section, Arguments passed to callback
	add_settings_field( 'directory_post_type', 'Post-Type', 'directory_field_callback', 'cid_settings', 'post_settings_section', array('name' => 'directory_post_type') );
	add_settings_field( 'directory_taxonomy', 'Taxonomy', 'directory_field_callback', 'cid_settings', 'post_settings_section', array('name' => 'directory_taxonomy') );
	add_settings_field( 'directory_default_logo', 'Default Logo', 'directory_field_callback', 'cid_settings', 'post_settings_section', array('name' => 'directory_default_logo') );
	
	$action_icons = get_option('directory_actions');
	if(is_array($action_icons)){
		foreach($action_icons as $icon) {
			if(is_array($icon)){
				add_settings_field('action_icon_'.$icon['icon_id'], $icon['icon_name'].' Icon ('.$icon['icon_id'].')', 'icon_types_callback', 'cid_settings_action_icons', 'action_icons_section', $icon );
			}
		}
	}
	
	$external_icons = get_option('external_icons');
	if(is_array($external_icons)){
		foreach($external_icons as $icon) {
			if(is_array($icon)){
				add_settings_field('external_icon_'.$icon['icon_id'], $icon['icon_name'].' Icon ('.$icon['icon_id'].')', 'icon_types_callback', 'cid_settings_external_icons', 'external_icons_section', $icon );
			}
		}
	}
}
add_action('admin_init', 'cid_setup_settings_fields'); 

// Callback setting up the directory post types and categories
function directory_field_callback( $arguments ) {
	$selected = get_option( $arguments['name'] );
	if($arguments['name'] == 'directory_default_logo') {
		$selected_src = wp_get_attachment_image_url($selected);
		echo '<img id="directory_default_logo_img" src="'.$selected_src.'"><input type="hidden" value="'.$selected.'" name="directory_default_logo" id="directory_default_logo"><input class="select_image_button" type="button" value="Select Logo"><input class="clear_image_button" type="button" value="Clear Logo">';
	} else {
		echo '<select name="'.$arguments['name'].'" id="'.$arguments['name'].'">';
		$values;
		switch ($arguments['name']) {
			case 'directory_post_type':
				$values = get_post_types();
				break;
			case 'directory_taxonomy':
				$values = get_taxonomies();
				break;
		}
		foreach($values as $value) {
			echo '<option value="'.$value.'"';
			if($value == $selected) {
				echo ' selected';
			}
			echo '>'.$value.'</option>';
		}
		echo '</select>';
	}
}

// Callback setting up the icon types
function icon_types_callback ($icon) {
	?>
	<label class="position-label">Position: <input type="number" class="position_number" data-icon-id="<?php echo $icon['icon_id'] ?>"></label>
	<input class="change_position_button" type="button" value="Change Position" data-icon-id="<?php echo $icon['icon_id'] ?>">
	<img class="image_preview" src="<?php echo $icon['icon_src']; ?>" data-icon-id="<?php echo $icon['icon_id'] ?>">
	<input class="select_image_button" type="button" value="Select Icon" data-icon-id="<?php echo $icon['icon_id'] ?>">
	<input class="delete_icon_type_button" type="button" value="Delete Icon Type" data-icon-id="<?php echo $icon['icon_id'] ?>">
	<label class="edit-label">Display Name: <input type="text" class="edit_name_text" data-icon-id="<?php echo $icon['icon_id'] ?>"></label>
	<input class="edit_name_button" type="button" value="Edit Name" data-icon-id="<?php echo $icon['icon_id'] ?>">
	<?php if(isset($icon['icon_parents'])) { ?>
		<h4>Parent Categories</h4>
		<?php 
		$categories = get_terms(array(
			'taxonomy'=> get_option('directory_taxonomy'),
			'hide-empty'=>false
			));
			
		$parent_categories = get_parent_categories($categories);
		$icon_parents = $icon['icon_parents'];
		
		if(is_array($icon_parents) && is_array($parent_categories)) {
			foreach($parent_categories as $parent_category) {
				$checked = '';
				if(in_array($parent_category, $icon_parents)) {
					$checked = 'checked';
				}
				?>
				<label class="parent-category-label"><?php echo get_term($parent_category)->name ?> (<?php echo $parent_category ?>)
					<input class="parent-category-checkbox" type="checkbox" data-icon-id="<?php echo $icon['icon_id'] ?>" value="<?php echo $parent_category ?>" <?php echo $checked ?>>
				</label>
			<?php }
		}
	}
}
							
// Setup ajax for adding and removing icon types
function action_icon_update() {
	if($_POST['page'] == 'action_icons') {
		if(isset($_POST['action_options'])) {
			update_option('directory_actions', $_POST['action_options']);
		} else {
			update_option('directory_actions', array());
		}
		
		if(isset($_POST['delete_icons']) && is_array($_POST['delete_icons'])) {	
			foreach($_POST['deleted_icons'] as $icon_id) {
				delete_metadata('post', 1, 'action_icon_'.$icon_id, '', true);
			}
		}
	} else if($_POST['page'] == 'external_icons') {
		if(isset($_POST['action_options'])) {
			update_option('external_icons', $_POST['action_options']);
		} else {
			update_option('external_icons', array());
		}
		
		if(isset($_POST['delete_icons']) && is_array($_POST['delete_icons'])) {	
			foreach($_POST['deleted_icons'] as $icon_id) {
				delete_metadata('post', 1, 'external_icon_'.$icon_id, '', true);
			}
		}
	}
	wp_die();
}
add_action('wp_ajax_action_icon_update', 'action_icon_update');

//Add Plugin Page Settings Link
function directory_plugin_action_links($links) {
    $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=community_impact_directory_settings') ) .'">Settings</a>';
	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'directory_plugin_action_links');

// Register settings
register_setting('cid_settings', 'directory_post_type');
register_setting('cid_settings', 'directory_taxonomy');
register_setting('cid_settings', 'directory_default_logo');







	

	/*

// Action Icons
function action_icons_setup_function($atts) {
	$a = shortcode_atts( array(
		'directory_address' => ''
	), $atts );
	$action_icons = get_option('directory_actions');
	
	$output = '';
	
	if(is_array($action_icons)){
		$output .= '<ul class="action-icon-header">';
		foreach($action_icons as $icon) {
			if(is_array($icon)){
				$output .= '<li class="action-icon"><a href="'.$a['directory_address'].'?action='.$icon['icon_id'].'"><img src="'.$icon['icon_src'].'">'.$icon['icon_name'].'</a></li>';
			}
		}
		$output .= '</ul>';
	}
	
	wp_enqueue_style('action_icon_style', plugin_dir_url(__FILE__) . 'action_icon_style.css');	
	
	return $output;
}

add_shortcode('directory_action_icons', 'action_icons_setup_function');



*/

// -------------- Uninstall -----------------
//for ($icon_id = 0; $icon_id < 20; $icon_id++) {
//	delete_metadata('post', 1,'action_icon_'.$icon_id, '', true);
//}

//delete_option('directory_actions');
?>