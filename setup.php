<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 'off');
if (get_magic_quotes_gpc()) {
    foreach ($_GET as $key => $value)
        $_GET[$key] = stripslashes($value);
    foreach ($_POST as $key => $value)
        $_POST[$key] = stripslashes($value);
    foreach ($_COOKIE as $key => $value)
        $_COOKIE[$key] = stripslashes($value);
}
$host = explode(':', $_SERVER['HTTP_HOST']);
if (count($host) > 1) {
    $_SERVER['HTTP_HOST'] = $host[0];
    $_SERVER['SERVER_PORT'] = $host[1];
}
unset($host);

define ('ROOT', '.');
require 'lib/config.php';

if (!empty($_GET['test'])) {
   echo getFingerPrint();
    exit;
}

$root = substr($_SERVER['SCRIPT_FILENAME'], 0, strlen($_SERVER['SCRIPT_FILENAME']) - 10);
$path = stripPath(substr($_SERVER['PHP_SELF'], 0, strlen($_SERVER['PHP_SELF']) - 10));

$baseLanguage = 'ko';
if( !empty($_POST['Lang']) ) $baseLanguage = $_POST['Lang'];
if( Locale::setDirectory('language') ) Locale::set( $baseLanguage );

if (file_exists($root . '/config.php') && (filesize($root . '/config.php') > 0)) {
    header('HTTP/1.1 503 Service Unavailable');
?>
<!DOCTYPE html PUBLIC "-//W3C//XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo TATTERTOOLS_NAME;?> <?php echo TATTERTOOLS_VERSION;?> - Setup</title>
<script  type="text/javascript">
//<![CDATA[
	function current(){ 
		document.getElementById("setup").submit();
	}
//]]>
</script> 
</head>
<body>
<p><?php echo _t('다시 설정하시려면 config.php를 먼저 삭제하셔야 합니다.');?></p>
<form id="setup" name="setup" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>"> 
<?php
	if( Locale::setDirectory('language')) {
		$currentLang = isset($_REQUEST['Lang']) ? $_REQUEST['Lang'] : '';
		$availableLanguages =   Locale::getSupportedLocales(); 
?> 
Select Language : <select name="Lang" id = "Lang" onchange= "current();" > 
<?php
		foreach( $availableLanguages as $key => $value) 
			print('<option value="'.$key.'" '.( $key == $currentLang ? ' selected="selected" ' : '').' >'.$value.'</option>'); 
?></select> 
<?php 
	}
?>
</form> 
</body>
</html>
<?php
    exit;
}

