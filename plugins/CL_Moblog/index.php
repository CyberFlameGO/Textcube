<?
requireComponent( "Needlworks.Mail.Pop3" );

class Moblog
{
	function Moblog( $username, $password, $host, $port = 110, $ssl = 0, 
		$userid = 1, $minsize = 10240, $visibility = 2 )
	{
		global $pop3logs;
		if( !isset($debugLogs) ) {
			$pop3logs = array();
		}
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
		$this->ssl = $ssl;
		$this->userid = $userid;
		$this->minsize = $minsize;
		$this->recentCount = 100;
		$this->visibility = array( "private", "protected", "public", "syndicated" );
		$this->visibility = $this->visibility[$visibility];

		$this->pop3 = new Pop3();
		$this->pop3->setLogger( array(&$this,'log') );
		$this->pop3->setStatCallback( array(&$this,'statCallback') );
		$this->pop3->setUidFilter( array(&$this,'checkUid') );
		$this->pop3->setSizeFilter( array(&$this,'checkSize') );
		$this->pop3->setRetrCallback( array(&$this,'retrieveCallback') );

		$this->uidl_file = ROOT.DS."cache".DS."pop3uidl.txt";

		if( file_exists( $this->uidl_file ) ) {
			$this->stored_uidl = file_get_contents( $this->uidl_file );
		} else {
			$this->stored_uidl = '';
		}

		global $service;
		$fallback_charsets = array( 'ko' => 'euc-kr' );
		if( isset( $fallback_charsets[$service['language']] ) ) {
			$this->pop3->setFallbackCharset( $fallback_charsets[$service['language']] );
		}
	}

	function log($msg)
	{
		$f = fopen( ROOT.DS."cache".DS."moblog.txt", "a" );
		fwrite( $f, date('Y-m-d H:i:s')." $msg\r\n" );
		fclose($f);
		if( $msg[0] == '*' ) {
			global $pop3logs;
			array_push( $pop3logs, substr($msg,2) );
		}
	}

	function saveUidl()
	{
		$f = fopen( $this->uidl_file, "w" );
		fwrite( $f, $this->stored_uidl );
		fclose($f);
		return true;
	}

	function check()
	{
		if( !$this->pop3->connect( $this->host, $this->port, $this->ssl ) ) {
			$this->log( "* "._t("접속 실패")." : ".$this->host.":".$this->port.($this->ssl?"(SSL)":"(no SSL)") );
			return false;
		}
		$this->log( "* "._t("접속 성공")." : ".$this->host.":".$this->port.($this->ssl?"(SSL)":"(no SSL)") );
		if( !$this->pop3->authorize( $this->username, $this->password ) ) {
			$this->log( "* "._t("인증 실패") );
			return false;
		}
		$this->log( "* "._t("인증 성공") );

		$this->pop3->run();

		if( !$this->pop3->quit() ) {
			return false;
		}
		return true;
	}

	function appendUid( $uid )
	{
		$today = date( 'Y-m-d H:i:s' );
		$this->stored_uidl .= "[$uid] $today\r\n";
		$this->saveUidl();
	}

	function checkUid( $uid, $number )
	{
		$ret = !!strstr( $this->stored_uidl, "[$uid]" );
		if( $ret ) {
			$this->log( "Msg $number: "._t("이미 확인한 메일")." : $uid" );
		}
		return $ret;
	}

	function checkSize( $size, $number, $total )
	{
		if( $number < $total - $this->recentCount ) {
			return true;
		}
		$this->log( "Msg $number: "._t("메일크기가 작음")." : $size" );
		return $size < $this->minsize;
	}

	function isMms( & $mail )
	{
		if( !empty($mail['mms']) ) {
			return true;
		}
		if( isset($mail['return_path']) && strstr( $mail['return_path'], 'mms' ) ) {
			/* KTF: ktfmms, SKT: vmms */
			return true;
		}
		return false;
	}

