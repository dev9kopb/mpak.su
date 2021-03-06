<?

// ----------------------------------------------------------------------
// Жираф cms Content Management System
// Copyright (C) 2007-2010 by the mpak.
// (Link: http://mpak.su)
// LICENSE and CREDITS
// This program is free software and it's released under the terms of the
// GNU General Public License(GPL) - (Link: http://www.gnu.org/licenses/gpl.html)http://www.gnu.org/licenses/gpl.html
// Original Author of file: Krivoshlykov Evgeniy (mpak) +7 929 1140042
// ----------------------------------------------------------------------

ini_set('display_errors', 1); error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
header('Content-Type: text/html;charset=UTF-8');

if(strpos($f = __FILE__, "phar://") === 0){ # Фал index.php внутри phar архива
	$conf["db"]["open_basedir"] = implode("/", array_slice(explode("/", dirname(dirname($f))), 2)). ":". dirname($f);
}elseif(empty($conf["db"]["open_basedir"])){ # Не в phar
	if(($backtrace = array_shift(debug_backtrace())) && (strpos($backtrace['file'], "phar://") === 0)){
		$conf["db"]["open_basedir"] = dirname($f). ":". dirname($backtrace['file']);
	}else{
		$conf["db"]["open_basedir"] = (ini_get("open_basedir") ?: dirname($f));
	}
} if (!isset($index) && file_exists($index = array_shift(explode(':', $conf["db"]["open_basedir"])). '/index.php')){
	include($index); if($content) die;
} if(!function_exists('mp_require_once')){
	function mp_require_once($link){
		global $conf, $arg, $tpl;
		foreach(explode('::', strtr(strtr($conf["db"]["open_basedir"], array(":"=>"::")), array("phar:://"=>"phar://"))) as $k=>$v){
			if (!file_exists($file_name = "$v/$link")) continue;
			include_once($file_name); return;
		}
	}
}

require_once("include/config.php"); # Конфигурация
mp_require_once("include/config.php"); # Конфигурация
mp_require_once("include/mpfunc.php"); # Функции системы

$conf['db']['conn'] = new PDO("{$conf['db']['type']}:host={$conf['db']['host']};dbname={$conf['db']['name']};charset=UTF8", $conf['db']['login'], $conf['db']['pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC));
if($conf['db']['type'] == "mysql"){
	$conf['db']['conn']->exec("set names utf8"); # Prior to PHP 5.3.6, the charset option was ignored
} $_REQUEST += $_GET += mpgt($_SERVER['REQUEST_URI'], $_GET);

if(!empty($_GET['m']) && array_search('admin', (array)$_GET['m'])){
	mp_require_once("include/func.php"); # Функции таблиц
}

if ((!array_key_exists('null', $_GET) && !empty($conf['db']['error'])) || !count(qn("SHOW TABLES"))){
	exit(mpopendir('include/install.php') ? mpct('include/install.php') : "Файл установки не найден");
}

if(array_key_exists('themes', (array)$_GET['m']) && empty($_GET['m']['themes']) && array_key_exists('null', $_GET) && empty($_GET['w']) && empty($_GET['h'])){
	if(empty($_GET['theme'])){
		$_GET['theme'] = mpql(mpqw("SELECT value FROM {$conf['db']['prefix']}settings WHERE name=\"theme\""), 0, 'value');
	} $ex = array('png'=>'image/png', 'jpg'=>'image/jpg', 'gif'=>'image/gif', 'otf'=>'font/opentype', 'css'=>'text/css', 'js'=>'text/javascript', 'swf'=>'application/x-shockwave-flash', 'ico' => 'image/x-icon', 'woff'=>'application/x-font-woff', 'svg'=>'image/svg+xml', 'tpl'=>'text/html', 'ogg'=>'application/ogg', 'mp3'=>'application/mp3', 'cur'=>'image/x-win-bitmap', 'rtf'=>'application/rtf');
	$fn = "themes/{$_GET['theme']}/{$_GET['']}";
	$ext = mb_strtoLower(preg_replace('#^.*\.(\w+)$#Uui','$1',$fn),'UTF-8'); //array_pop(explode('.', $fn));
	header('Cache-Control: public,max-age=28800');
	header("Expires: ".gmdate("D, d M Y H:i:s", time() + 3600)." GMT");
	header("Content-type: ". ($ex[$ext] ? $ex[$ext] : "application/$ext"));
	header('Last-Modified: '. date("r", filemtime(mpopendir($fn))));
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= filemtime(mpopendir($fn)))){
		header('HTTP/1.0 304 Not Modified');
	}else if(preg_match('#jpg|jpeg|bmp|png#Uui',$ext)){
		echo mprs(mpopendir($fn), $_GET['w'], $_GET['h'], $_GET['c']);
	}else{
		readfile(mpopendir($fn));
	} exit();
 }

