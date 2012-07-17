<?php
/*
1.

c:bluemotion hyeonseok$ curl http://www.gnjje.go.kr/main.do
<html><script lang=javascript>
document.cookie = '_accessKey2=oEoLvvPnbtJRhgsACzauGfdIxFyz-Aez'
window.location.reload();
</script></html>

쿠키, 자바스크립트를 사용할 수 있어야만 접근할 수 있는 사이트 처리?

2.


*/

if ($_SERVER['SERVER_NAME'] == 'localhost') {
	ini_set('display_errors', 16);
	error_reporting(16);
} else {
	ini_set('display_errors', 1);
	error_reporting(1);
}
ini_set('user_agent', 'NIA Web Accessibility Pre-Checker');
ini_set('max_execution_time', '15');
ini_set('allow_url_fopen', 'On');
date_default_timezone_set('Asia/Seoul');
header('Content-type: text/html; charset=utf-8');

include('simplehtmldom_1_5/simple_html_dom.php');
include('functions.php');

$report_message = array(
	'img' => '대체텍스트 제공 여부', 
	'title' => '제목 제공 여부', 
	'lang' => '페이지 언어 제공 여부', 
	'label' => '서식 레이블 제공 여부', 
);

/*
$_POST['url'] = <<<sampleurl
http://www.naver.com/
http://www.daum.net/
http://www.paran.com/
http://www.nate.com/
http://www.aa.com/
http://www.yahoo.com/
http://bing.search.daum.net/search?q=bing
http://www.apple.com/
http://www.microsoft.com/
http://www.hp.com/
sampleurl;
//*/

