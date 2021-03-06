<? die;

if(isset($_GET[''])) $_GET['q'] = $_GET[''];
if($_SERVER['HTTP_IF_MODIFIED_SINCE'] >= filemtime($localFileName)){
	exit(header('HTTP/1.0 304 Not Modified'));
}else if (isset($_GET['q'])){
	$ext = array_pop(explode('.', $_GET['q']));
	$tn = "themes/".basename($_GET['theme'] ? $_GET['theme'] : $conf['settings']['theme']);
	$res_name = $tn ."/".strtr($_GET['q'], array('..'=>''));
	if($conf['settings']['themes_file_not_exists_event'] && !is_file($res_name)){
		header("HTTP/1.0 404 Not Found");
		mpevent("Файл темы не найден ошибка 404", $res_name);
	}else{
		header('Last-Modified: Cache-Control: max-age=86400, must-revalidate');
		header("Expires: " . date("r", time() + 3600));
		header("Cache-Control: public");
		$defaultmimes = array(
			'otf'=>'font/opentype', 'cur'=>'application/octet-stream', 'ani'=>'application/x-navi-animation', 'aif' => 'audio/x-aiff', 'aiff' => 'audio/x-aiff', 'arc' => 'application/octet-stream', 'arj' => 'application/octet-stream', 'art' => 'image/x-jg', 'asf' => 'video/x-ms-asf', 'asx' => 'video/x-ms-asf', 'avi' => 'video/avi', 'bin' => 'application/octet-stream', 'bm' => 'image/bmp', 'bmp' => 'image/bmp', 'bz2' => 'application/x-bzip2', 'css' => 'text/css', 'doc' => 'application/msword', 'dot' => 'application/msword', 'dv' => 'video/x-dv', 'dvi' => 'application/x-dvi', 'eps' => 'application/postscript', 'exe' => 'application/octet-stream', 'gif' => 'image/gif', 'gz' => 'application/x-gzip', 'gzip' => 'application/x-gzip', 'htm' => 'text/html', 'html' => 'text/html', 'ico' => 'image/x-icon', 'jpe' => 'image/jpeg', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'js' => 'application/x-javascript', 'log' => 'text/plain', 'mid' => 'audio/x-midi', 'mov' => 'video/quicktime', 'mp2' => 'audio/mpeg', 'mp3' => 'audio/mpeg3', 'mpg' => 'audio/mpeg', 'pdf' => 'aplication/pdf', 'png' => 'image/png', 'rtf' => 'application/rtf', 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'txt' => 'text/plain', 'xml' => 'text/xml', 'ttf'=>'application/x-font-ttf', 'woff'=>'application/x-font-woff', 'svg'=>'image/svg+xml',
		);
		header("Content-type: ".($defaultmimes[$ext] ? $defaultmimes[$ext] : "text/$ext"));
		if(($_GET['w'] || $_GET['h']) && array_search($ext, array(1=>'jpg', 'png', 'gif'))){
			echo mprs(mpopendir($res_name), $_GET['w'], $_GET['h'], $_GET['c']);
		}else{
			if($f = fopen(mpopendir($res_name), "rb")){
				while(!feof($f)) {
					echo fread($f, 256);
				}
			}
		}
	}
}else if($_GET['id']){
	$theme = mpql(mpqw("SELECT theme FROM {$conf['db']['prefix']}{$arg['modpath']} WHERE id=".(int)$_GET['id']), 0, 'theme');
	if (isset($_GET['null'])){
		header('Content-Type: image/svg+xml');
		header('Last-Modified: Cache-Control: max-age=86400, must-revalidate');
		header("Content-type: {$v['type']}");
		echo $theme;
	}else{
		echo "<pre>$theme</pre>";
	}
}else if((int)$_GET['svg']){
	$svg = mpql(mpqw("SELECT svg FROM {$conf['db']['prefix']}{$arg['modpath']}_svg WHERE id=".(int)$_GET['svg']), 0, 'svg');
	foreach($conf['settings'] as $k=>$v){
		$config["<!-- [settings:$k] -->"] = $v;
	}
	header('Content-Type: image/svg+xml');
	header('Last-Modified: Cache-Control: max-age=86400, must-revalidate');
	echo strtr($svg, $config); die;
}else{
	header("Location: /{$arg['modpath']}:404");
}