$conf['db']['info'] = 'Загрузка свойств модулей';
$conf['settings'] = array('http_host'=>$_SERVER['HTTP_HOST'])+array_column(rb("{$conf['db']['prefix']}settings"), "value", "name");
$conf['settings']['access_array'] = array('0'=>'Запрет', '1'=>'Чтение', '2'=>'Добавл', '3'=>'Запись', '4'=>'Модер', '5'=>'Админ');
if($conf['settings']['users_log']) $conf['event'] = rb("{$conf['db']['prefix']}users_event", "name");
if ($conf['settings']['microtime']) $conf['settings']['microtime'] = microtime();

if($conf['settings']['del_sess']){
	$func = create_function('&$val, $key','$val = strtr(stripslashes($val), array("\\\\"=>"&#92;", \'"\'=>"&#34;", "\'"=>"&#39;"));');
	array_walk ($get = $_GET, $func); $post = $_POST;
	if (isset($post['pass'])) $post['pass'] = 'hide';
	if (isset($post['pass2'])) $post['pass2'] = 'hide';
	array_walk ($post, $func); array_walk ($files = $_FILES, $func); array_walk ($server = $_SERVER, $func);
	$request = serialize(array('$_POST'=>$post, '$_GET'=>$get, '$_FILES'=>$files, '$_SERVER'=>$server));
} setlocale (LC_ALL, "Russian"); putenv("LANG=ru_RU");// bindtextdomain("messages", "./locale"); textdomain("messages"); bind_textdomain_codeset('messages', 'CP1251'); //setlocale(LC_ALL, "ru_RU.CP1251")

if (!$guest_id = mpql(mpqw("SELECT id as gid FROM {$conf['db']['prefix']}users WHERE name='{$conf['settings']['default_usr']}'", "Получаем свойства пользователя гость"), 0, "gid")){ # Создаем пользователя в случае если его нет
	$guest_id = mpfdk("{$conf['db']['prefix']}users", $w = array("name"=>$conf['settings']['default_usr']), $w += array("pass"=>"nopass", "reg_time"=>time(), "last_time"=>time()));
	$guest_grp_id = mpfdk("{$conf['db']['prefix']}users_grp", $w = array("name"=>$conf['settings']['default_grp']), $w);
	$guest_mem_id = mpfdk("{$conf['db']['prefix']}users", $w = array("uid"=>$guest_id, "grp_id"=>$guest_grp_id), $w);
}

$sess = ql($sql = "SELECT * FROM {$conf['db']['prefix']}sess WHERE `ip`='{$_SERVER['REMOTE_ADDR']}' AND last_time>=".(time()-$conf['settings']['sess_time'])." AND `agent`=\"".mpquot($_SERVER['HTTP_USER_AGENT']). "\" AND (". ($_COOKIE["{$conf['db']['prefix']}sess"] ? "sess=\"". mpquot($_COOKIE["{$conf['db']['prefix']}sess"]). "\"" : "uid=". (int)$guest_id).") ORDER BY id DESC", 0);
if(!$sess){
	$sess = array('uid'=>$guest_id, 'sess'=>md5("{$_SERVER['REMOTE_ADDR']}:".microtime()), 'ref'=>mpidn(urldecode($_SERVER['HTTP_REFERER'])), 'ip'=>$_SERVER['REMOTE_ADDR'], 'agent'=>$_SERVER['HTTP_USER_AGENT'], 'url'=>$_SERVER['REQUEST_URI']);
	$res = qw($sql = "INSERT INTO {$conf['db']['prefix']}sess (uid, ref, sess, last_time, ip, agent, url) VALUES (". (int)$guest_id. ", '". mpquot($sess['ref']). "', \"". mpquot($_COOKIE["{$conf['db']['prefix']}sess"]). "\", ".time().", '". mpquot($sess['ip']). "', '".mpquot($sess['agent'])."', '".mpquot($sess['url'])."')");
	$sess['id'] = $conf['db']['conn']->lastInsertId();
}

