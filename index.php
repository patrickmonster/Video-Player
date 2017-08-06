<?
// i = disk   v = vido name  d = dirs 
require_once '../lib/h.php';

function file_size($size) { 
	$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB"); 
	if ($size == 0) {
		return('n/a'); 
	} else {
		return (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i]); 
	}
}

error_reporting(E_ALL);
ini_set("display_errors", 1);

//디스크 정보 취득
$i = isset($_GET['i'])?h($_GET['i']):1;
//디렉토리 정보 취득
$d = isset($_GET['d'])?h($_GET['d']):'';
if($d==='/')$d='';
//비디오 정보 취득
$v = isset($_GET['v'])?h($_GET['v']):'';
//JSON 뷰모드
$j= isset($_GET['j']);

$dir ='/mnt/HDD'.$i;//기본 루트
$dir.=$d;//기본루트 + 원하는 루트
//html 요청
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
	!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
	strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if (IS_AJAX){
	//루트 경로
	switch($i){
		case 1:
			$root=array("Ani");
			break;
		case 2:
			$root=array("Ani","Drama","Move");
			break;
		define:
			die("항목이 존재하지 않음");
			break;
	}

	$rootcount = isset($_GET["d"])?substr_count(h($_GET["d"]), "/"):0;
	if(isset($_GET['d']) && strlen(h($_GET['d']))>0)$rootcount++;
	$files = array();
	$count = 0;

	/*
	if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest'){
		die("Back to page...");
		exit;
	}*/

	if (mb_strpos($dir, '.')||!is_dir($dir)){
		die('No sourch dirs...'.$dir);
		exit;
	}

	header("Contnet-Type: application/json; charset=UTF-8");
	header("X-Contnet-Type-Options: mosniff");

	$dirs = scandir($dir);

	$point= strpos($dir, '/', 16);
	$str = $point?substr($dir,$point):'';
	if($d===''){
		foreach($root as $filename){
			$files[] = array('type'=>'dir','name' => $filename,'host'=>$_SERVER["HTTP_HOST"], 
				'dir'=>'/', 'disk' => $i ,'size'=>'0Bytes','up' => '');
			$count ++;
		}
	}else{
		$rootdir=strrpos($d,'/');
		foreach($dirs as $filename){
			if($filename == "." || $filename == ".." || mb_strpos($filename,'.db')|| mb_strpos($filename,'.smi')){
				continue;
			}
			$files[] = array('type'=>is_dir($dir.'/'.$filename)?'dir':'file' ,'name' => $filename,'point' => $count,
				'host' => $_SERVER["HTTP_HOST"], 'dir'  => $str.'/', 'disk' => $i ,'size'=>file_size(filesize($dir.'/'. $filename)),
				'up' => $d===''?false:substr($d, 0,$rootdir),'root' => $rootcount);
			$count ++;
		}
	}

	// 정렬, 역순으로 정렬하려면 rsort 사용
	//sort($files);
	echo json_encode($files,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}else{//HTTP
	if(isset($_GET['v'])){//동영상 뷰일경우
		if($j){
      //is view http request
		}
		set_time_limit(0);
		$file ='/mnt/HDD'.$i.'/Media' . $d.$v;
		ob_clean();
		@ini_set('error_reporting', E_ALL & ~ E_NOTICE);
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 'Off');
		if(!is_file($file))
			die('No sourch file...');
		$mime = "application/octet-stream";
		$size = filesize($file);
		header('Content-type: ' . $mime);
		if(isset($_SERVER['HTTP_RANGE'])){
			$ranges = array_map('intval',explode('-',substr($_SERVER['HTTP_RANGE'], 6)));
			if(!$ranges[1]){
				$ranges[1] = $size - 1;
			}
			header('HTTP/1.1 206 Partial Content');
			header('Accept-Ranges: bytes');
			header('Content-Length: ' . $size);
			header(
				sprintf(
					'Content-Range: bytes %d-%d/%d',
					$ranges[0], // The start range
					$ranges[1], // The end range
					$size // Total size of the file
				)
			);
			$f = fopen($file, 'rb');
			$chunkSize = 8192;
			fseek($f, $ranges[0]);
			while(true){
				if(ftell($f) >= $ranges[1]){
					break;
				}
				echo fread($f, $chunkSize);
				@ob_flush();
				flush();
			}
		}else {
			header('Content-Length: ' . $size);
			@readfile($file);
			@ob_flush();
			flush();
		}
	}else{
	?>
<!DOCTYPE html>
<html>
  <head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<meta name="robots" content="noarchive, nofollow, nosnippet" />
	<link rel="shortcut icon" type="image/png" href="http://549.ipdisk.co.kr/Image/icons/favicon.png">
	<link rel="icon" type="image/png" href="http://549.ipdisk.co.kr/Image/icons/favicon.png">

    <title>Full player example</title>
    <!-- Uncomment the following meta tag if you have issues rendering this page on an intranet or local site. -->    
    <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge"/> -->
	
	<script src="http://549.ipdisk.co.kr/js/jquery-3.2.1.min.js"></script>
    <script type="text/javascript">
function init() {
	var video = document.getElementById("Video1");                      
	var list = {};
	var root='';
	if (video.canPlayType) {
		function getVideo(fileURL) {    
			console.log(fileURL);
			if (fileURL != "") {
				video.src = fileURL;
				video.load();
				video.play();
				//document.getElementById("play").click();
			} else {
				errMessage("Enter a valid video URL");
			}
		}
		
		function load(dir) {
			var l=[];
			if(dir)dir='&d='+dir;else dir='';
			//console.log("<?=basename($_SERVER['PHP_SELF'])?>"+dir);
			$.ajax({
				url:'?i=<?=$i?>' + dir,
				type:'post',
				dataType:'json',
				async:false,
				success:function(e,i,d) {
					for(var i in e){
						d=e[i];
						var a={};
						a.dir=d.dir;
						root = d.up;
						a.name=d.name;
						a.isFile=(d.type==='file')
						l.push(a);
					}
				},error:function(e) {
          //error to video player
				}
			});
			return l;
		}

		function errMessage(msg) {
			document.getElementById("errorMsg").textContent = msg + "";
			setTimeout("document.getElementById('errorMsg').textContent=''", 5000);
		}
		
		
		function dataload(link){
			list = load(link);
			link=link||'';
			console.log(list);
			console.log(link);
			$('#root').text(link);
			var t='';
			for(var i in list)
				t+=('<div><a href="">'+list[i].name+'</a></div>');
			$("#list").html(t);
			$('#list').find('div>a').on('click', function(e){
				e.preventDefault();
				var obj = list[$(this).parent().index()];
				if(obj.isFile)//파일
					getVideo('?i=<?=$i?>&v='+encodeURIComponent(obj.name)+'&d='+encodeURIComponent(obj.dir));
				else dataload(obj.dir+obj.name);
			});
		}
		
		$('#up').on('click',function(){//상위
			if ($('#root').text()=='root')
				return;
			dataload(root);
		});

		dataload();
		$('#root').text('root');
		
		video.addEventListener("error", function (err) {
			errMessage(err);
		}, true);
		
	} // end of runtime
}// end of master

$(function(){
	init();
});

	</script>
    <style>
div a{
	width:400px;
	text-overflow: ellipsis;
    overflow: hidden;
    display: block;
	height: 50px;
	background-color: #2196f3;
	color : black;
	margin-bottom:1px;
}
#list{
    display: block;
	height:250px;
	overflow-y: scroll; overflow-x: hidden;
}
	</style>
    </head>
    <body>        
    
	<div id='videoframe'>
		<video id="Video1" controls style="border: 1px solid blue;" height="225" width="400" title="video element">            
			 HTML5 Video is required for this example
		</video>
	</div>
	<div><a id='up' style='cursor: pointer;'><label>Root:<span id='root'></span></a></label></div>
	<div id='list' style="width:400px;"></div>
    <div title="Error message area" id="errorMsg" style="color:Red;"></div>
    </body>
</html>
	<?php
	}
}

?>