if (array_key_exists('phpinfo',$_GET)) {
	phpinfo();
	exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo TATTERTOOLS_NAME;?> <?php echo TATTERTOOLS_VERSION;?> - Setup</title>
<link rel="stylesheet" media="screen" type="text/css" href="style/setup/style.css" />
<script type="text/javascript">
//<![CDATA[
    function init() {
    }
    
    function previous() {
    }

	function current(){ 
		document.getElementById("step").value ="" ; 
		document.getElementById("setup").submit() ; 
	} 
	
    function next(type) {
		if (type != undefined)
			document.getElementById("setupMode").value = type;
        document.getElementById("setup").submit();
    }
    
    function show(id) {
        if (document.getElementById("typeDomain"))
            document.getElementById("typeDomain").style.display = "none";
        if (document.getElementById("typePath"))
            document.getElementById("typePath").style.display = "none";
        if (document.getElementById("typeSingle"))
            document.getElementById("typeSingle").style.display = "none";
        if (document.getElementById(id))
            document.getElementById(id).style.display = "block";
    }
//]]>
</script>
</head>
<body onload="init()">
<div id="container">
  <form id="setup" name="setup" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
  <div id="title"><h1><img src="style/setup/title.gif" width="250" height="54" alt="<?php echo TATTERTOOLS_NAME;?> <?php echo TATTERTOOLS_VERSION;?> Setup" /></h1></div>
  <input type="hidden" name="Lang" id="Lang" value="<?php echo $baseLanguage;?>" />
<?php
if (empty($_POST['step'])) {
?>
  <div id="inner">
    <input type="hidden" name="step" value="1" />
    <h2><span class="step"><?php echo _f('%1단계', 1);?></span> : <?php echo _t('태터툴즈 설치를 시작합니다.');?></h2>
		<div id="langSel" > <?php drowSetLang( $baseLanguage, 'Norm');?></div> 
    <div id="info"><b><?php echo TATTERTOOLS_VERSION;?></b><br />
      <?php echo TATTERTOOLS_COPYRIGHT;?><br />
      Homepage: <a href="<?php echo TATTERTOOLS_HOMEPAGE;?>"><?php echo TATTERTOOLS_HOMEPAGE;?></a></div>
    <div id="content">
      <ol>
        <li><?php echo _t('소스를 포함한 소프트웨어에 포함된 모든 저작물(이하, 태터툴즈)의 저작권자는 Tatter &amp; Company와 Tatter &amp; Friends입니다.');?></li>
        <li><?php echo _t('태터툴즈는 GPL 라이선스로 제공되며, 모든 사람이 자유롭게 이용할 수 있습니다.');?></li>
        <li><?php echo _t('프로그램 사용에 대한 유지 및 보수 등의 의무와, 사용 중 데이터 손실 등에 대한 사고책임은 모두 사용자에게 있습니다.');?></li>
        <li><?php echo _t('스킨 및 트리, 플러그인의 저작권은 각 제작자에게 있습니다.');?></li>
      </ol>
    </div>
  <div id="navigation">
    <a href="#" onclick="next(); return false;" title="<?php echo _t('다음');?>"><img src="style/setup/icon_next.gif" width="74" height="24" alt="<?php echo _t('다음');?>" /></a>
  </div>
  </div>
<?php
}
else if ($_POST['step'] == 7) {
	checkStep(8, false);
}
else {
	
	function mysql_tt_escape_string($string) {
		global $mysql_escaping_function;
		return $mysql_escaping_function($string);
	}
	
	for ($i = 1; $i <= $_POST['step']; $i ++) {
        if (!checkStep($i))
            break;
        if ($i == 3) {
			if (function_exists('mysql_real_escape_string') && (mysql_real_escape_string('ㅋ') == 'ㅋ')) {
				$mysql_escaping_function =  create_function('$string', 'return mysql_real_escape_string($string);');
			} else {
				$mysql_escaping_function =  create_function('$string', 'return mysql_escape_string($string);');
			}
		}
    }
    if ($i > $_POST['step'])
        checkStep($_POST['step'] + 1, false);
}

function checkStep($step, $check = true) {
    global $root, $path;
    $error = 0;
    if ($step == 1) {
        if ($check)
            return true;
    }
    else if ($step == 2) {
        if ($check) {
			if (!empty($_POST['mode'])) {
				switch ($_POST['mode']) {
					case 'install':
					case 'setup':
					case 'uninstall':
						return true;
				}
			}
        }
?>
  <input type="hidden" name="step" value="2" />
  <input id="setupMode" type="hidden" name="mode" value="" />
  <div id="inner">
    <h2><span class="step"><?php echo _f('%1단계', 2);?></span> : <?php echo _t('작업 유형을 선택해 주십시오.');?></h2>
    <div style="text-align:center">
      <div style="width:300px; padding:40px 0px 40px 0px">
        <div style="margin:20px;"><input type="button" value="<?php echo _t('새로운 태터툴즈를 설정합니다');?>" style="width:100%; height:40px; font-size:14px" onclick="next('install');return false;" /></div>
        <div style="margin:20px;"><input type="button" value="<?php echo _t('태터툴즈를 다시 설정합니다');?>" style="width:100%; height:40px; font-size:14px" onclick="next('setup');return false;" /></div>
        <div style="margin:20px;"><input type="button" value="<?php echo _t('태터툴즈 테이블을 삭제합니다');?>" style="width:100%; height:40px; font-size:14px" onclick="next('uninstall');return false;" /></div>
      </div>
    </div>
  </div>
<?php
    }
    else if ($step == 3) {
        if ($check) {
			switch ($_POST['mode']) {
				case 'install':
				case 'setup':
					if (!empty($_POST['dbServer']) && !empty($_POST['dbName']) && !empty($_POST['dbUser']) && isset($_POST['dbPassword']) && isset($_POST['dbPrefix'])) {
						if (!mysql_connect($_POST['dbServer'], $_POST['dbUser'], $_POST['dbPassword']))
							$error = 1;
						else if (!mysql_select_db($_POST['dbName']))
							$error = 2;
						else if (!empty($_POST['dbPrefix']) && !ereg('^[[:alnum:]_]+$', $_POST['dbPrefix']))
							$error = 3;
						else
							return true;
					}
					break;
				case 'uninstall':
					if (!empty($_POST['dbServer']) && !empty($_POST['dbName']) && !empty($_POST['dbUser']) && isset($_POST['dbPassword'])) {
						if (!mysql_connect($_POST['dbServer'], $_POST['dbUser'], $_POST['dbPassword']))
							$error = 1;
						else if (!mysql_select_db($_POST['dbName']))
							$error = 2;
						else
							return true;
					}
					break;
            }
        }
?>
  <input type="hidden" name="step" value="3" />
  <input type="hidden" name="mode" value="<?php echo $_POST['mode'];?>" />
  <div id="inner">
    <h2><span class="step"><?php echo _f('%1단계', 3);?></span> : <?php echo _t('작업 정보를 입력해 주십시오.');?></h2>
    <div id="userinput">
    <table class="inputs">
<?php
		switch ($_POST['mode']) {
			case 'install':
			case 'setup':
?>
      <tr>
        <th><?php echo _t('데이터베이스 서버');?> :</th>
        <td>
          <input type="text" name="dbServer" value="<?php echo (isset($_POST['dbServer']) ? $_POST['dbServer'] : 'localhost');?>" class="input<?php echo ($check && (empty($_POST['dbServer']) || ($error == 1)) ? ' input_error' : '');?>" />
        </td>
      </tr>
      <tr>
        <th><?php echo _t('데이터베이스 이름');?> :</th>
        <td>
          <input type="text" name="dbName" value="<?php echo (isset($_POST['dbName']) ? $_POST['dbName'] : 'tattertools');?>" class="input<?php echo ($check && (empty($_POST['dbName']) || ($error == 2)) ? ' input_error' : '');?>" />
        </td>
      </tr>
      <tr>
        <th><?php echo _t('데이터베이스 사용자명');?> :</th>
        <td>
          <input type="text" name="dbUser" value="<?php echo (isset($_POST['dbUser']) ? $_POST['dbUser'] : '');?>" class="input<?php echo ($check && (empty($_POST['dbUser']) || $error) ? ' input_error' : '');?>" />
        </td>
      </tr>
      <tr>
        <th><?php echo _t('데이터베이스 암호');?> :</th>
        <td>
          <input type="password" name="dbPassword" value="<?php echo (isset($_POST['dbPassword']) ? htmlspecialchars($_POST['dbPassword']) : '');?>" class="input<?php echo ($check && ($error == 1) ? ' input_error' : '');?>" />
        </td>
      </tr>
      <tr>
        <th><?php echo _t('테이블 식별자');?> :</th>
        <td>
          <input type="text" name="dbPrefix" value="<?php echo (isset($_POST['dbPrefix']) ? $_POST['dbPrefix'] : 'tt_');?>" class="input <?php echo ($check && ($error == 3) ? ' input_error' : '');?>" />
        </td>
      </tr>
<?php
				break;
			case 'uninstall':
?>
      <tr>
        <th><?php echo _t('데이터베이스 서버');?> :</th>
        <td>
          <input type="text" name="dbServer" value="<?php echo (isset($_POST['dbServer']) ? $_POST['dbServer'] : 'localhost');?>" class="input<?php echo ($check && (empty($_POST['dbServer']) || ($error == 1)) ? ' input_error' : '');?>" />
        </td>
      </tr>
      <tr>
        <th><?php echo _t('데이터베이스 이름');?> :</th>
        <td>
          <input type="text" name="dbName" value="<?php echo (isset($_POST['dbName']) ? $_POST['dbName'] : 'tattertools');?>" class="input<?php echo ($check && (empty($_POST['dbName']) || ($error == 2)) ? ' input_error' : '');?>" />
        </td>
      </tr>
      <tr>
        <th><?php echo _t('데이터베이스 사용자명');?> :</th>
        <td>
          <input type="text" name="dbUser" value="<?php echo (isset($_POST['dbUser']) ? $_POST['dbUser'] : '');?>" class="input<?php echo ($check && (empty($_POST['dbUser']) || $error) ? ' input_error' : '');?>" />
        </td>
      </tr>
      <tr>
        <th><?php echo _t('데이터베이스 암호');?> :</th>
        <td>
          <input type="password" name="dbPassword" value="<?php echo (isset($_POST['dbPassword']) ? htmlspecialchars($_POST['dbPassword']) : '');?>" class="input<?php echo ($check && ($error == 1) ? ' input_error' : '');?>" />
        </td>
      </tr>
<?php
		}
?>
    </table>
    </div>
    <div id="warning"><?php
        if ($error == 1)
           echo _t('데이터베이스 서버에 연결할 수 없습니다. 정보를 다시 입력해 주십시오.');
        else if ($error == 2)
           echo _t('데이터베이스를 사용할 수가 없습니다. 정보를 다시 입력해 주십시오.');
        else if ($error == 3)
           echo _t('테이블 식별자가 올바르지 않습니다. 다시 입력해 주십시오.');
        else if ($error == 6)
           echo _t('데이터베이스에 연결할 수 없습니다.');
        else if ($error == 7)
           echo _t('데이터베이스에 접근할 수 없습니다.');
        else if ($error == 8)
           echo _t('새로운 테이블 식별자가 올바르지 않습니다. 다시 입력해 주십시오.');
        else if ($check)
           echo _t('표시된 정보가 부족합니다.');
        else
           echo '&nbsp;';
?></div>
  <div id="navigation">
    <a href="#" onclick="window.history.back()" title="<?php echo _t('이전');?>"><img src="style/setup/icon_prev.gif" width="74" height="24" alt="<?php echo _t('이전');?>" /></a>
    <a href="#" onclick="next(); return false;" title="<?php echo _t('다음');?>"><img src="style/setup/icon_next.gif" width="74" height="24" alt="<?php echo _t('다음');?>" /></a>
  </div>
  </div>
<?php
    }
    else if (($step == 4) || ($step == 33)) {
        if ($check) {
			if ($_POST['mode'] == 'uninstall') {
				if (empty($_POST['target'])) {
					checkStep(2, false);
					return false;
				}
				else {
					checkStep(205, false);
					return false;
				}
			}
            if (!empty($_POST['checked']))
                return true;
        }
		if ($_POST['mode'] == 'uninstall')
			return checkStep(204, false);
?>
  <input type="hidden" name="step" value="4" />
  <input type="hidden" name="mode" value="<?php echo $_POST['mode'];?>" />
  <input type="hidden" name="dbServer" value="<?php echo (isset($_POST['dbServer']) ? $_POST['dbServer'] : '');?>" />
  <input type="hidden" name="dbName" value="<?php echo (isset($_POST['dbName']) ? $_POST['dbName'] : '');?>" />
  <input type="hidden" name="dbUser" value="<?php echo (isset($_POST['dbUser']) ? $_POST['dbUser'] : '');?>" />
  <input type="hidden" name="dbPassword" value="<?php echo (isset($_POST['dbPassword']) ? htmlspecialchars($_POST['dbPassword']) : '');?>" />
  <input type="hidden" name="dbPrefix" value="<?php echo (isset($_POST['dbPrefix']) ? $_POST['dbPrefix'] : '');?>" />
  <div id="inner">
    <h2><span class="step"><?php echo _f('%1단계', 4);?></span> : <?php echo _t('설치 요구 사항을 확인하고 있습니다.');?> </h2>
    <div id="content-box">
    <h3><?php echo _t('환경');?></h3>
    <ul>
      <li><?php echo _t('하드웨어');?>: <?php echo @exec('uname -mp');?></li>
      <li><?php echo _t('운영체제');?>: <?php echo @exec('uname -sir');?></li>
      <li><?php echo _t('웹서버');?>: <?php echo $_SERVER['SERVER_SOFTWARE'];?> <?php echo $_SERVER['SERVER_SIGNATURE'];?></li>
      <li><?php echo _t('PHP 버전');?>: <?php echo phpversion();?></li>
      <li><?php echo _t('MySQL 버전');?>: <?php echo mysql_get_server_info();?></li>
    </ul>
    <h3>PHP</h3>
    <ul>
<?php
        $functions = "
addslashes
array_flip
array_key_exists
array_pop
array_push
array_shift
array_slice
base64_encode
ceil
checkdate
closedir
copy
count
dechex
dir
ereg
ereg_replace
eregi
eregi_replace
explode
fclose
feof
fgets
file_exists
file_get_contents
filesize
fopen
fputs
fread
fsockopen
function_exists
fwrite
get_magic_quotes_gpc
getimagesize
gmdate
gmmktime
gmstrftime
header
html_entity_decode
htmlspecialchars
implode
ini_set
intval
is_dir
is_file
is_null
is_numeric
is_writable
ksort
ltrim
mail
max
md5
microtime
min
mkdir
mktime
move_uploaded_file
mysql_affected_rows
mysql_connect
mysql_error
mysql_escape_string
mysql_fetch_array
mysql_fetch_row
mysql_insert_id
mysql_num_rows
mysql_query
mysql_result
mysql_select_db
nl2br
number_format
ob_end_clean
ob_get_contents
ob_start
opendir
ord
parse_url
preg_match
preg_replace
rand
rawurlencode
readdir
rmdir
rtrim
session_cache_expire
session_destroy
session_id
session_name
session_set_cookie_params
session_set_save_handler
session_start
setcookie
sizeof
sprintf
str_replace
strftime
stripslashes
strlen
strncasecmp
strncmp
strpos
strrev
strtolower
strval
substr
substr_count
substr_replace
time
trim
unlink
urlencode
xml_get_error_code
xml_parse
xml_parser_create
xml_parser_free
xml_parser_set_option
xml_set_character_data_handler
xml_set_default_handler
xml_set_element_handler
xml_set_object
";
        $required = array();
        foreach (explode("\n", str_replace("\r", '', trim($functions))) as $function) {
            if (!function_exists($function))
                array_push($required, $function);
        }
        if (count($required) == 0) {
?>
                  <li>OK</li>
<?php
        }
        else {
            $error = 4;
?>
                <span style="color:red"><?php echo _t('함수가 설치되어야 합니다.');?></span>
<?php
            foreach ($required as $function) {
?>
                  <li style="color:red"><?php echo $function;?></li>
<?php
            }
        }
?>
    </ul>
    <h3>MySQL</h3>
    <ul>
<?php
        if (mysql_query('SET CHARACTER SET utf8'))
           echo '<li>Character Set: OK</li>';
        else {
           echo '<li style="color:navy">Character Set: ', _t('UTF8 미지원 (경고: 한글 지원이 불완전할 수 있습니다.)'), '</li>';
        }
        if (mysql_query('SET SESSION collation_connection = \'utf8_general_ci\''))
           echo '<li>Collation: OK</li>';
        else {
           echo '<li style="color:navy">Collation: ', _t('UTF8 General 미지원 (경고: 한글 지원이 불완전할 수 있습니다.)'), '</li>';
        }
        if (mysql_query("CREATE TABLE {$_POST['dbPrefix']}Setup (a INT NOT NULL)")) {
            mysql_query("DROP TABLE {$_POST['dbPrefix']}Setup");
           echo '<li>', _t('테이블 생성 권한'), ': OK</li>';
        }
        else {
            $error = 6;
           echo '<li style="color:red">', _t('테이블 생성 권한'), ': ', _t('없음'), '</li>';
        }
?>
    </ul>
<?php
        $tables = array();
        if ($result = mysql_query("SHOW TABLES")) {
            while ($table = mysql_fetch_array($result)) {
                if (strncmp($table[0], $_POST['dbPrefix'], strlen($_POST['dbPrefix'])))
                    continue;
                switch (strtolower(substr($table[0], strlen($_POST['dbPrefix'])))) {
                    case 'attachments':
                    case 'blogsettings':
                    case 'blogstatistics':
                    case 'categories':
					case 'comments':
					case 'commentsnotified':
					case 'commentsnotifiedqueue':
					case 'commentsnotifiedsiteinfo':
					case 'dailystatistics':
					case 'entries':
					case 'feedgrouprelations':
					case 'feedgroups':
					case 'feeditems':
					case 'feedreads':
					case 'feedsettings':
					case 'feedstarred':
					case 'feeds':
					case 'filters':
                    case 'links':
					case 'plugins':
                    case 'refererlogs':
                    case 'refererstatistics':
                    case 'reservedwords':
					case 'ServiceSetting':
                    case 'sessionvisits':
                    case 'sessions':
                    case 'skinsettings':
                    case 'tagrelations':
                    case 'tags':
                    case 'trackbacklogs':
                    case 'trackbacks':
					case 'UserSettings':
                    case 'users':
                        $tables[count($tables)] = $table[0];
                        break;
                }
            }
        }
		
		switch ($_POST['mode']) {
			case 'install':
				echo '<h3>', _t('새 데이터베이스 테이블'), '</h3>';
				if (count($tables) == 0) {
					echo '<ul><li>OK</li></ul>';
				} else {
					$error = 7;
					echo '<ul style="color:red">', _t('테이블이 이미 존재합니다.');
					foreach ($tables as $table)
						echo '<li>', $table, '</li>';
					echo '</ul>';
				}
				break;
			case 'setup':
				echo '<h3>', _t('데이터베이스 테이블 확인'), '</h3>';
				if ((count($tables) == 32) || ((count($tables) == 31) && !in_array('Filters', $tables))) {
					echo '<ul><li>OK</li></ul>';
				} else {
					$error = 7;
					echo '<ul style="color:red">', _t('테이블이 존재하지 않습니다.');
					foreach ($tables as $table)
						echo '<li>', $table, '</li>';
					echo '</ul>';
				}
		}
?>
    <h3><?php echo _t('파일 시스템 권한');?></h3>
    <ul>
<?php
        $filename = $root . '/.htaccess';
        if (file_exists($filename)) {
            if (is_writable($filename)) {
                if (filesize($filename))
                   echo '<li style="color:navy">', _f('설정 파일: OK (경고: "%1" 파일을 덮어 쓰게 됩니다.)', $filename), '</li>';
                else
                   echo '<li>', _t('웹 설정 파일'), ': OK</li>';
            }
            else {
                $error = 8;
               echo '<li style="color:red">', _t('웹 설정 파일'), ': ', _f('"%1"에 접근할 수 없습니다. 퍼미션을 %2(으)로 수정해 주십시오.', $filename, '0666'), '</li>';
            }
        }
        else if (is_writable($root))
           echo '<li>', _t('웹 설정 파일'), ': OK</li>';
        else {
            $error = 9;
           echo '<li style="color:red">', _t('웹 설정 파일'), ': ', _f('"%1"에 %2 파일을 생성할 수 없습니다. "%1"의 퍼미션을 %3(으)로 수정해 주십시오.', $root, '.htaccess', '0777'), '</li>';
        }
        
        $filename = $root . '/config.php';
        if (file_exists($filename)) {
            if (is_writable($filename)) {
                if (filesize($filename))
                   echo '<li style="color:navy">', _f('설정 파일: OK (경고: "%1" 파일을 덮어 쓰게 됩니다.)', $filename), '</li>';
                else
                   echo '<li>', _t('설정 파일'), ': OK</li>';
            }
            else {
                $error = 10;
               echo '<li style="color:red">', _t('설정 파일'), ': ', _f('"%1"에 접근할 수 없습니다. 퍼미션을 %2(으)로 수정해 주십시오.', $filename, '0666'), '</li>';
            }
        }
        else if (is_writable($root))
           echo '<li>', _t('설정 파일'), ': OK</li>';
        else {
            $error = 11;
           echo '<li style="color:red">', _t('설정 파일'), ': ', _f('"%1"에 %2 파일을 생성할 수 없습니다. "%1"의 퍼미션을 %3(으)로 수정해 주십시오.', $root, 'config.php', '0777'), '</li>';
        }
        
        $filename = $root . '/attach';
        if (file_exists($filename)) {
            if (is_dir($filename) && is_writable($filename))
               echo '<li>', _t('첨부 디렉토리'), ': OK</li>';
            else {
                $error = 12;
               echo '<li style="color:red">', _t('첨부 디렉토리'), ': ', _f('"%1"에 접근할 수 없습니다. 퍼미션을 %2(으)로 수정해 주십시오.', $filename, '0777'), '</li>';
            }
        } else if (mkdir($filename)) {
			@chmod($filename, 0777);
           echo '<li>', _t('첨부 디렉토리'), ': OK</li>';
        } else {
            $error = 13;
           echo '<li style="color:red">', _t('첨부 디렉토리'), ': ', _f('"%1"에 %2 디렉토리를 생성할 수 없습니다. "%1"의 퍼미션을 %3(으)로 수정해 주십시오.', $root, 'attach', '0777'), '</li>';
        }
        
        $filename = $root . '/cache';
        if (is_dir($filename)) {
            if (is_writable($filename))
               echo '<li>', _t('캐시 디렉토리'), ': OK</li>';
            else {
                $error = 12;
               echo '<li style="color:red">', _t('캐시 디렉토리'), ': ', _f('"%1"에 접근할 수 없습니다. 퍼미션을 %2(으)로 수정해 주십시오.', $filename, '0777'), '</li>';
            }
        } else if (mkdir($filename)) {
			@chmod($filename, 0777);
           echo '<li>', _t('캐시 디렉토리'), ': OK</li>';
        } else {
            $error = 13;
           echo '<li style="color:red">', _t('캐시 디렉토리'), ': ', _f('"%1"에 %2 디렉토리를 생성할 수 없습니다. "%1"의 퍼미션을 %3(으)로 수정해 주십시오.', $root, 'cache', '0777'), '</li>';
        }
        
        $filename = $root . '/skin/customize';
        if (is_dir($filename)) {
            if (is_writable($filename))
               echo '<li>', _t('스킨 디렉토리'), ': OK</li>';
            else {
                $error = 14;
               echo '<li style="color:red">', _t('스킨 디렉토리'), ': ', _f('"%1"에 접근할 수 없습니다. 퍼미션을 %2(으)로 수정해 주십시오.', $filename, '0777'), '</li>';
            }
        } else if (mkdir($filename)) {
			@chmod($filename, 0777);
           echo '<li>', _t('스킨 디렉토리'), ': OK</li>';
        } else {
            $error = 15;
           echo '<li style="color:red">', _t('스킨 디렉토리'), ': ', _f('"%1"에 %2 디렉토리를 생성할 수 없습니다. "%1"의 퍼미션을 %3(으)로 수정해 주십시오.', "$root/skin", 'customize', '0777'), '</li>';
        }
?>
    </ul>
<?php
        if ($step == 33) {
            $error = 16;
?>
    <h3><?php echo _t('Apache Rewrite Engine');?></h3>
    <ul style="color:red">
      <li><?php echo _t('Rewrite를 사용할 수 없습니다.');?><br /><span style="color:black"><?php echo _t('다음 항목을 확인하십시오.');?></span></li>
      <ol style="color:blue">
        <li><?php echo _t('웹서버 설정에 <b>mod_rewrite</b>의 로딩이 포함되어야 합니다.');?><br />
          <samp><?php echo _t('예: LoadModule <b>rewrite_module</b> modules/<b>mod_rewrite</b>.so');?></samp>
        </li>
        <li><?php echo _t('웹서버 설정의 이 디렉토리에 대한 <em>Options</em> 항목에 <b>FollowSymLinks</b>가 포함되거나 <b>All</b>이어야 합니다.');?>
          <samp><br /><?php echo _t('예: Options <b>FollowSymLinks</b>');?></samp>
          <samp><br /><?php echo _t('예: Options <b>All</b>');?></samp>
        </li>
        <li><?php echo _t('웹서버 설정의 이 디렉토리에 대한 <em>AllowOverride</em> 항목에 <b>FileInfo</b>가 포함되거나 <b>All</b>이어야 합니다.');?>
          <samp><br /><?php echo _t('예: AllowOverride <b>FileInfo</b>');?></samp>
          <samp><br /><?php echo _t('예: AllowOverride <b>All</b>');?></samp>
        </li>
        <li><b><?php echo _t('위 2와 3의 문제는 아래 내용을 웹서버 설정에 포함시켜 해결할 수 있습니다.');?></b>
          <samp style="color:black"><br />
          &lt;Directory &quot;<?php echo $root;?>&quot;&gt;<br />
          &nbsp;&nbsp;Options FollowSymLinks<br />
          &nbsp;&nbsp;AllowOverride FileInfo<br />
          &lt;/Directory&gt;
          </samp>
        </li>
      </ul>
    </ul>
<?php
        }
?>
  </div>
  <div id="navigation">
    <a href="#" onclick="window.history.back()" title="<?php echo _t('이전');?>"><img src="style/setup/icon_prev.gif" width="74" height="24" alt="<?php echo _t('이전');?>" /></a>
    <a href="#" onclick="next(); return false;" title="<?php echo _t('다음');?>"><img src="style/setup/icon_next.gif" width="74" height="24" alt="<?php echo _t('다음');?>" /></a>
  </div>
  </div>
  <input type="hidden" name="checked" value="<?php echo ($error ? '' : 'checked');?>" />
<?php
    }
    else if ($step == 5) {
        if ($check) {
            if (!empty($_POST['domain']) && !empty($_POST['type']))
                return true;
        }

        $filename = $root . '/.htaccess';
        $fp = fopen($filename, 'w+');
        if (!$fp) {
            checkStep($step - 1, false);
            return false;
        }
        fwrite($fp,
"RewriteEngine On
RewriteBase $path/
RewriteRule ^testrewrite$ setup.php [L]"
        );
        fclose($fp);
		@chmod($filename, 0666);
        
        if (testMyself(substr(getFingerPrint(), 0, 6) . substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.')), $path . '/testrewrite?test=now', $_SERVER['SERVER_PORT']))
            $rewrite = 3;
        else if (testMyself(substr(getFingerPrint(), 0, 6) . '.' . $_SERVER['HTTP_HOST'], $path . '/testrewrite?test=now', $_SERVER['SERVER_PORT']))
            $rewrite = 2;
        else if (testMyself($_SERVER['HTTP_HOST'], $path . '/testrewrite?test=now', $_SERVER['SERVER_PORT']))
            $rewrite = 1;
        else {
            $rewrite = 0;
			@unlink($filename);
            checkStep(33, false);
            return false;
        }
		@unlink($filename);
        $domain = $rewrite == 3 ? substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.') + 1) : $_SERVER['HTTP_HOST'];
?>
  <input type="hidden" name="step" value="<?php echo $step;?>" />
  <input type="hidden" name="mode" value="<?php echo $_POST['mode'];?>" />
  <input type="hidden" name="dbServer" value="<?php echo (isset($_POST['dbServer']) ? $_POST['dbServer'] : '');?>" />
  <input type="hidden" name="dbName" value="<?php echo (isset($_POST['dbName']) ? $_POST['dbName'] : '');?>" />
  <input type="hidden" name="dbUser" value="<?php echo (isset($_POST['dbUser']) ? $_POST['dbUser'] : '');?>" />
  <input type="hidden" name="dbPassword" value="<?php echo (isset($_POST['dbPassword']) ? htmlspecialchars($_POST['dbPassword']) : '');?>" />
  <input type="hidden" name="dbPrefix" value="<?php echo (isset($_POST['dbPrefix']) ? $_POST['dbPrefix'] : '');?>" />
  <input type="hidden" name="checked" value="<?php echo (isset($_POST['checked']) ? $_POST['checked'] : '');?>" />
  <input type="hidden" name="domain" value="<?php echo $domain;?>" />
  <div id="inner">
  <h2><span class="step"><?php echo _f('%1단계', $step);?></span> : <?php echo _t('사용 가능한 운영 방법은 다음과 같습니다. 선택하여 주십시오.');?></h2>
  <div id="userinput">
    <table class="inputs">
<?php
        if ($rewrite >= 1) {
?>
      <tr>
        <th width="120"><strong><?php echo _t('다중 사용자');?> : </strong></th>
        <td>
<?php
            if ($rewrite >= 2) {
?>
        <label for="type1"><input type="radio" id="type1" name="type" value="domain" checked="checked" onclick="show('typeDomain')" />
                      <?php echo _t('도메인네임(DNS)으로 블로그 식별');?></label>
        <br />
<?php
            }
?>
        <label for="type2"><input type="radio" id="type2" name="type" value="path"<?php echo ($rewrite == 1 ? ' checked="checked"' : '');?> onclick="show('typePath')" />
        <?php echo _t('하위 경로(Path)로 블로그 식별');?></label></td>
      </tr>
      <tr>
        <th style="padding-top:10px"><strong><?php echo _t('단일 사용자');?> : </strong></th>
        <td style="padding-top:10px">
          <label for="type3"><input type="radio" id="type3" name="type" value="single" onclick="show('typeSingle')" /><?php echo _t('단일 블로그');?></label></td>
      </tr>
<?php
        }
?>
      <tr>
        <th style="padding-top:20px"><?php echo _t('블로그 주소 예시');?></th>
        <td style="padding-top:20px; height:100px">
        <ul id="typeDomain"<?php echo ($rewrite >= 2 ? '' : ' style="display:none"');?>>
          <li>http://<b>blog1</b>.<?php echo $domain;?><?php echo ($_SERVER['SERVER_PORT'] == 80 ? '' : ":{$_SERVER['SERVER_PORT']}");?><?php echo $path;?>/</li>
          <li>http://<b>blog2</b>.<?php echo $domain;?><?php echo ($_SERVER['SERVER_PORT'] == 80 ? '' : ":{$_SERVER['SERVER_PORT']}");?><?php echo $path;?>/</li>
        </ul> 
        <ul id="typePath"<?php echo ($rewrite == 1 ? '' : ' style="display:none"');?>>
          <li>http://<?php echo $domain;?><?php echo ($_SERVER['SERVER_PORT'] == 80 ? '' : ":{$_SERVER['SERVER_PORT']}");?><?php echo $path;?>/<b>blog1</b></li>
          <li>http://<?php echo $domain;?><?php echo ($_SERVER['SERVER_PORT'] == 80 ? '' : ":{$_SERVER['SERVER_PORT']}");?><?php echo $path;?>/<b>blog2</b></li>
        </ul> 
        <ul id="typeSingle" style="display:none">
          <li>http://<?php echo $domain;?><?php echo ($_SERVER['SERVER_PORT'] == 80 ? '' : ":{$_SERVER['SERVER_PORT']}");?><?php echo $path;?>/</li>
        </ul> 
        </td>
      </tr>
    </table>
  </div>
  <div id="navigation">
    <a href="#" onclick="window.history.back()" title="<?php echo _t('이전');?>"><img src="style/setup/icon_prev.gif" width="74" height="24" alt="<?php echo _t('이전');?>" /></a>
    <a href="#" onclick="next(); return false;" title="<?php echo _t('다음');?>"><img src="style/setup/icon_next.gif" width="74" height="24" alt="<?php echo _t('다음');?>" /></a>
  </div>
  </div>
<?php
    }
    else if ($step == 6) {
        if ($check) {
            if (!empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['password2']) && (($_POST['type'] == 'single') || !empty($_POST['blog'])) && isset($_POST['name'])) {
                if (!ereg('^[^@]+@([[:alnum:]]+(-[[:alnum:]]+)*\.)+[[:alnum:]]+(-[[:alnum:]]+)*$', $_POST['email']))
                    $error = 51;
                else if ($_POST['password'] != $_POST['password2'])
                    $error = 52;
                else if (($_POST['type'] != 'single') && !ereg('^[[:alnum:]]+$', $_POST['blog']))
                    $error = 53;
                else
                    return true;
            }
        } else {
			@mysql_query('SET CHARACTER SET utf8');
			if ($result = mysql_query("SELECT loginid, password, name FROM {$_POST['dbPrefix']}Users WHERE userid = 1")) {
				@list($_POST['email'], $_POST['password'], $_POST['name']) = mysql_fetch_row($result);
				$_POST['password2'] = $_POST['password'];
				mysql_free_result($result);
			}
			if ($result = mysql_query("SELECT name FROM {$_POST['dbPrefix']}BlogSettings WHERE owner = 1")) {
				@list($_POST['blog']) = mysql_fetch_row($result);
				mysql_free_result($result);
			}
		}
		
?>
  <input type="hidden" name="step" value="<?php echo $step;?>" />
  <input type="hidden" name="mode" value="<?php echo $_POST['mode'];?>" />
  <input type="hidden" name="dbServer" value="<?php echo (isset($_POST['dbServer']) ? $_POST['dbServer'] : '');?>" />
  <input type="hidden" name="dbName" value="<?php echo (isset($_POST['dbName']) ? $_POST['dbName'] : '');?>" />
  <input type="hidden" name="dbUser" value="<?php echo (isset($_POST['dbUser']) ? $_POST['dbUser'] : '');?>" />
  <input type="hidden" name="dbPassword" value="<?php echo (isset($_POST['dbPassword']) ? htmlspecialchars($_POST['dbPassword']) : '');?>" />
  <input type="hidden" name="dbPrefix" value="<?php echo (isset($_POST['dbPrefix']) ? $_POST['dbPrefix'] : '');?>" />
  <input type="hidden" name="checked" value="<?php echo (isset($_POST['checked']) ? $_POST['checked'] : '');?>" />
  <input type="hidden" name="domain" value="<?php echo (isset($_POST['domain']) ? $_POST['domain'] : '');?>" />
  <input type="hidden" name="type" value="<?php echo (isset($_POST['type']) ? $_POST['type'] : '');?>" />
  <div id="inner">
    <h2><span class="step"><?php echo _f('%1단계', $step);?></span> : <?php echo _t('관리자 정보 입력');?></h2>
    <div id="userinput">
      <table class="inputs">
        <tr>
          <th style="width:100px"><?php echo _t('이메일');?> : </th>
          <td>
            <input type="text" name="email" value="<?php echo (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '');?>" class="input_email"<?php echo ($check && (empty($_POST['email']) || ($error == 51)) ? ' style="border-color:red"' : '');?> />
          </td>
        </tr>
        <tr>
          <th><?php echo _t('비밀번호');?> : </th>
          <td>
            <input type="password" name="password" value="<?php echo (isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '');?>" class="input_password"<?php echo ($check && empty($_POST['password']) ? ' style="border-color:red"' : '');?> />
          </td>
        </tr>
        <tr>
          <th><?php echo _t('비밀번호 확인');?> : </th>
          <td>
            <input type="password" name="password2" value="<?php echo (isset($_POST['password2']) ? htmlspecialchars($_POST['password2']) : '');?>" class="input_password"<?php echo ($check && empty($_POST['password2']) ? ' style="border-color:red"' : '');?> />
          </td>
        </tr>
        <tr>
          <th><?php echo _t('블로그 식별자');?> : </th>
          <td>
            <input type="text" name="blog" value="<?php echo (isset($_POST['blog']) ? htmlspecialchars($_POST['blog']) : '');?>" class="input_password"<?php echo ($check && (empty($_POST['blog']) || ($error == 53)) ? ' style="border-color:red"' : '');?> />
          </td>
        </tr>
        <tr>
          <th><?php echo _t('필명');?> : </th>
          <td>
            <input type="text" name="name" value="<?php echo (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '');?>" class="input_password" />
          </td>
        </tr>
      </table>
      <div id="warning"><?php
        if ($error == 51)
           echo _t('이메일이 올바르지 않습니다.');
        else if ($error == 52)
           echo _t('비밀번호가 일치하지 않습니다.');
        else if ($error == 53)
           echo _t('블로그 식별자가 올바르지 않습니다.');
        else if ($check)
           echo _t('표시된 정보가 부족합니다.');
        else
           echo '&nbsp;';
?></div>
    </div>
  <div id="navigation">
    <a href="#" onclick="window.history.back()" title="<?php echo _t('이전');?>"><img src="style/setup/icon_prev.gif" width="74" height="24" alt="<?php echo _t('이전');?>" /></a>
    <a href="#" onclick="next(); return false;" title="<?php echo _t('다음');?>"><img src="style/setup/icon_next.gif" width="74" height="24" alt="<?php echo _t('다음');?>" /></a>
  </div>
  </div>
<?php
    }
    else if ($step == 7) {
        if ($check)
            return true;
        
?>
  <input type="hidden" name="step" value="<?php echo $step;?>" />
  <input type="hidden" name="mode" value="<?php echo $_POST['mode'];?>" />
  <input type="hidden" name="dbServer" value="<?php echo (isset($_POST['dbServer']) ? $_POST['dbServer'] : '');?>" />
  <input type="hidden" name="dbName" value="<?php echo (isset($_POST['dbName']) ? $_POST['dbName'] : '');?>" />
  <input type="hidden" name="dbUser" value="<?php echo (isset($_POST['dbUser']) ? $_POST['dbUser'] : '');?>" />
  <input type="hidden" name="dbPassword" value="<?php echo (isset($_POST['dbPassword']) ? htmlspecialchars($_POST['dbPassword']) : '');?>" />
  <input type="hidden" name="dbPrefix" value="<?php echo (isset($_POST['dbPrefix']) ? $_POST['dbPrefix'] : '');?>" />
  <input type="hidden" name="checked" value="<?php echo (isset($_POST['checked']) ? $_POST['checked'] : '');?>" />
  <input type="hidden" name="domain" value="<?php echo (isset($_POST['domain']) ? $_POST['domain'] : '');?>" />
  <input type="hidden" name="type" value="<?php echo (isset($_POST['type']) ? $_POST['type'] : '');?>" />
  <input type="hidden" name="blog" value="<?php echo (isset($_POST['blog']) ? $_POST['blog'] : '');?>" />
  <div id="inner">
    <h2><span class="step"><?php echo _f('%1단계', $step);?></span> : <?php echo _t('데이터베이스를 준비하고 있습니다. 잠시만 기다려 주십시오.');?></h2>
    <div id="content-box" style="text-align:center">
	<p></p>
    </div>
  </div><!-- inner -->
  </form>
</div><!-- container -->
<?php
		function fail($msg) {
			flush();
			if ($_POST['mode'] == 'install') {
			}
			exit;
		}

		$loginid = mysql_tt_escape_string($_POST['email']);
		$password = md5($_POST['password']);
		$name = mysql_tt_escape_string($_POST['name']);
		$baseLanguage = mysql_tt_escape_string( $_POST['Lang']);
		$baseTimezone = mysql_tt_escape_string( substr(_t('default:Asia/Seoul'),8));

        $charset = 'TYPE=MyISAM DEFAULT CHARSET=utf8';
        if (!@mysql_query('SET CHARACTER SET utf8'))
            $charset = 'TYPE=MyISAM';
        @mysql_query('SET SESSION collation_connection = \'utf8_general_ci\'');
        
        if ($_POST['mode'] == 'install') {
            $schema = "
CREATE TABLE {$_POST['dbPrefix']}Attachments (
  owner int(11) NOT NULL default '0',
  parent int(11) NOT NULL default '0',
  name varchar(32) NOT NULL default '',
  label varchar(64) NOT NULL default '',
  mime varchar(32) NOT NULL default '',
  size int(11) NOT NULL default '0',
  width int(11) NOT NULL default '0',
  height int(11) NOT NULL default '0',
  attached int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  enclosure tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (owner,name)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}BlogSettings (
  owner int(11) NOT NULL default '0',
  name varchar(32) NOT NULL default '',
  secondaryDomain varchar(64) NOT NULL default '',
  defaultDomain int(1) NOT NULL default '0',
  url varchar(80) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  logo varchar(64) NOT NULL default '',
  logoLabel varchar(255) NOT NULL default '',
  logoWidth int(11) NOT NULL default '0',
  logoHeight int(11) NOT NULL default '0',
  useSlogan int(1) NOT NULL default '1',
  entriesOnPage int(11) NOT NULL default '10',
  entriesOnList int(11) NOT NULL default '10',
  entriesOnRSS int(11) NOT NULL default '10',
  publishWholeOnRSS int(1) NOT NULL default '1',
  publishEolinSyncOnRSS int(1) NOT NULL default '1',
  allowWriteOnGuestbook int(1) NOT NULL default '1',
  allowWriteDoubleCommentOnGuestbook char(1) NOT NULL default '1',
  language VARCHAR(5) NOT NULL DEFAULT 'en',
  blogLanguage VARCHAR(5) NOT NULL DEFAULT 'en',
  timezone VARCHAR(32) NOT NULL DEFAULT 'GMT',
  PRIMARY KEY  (owner),
  UNIQUE KEY name (name)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}BlogStatistics (
  owner int(11) NOT NULL default '0',
  visits int(11) NOT NULL default '0',
  PRIMARY KEY  (owner)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Categories (
  owner int(11) NOT NULL default '0',
  id int(11) NOT NULL auto_increment,
  parent int(11) default NULL,
  name varchar(127) NOT NULL default '',
  priority int(11) NOT NULL default '0',
  entries int(11) NOT NULL default '0',
  entriesInLogin int(11) NOT NULL default '0',
  label varchar(255) NOT NULL default '',
  visibility tinyint(4) NOT NULL default '2',
  bodyId varchar(20) default NULL,
  PRIMARY KEY  (id),
  KEY owner (owner)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Comments (
  owner int(11) NOT NULL default '0',
  replier int(11) default NULL,
  id int(11) NOT NULL auto_increment,
  entry int(11) NOT NULL default '0',
  parent int(11) default NULL,
  name varchar(80) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  homepage varchar(80) NOT NULL default '',
  secret int(1) NOT NULL default '0',
  comment text NOT NULL,
  ip varchar(15) NOT NULL default '',
  written int(11) NOT NULL default '0',
  isFiltered int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY owner (owner),
  KEY entry (entry),
  KEY parent (parent)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}CommentsNotified (
  owner int(11) NOT NULL default '0',
  replier int(11) default NULL,
  id int(11) NOT NULL auto_increment,
  entry int(11) NOT NULL default '0',
  parent int(11) default NULL,
  name varchar(80) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  homepage varchar(80) NOT NULL default '',
  secret int(1) NOT NULL default '0',
  comment text NOT NULL,
  ip varchar(15) NOT NULL default '',
  written int(11) NOT NULL default '0',
  modified int(11) NOT NULL default '0',
  siteId int(11) NOT NULL default '0',
  isNew int(1) NOT NULL default '1',
  url varchar(255) NOT NULL default '',
  remoteId int(11) NOT NULL default '0',
  entryTitle varchar(255) NOT NULL default '',
  entryUrl varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY owner (owner),
  KEY entry (entry)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}CommentsNotifiedQueue (
  owner int(11) NOT NULL default '0',
  id int(11) NOT NULL auto_increment,
  commentId int(11) NOT NULL default '0',
  sendStatus int(1) NOT NULL default '0',
  checkDate int(11) NOT NULL default '0',
  written int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY commentId (commentId)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}CommentsNotifiedSiteInfo (
  id int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  name varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  modified int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY url (url),
  UNIQUE KEY id (id)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}DailyStatistics (
  owner int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  visits int(11) NOT NULL default '0',
  PRIMARY KEY  (owner,date)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Entries (
  owner int(11) NOT NULL default '0',
  id int(11) NOT NULL auto_increment,
  draft tinyint(1) NOT NULL default '0',
  visibility tinyint(4) NOT NULL default '0',
  category int(11) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  slogan varchar(255) NOT NULL default '',
  content mediumtext NOT NULL,
  location varchar(255) NOT NULL default '/',
  password varchar(32) default NULL,
  acceptComment int(1) NOT NULL default '1',
  acceptTrackback int(1) NOT NULL default '1',
  published int(11) NOT NULL default '0',
  created int(11) NOT NULL default '0',
  modified int(11) NOT NULL default '0',
  comments int(11) NOT NULL default '0',
  trackbacks int(11) NOT NULL default '0',
  PRIMARY KEY (owner, id, draft),
  KEY owner (owner),
  KEY category (category)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}FeedGroupRelations (
  owner int(11) NOT NULL default '0',
  feed int(11) NOT NULL default '0',
  groupId int(11) NOT NULL default '0',
  PRIMARY KEY  (owner,feed,groupId)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}FeedGroups (
  owner int(11) NOT NULL default '0',
  id int(11) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  PRIMARY KEY  (owner,id)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}FeedItems (
  id int(11) NOT NULL auto_increment,
  feed int(11) NOT NULL default '0',
  author varchar(255) NOT NULL default '',
  permalink varchar(255) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  description text NOT NULL,
  tags varchar(255) NOT NULL default '',
  enclosure varchar(255) NOT NULL default '',
  written int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY feed (feed),
  KEY written (written),
  KEY permalink (permalink)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}FeedReads (
  owner int(11) NOT NULL default '0',
  item int(11) NOT NULL default '0',
  PRIMARY KEY  (owner,item)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}FeedSettings (
  owner int(11) NOT NULL default '0',
  updateCycle int(11) NOT NULL default '120',
  feedLife int(11) NOT NULL default '30',
  loadImage int(11) NOT NULL default '1',
  allowScript int(11) NOT NULL default '1',
  newWindow int(11) NOT NULL default '1',
  PRIMARY KEY  (owner)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}FeedStarred (
  owner int(11) NOT NULL default '0',
  item int(11) NOT NULL default '0',
  PRIMARY KEY  (owner,item)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Feeds (
  id int(11) NOT NULL auto_increment,
  xmlURL varchar(255) NOT NULL default '',
  blogURL varchar(255) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  language varchar(5) NOT NULL default 'en-US',
  modified int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Filters (
  id int(11) NOT NULL auto_increment,
  owner int(11) NOT NULL default '0',
  type enum('content','ip','name','url') NOT NULL default 'content',
  pattern varchar(255) NOT NULL default '',
  PRIMARY KEY (id),
  UNIQUE KEY owner (owner, type, pattern)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Links (
  owner int(11) NOT NULL default '0',
  id int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  rss varchar(255) NOT NULL default '',
  written int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY owner (owner,url)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Plugins (
  owner int(11) NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  settings text,
  PRIMARY KEY  (owner,name)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}RefererLogs (
  owner int(11) NOT NULL default '0',
  host varchar(64) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  referred int(11) NOT NULL default '0'
) $charset;
CREATE TABLE {$_POST['dbPrefix']}RefererStatistics (
  owner int(11) NOT NULL default '0',
  host varchar(64) NOT NULL default '',
  count int(11) NOT NULL default '0',
  PRIMARY KEY  (owner,host)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}ReservedWords (
  word varchar(16) NOT NULL default '',
  PRIMARY KEY  (word)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}ServiceSettings (
  name varchar(32) NOT NULL default '',
  value varchar(255) NOT NULL default '',
  PRIMARY KEY  (name)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}SessionVisits (
  id varchar(32) NOT NULL default '',
  address varchar(15) NOT NULL default '',
  blog int(11) NOT NULL default '0',
  PRIMARY KEY  (id,address,blog)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Sessions (
  id varchar(32) NOT NULL default '',
  address varchar(15) NOT NULL default '',
  userid int(11) default NULL,
  preexistence int(11) default NULL,
  data text default NULL,
  server varchar(64) NOT NULL default '',
  request varchar(255) NOT NULL default '',
  referer varchar(255) NOT NULL default '',
  timer float NOT NULL default '0',
  created int(11) NOT NULL default '0',
  updated int(11) NOT NULL default '0',
  PRIMARY KEY  (id,address)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}SkinSettings (
  owner int(11) NOT NULL default '0',
  skin varchar(32) NOT NULL default 'tistory',
  entriesOnRecent int(11) NOT NULL default '10',
  commentsOnRecent int(11) NOT NULL default '10',
  commentsOnGuestbook int(11) NOT NULL default '5',
  archivesOnPage int(11) NOT NULL default '5',
  tagsOnTagbox tinyint(4) NOT NULL default '10',
  tagboxAlign tinyint(4) NOT NULL default '1',
  trackbacksOnRecent int(11) NOT NULL default '5',
  expandComment int(1) NOT NULL default '1',
  expandTrackback int(1) NOT NULL default '1',
  recentNoticeLength int(11) NOT NULL default '30',
  recentEntryLength int(11) NOT NULL default '30',
  recentCommentLength int(11) NOT NULL default '30',
  recentTrackbackLength int(11) NOT NULL default '30',
  linkLength int(11) NOT NULL default '30',
  showListOnCategory int(1) NOT NULL default '1',
  showListOnArchive int(1) NOT NULL default '1',
  tree varchar(32) NOT NULL default 'base',
  colorOnTree varchar(6) NOT NULL default '000000',
  bgColorOnTree varchar(6) NOT NULL default '',
  activeColorOnTree varchar(6) NOT NULL default 'FFFFFF',
  activeBgColorOnTree varchar(6) NOT NULL default '00ADEF',
  labelLengthOnTree int(11) NOT NULL default '30',
  showValueOnTree int(1) NOT NULL default '1',
  PRIMARY KEY  (owner)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}TagRelations (
  owner int(11) NOT NULL default '0',
  tag int(11) NOT NULL default '0',
  entry int(11) NOT NULL default '0',
  PRIMARY KEY  (owner, tag, entry),
  KEY owner (owner)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Tags (
  id int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}TrackbackLogs (
  owner int(11) NOT NULL default '0',
  id int(11) NOT NULL auto_increment,
  entry int(11) NOT NULL default '0',
  url varchar(255) NOT NULL default '',
  written int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Trackbacks (
  id int(11) NOT NULL auto_increment,
  owner int(11) NOT NULL default '0',
  entry int(11) NOT NULL default '0',
  url varchar(255) NOT NULL default '',
  writer int(11) default NULL,
  site varchar(255) NOT NULL default '',
  subject varchar(255) NOT NULL default '',
  excerpt varchar(255) NOT NULL default '',
  ip varchar(15) NOT NULL default '',
  written int(11) NOT NULL default '0',
  isFiltered int(11) NOT NULL default '0',
  PRIMARY KEY (id),
  UNIQUE KEY owner (owner, entry, url)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}Users (
  userid int(11) NOT NULL auto_increment,
  loginid varchar(64) NOT NULL default '',
  password varchar(32) default NULL,
  name varchar(32) NOT NULL default '',
  created int(11) NOT NULL default '0',
  lastLogin int(11) NOT NULL default '0',
  host int(11) NOT NULL default '0',
  PRIMARY KEY  (userid),
  UNIQUE KEY loginid (loginid)
) $charset;
CREATE TABLE {$_POST['dbPrefix']}UserSettings (
  user int(11) NOT NULL default '0',
  name varchar(32) NOT NULL default '',
  value text NOT NULL default '',
  PRIMARY KEY (user,name)
) $charset;
INSERT INTO {$_POST['dbPrefix']}Users VALUES (1, '$loginid', '$password', '$name', UNIX_TIMESTAMP(), 0, 0);
INSERT INTO {$_POST['dbPrefix']}ServiceSettings (name, value) VALUES ('newlineStyle', '1.1'); 
INSERT INTO {$_POST['dbPrefix']}BlogSettings (owner, name, language, blogLanguage, timezone) VALUES (1, '{$_POST['blog']}', '$baseLanguage', '$baseLanguage', '$baseTimezone');
INSERT INTO {$_POST['dbPrefix']}SkinSettings (owner) VALUES (1);
INSERT INTO {$_POST['dbPrefix']}FeedSettings (owner) values(1);
INSERT INTO {$_POST['dbPrefix']}FeedGroups (owner) values(1)";
            $query = explode(';', trim($schema));
            foreach ($query as $sub) {
                if (!mysql_query($sub)) {
					@mysql_query(
						"DROP TABLE
							{$_POST['dbPrefix']}Attachments,
							{$_POST['dbPrefix']}BlogSettings,
							{$_POST['dbPrefix']}BlogStatistics,
							{$_POST['dbPrefix']}Categories,
							{$_POST['dbPrefix']}Comments,
							{$_POST['dbPrefix']}CommentsNotified,
							{$_POST['dbPrefix']}CommentsNotifiedQueue,
							{$_POST['dbPrefix']}CommentsNotifiedSiteInfo,
							{$_POST['dbPrefix']}ContentFilters,
							{$_POST['dbPrefix']}DailyStatistics,
							{$_POST['dbPrefix']}Entries,
							{$_POST['dbPrefix']}FeedGroupRelations,
							{$_POST['dbPrefix']}FeedGroups,
							{$_POST['dbPrefix']}FeedItems,
							{$_POST['dbPrefix']}FeedReads,
							{$_POST['dbPrefix']}FeedSettings,
							{$_POST['dbPrefix']}FeedStarred,
							{$_POST['dbPrefix']}Feeds,
							{$_POST['dbPrefix']}Filters,
							{$_POST['dbPrefix']}Links,
							{$_POST['dbPrefix']}Plugins,
							{$_POST['dbPrefix']}RefererLogs,
							{$_POST['dbPrefix']}RefererStatistics,
							{$_POST['dbPrefix']}ReservedWords,
							{$_POST['dbPrefix']}ServiceSettings,
							{$_POST['dbPrefix']}SessionVisits,
							{$_POST['dbPrefix']}Sessions,
							{$_POST['dbPrefix']}SkinSettings,
							{$_POST['dbPrefix']}TagRelations,
							{$_POST['dbPrefix']}Tags,
							{$_POST['dbPrefix']}TrackbackLogs,
							{$_POST['dbPrefix']}Trackbacks,
							{$_POST['dbPrefix']}UserSettings,
							{$_POST['dbPrefix']}Users"
					);
					echo '<script type="text/javascript">alert("', _t('테이블을 생성하지 못했습니다.'), '")</script>';
					$error = 1;
					break;
				}
			}
        }
		else {
			$password2 = mysql_tt_escape_string($_POST['password']);
            $schema = "
				UPDATE {$_POST['dbPrefix']}Users SET loginid = '$loginid', name = '$name' WHERE userid = 1;
				UPDATE {$_POST['dbPrefix']}Users SET password = '$password' WHERE userid = 1 AND password <> '$password2';
				UPDATE {$_POST['dbPrefix']}BlogSettings SET name = '{$_POST['blog']}' , language = '$baseLanguage' , timezone = '$baseTimezone' WHERE owner = 1"; 
            $query = explode(';', trim($schema));
            foreach ($query as $sub) {
                if (!mysql_query($sub)) {
					echo '<script type="text/javascript">alert("', _t('정보를 갱신하지 못했습니다.'), '")</script>';
					$error = 2;
					break;
				}
			}
		}
		if (!$error)
			echo '<script type="text/javascript">next()</script>';
?>
</body>
</html>
<?php
    }
    else if ($step == 8) {
        if ($check)
            return true;

        $filename = $root . '/config.php';
        $fp = fopen($filename, 'w+');
        if ($fp) {
            fwrite($fp,
"<?php
ini_set('display_errors', 'off');
\$database['server'] = '{$_POST['dbServer']}';
\$database['database'] = '{$_POST['dbName']}';
\$database['username'] = '{$_POST['dbUser']}';
\$database['password'] = '{$_POST['dbPassword']}';
\$database['prefix'] = '{$_POST['dbPrefix']}';
\$service['type'] = '{$_POST['type']}';
\$service['domain'] = '{$_POST['domain']}';
\$service['path'] = '$path';
\$service['skin'] = 'tistory';
//\$serviceURL = 'http://{$_POST['domain']}{$path}' ; // for path of Skin, plugin and etc.
?>"
            );
            fclose($fp);
            @chmod($filename, 0666);
        }
        
        $filename = $root . '/.htaccess';
        $fp = fopen($filename, 'w+');
        if ($fp) {
            fwrite($fp, $_POST['type'] == 'path' ?
"#<IfModule mod_url.c>
#CheckURL Off
#</ifModule>
RewriteEngine On
RewriteBase $path/
RewriteCond %{ENV:REDIRECT_SURI} !^$
RewriteRule (.*) - [L]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^(.+[^/])$ $1/ [L]
RewriteRule ^$ blog/index.php [E=SURI:1,L]
RewriteRule ^[[:alnum:]]+/*$ blog/index.php [E=SURI:1,L]
RewriteRule ^[[:alnum:]]+/+[0-9]+$ blog/item.php [E=SURI:1,L]
RewriteRule ^favicon\.ico$ blog/favicon.ico.php [E=SURI:1,L]
RewriteRule ^[[:alnum:]]+/+favicon\.ico$ blog/favicon.ico.php [E=SURI:1,L]
RewriteRule ^index\.gif$ blog/index.gif.php [E=SURI:1,L]
RewriteRule ^[[:alnum:]]+/+index\.gif$ blog/index.gif.php [E=SURI:1,L]
RewriteCond %{QUERY_STRING} (^|&)pl=([0-9]+)
RewriteRule ^([[:alnum:]]+)/+index\.php$ $1/%2 [NE,L]
RewriteRule ^[[:alnum:]]+/+index\.php$ blog/index.php [E=SURI:1,L]
RewriteRule ^[[:alnum:]]+/+index\.xml$ blog/rss/index.php [E=SURI:1,L]
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule !^(blog|cache)/ - [L]
RewriteRule ^[[:alnum:]]+/+(thumbnail)/([0-9]+/.+) cache/$1/$2 [E=SURI:1,L]
RewriteRule ^[[:alnum:]]+/+(entry|attachment|category|keylog|tag|search|plugin)/? blog/$1/index.php [E=SURI:1,L]
RewriteRule ^[[:alnum:]]+/+(.+)/[0-9]+$ blog/$1/item.php [E=SURI:1,L]
RewriteRule ^[[:alnum:]]+/+(.+)$ blog/$1/index.php [E=SURI:1,L]
"
                    :
"#<IfModule mod_url.c>
#CheckURL Off
#</IfModule>
RewriteEngine On
RewriteBase $path/
RewriteCond %{ENV:REDIRECT_SURI} !^$
RewriteRule (.*) - [L]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^(.+[^/])$ $1/ [L]
RewriteRule ^$ blog/index.php [E=SURI:1,L]
RewriteRule ^[0-9]+$ blog/item.php [E=SURI:1,L]
RewriteRule ^favicon\.ico$ blog/favicon.ico.php [E=SURI:1,L]
RewriteRule ^index\.gif$ blog/index.gif.php [E=SURI:1,L]
RewriteCond %{QUERY_STRING} (^|&)pl=([0-9]+)
RewriteRule ^index\.php$ %2 [NE,L]
RewriteRule ^index\.php$ blog/index.php [E=SURI:1,L]
RewriteRule ^index\.xml$ blog/rss/index.php [E=SURI:1,L]
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule !^(blog|cache)/ - [L]
RewriteRule ^(thumbnail)/([0-9]+/.+) cache/$1/$2 [E=SURI:1,L]
RewriteRule ^(entry|attachment|category|keylog|tag|search|plugin)/? blog/$1/index.php [E=SURI:1,L]
RewriteRule ^(.+)/[0-9]+$ blog/$1/item.php [E=SURI:1,L]
RewriteRule ^(.+)$ blog/$1/index.php [E=SURI:1,L]
"
            );
            fclose($fp);
            @chmod($filename, 0666);
        }
    
        switch ($_POST['type']) {
            case 'domain':
                $blogURL = "http://{$_POST['blog']}.{$_POST['domain']}" . ($_SERVER['SERVER_PORT'] != 80 ? ":{$_SERVER['SERVER_PORT']}" : '') . "$path";
                break;
            case 'path':
                $blogURL = "http://{$_POST['domain']}" . ($_SERVER['SERVER_PORT'] != 80 ? ":{$_SERVER['SERVER_PORT']}" : '') . "$path/{$_POST['blog']}";
                break;
            case 'single':
                $blogURL = "http://{$_POST['domain']}" . ($_SERVER['SERVER_PORT'] != 80 ? ":{$_SERVER['SERVER_PORT']}" : '') . "$path";
                break;
        }
?>
  <div id="inner">
    <h2><span class="step"><?php echo _t('설치완료');?></span> : <?php echo _t('테터툴즈가 성공적으로 설치되었습니다.');?></h2>
    <div id="content-box">
      <p>
        
      </p>
      <ul>
        <li><?php echo _t('태터툴즈 주소');?><br />
          <a href="<?php echo $blogURL;?>/"><?php echo $blogURL;?>/</a><br />
          <br />
        </li>
        <li><?php echo _t('태터툴즈 관리 툴 주소');?><br />
          <a href="<?php echo $blogURL;?>/owner"><?php echo $blogURL;?>/owner</a></li>
      </ul>
      <p>
        <?php echo _t('태터툴즈 관리 툴로 로그인 하신 후 필요사항을 수정해 주십시오.');?><br />
        <?php echo _t('태터툴즈를 이용해 주셔서 감사합니다.');?>
      </p>
    </div>
  </div>
<?php
    }
	else if ($step == 204) {
?>
  <input type="hidden" name="step" value="4" />
  <input type="hidden" name="mode" value="<?php echo $_POST['mode'];?>" />
  <input type="hidden" name="dbServer" value="<?php echo (isset($_POST['dbServer']) ? $_POST['dbServer'] : '');?>" />
  <input type="hidden" name="dbName" value="<?php echo (isset($_POST['dbName']) ? $_POST['dbName'] : '');?>" />
  <input type="hidden" name="dbUser" value="<?php echo (isset($_POST['dbUser']) ? $_POST['dbUser'] : '');?>" />
  <input type="hidden" name="dbPassword" value="<?php echo (isset($_POST['dbPassword']) ? htmlspecialchars($_POST['dbPassword']) : '');?>" />
  <div id="inner">
    <h2><span class="step"><?php echo _f('%1단계', 4);?></span> : <?php echo _t('삭제하고자 하는 테이블을 선택하여 주십시오.');?></h2>
    <div id="userinput">
    <table id="info">
      <tr>
        <th><?php echo _t('식별자');?></th>
        <th><?php echo _t('버전');?></th>
        <th><?php echo _t('테이블');?></th>
 	    <th></th>
     </tr>
<?php
        $tables = array();
		$ckeckedString = 'checked ';
        if ($result = mysql_query("SHOW TABLES")) {
            while ($table = mysql_fetch_array($result)) {
				$table = $table[0];
				if (ereg('Entries$', $table) && checkTables('1.1', $prefix = substr($table, 0, strlen($table) - 7))) {
?>
      <tr>
        <th><?php echo $prefix;?></th>
        <th>1.1</th>
        <td><?php echo implode(', ', getTables('1.1', $prefix));?></td>
	    <th><input type="radio" name="target" value="1.1_<?php echo $prefix;?>" <?php echo $ckeckedString;?>/></th>
      </tr>
<?php
					$ckeckedString = '';
				} else if (ereg('Entries$', $table) && checkTables('1.0.2', $prefix = substr($table, 0, strlen($table) - 7))) {
?>
      <tr>
        <th><?php echo $prefix;?></th>
        <th>1.0.2</th>
        <td><?php echo implode(', ', getTables('1.0.2', $prefix));?></td>
	    <th><input type="radio" name="target" value="1.0.2_<?php echo $prefix;?>" <?php echo $ckeckedString;?>/></th>
      </tr>
<?php
					$ckeckedString = '';
				} else if (ereg('Entries$', $table) && checkTables('1.0.0', $prefix = substr($table, 0, strlen($table) - 7))) {
?>
      <tr>
        <th><?php echo $prefix;?></th>
        <th>1.0.0</th>
        <td><?php echo implode(', ', getTables('1.0.0', $prefix));?></td>
	    <th><input type="radio" name="target" value="1.0.0_<?php echo $prefix;?>" <?php echo $ckeckedString;?>/></th>
      </tr>
<?php
					$ckeckedString = '';
				} else if (ereg('Entries$', $table) && checkTables('1.0.b2', $prefix = substr($table, 0, strlen($table) - 7))) {
?>
      <tr>
        <th><?php echo $prefix;?></th>
        <th>1.0 Beta 2</th>
        <td><?php echo implode(', ', getTables('1.0.b2', $prefix));?></td>
	    <th><input type="radio" name="target" value="1.0.b2_<?php echo $prefix;?>" <?php echo $ckeckedString;?>/></th>
      </tr>
<?php
					$ckeckedString = '';
				} else if (ereg('^t3_(.*)_10ofmg$', $table) && checkTables('0.97', $prefix = substr($table, 3, strlen($table) - 10))) {
?>
      <tr>
        <th><?php echo $prefix;?></th>
        <th>0.97 (Classic)</th>
        <td><?php echo implode(', ', getTables('0.97', $prefix));?></td>
	    <th><input type="radio" name="target" value="0.97_<?php echo $prefix;?>" <?php echo $ckeckedString;?>/></th>
      </tr>
<?php
					$ckeckedString = '';
				} else if (ereg('^t3_(.*)_ct1$', $table) && checkTables('0.96', $prefix = substr($table, 3, strlen($table) - 7))) {
?>
      <tr>
        <th><?php echo $prefix;?></th>
        <th>0.96x</th>
        <td><?php echo implode(', ', getTables('0.96', $prefix));?></td>
	    <th><input type="radio" name="target" value="0.96_<?php echo $prefix;?>" <?php echo $ckeckedString;?>/></th>
      </tr>
<?php
					$ckeckedString = '';
				}
			}
		}
?>
    </table>
    </div>
  <div id="navigation">
    <a href="#" onclick="window.history.back()" title="<?php echo _t('이전');?>"><img src="style/setup/icon_prev.gif" width="74" height="24" alt="<?php echo _t('이전');?>" /></a>
    <a href="#" onclick="if (confirm('<?php echo _t('삭제하시겠습니까?');?>') && confirm('<?php echo _t('정말 삭제하시겠습니까?');?>')) next(); return false;" title="<?php echo _t('다음');?>"><img src="style/setup/icon_next.gif" width="74" height="24" alt="<?php echo _t('다음');?>" /></a>
  </div>
  </div>
<?php
	}
	else if ($step == 205) {
?>
  <input type="hidden" name="step" value="1" />
  <div id="inner">
    <h2><span class="step"><?php echo _f('%1단계', 5);?></span> : <?php echo _t('선택된 테이블을 삭제하고 있습니다.');?></h2>
    <div id="userinput">
    <table id="info">
      <tr>
        <th><?php echo _t('식별자');?></th>
        <th><?php echo _t('버전');?></th>
        <th><?php echo _t('테이블');?></th>
     </tr>
<?php
		list($version, $prefix) = explode('_', $_POST['target'], 2);
		$result = false;
		if (checkTables($version, $prefix)) {
?>
      <tr>
        <th><?php echo $prefix;?></th>
        <th><?php echo $version;?></th>
        <td><?php echo implode(', ', getTables($version, $prefix));?></td>
      </tr>
<?php
			$result = @mysql_query('DROP TABLE ' . implode(', ', getTables($version, $prefix)));
		}
?>
    </table>
	<p><?php echo ($result ? _t('삭제하였습니다.') : '<span style="color:red">' . _t('삭제하지 못했습니다.') . '</span>');?></p>
    </div>
  <div id="navigation">
    <a href="#" onclick="window.history.back()" title="<?php echo _t('이전');?>"><img src="style/setup/icon_prev.gif" width="74" height="24" alt="<?php echo _t('이전');?>" /></a>
    <a href="#" onclick="next(); return false;" title="<?php echo _t('다음');?>"><img src="style/setup/icon_next.gif" width="74" height="24" alt="<?php echo _t('다음');?>" /></a>
  </div>
  </div>
<?php
	}
}
 
function drowSetLang( $currentLang = "ko"  ,$curPosition = 'Norm' /*or 'Err'*/ ){ 
	if( Locale::setDirectory('language'))   $availableLanguages =   Locale::getSupportedLocales(); 
	else return false; 
?> 
Select Default Language : <select name="Lang" id = "Lang" onchange= "current();" > 
<?php      foreach( $availableLanguages as $key => $value) 
			print('<option value="'.$key.'" '.( $key == $currentLang ? ' selected="selected" ' : '').' >'.$value.'</option>'); 
?></select> 
<?php 
	return true;
}

function stripPath($path) {
	$path = rtrim($path, '/');
	while (strpos($path, '//') !== false)
		$path = str_replace('//', '/', $path);
	return $path;
}

function testMyself($host, $path, $port) {
    $socket = @fsockopen($host, $port, $errno, $errstr, 10);
    if ($socket === false)
        return false;
    fputs($socket, "GET $path HTTP/1.1\r\n");
    fputs($socket, "Host: $host\r\n");
    fputs($socket, "User-Agent: Mozilla/4.0 (compatible; Tattertools 1.1 Setup)\r\n");
    fputs($socket, "Connection: close\r\n");
    fputs($socket, "\r\n");
    $response = '';
    while (!feof($socket))
        $response .= fgets($socket, 128);
    fclose($socket);
    return strstr($response, getFingerPrint()) ? true : false;
}

function getFingerPrint() {
    return md5($_SERVER['SERVER_SOFTWARE'] . $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] . phpversion());
}

function checkTables($version, $prefix) {
	if (!$tables = getTables($version, $prefix))
		return false;
	foreach ($tables as $table) {
		if ($result = mysql_query("DESCRIBE $table"))
			mysql_free_result($result);
		else 
			return false;
	}
	return true;
}

function getTables($version, $prefix) {
	switch ($version) {
		case '1.1':
			return array("{$prefix}Attachments", "{$prefix}BlogSettings", "{$prefix}BlogStatistics", "{$prefix}Categories", "{$prefix}Comments", "{$prefix}CommentsNotified", "{$prefix}CommentsNotifiedQueue", "{$prefix}CommentsNotifiedSiteInfo", "{$prefix}DailyStatistics", "{$prefix}Entries", "{$prefix}FeedGroupRelations", "{$prefix}FeedGroups", "{$prefix}FeedItems", "{$prefix}FeedReads", "{$prefix}Feeds", "{$prefix}FeedSettings", "{$prefix}FeedStarred", "{$prefix}Filters", "{$prefix}Links", "{$prefix}Plugins", "{$prefix}RefererLogs", "{$prefix}RefererStatistics", "{$prefix}ReservedWords", "{$prefix}ServiceSettings", "{$prefix}Sessions", "{$prefix}SessionVisits", "{$prefix}SkinSettings", "{$prefix}TagRelations", "{$prefix}Tags", "{$prefix}TrackbackLogs", "{$prefix}Trackbacks", "{$prefix}Users", "{$prefix}UserSettings");
		case '1.0.2':
			return array("{$prefix}Attachments", "{$prefix}BlogSettings", "{$prefix}BlogStatistics", "{$prefix}Categories", "{$prefix}Comments", "{$prefix}CommentsNotified", "{$prefix}CommentsNotifiedQueue", "{$prefix}CommentsNotifiedSiteInfo", "{$prefix}DailyStatistics", "{$prefix}Entries", "{$prefix}FeedGroupRelations", "{$prefix}FeedGroups", "{$prefix}FeedItems", "{$prefix}FeedReads", "{$prefix}Feeds", "{$prefix}FeedSettings", "{$prefix}FeedStarred", "{$prefix}Filters", "{$prefix}Links", "{$prefix}Personalization", "{$prefix}Plugins", "{$prefix}RefererLogs", "{$prefix}RefererStatistics", "{$prefix}ReservedWords", "{$prefix}Sessions", "{$prefix}SessionVisits", "{$prefix}SkinSettings", "{$prefix}TagRelations", "{$prefix}Tags", "{$prefix}TrackbackLogs", "{$prefix}Trackbacks", "{$prefix}Users");
		case '1.0.0':
			return array("{$prefix}Attachments", "{$prefix}BlogSettings", "{$prefix}BlogStatistics", "{$prefix}Categories", "{$prefix}Comments", "{$prefix}CommentsNotified", "{$prefix}CommentsNotifiedQueue", "{$prefix}CommentsNotifiedSiteInfo", "{$prefix}ContentFilters", "{$prefix}DailyStatistics", "{$prefix}Entries", "{$prefix}FeedGroupRelations", "{$prefix}FeedGroups", "{$prefix}FeedItems", "{$prefix}FeedOwners", "{$prefix}FeedReads", "{$prefix}Feeds", "{$prefix}FeedSettings", "{$prefix}FeedStarred", "{$prefix}GuestFilters", "{$prefix}HostFilters", "{$prefix}Links", "{$prefix}MonthlyStatistics", "{$prefix}Personalization", "{$prefix}Plugins", "{$prefix}RefererLogs", "{$prefix}RefererStatistics", "{$prefix}ReservedWords", "{$prefix}Sessions", "{$prefix}SessionVisits", "{$prefix}SkinSettings", "{$prefix}TagRelations", "{$prefix}Tags", "{$prefix}TrackbackLogs", "{$prefix}Trackbacks", "{$prefix}URLFilters", "{$prefix}Users");
		case '1.0.b2':
			return array("{$prefix}Attachments", "{$prefix}BlogSettings", "{$prefix}BlogStatistics", "{$prefix}Categories", "{$prefix}ContentFilters", "{$prefix}DailyStatistics", "{$prefix}Entries", "{$prefix}GuestFilters", "{$prefix}HostFilters", "{$prefix}Links", "{$prefix}MonthlyStatistics", "{$prefix}RefererLogs", "{$prefix}RefererStatistics", "{$prefix}Replies", "{$prefix}ReservedWords", "{$prefix}ServiceSetting", "{$prefix}SessionVisits", "{$prefix}Sessions", "{$prefix}SkinSettings", "{$prefix}TagRelations", "{$prefix}Tags", "{$prefix}TrackbackLogs", "{$prefix}Trackbacks", "{$prefix}URLFilters", "{$prefix}Users");
		case '0.97':
			return array("t3_{$prefix}_10ofmg", "t3_{$prefix}_10ofmg_cnt_log", "t3_{$prefix}_10ofmg_count", "t3_{$prefix}_10ofmg_ct1", "t3_{$prefix}_10ofmg_ct2", "t3_{$prefix}_10ofmg_files", "t3_{$prefix}_10ofmg_guest", "t3_{$prefix}_10ofmg_guest_icon", "t3_{$prefix}_10ofmg_guest_reply", "t3_{$prefix}_10ofmg_keyword", "t3_{$prefix}_10ofmg_keyword_files", "t3_{$prefix}_10ofmg_link", "t3_{$prefix}_10ofmg_notice_log", "t3_{$prefix}_10ofmg_notice_queue", "t3_{$prefix}_10ofmg_referlog", "t3_{$prefix}_10ofmg_referstat", "t3_{$prefix}_10ofmg_reply", "t3_{$prefix}_10ofmg_rss", "t3_{$prefix}_10ofmg_rss_group", "t3_{$prefix}_10ofmg_rss_item", "t3_{$prefix}_10ofmg_setting", "t3_{$prefix}_10ofmg_spam_filter", "t3_{$prefix}_10ofmg_tag", "t3_{$prefix}_10ofmg_tblog", "t3_{$prefix}_10ofmg_trackback");
		case '0.96':	
			return array("t3_{$prefix}", "t3_{$prefix}_cnt_log", "t3_{$prefix}_count", "t3_{$prefix}_ct1", "t3_{$prefix}_ct2", "t3_{$prefix}_files", "t3_{$prefix}_guest", "t3_{$prefix}_guest_icon", "t3_{$prefix}_guest_reply", "t3_{$prefix}_keyword", "t3_{$prefix}_keyword_files", "t3_{$prefix}_link", "t3_{$prefix}_referlog", "t3_{$prefix}_referstat", "t3_{$prefix}_reply", "t3_{$prefix}_rss", "t3_{$prefix}_rss_group", "t3_{$prefix}_rss_item", "t3_{$prefix}_setting", "t3_{$prefix}_tblog", "t3_{$prefix}_trackback");
	}
	return null;
}
?>
  </div><!-- inner -->
  </form>
</div><!-- container -->
</body>
</html>