if($_COOKIE["{$conf['db']['prefix']}sess"] != $sess['sess']){
	$sess['sess'] = md5("{$_SERVER['REMOTE_ADDR']}:".microtime());
	setcookie("{$conf['db']['prefix']}sess", $sess['sess'], 0, "/");
}

if(!isset($_REQUEST['NoUpSes']) OR !isset($_REQUEST['null'])){ # Обновление информации о сессии При запросе ресурса обязательна
	mpqw("UPDATE {$conf['db']['prefix']}sess SET count_time = count_time+".time()."-last_time, last_time=".time().", ".(isset($_GET['null']) ? 'cnull=cnull' : 'count=count')."+1, sess=\"". mpquot($sess['sess']). "\" WHERE id=". (int)$sess['id']);
}

if (strlen($_POST['name']) && strlen($_POST['pass']) && $_POST['reg'] == 'Аутентификация' && $uid = mpql(mpqw("SELECT id FROM {$conf['db']['prefix']}users WHERE type_id=1 AND name = \"".mpquot($_POST['name'])."\" AND pass='".mphash($_POST['name'], $_POST['pass'])."'", 'Проверка существования пользователя'), 0, 'id')){
	qw($sql = "UPDATE {$conf['db']['prefix']}sess SET uid=".($sess['uid'] = $uid)." WHERE id=". (int)$sess['id']);
	qw($sql = "UPDATE {$conf['db']['prefix']}users SET last_time=". time(). " WHERE id=".(int)$uid);
//	header("Location: ". $_SERVER['REQUEST_URI']); exit;
}elseif(isset($_GET['logoff'])){ # Если пользователь покидает сайт
	mpqw("UPDATE {$conf['db']['prefix']}sess SET sess = '!". mpquot($sess['sess']). "' WHERE id=". (int)$sess['id'], 'Выход пользователя');
	if(!empty($_SERVER['HTTP_REFERER'])){
		header("Location: ". ($conf['settings']['users_logoff_location'] ? $conf['settings']['users_logoff_location'] : $_SERVER['HTTP_REFERER'])); exit;
	}// if($conf['settings']['del_sess'] == 0){ # Стираем просроченные сессии
	mpqw($sql = "DELETE FROM {$conf['db']['prefix']}sess WHERE last_time < ".(time() - $conf['settings']['sess_time']), 'Удаление сессий');
	mpqw($sql = "DELETE FROM {$conf['db']['prefix']}sess_post WHERE time < ".(time() - $conf['settings']['sess_time']), 'Удаление данных сессии');
//	}
}

$user = mpql(mpqw("SELECT *, id AS uid, name AS uname FROM {$conf['db']['prefix']}users WHERE id={$sess['uid']}", 'Проверка пользователя'));
list($k, $conf['user']) = each($user);
if(($conf['settings']['users_uname'] = $conf['user']['uname']) == $conf['settings']['default_usr']){
	$conf['user']['uid'] = -$sess['id'];
} $conf['settings']['users_uid'] = $conf['user']['uid'];

$conf['db']['info'] = 'Получаем информацию о группах в которые входит пользователь';
$conf['user']['gid'] = array_column(qn("SELECT g.id, g.name FROM {$conf['db']['prefix']}users_grp as g, {$conf['db']['prefix']}users_mem as m WHERE (g.id=m.grp_id) AND m.uid=". (int)$sess['uid']), "name", "id");
$conf['user']['sess'] = $sess;

