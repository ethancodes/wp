<?php
/*
Plugin Name: Team
Description: Team Member Profiles
Version: 0.3
Author: Zone 5
Author URI: http://www.zone5.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


add_action( 'init', 'team_create_post_type' );
function team_create_post_type() {
	register_post_type( 'team_member',
		array(
			'labels' => array(
				'name' => 'Team Members',
				'singular_name' => 'Team Member',
				'add_new_item' => 'Add New Team Member',
				'edit_item' => 'Edit Team Member',
				'new_item' => 'New Team Member',
				'view_item' => 'View Team Member'
			),
			'public' => true,
			'has_archive' => true,
#			'rewrite' => array('slug' => 'team-members')
			'supports' => array( 'title', 'editor', 'thumbnail' )
		)
	);
	$args = array(  
        'hierarchical' => false,
        'label' => 'Team Member Categories',
        'query_var' => true,  
#        'rewrite' => true,
        'labels' => array(
        	'separate_items_with_commas' => 'Separate categories with commas',
        	'add_or_remove_items' => 'Add or remove categories',
        	'choose_from_most_used' => 'Choose from the most used categories'
        )
    );
	register_taxonomy('view_team_members', 'team_member', $args);
}


add_action( 'add_meta_boxes', 'team_social_box' );
function team_social_box() {
	add_meta_box( 
        'team_social_box',
        'Social Networks',
        'team_social_box_form',
        'team_member' 
    );
}
function team_social_box_form($p) {
	
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'team_noncename' );
	
	$networks = team_get_social_networks();
	
	echo '<table cellspacing="0" cellpadding="0" border="0"><tbody>';
	
	foreach ($networks as $n) {
		echo '<tr><td style="text-align: right; padding: 5px;">';
		echo '<label for="team_social_' . $n['abbr'] . '">' . $n['title'] . '</label> ';
		echo '</td><td style="padding: 5px;">';
		echo '<input type="text" id="team_social_' . $n['abbr'] . '" name="team_social_' . $n['abbr'] . '" ';
		echo 'value="' . get_post_meta($p->ID, 'team_social_' . $n['abbr'], true) . '" ';
		echo 'size="75" />';
		echo '</td></tr>';
	}
	
	echo '</tbody></table>';
		
}

add_action( 'save_post', 'team_save_postdata' );
function team_save_postdata( $post_id ) {
	// verify if this is an auto save routine. 
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['team_noncename'], plugin_basename( __FILE__ ) ) )
		return;

  
	// Check permissions
	if ( !current_user_can( 'edit_post', $post_id ) )
    	return;

	// OK, we're authenticated: we need to find and save the data
	
	$networks = team_get_social_networks();
	foreach ($networks as $n) {
		$v = $_POST['team_social_' . $n['abbr']];
		update_post_meta($post_id, 'team_social_' . $n['abbr'], $v);
	}

}






function team_get_social_networks() {
	$networks = array();
	$networks[] = array(
			'title' => 'Facebook',
			'abbr' => 'fb'
	);
	$networks[] = array(
			'title' => 'Twitter',
			'abbr' => 'tw'
	);
	$networks[] = array(
			'title' => 'LinkedIn',
			'abbr' => 'in'
	);
	$networks[] = array(
			'title' => 'Delicious',
			'abbr' => 'de'
	);
	$networks[] = array(
			'title' => 'Flickr',
			'abbr' => 'fl'
	);
	$networks[] = array(
			'title' => 'StumbleUpon',
			'abbr' => 'su'
	);
	$networks[] = array(
			'title' => 'Quora',
			'abbr' => 'qu'
	);
	$networks[] = array(
			'title' => 'Google+',
			'abbr' => 'gp'
	);
	$networks[] = array(
			'title' => 'Focus',
			'abbr' => 'fo'
	);
	$networks[] = array(
			'title' => 'Foursquare',
			'abbr' => 'fs'
	);
	$networks[] = array(
			'title' => 'Digg',
			'abbr' => 'dg'
	);
	$networks[] = array(
			'title' => 'YouTube',
			'abbr' => 'yt'
	);

	return $networks;
}


?>