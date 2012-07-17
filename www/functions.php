<?php
function has_parent($element, $parent) {
	if ($element -> parent() && $element -> parent() -> tag == $parent) {
		return true;
	} else if ($element -> parent()) {
		return has_parent($element -> parent(), $parent);
	} else {
		return false;
	}
}

function logs($log) {
	$time = time();
	$filename = 'logs/' . 'eval_' . date('Ymd', $time) . '.tsv';
	$fp = fopen($filename, 'a');
	$tsv = '';
	foreach ($log as $item) {
		$tsv .= "\t" . $item;
	}
	fwrite($fp, $time . "\t" . $_SERVER['REMOTE_ADDR'] . "\t" . $_SERVER['HTTP_USER_AGENT'] . $tsv . "\n");
	fclose($fp);

	return true;
}

function logs_dump($url, $html) {
	$time = time();
	$filename = 'logs/' . 'dump_' . date('YmdH', $time) . '.tsv';
	$fp = fopen($filename, 'a');
	$tsv = $url . "\t" . $html . "\n";
	fwrite($fp, $tsv);
	fclose($fp);

	return true;
}

function get_result($url) {
	$img_fail = $img_pass = 0;
	$img_fail_msg = array();
	$title_fail = $title_pass = 0;
	$title_fail_msg = array();
	$lang_fail = $lang_pass = 0;
	$lang_fail_msg = array();
	$label_fail = $label_pass = 0;
	$label_fail_msg = array();

	$html = file_get_html($url);
	if (!$html) {
		logs(array($url, 'error'));
		return false;
	}
	logs_dump($url, $html);

	// 대체텍스트
	$images = $html -> find('img, area, input[type=image]');
	foreach ($images as $img) {
		if (!isset($img -> alt)) {
			$img_fail++;
			$img_fail_msg[] = $img -> outertext;
		} else {
			$img_pass++;
			//echo('PASS' . "\n");	//alt exists
		}
	}
	
	// 페이지 제목
	$titles = $html -> find('title');
	if (count($titles) < 1) {
		$title_fail++;
		$title_fail_msg[] = $title -> outertext;
	} else {
		$title_pass++;
		//echo('PASS, ' . count($titles[0] -> find('text')) . "\n");	//title exists
	}
	
	// 프레임 제목
	$frames = $html -> find('frame, iframe');
	foreach ($frames as $frame) {
		if (!isset($frame -> title) || strlen($frame -> title) < 1) {
			$title_fail++;
			$title_fail_msg[] = $frame -> outertext;
		} else {
			$title_pass++;
			//echo('PASS, ' . $frame -> title . "\n");	//frame title exists
		}
	}
	
	// 기본 언어
	$langs = $html -> find('html');
	foreach ($langs as $lang) {
		if (!isset($lang -> lang) && !($lang -> hasAttribute('xml:lang'))) {
			$lang_fail++;
			//$lang_fail_msg[] = 'no lang attr';
		} else {
			$lang_pass++;
		}
	}
	
	// 레이블
	$inputs = $html -> find('input[type=text], input[type=password], input[type=checkbox], input[type=radio], select, textarea');
	foreach ($inputs as $input) {
		if (
			(isset($input -> id) && count($html -> find('label[for=' . $input -> id . ']')) == 1)
			|| (isset($input -> id) && count($html -> find('label[for=' . $input -> id . ']')) < 1 && $input -> hasAttribute('title'))
			|| (strlen($input -> getAttribute('title')) > 0)
			|| (has_parent($input, 'label'))
		) {
			$label_pass++;
			//echo('PASS' . "\n");
		} else {
			$label_fail++;
			$label_fail_msg[] = $input -> outertext;
		}
	}

	logs(array(
		$url, 
		$img_fail + $img_pass,
		$img_pass,
		$title_fail + $title_pass,
		$title_pass,
		$lang_fail + $lang_pass,
		$lang_pass,
		$label_fail + $label_pass,
		$label_pass,
	));

	$html->clear(); 
	unset($html);

	return array(
		'img' => array('total' => $img_fail + $img_pass, 'pass' => $img_pass, 'err' => $img_fail_msg), 
		'title' => array('total' => $title_fail + $title_pass, 'pass' => $title_pass, 'err' => $title_fail_msg), 
		'lang' => array('total' => $lang_fail + $lang_pass, 'pass' => $lang_pass, 'err' => $lang_fail_msg), 
		'label' => array('total' => $label_fail + $label_pass, 'pass' => $label_pass, 'err' => $label_fail_msg), 
	);
}
?>
