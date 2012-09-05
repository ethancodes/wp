<?php


/*
	TODO
	
		phase 2 current breaks the categories
	
*/


$type = '';
if (array_key_exists('type', $_REQUEST)) {
	$type = trim(strip_tags($_REQUEST['type']));
}
if ($type == '') {
	echo 'no type';
	exit;
}

define('TYPE', $type);

if (TYPE == 'blog') {
	define('CATEGORY', 1);
} else if (TYPE == 'story') {
	define('CATEGORY', 4);
}

if (!defined('CATEGORY')) {
	echo 'no category<br />invalid type?';
	exit;
}

ini_set('log_errors', 1);
ini_set('error_log','/home/76633/logs/error_log');
error_reporting(E_ALL);

require_once 'functions.php';


$phase = 0;
if (array_key_exists('phase', $_REQUEST)) {
	$phase = trim(strip_tags($_REQUEST['phase']));
}

if ($phase == 0) {
	// fetch a list of node ids from Drupal, store them in a file

	require_once 'boot_drupal.php';
	$nids = get_nids($type);
	file_put_contents(IMPORT_PATH . TYPE . '_nids.txt', implode(' ', $nids));
	echo 'NIDS written to ' . IMPORT_PATH . TYPE . '_nids.txt';
	exit;

} else if ($phase == 1) {
	// use the file from phase 0, import those nodes into wp

	if (!file_exists(IMPORT_PATH . TYPE . '_nids.txt')) {
		echo 'no ' . IMPORT_PATH . TYPE . '_nids.txt<br />';
		echo 'run phase 0 first?';
		exit;
	}

	require_once 'boot_wp.php';
	$nids = explode(" ", file_get_contents(IMPORT_PATH . TYPE . '_nids.txt'));

	foreach ($nids as $nid) {
		
		$node = get_node($nid);
		
		if ($node === false) {
			echo '<p>COULD NOT GET NID#' . $nid . '</p>';
			continue;
		}
		
		/*	alright, so
			we need to create a new post
				title
				body
				date
				category
				tags, if any
			pull images
				add the to media library
			update img code in body
			save
		*/
		
		$args = array('category' => CATEGORY);
		$post = set_post($node, $args);
#		vardump($post); exit;
		wp_insert_post($post);
		
		echo '<p>imported nid#' . $nid . ' ' . $node->title . '</p>';
#		exit;
			
	}
	
} else if ($phase == 2) {
	// wade through Posts looking for images
	// if those images are on the old server, bring them over
	
	require_once 'boot_wp.php';
	$args = array(
		'category' => CATEGORY,
		'numberposts' => -1
	);
	$posts = get_posts($args);
#	vardump($posts);

	$check = 0;

	
	foreach ($posts as $p) {
	
#		$p->post_category = array(CATEGORY);	
#		vardump($p);
#		continue;
	
#		if ($check > 5) exit;
	
		echo '<h2>' . $p->post_title . '</h2>';

		$post_images = find_post_images($p->post_content);
		echo count($post_images) . ' images<br />';
		
		if (count($post_images) == 0) {
			echo '<br />';
			continue;
		}

		// let's also get rid of all of those stupid <span> tags
		$post_content = preg_replace('#</?span[^>]*>#is', '', $p->post_content);
#		vardump(htmlentities($post_content));
#		exit;
#		continue;

		
		$mig_images = images_to_migrate($post_images);
		echo count($mig_images) . ' images to migrate<br />';
		if (count($mig_images) == 0) {
			echo '<br />';
			continue;
		}
#		vardump($mig_images);
#		exit;

		$copied = copy_images($mig_images);
		echo count($copied) . ' images copied<br />';
		if (count($copied) == 0) {
			echo '<br />';
			continue;
		}
		
		foreach ($copied as $copied_image) {
			echo 'replacing HTML for ' . basename($copied_image) . '<br />';
		
			$attach_id = add_media($copied_image, $p->ID);
#			vardump($attach_id, 'add media');
				
			// now replace the old image code with the new image code
			// no idea how i'm going to do that
						
			// alright, find the <img> tag for this image
			$imgfile = basename($copied_image);
			$oldimghtml = find_img_tag($imgfile, $post_content);
#			vardump($oldimghtml, 'old');
			
			// replace that with our new html
			$newimg = wp_get_attachment_image_src($attach_id);
#			vardump($newimg, 'wp_get_attachment_image_src');
			$newimghtml = '<img class="alignleft" alt="" src="' . $newimg[0] . '" width="' . $newimg[1] . '" height="' . $newimg[2] . '">';
#			vardump($newimghtml, 'new');
			$post_content = str_replace($oldimghtml, $newimghtml, $post_content);
			
#			vardump(htmlentities($post_content));
				
		}
		
		echo 'Saving... ';
		$p->post_content = $post_content;
		$p->post_category = array(CATEGORY);
		vardump($p);

		wp_insert_post($p);
		$check++;
			
#		exit;

		echo '<br />';
	}


}

?>