if (isset($_POST['url'])) {
	$urls = $_POST['url'];
	$results = array();
	$reports = array();
	$url_count = 0;

	$url_items = explode("\n", $urls);
	foreach ($url_items as $url) {
		if ($url_count > 9) {
			break;
		}
		if (strlen(trim($url)) < 1) {
			continue;
		}
		if (strpos($url, '://') === false) {
			$url = 'http://' . $url;
		}
		$results[trim($url)] = get_result(trim($url));
		$url_count++;
	}
	foreach ($results as $result) {
		if (!$result) {
			if (!isset($reports['nocontent'])) {
				$reports['nocontent'] = 0;
			}
			$reports['nocontent']++;
			continue;
		}
		foreach ($result as $check_type => $data) {
			if (!isset($reports[$check_type]['total'])) {
				$reports[$check_type]['total'] = 0;
			}
			if (!isset($reports[$check_type]['pass'])) {
				$reports[$check_type]['pass'] = 0;
			}
			/*if (!isset($reports[$check_type]['err'])) {
				$reports[$check_type]['err'] = array();
			}*/
			$reports[$check_type]['total'] += $data['total'];
			$reports[$check_type]['pass'] += $data['pass'];
			/*if (count($data['err']) > 0) {
				$reports[$check_type]['err'] = array_merge($reports[$check_type]['err'], $data['err']);
			}*/
		}
	}
	$total_pages = count($results);
} else {
	$urls = false;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>웹 접근성 자가진단 서비스</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!--[if lt IE 9]>
<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
<style type="text/css">
body {
	font-family: "맑은 고딕", "Malgun Gothic", "돋움", "Dotum", sans-serif;
}
header {
	border-bottom: 3px solid #eee;
}
h1, 
h2, 
h3, 
h4, 
h5, 
h6  {
	margin: 1em 0 0.5em;
}
div > section:first-child h1 {
	margin-top: 0;
}
.well textarea {
	width: 100%;
	box-sizing: border-box;
}
.well p:last-child {
	margin-bottom: 0;
}
.fail {
	color: #d00;
}
code {
	font-size: 0.8em;
}
.warning {
	color: #c00;
}
strong {
	color: #00c;
}
</style>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-195942-10']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</head>
<body>
<!-- div class="navbar">
	<div class="navbar-inner">
		<div class="container-fluid">
			<a class="brand" href="./check.php">.</a>
		</div>
	</div>
</div -->
<div class="container-fluid">
	<div class="row-fluid">
		<div class="span12">
			<header>
				<h1><img src="nia.jpg" alt="NIA" width="100"> 웹 접근성 품질마크 자가진단 서비스</h1>
			</header>
			<section>
				<h2>진단 대상 페이지</h2>
				<p>검사할 페이지의 URL을 엔터로 구분해서 입력해 주세요.</p>
				<form action="check.php" method="post" class="well">
					<p><label>URLs: <textarea name="url" rows="10" cols="80"><?php echo($urls); ?></textarea></label></p>
					<p id="line-count"></p>
					<p><input type="submit" value="검사" class="btn" id="submit"></p>
				</form>
			</section>

			<?php if (isset($total_pages) && $total_pages > 0) { ?>
			<section>
				<h2>검사 결과</h2>
				<p><?php echo(date('Y년 m월 d일, ', time())); ?><?php echo($total_pages); ?>페이지의 검사대상 페이지에서, </p>
				<?php
				//print_r($reports);
				$is_fail = false;
				$is_first = true;
				$has_data = false;
				foreach ($reports as $key => $report) {
					if ($key == 'nocontent') {
						continue;
					}
					if ($is_first) {
						echo('<ul>');
						$is_first = false;
						$has_data = true;
					}
					if ($report['pass'] / $report['total'] < 0.95 && $report['total'] != 0) {
						$css_class = ' class="fail"';
						$is_fail = true;
					} else {
						$css_class = '';
					}
					echo('<li' . $css_class . '>' . ((isset($report_message[$key])) ? $report_message[$key] : $key) . ': ' . ceil(($report['total'] > 0 ? $report['pass'] / $report['total'] : 1) * 10000) / 100 . '%</li>');
				}
				if ($has_data) {
					echo('</ul>');
					echo('<p>' . (($is_fail) ? '95% 만족도를 달성하지 못했습니다.' : '95% 만족도를 달성하였습니다.') . '</p>');
				}
				if (isset($reports['nocontent']) && $reports['nocontent'] > 0) {
					echo('<p>' . ($total_pages - $reports['nocontent']) . '페이지를 검사하였고 ' . $reports['nocontent'] . '페이지는 검사를 하지 못하였습니다.</p>');
				}
				?>
			</section>

			<section>
				<h2>상세 결과</h2>
				<?php
				function print_result_item($arr) {
					echo('<td>');
					if (isset($arr['total']) && isset($arr['pass'])) {
						echo($arr['pass'] . '/' . $arr['total']);
					} else {
						echo('Unable to test.');
					}
					if (count($arr['err']) > 0) {
						echo('<ol>');
						foreach ($arr['err'] as $item) {
							echo('<li><code>' . htmlspecialchars($item) . '</code></li>');
						}
						echo('</ol>');
					}
					echo('</td>');
				}
				echo('<table class="table table-bordered">' . "\n");
				echo('<thead><tr><th>URL</th><th>이미지</th><th>제목</th><th>언어</th><th>레이블</th></tr></thead>' . "\n");
				echo('<tbody>');
				foreach ($results as $url => $result) {
					echo('<tr>');
					echo('<th><a href="' . $url . '">' . ((strlen($url) > 50) ? substr($url, 0, 48) . '..' : $url) . '</a></th>');
					print_result_item($result['img']);
					print_result_item($result['title']);
					print_result_item($result['lang']);
					print_result_item($result['label']);
				}
				echo('</tbody>');
				echo('</table>');
				//echo('<pre>'); print_r($results); echo('</pre>');
				?>
			</section>
			<?php } ?>

			<section>
				<h2>주의사항</h2>
				<p>자가진단에 해당하는 4개항목의 준수율이 95%이상인 경우 접수</p>
				<p>※ 대체 텍스트 제공, 제목 제공, 기본 언어 표시, 레이블 제공의 4개 항 목으로 각 항목별 준수율 95% 이상일 경우만 접수 가능</p>

				<h2>이 도구의 한계</h2>
				<ul>
					<li>자바스크립트나 CSS를 이용하여 브라우저에서 동적으로 생성한 콘텐츠는 검사할 수 없습니다.</li>
					<li>로그인과 같이 접속 권한이나 쿠키가 필요한 페이지는 검사할 수 없습니다.</li>
					<li>너무 많은 페이지를 넣을 경우 작동이 중간에 중단 될 수 있습니다.</li>
					<li>속성이름과 속성값 사이에 공백이 없는 등 (&lt;img src="img.png<strong>"a</strong>lt="" /&gt;) HTML 구문에 오류가 있는 경우 오류로 검사됩니다.</li>
				</ul>
			</section>
		</div>
	</div>
</div>
<script type="text/javascript">
var el = document.getElementsByTagName('textarea')[0];
var placeholder = document.getElementById('line-count');
var submitButton = document.getElementById('submit');
el.onkeyup = function (e) {
	var val = this.value;
	var items = val.split("\n");
	var lines = 0;
	for (var i = 0, cnt = items.length; i < cnt; i++) {
		if (items[i].replace(/^\s\s*/, '').replace(/\s\s*$/, '').length > 0) {
			lines++;
		}
	}
	if (lines < 11) {
		placeholder.innerHTML = 'URL' + lines + '개 입력';
		placeholder.className = '';
		submitButton.disabled = false;
	} else {
		placeholder.innerHTML = 'URL' + lines + '개 입력, URL은 10개까지만 입력 가능합니다.';
		placeholder.className = 'warning';
		submitButton.disabled = true;
	}
}
el.onkeyup();
</script>
</body>
</html>