	function _getDecoratedContent( & $mail, $docid ) {
			$alt = htmlentities($mail['attachments'][0]['filename'],ENT_QUOTES,'utf-8');
			$content = '<p>$TEXT</p><p>[##_1C|$FILENAME|width="$WIDTH" height="$HEIGHT" alt="'.$alt.'"|_##]</p>';
			$text = "<h3 id=\"$docid\">".(empty($mail['subject']) ? $docid : $mail['subject'])."</h3>\r\n";
			$text .= isset($mail['text']) ? $mail['text'] : '';
			return str_replace( '$TEXT', $text , $content );
	}

	function statCallback( $total, $totalsize )
	{
		$this->log( "* ".sprintf( _t("총 %d개의 메시지"),$total) );
		$lastStat = getBlogSetting( 'MmsPop3stat', '' );
		$stat = "$total $totalsize";
		if( $stat == $lastStat ) {
			$this->log( "* "._t("새로운 메시지가 없습니다") );
			return false;
		}
		setBlogSetting( 'MmsPop3stat', $stat );
		return true;
	}

	function retrieveCallback( $lines, $uid )
	{
		$slogan = date( "Y-m-d" );
		$docid = date( "H:i:s" );
		$this->appendUid( $uid );
		$mail = $this->pop3->parse( $lines );
		if( in_array( $mail['subject'], array( '제목없음' ) ) ) {
			$mail['subject'] = '';
		}
		if( !$this->isMms($mail) ) {
			$this->log( "* "._t("메일").": " . $mail['subject'] . " [SKIP]" );
			return false;
		}
		if( empty($mail['attachments']) ) {
			$this->log( "* "._t("메일").": " . $mail['subject'] . " [SKIP]" );
			return false;
		}
		requireComponent( "Textcube.Data.Post" );

		$post = new Post();

		if( $post->open( "slogan = '$slogan'" ) ) {
			$post->content .= $this->_getDecoratedContent( $mail, $docid );
			$post->modified = time();
			$post->visibility = $this->visibility;
		} else {
			$post->title = empty($mail['subject']) ? $slogan : $mail['subject'];
			$post->userid = $this->userid;
			$post->content = $this->_getDecoratedContent( $mail, $docid );
			$post->contentFormatter = getDefaultFormatter();
			$post->contentEditor = getDefaultEditor();
			$post->created = time();
			$post->acceptComment = true;
			$post->acceptTrackback = true;
			$post->visibility = $this->visibility;
			$post->published = time();
			$post->modified = time();
			$post->slogan = $slogan;
			if( !$post->add() ) {
				$this->log( "* "._t("메일").": " . $mail['subject'] . " [ERROR]" );
				$this->log( _t("실패: 글을 추가하지 못하였습니다")." : " . $post->error );
				return false;
			}
		}

		$this->log( _t("첨부")." : {$mail['attachments'][0]['filename']}" );
		requireModel( "blog.api" );
		$att = api_addAttachment( getBlogId(), $post->id, 
					array( 
							'name' => $mail['attachments'][0]['filename'], 
							'content' => $mail['attachments'][0]['decoded_content'], 
							'size' => $mail['attachments'][0]['length']
					) 
			);
		if( !$att ) {
			$this->log( "* "._t("메일").": " . $mail['subject'] . " [ERROR]" );
			$this->log( _t("실패: 첨부파일을 추가하지 못하였습니다")." : " . $post->error );
			return false;
		}
		$post->content = str_replace( '$FILENAME', $att['name'], $post->content );
		$post->content = str_replace( '$WIDTH', $att['width'], $post->content );
		$post->content = str_replace( '$HEIGHT', $att['height'], $post->content );
		if( !$post->update() ) {
			$this->log( "* "._t("메일").": " . $mail['subject'] . " [ERROR]" );
			$this->log( _t("실패: 글을 추가하지 못하였습니다").". : " . $post->error );
			return false;
		}
		$this->log( "* "._t("메일").": " . $mail['subject'] . " [OK]" );
		return true;
	}
}

