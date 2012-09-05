<?php

define('CATEGORY', 1);
define('TYPE', 'blog');

ini_set('log_errors', 1);
ini_set('error_log','/home/76633/logs/error_log');
error_reporting(E_ALL);

require_once 'functions.php';


$phase = 0;
if (array_key_exists('phase', $_REQUEST)) {
	$phase = trim(strip_tags($_REQUEST['phase']));
}

if ($phase == 0) {

	phase_0(TYPE);	
	exit;

} else if ($phase == 1) {

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
				category (News)
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
	
}

?>