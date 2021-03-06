<?php
class Wast {
	var $file_path;

	function __construct() {
		$this->file_path = dirname(__FILE__) . '/logs/';
	}

	function get_file_list() {
		$files = scandir($this->file_path);

		$eval = array();
		$tags = array();

		foreach ($files as $file) {
			if (strpos($file, 'eval_') !== false) {
				$eval[] = $file;
			} else if (strpos($file, 'tags_') !== false) {
				$tags[] = $file;
			}
		}

		rsort($eval);
		rsort($tags);

		return array(
			'eval' => $eval, 
			'tags' => $tags
		);
	}

	function remove_duplicate_and_sum_by_host_in_week($items) {
		$time = time();
		rsort($items);
		$urls = array();
		$result = array();
		foreach ($items as $item) {
			$explode = explode("\t", trim($item));
			if ($explode[0] + 60 * 60 * 24 * 7 < $time || $explode[4] == 'error' || in_array($explode[3], $urls)) {
				continue;
			}
			$urls[] = $explode[3];
			$parse_url = parse_url($explode[3]);
			$host = $parse_url['host'];
			if (!isset($result[$host])) {
				$result[$host] = array(
					'time' => $explode[0],
					'scheme' => $parse_url['scheme'], 
					'port' => $parse_url['port'], 
					'image_count' => $explode[4], 
					'image_pass' => $explode[5], 
					'title_count' => $explode[6], 
					'title_pass' => $explode[7], 
					'lang_count' => $explode[8], 
					'lang_pass' => $explode[9], 
					'label_count' => $explode[10], 
					'label_pass' => $explode[11], 
					'url_count' => 1, 
				);
			} else {
				$result[$host] = array(
					'time' => $result[$host]['time'],
					'scheme' => $parse_url['scheme'], 
					'port' => $parse_url['port'], 
					'image_count' => $result[$host]['image_count'] + $explode[4], 
					'image_pass' => $result[$host]['image_pass'] + $explode[5], 
					'title_count' => $result[$host]['title_count'] + $explode[6], 
					'title_pass' => $result[$host]['title_pass'] + $explode[7], 
					'lang_count' => $result[$host]['lang_count'] + $explode[8], 
					'lang_pass' => $result[$host]['lang_pass'] + $explode[9], 
					'label_count' => $result[$host]['label_count'] + $explode[10], 
					'label_pass' => $result[$host]['label_pass'] + $explode[11], 
					'url_count' => $result[$host]['url_count'] + 1, 
				);
			}
		}
		return $result;
	}

	function get_recent_sites() {
		$file_list = $this->get_file_list();
		$items = array();
		for ($i = 0; $i < 8; $i++) {
			$items = array_merge($items, file($this->file_path . $file_list['eval'][$i]));
		}
		$data = $this->remove_duplicate_and_sum_by_host_in_week($items);
		return $data;
	}
}

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

function logs_tag($tags) {
	$time = time();
	$filename = 'logs/' . 'tags_' . date('Ymd', $time) . '.tsv';
	$fp = fopen($filename, 'a');
	$tsv = '';
	foreach ($tags as $item) {
		$tsv .= "\t" . $item;
	}
	fwrite($fp, $time . $tsv . "\n");
	fclose($fp);

	return true;
}

function remove_old_dump_file() {
	$files = scandir('logs/');
	$today = date('Ymd', time());
	foreach ($files as $file) {
		if (strpos($file, 'dump_') !== false) {
			$file_date = substr($file, 5, 8);
			if ($file_date < $today - 7) {
				unlink('logs/' . $file);
			}
		}
	}
}

function logs_dump($url, $html) {
	$time = time();
	$filename = 'logs/' . 'dump_' . date('YmdH', $time) . '.tsv';
	$fp = fopen($filename, 'a');
	$tsv = $url . "\t" . $html . "\n";
	fwrite($fp, $tsv);
	fclose($fp);

	remove_old_dump_file();

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

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($curl, CURLOPT_USERAGENT, 'NIA Web Accessibility Pre-Checker');
	$str = curl_exec($curl);
	curl_close($curl);

	$html = str_get_html($str);
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

	logs_tag(array(
		$url,
		count($html -> find('a')),
		count($html -> find('abbr')),
		count($html -> find('acronym')),
		count($html -> find('address')),
		count($html -> find('applet')),
		count($html -> find('area')),
		count($html -> find('b')),
		count($html -> find('base')),
		count($html -> find('basefont')),
		count($html -> find('bdo')),
		count($html -> find('big')),
		count($html -> find('blockquote')),
		count($html -> find('body')),
		count($html -> find('br')),
		count($html -> find('button')),
		count($html -> find('caption')),
		count($html -> find('center')),
		count($html -> find('cite')),
		count($html -> find('code')),
		count($html -> find('col')),
		count($html -> find('colgroup')),
		count($html -> find('dd')),
		count($html -> find('del')),
		count($html -> find('dfn')),
		count($html -> find('dir')),
		count($html -> find('div')),
		count($html -> find('dl')),
		count($html -> find('dt')),
		count($html -> find('em')),
		count($html -> find('fieldset')),
		count($html -> find('font')),
		count($html -> find('form')),
		count($html -> find('frame')),
		count($html -> find('frameset')),
		count($html -> find('h1')),
		count($html -> find('h2')),
		count($html -> find('h3')),
		count($html -> find('h4')),
		count($html -> find('h5')),
		count($html -> find('h6')),
		count($html -> find('head')),
		count($html -> find('hr')),
		count($html -> find('html')),
		count($html -> find('i')),
		count($html -> find('iframe')),
		count($html -> find('img')),
		count($html -> find('input')),
		count($html -> find('ins')),
		count($html -> find('isindex')),
		count($html -> find('kbd')),
		count($html -> find('label')),
		count($html -> find('legend')),
		count($html -> find('li')),
		count($html -> find('link')),
		count($html -> find('map')),
		count($html -> find('menu')),
		count($html -> find('meta')),
		count($html -> find('noframes')),
		count($html -> find('noscript')),
		count($html -> find('object')),
		count($html -> find('ol')),
		count($html -> find('optgroup')),
		count($html -> find('option')),
		count($html -> find('p')),
		count($html -> find('param')),
		count($html -> find('pre')),
		count($html -> find('q')),
		count($html -> find('s')),
		count($html -> find('samp')),
		count($html -> find('script')),
		count($html -> find('select')),
		count($html -> find('small')),
		count($html -> find('span')),
		count($html -> find('strike')),
		count($html -> find('strong')),
		count($html -> find('style')),
		count($html -> find('sub')),
		count($html -> find('sup')),
		count($html -> find('table')),
		count($html -> find('tbody')),
		count($html -> find('td')),
		count($html -> find('textarea')),
		count($html -> find('tfoot')),
		count($html -> find('th')),
		count($html -> find('thead')),
		count($html -> find('title')),
		count($html -> find('tr')),
		count($html -> find('tt')),
		count($html -> find('u')),
		count($html -> find('ul')),
		count($html -> find('var')),
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
