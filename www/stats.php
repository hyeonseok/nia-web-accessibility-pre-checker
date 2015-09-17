<?php
require_once('functions.php');

$app = new Wast();

$file_list = $app->get_file_list();
$eval = $file_list['eval'];
$tags = $file_list['tags'];

$eval_count = array();
foreach($eval as $item) {
	$count = count(file('logs/' . $item));
	$eval_count[$item] = $count;
}

print_r($eval_count);
?>