if ($conf['settings']['start_mod'] && !$_GET['m']){
	if (strpos($conf['settings']['start_mod'], 'array://') === 0){
		$_GET = unserialize(substr($conf['settings']['start_mod'], 8));
	}else if(strpos($conf['settings']['start_mod'], "http://") === 0){
		header("Location: {$conf['settings']['start_mod']}"); exit;
	}else{
		$_GET = mpgt($_SERVER['REQUEST_URI'] = $conf['settings']['start_mod']);
	}
}

foreach(mpql(mpqw("SELECT * FROM {$conf['db']['prefix']}modules WHERE enabled = 2", 'Информация о модулях')) as $k=>$v){
	if (array_search($conf['user']['uname'], explode(',', $conf['settings']['admin_usr'])) !== false) $v['access'] = 5; # Права суперпользователя
	$conf['modules'][ $v['folder'] ] = $v;
	$conf['modules'][ $v['folder'] ]['modname'] = (strpos($_SERVER['HTTP_HOST'], "xn--") !== false) ? mb_strtolower($v['name']) : $v['folder'];
	$conf['modules'][ $v['name'] ] = &$conf['modules'][ $v['folder'] ];
	$conf['modules'][ mb_strtolower($v['name']) ] = &$conf['modules'][ $v['folder'] ];
	$conf['modules'][ $v['id'] ] = &$conf['modules'][ $v['folder'] ];
}

$conf['settings']['modpath'] = $conf['modules'][ array_shift(array_keys($_GET['m'])) ]['folder'];
$conf['settings']['fn'] = array_shift(array_values($_GET['m'])) ? array_shift(array_values($_GET['m'])) : "index";

if(isset($_GET['theme']) && $_GET['theme'] != $conf['user']['sess']['theme']){
	$conf['user']['sess']['theme'] = $conf['settings']['theme'] = basename($_GET['theme']);
}elseif($conf['user']['sess']['theme']){
	$conf['settings']['theme'] = $conf['user']['sess']['theme'];
}

//$m = array_shift(array_keys($_GET['m']));
//$f = array_shift(array_values($_GET['m']));

if (empty($f)) $f = 'index';
if (!empty($conf['settings']["theme/*:{$conf['settings']['fn']}"])) $conf['settings']['theme'] = $conf['settings']["theme/*:{$conf['settings']['fn']}"];
if (!empty($conf['settings']["theme/{$conf['settings']['modpath']}:*"])) $conf['settings']['theme'] = $conf['settings']["theme/{$conf['settings']['modpath']}:*"];
if (!empty($conf['settings']["theme/{$conf['settings']['modpath']}:{$conf['settings']['fn']}"])) $conf['settings']['theme'] = $conf['settings']["theme/{$conf['settings']['modpath']}:{$conf['settings']['fn']}"];

$content = ((mpopendir($init = "include/init.php")) ? mpct($init, $arg = array("access"=>(array_search($conf['settings']['admin_grp'], $conf['user']['gid']) ? 5 : 1))) : ""); # Установка предварительных переменных

if(!array_key_exists("null", $_GET) && $conf['modules']['seo']){
	if($_GET['p']){
		$r = strtr($_SERVER['REQUEST_URI'], array("?p={$_GET['p']}"=>"", "&p={$_GET['p']}"=>"", "/p:{$_GET['p']}"=>""));
	}else{ $r = urldecode(preg_replace("#([\#\?].*)?$#",'',$_SERVER['REQUEST_URI'])); }	
	
	foreach(erb('mp_seo_redirect') as $rule){
		if(preg_match("#^{$rule['from']}$#iu",$r)){
			$redirect = $rule; break;
		}
	}
	
	if(isset($redirect)){
		$redirect['to'] = preg_replace("#^{$redirect['from']}$#iu",$redirect['to'],$r);
		if(strpos($redirect['to'], "http://") === 0){
			exit(header("Location: {$redirect['to']}"));
		}else{
			$_REQUEST = ($_GET = mpgt($redirect['to'])+array_diff_key($_GET, array("m"=>"Устаревшие адресации"))+$_REQUEST);
			$conf['settings']['canonical'] = $r;
		}
	}elseif($conf['settings']['start_mod'] == $_SERVER['REQUEST_URI']){ # Заглавная страница
		$conf['settings']['canonical'] = "/";
	}else{ # Ссылка на основную страницу
		$conf['settings']['canonical'] = $_SERVER['REQUEST_URI'];
	}
}