function moblog_check()
{
	if( isset($_GET['check']) && $_GET['check'] == 1 ) {
		echo "<style>.emplog{color:red}.oklog{color:blue}</style>";
		echo "<ul>";
		echo join( "", 
			array_map(
				create_function( '$li', 'return preg_match( "/^\S+\s+\S+\s+\*/", $li ) ? 
						(preg_match( "/\[OK\]$/", $li ) ? "<li class=\"oklog\">$li</li>" : "<li class=\"emplog\">$li</li>") 
						: "<li>$li</li>";'), 
				split( "\n",file_get_contents(ROOT.DS."cache".DS."moblog.txt"))
			)
		);
		echo "</ul>";
		exit;
	}

	$pop3host = getBlogSetting( 'MmsPop3Host', 'localhost' );
	$pop3port = getBlogSetting( 'MmsPop3Port', 110 );
	$pop3ssl = getBlogSetting( 'MmsPop3Ssl', 0 );
	$pop3username = getBlogSetting( 'MmsPop3Username', '' );
	$pop3password = getBlogSetting( 'MmsPop3Password', '' );
	$pop3minsize = getBlogSetting( 'MmsPop3MinSize', 0 );
	$pop3minsize *= 1024;
	$pop3fallbackuserid = getBlogSetting( 'MmsPop3Fallbackuserid', 1 );
	$pop3visibility = getBlogSetting( 'MmsPop3Visibility', '2' );

	header( "Content-type: text/html; charset:utf-8" );
	echo "<html><body><ul><li>";
	$moblog = new Moblog( $pop3username, $pop3password, $pop3host, $pop3port, $pop3ssl, $pop3fallbackuserid, $pop3minsize, $pop3visibility );
	$moblog->log( "--BEGIN--" );
	$moblog->check();
	$moblog->log( "-- END --" );
	if( Acl::check( 'group.administrators' ) ) {
		global $pop3logs;
		print join("</li><li>",$pop3logs);
	}
	echo "</li></ul></body></html>";
	return true;
}

function moblog_manage()
{
	global $blogURL;
	requireModel("common.setting");
	if( Acl::check('group.administrators') && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
		setBlogSetting( 'MmsPop3Email', $_POST['pop3email'] );
		setBlogSetting( 'MmsPop3Host', $_POST['pop3host'] );
		setBlogSetting( 'MmsPop3Port', $_POST['pop3port'] );
		setBlogSetting( 'MmsPop3Ssl', !empty($_POST['pop3ssl'])?1:0 );
		setBlogSetting( 'MmsPop3Username', $_POST['pop3username'] );
		setBlogSetting( 'MmsPop3Password', $_POST['pop3password'] );
		setBlogSetting( 'MmsPop3Visibility', $_POST['pop3visibility'] );
		setBlogSetting( 'MmsPop3Fallbackuserid', getUserId() );
		setBlogSetting( 'MmsPop3MinSize', 0 );
	}
	$pop3email = getBlogSetting( 'MmsPop3Email', '' );
	$pop3host = getBlogSetting( 'MmsPop3Host', 'localhost' );
	$pop3port = getBlogSetting( 'MmsPop3Port', 110 );
	$pop3ssl = getBlogSetting( 'MmsPop3Ssl', 0 ) ? " checked=1 " : "";
	$pop3username = getBlogSetting( 'MmsPop3Username', '' );
	$pop3password = getBlogSetting( 'MmsPop3Password', '' );
	$pop3minsize = getBlogSetting( 'MmsPop3MinSize', 0 );
	$pop3fallheadercharset = getBlogSetting( 'MmsPop3Fallbackcharset', 'euc-kr' );
	$pop3visibility = getBlogSetting( 'MmsPop3Visibility', '2' );
?>
						<hr class="hidden" />
						
						<div id="part-setting-editor" class="part">
							<h2 class="caption"><span class="main-text"><?php echo _t('MMS 메시지 확인용 메일 환경 설정');?></span></h2>
<?php if( !Acl::check( "group.administrators" ) ): ?>
								<div id="editor-section" class="section">
										<dl id="formatter-line" class="line">
											<dt><span class="label"><?php echo _t('MMS 수신 이메일');?></span></dt>
											<dd>
											<?php if( empty($pop3email) ): ?>
												<?php echo _t('비공개') ?>
											<?php else: ?>
												<?php echo $pop3email;?>
											<?php endif ?>
											</dd>
											<dd>
											<?php if( empty($pop3email) ): ?>
												<?php echo _t('MMS 메시지를 보내어 연동할 이메일이 공개되지 않았습니다'); ?>
											<?php else: ?>
												<?php echo _t('이동전화를 이용하여 위 메일로 MMS 메시지를 보내면 블로그에 게시됩니다'); ?>
											<?php endif ?>
											</dd>
										</dl>
								</div>
<?php else: ?>
							<form id="editor-form" class="data-inbox" method="post" action="<?php echo $blogURL;?>/owner/plugin/adminMenu?name=CL_Moblog/moblog_manage">
								<div id="editor-section" class="section">
									<fieldset class="container">
										<legend><?php echo _t('MMS 환경을 설정합니다');?></legend>
										
										<dl id="formatter-line" class="line">
											<dt><span class="label"><?php echo _t('MMS용 이메일');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3email" value="<?php echo $pop3email;?>" /> 
												<?php echo _t('(필진에 공개 됩니다)'); ?>
											</dd>
										</dl>
										<dl id="formatter-line" class="line">
											<dt><span class="label"><?php echo _t('POP3 호스트');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3host" value="<?php echo $pop3host;?>" />
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('POP3 포트');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3port" value="<?php echo $pop3port;?>" />
												<input type="checkbox" name="pop3ssl" value="1" <?php echo $pop3ssl;?> /> SSL
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('POP3 아이디');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3username" value="<?php echo $pop3username;?>" />
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('POP3 비밀번호');?></span></dt>
											<dd>
												<input type="password" style="width:14em" class="input-text" name="pop3password" value="<?php echo $pop3password;?>" />
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('공개여부');?></span></dt>
											<dd>
													<span id="status-private" class="status-private"><input type="radio" id="visibility_private" class="radio" name="pop3visibility" value="0"<?php echo ($pop3visibility == 0 ? ' checked="checked"' : '');?> /><label for="visibility_private"><?php echo _t('비공개');?></label></span>
													<span id="status-protected" class="status-protected"><input type="radio" id="visibility_protected" class="radio" name="pop3visibility" value="1"<?php echo ($pop3visibility == 1 ? ' checked="checked"' : '');?> /><label for="visibility_protected"><?php echo _t('보호');?></label></span>
													<span id="status-public" class="status-public"><input type="radio" id="visibility_public" class="radio" name="pop3visibility" value="2"<?php echo ($pop3visibility == 2 ? ' checked="checked"' : '');?> /><label for="visibility_public"><?php echo _t('공개');?></label></span>
													<span id="status-syndicated" class="status-syndicated"><input type="radio" id="visibility_syndicated" class="radio" name="pop3visibility" value="3"<?php echo ($pop3visibility == 3 ? ' checked="checked"' : '');?> /><label for="visibility_syndicated"><?php echo _t('발행');?></label></span>
											</dd>
										</dl>
									<div class="button-box">
										<input type="submit" class="save-button input-button wide-button" value="<?php echo _t('저장하기');?>"  />
									</div>
								</div>
							</form>
							<h2 class="caption"><span class="main-text"><?php echo _t('MMS 메시지 테스트');?></span></h2>
								<div id="editor-section" class="section">
									<dl id="formatter-line" class="line">
										<dt><span class="label"><?php echo _t('명령');?></span></dt>
										<dd>
											<input type="button" class="save-button input-button wide-button" value="<?php echo _t('로그보기');?>"  
												onclick="document.getElementById('pop3_debug').src='<?php echo $blogURL?>/plugin/moblog/check?check=1&rnd='+((new Date()).getTime())" />
											<input type="button" class="save-button input-button wide-button" value="<?php echo _t('시험하기');?>" 
												onclick="document.getElementById('pop3_debug').src='<?php echo $blogURL?>/plugin/moblog/check?rnd='+((new Date()).getTime())" />
										</dd>
									</dl>
								</div>
								<iframe src="about:blank" class="debug_message" id="pop3_debug" style="width:100%; height:400px">
								</iframe>
<?php endif ?>
						</div>
<?php
}
?>
