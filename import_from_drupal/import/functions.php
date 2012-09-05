<?php

define('ZONE5_PATH', '/home/76633/domains/zone5.com/html/');
define('IMPORT_PATH', '/home/76633/domains/dev04.zone5hosting.com/html/import/');

function vardump($v, $label = '') {

	$bgcolors = array('fff', 'ccf', 'cfc', 'fcc', 'ffc', 'cff', 'fcf', 'ccc');
	shuffle($bgcolors);

	echo '<div style="border: 1px solid black; padding: 10px; margin: 10px; background-color: #' . end($bgcolors) . '">';
	if ($label) echo '<strong>' . $label . '</strong>';
	echo '<pre>'; var_dump($v); echo '</pre>';
	echo '</div>';
}


function get_nids($type) {
	$sql  = 'SELECT nid FROM node WHERE type = "' . $type . '" ';
	$sql .= 'AND status = 1 ';
	$sql .= 'ORDER BY nid ';
	
	$nids = array();
	$res  = db_query($sql);
	while ($row = db_fetch_array($res)) {
		$nids[] = $row['nid'];
	}
	
	return $nids;
}


function get_node($nid) {

	$url = 'http://www.zone5.com/export/get_node.php?nid=' . $nid;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$serialized = curl_exec($ch);
	curl_close($ch);
	
	return unserialize($serialized);

}


function set_post(&$node, $args) {
	// http://codex.wordpress.org/Function_Reference/wp_insert_post
		
	$post = array();
	
	$post['comment_status'] = 'closed';
	$post['ping_status'] = 'closed';
	$post['post_author'] = 1; // need to look this up? $node->name == "paulh"
	$post['post_category'] = array($args['category']);
	$post['post_content'] = $node->body;
	$post['post_date'] = date('Y-m-d H:i:s', $node->created);
	$post['post_status'] = 'publish';
	$post['post_title'] = $node->title;
	$post['post_type'] = 'post';
	
	/*
	["tags"]=>
  array(1) {
    [2]=>
    array(3) {
      [53]=>
      object(stdClass)#208 (6) {
        ["tid"]=>
        string(2) "53"
        ["vid"]=>
        string(1) "2"
        ["name"]=>
        string(8) "election"
        ["description"]=>
        string(0) ""
        ["weight"]=>
        string(1) "0"
        ["v_weight_unused"]=>
        string(1) "0"
      }
    */
    $tags = array();
    if (count($node->tags)) {
	    foreach ($node->tags as $vid => $terms) {
	    	foreach ($terms as $term) {
	    		$tags[] = $term->name;
	    	}
	    }
	}
	
	$post['tags_input'] = implode(',', $tags); // need to look this up! $node->tags == array()
	
	return $post;
}


function find_post_images($post_content) {

	$images = array();
	$pos = strpos($post_content, '<img');
	while ($pos !== false) {
#		echo $pos . ' ';
		$endpos = strpos($post_content, '>', $pos);
#		echo $endpos . ' ';
		$img = substr($post_content, $pos, $endpos - $pos + 1);
#		echo $img;
		$images[] = $img;
	
		$pos = strpos($post_content, '<img', $pos + 1);
	}
	
	return $images;

}


function images_to_migrate($images) {
	$mig = array();
	foreach ($images as $i) {
		// first, part out the src
		$src = get_img_src($i);
#		echo $src . ' ';
		$src = str_replace('http://www.zone5.com', '', $src);
		if (substr($src, 0, 6) == '/sites') {
			$mig[] = $src;
		}
	}
	return $mig;
}


function get_img_src($img_html) {
	$src = '';
	$pos = strpos($img_html, 'src=');
	if ($pos !== false) {
		$endpos = strpos($img_html, '"', $pos + 5);
		$src = substr($img_html, $pos + 5, $endpos - $pos - 5);
	}
	return $src;
}


function find_img_tag($imgfile, $html) {
	$img = '';

	$filepos = strpos($html, $imgfile);
#	vardump($filepos, 'filepos');
	if ($filepos !== false) {
		$imgpos = strrpos(substr($html, 0, $filepos), '<img');
#		vardump($imgpos, 'imgpos');
		if ($imgpos !== false) {
			$endpos = strpos($html, '>', $imgpos);
#			vardump($endpos, 'endpos');
			$img = substr($html, $imgpos, $endpos - $imgpos + 1);
		}
	}
	
#	vardump($img, 'img');
	return $img;
}


function copy_images($images) {

	$copied = array();

	$wp_upload_dir = wp_upload_dir();
#	vardump($wp_upload_dir); exit;

	foreach ($images as $i) {
		if (substr($i, 0, 1) == '/') $i = substr($i, 1);
		$oldimg = ZONE5_PATH . $i;
		$newimg = $wp_upload_dir['path'] . '/' . basename($i);
		echo 'copying ' . basename($i) . ' ';
		$ok = copy($oldimg, $newimg);
		if ($ok) {
			echo 'ok';
			$copied[] = $newimg;
		} else {
			echo 'FAILED';
		}
		echo '<br />';
	}
	return $copied;
}



function add_media($filename, $post_id) {

	$wp_filetype = wp_check_filetype(basename($filename), null );
	$wp_upload_dir = wp_upload_dir();
	$attachment = array(
		'guid' => $wp_upload_dir['baseurl'] . _wp_relative_upload_path( $filename ), 
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
	// you must first include the image.php file
	// for the function wp_generate_attachment_metadata() to work
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	
	return $attach_id;

}



?>