if($_GET['id'] && $conf['settings']['modules_default'] && empty($conf['modules'][ ($mp = array_shift(array_keys($_GET['m']))) ])){
	$_GET['m'] = array($conf['settings']['modules_default']=>$_GET['m'][ $mp ]);
} # Устанавливаем дефолтный раздел. Если нет среди установленных то он считает что страничка оттуда


foreach((array)mpql(mpqw("SELECT * FROM {$conf['db']['prefix']}modules_gaccess", 'Права доступа группы к модулю')) as $k=>$v){
	if ( $conf['user']['gid'][ $v['gid'] ] && array_search($conf['user']['uname'], explode(',', $conf['settings']['admin_usr'])) === false)
		$conf['modules'][ $v['mid'] ]['access'] = $v['access'];
}
foreach((array)mpql(mpqw("SELECT * FROM {$conf['db']['prefix']}modules_uaccess ORDER BY uid", 'Права доступа пользователя к модулю')) as $k=>$v){
	if ($conf['user']['uid'] == $v['uid'] && array_search($conf['user']['uname'], explode(',', $conf['settings']['admin_usr'])) === false)
		$conf['modules'][ $v['mid'] ]['access'] = $v['access'];
}

$m = array_shift(array_keys($_GET['m']));
$f = array_shift(array_values($_GET['m']));

if (empty($f)) $f = 'index';
if (!empty($conf['settings']["theme/*:$f"])) $conf['settings']['theme'] = $conf['settings']["theme/*:$f"];
if (!empty($conf['settings']["theme/$m:*"])) $conf['settings']['theme'] = $conf['settings']["theme/$m:*"];
if (!empty($conf['settings']["theme/$m:$f"])) $conf['settings']['theme'] = $conf['settings']["theme/$m:$f"];

if ((strpos($f, "admin") === 0) && $conf['settings']["theme/*:admin"]){
	$conf['settings']['theme'] = $conf['settings']["theme/*:admin"];
}
 
if(isset($_GET['theme']) && $_GET['theme'] != $conf['user']['sess']['theme']){
	$conf['user']['sess']['theme'] = $conf['settings']['theme'] = basename($_GET['theme']);
}elseif($conf['user']['sess']['theme']){
	$conf['settings']['theme'] = $conf['user']['sess']['theme'];
}

if($t = mpopendir($f = "themes/{$conf['settings']['theme']}/". ($_GET['index'] ? basename($_GET['index']) : "index"). ".html")){
	$tc = ($conf['settings']['theme_exec'] ? mpeval($f) : file_get_contents($t));
}else{ die("Шаблон {$f} не найден"); }

if(isset($_GET['m']['sqlanaliz'])){
	$zblocks = bcont();
	$content .= mcont($content);
}else{
	$content .= mcont($content);
	$zblocks = bcont();
} if(!array_key_exists('null', $_GET)){
	$content = str_replace('<!-- [modules] -->', $content, $tc);
} $content = strtr($content, (array)$zblocks);

if ($conf['settings']['microtime']){
	$conf['settings']['microtime'] = (substr(microtime(), strpos(microtime(), ' ')) - substr($conf['settings']['microtime'], strpos($conf['settings']['microtime'], ' ')) + microtime() - $conf['settings']['microtime']);
}

$aid = spisok("SELECT id, aid FROM {$conf['db']['prefix']}settings");
foreach($conf['settings'] as $k=>$v){
	$content = str_replace("<!-- [settings:$k] -->", $v, $content);
} echo $content;
