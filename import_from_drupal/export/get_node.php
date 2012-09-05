<?php

$nid = 0;
if (array_key_exists('nid', $_REQUEST)) {
	$nid = trim(strip_tags($_REQUEST['nid']));
}

// bootstrap drupal
chdir('/home/76633/domains/zone5.com/html');
require_once('/home/76633/domains/zone5.com/html/includes/bootstrap.inc');
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$node = node_load($nid);

$body = img_assist_filter('process', 0, -1, $node->body);
$body = str_replace('http://www.zone5.com/export', '', $body);
$node->body = $body;

echo serialize($node);

?>