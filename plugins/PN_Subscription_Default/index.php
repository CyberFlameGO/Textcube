<?php
/* Subscription statistics plugin for Textcube 1.1
   ----------------------------------
   Version 1.5
   Needlworks development team.

   Creator          : inureyes
   Maintainer       : gendoh, inureyes, graphittie

   Created at       : 2006.9.21
   Last modified at : 2007.5.5
 
 This plugin shows RSS subscription statistics on administration menu.
 For the detail, visit http://forum.tattersite.com/ko


 General Public License
 http://www.gnu.org/licenses/gpl.html

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

*/
function PN_Subscription_Default()
{
	global $pluginMenuURL, $pluginSelfParam, $totalSubscribers, $updatedSubscribers;
	requireComponent( "Textcube.Model.Statistics");
	requireComponent( "Textcube.Function.misc");
	$blogid = getBlogId();
	$temp = getSubscriptionStatistics($blogid);
	$aggregatorInfo = organizeAggregatorInfo($temp);
?>
						<script type="text/javascript">
							//<![CDATA[
								window.addEventListener("load", execLoadFunction, false);
								
								function execLoadFunction() {
									//removeItselfById('log-pages-submit');
								}
							//]]>
						</script>
						
						<div id="part-statistics-total" class="part">
							<h2 class="caption"><span class="main-text">전체 피드 통계</span></h2>
							<dl class="data-inbox">
								<dt class="number"><span class="text">전체 구독자수</span></dt>
								<dd class="number"><span class="text"><?php echo $totalSubscribers;?> 명</span></dd>
								<dt class="aggregator"><span class="text">구독기</span></dt>
								<dd class="aggregator"><span class="text"><?php echo sizeof($aggregatorInfo);?> 종류의 구독기 및 크롤러가 구독중입니다.</span></dd>
								<dt class="lastRSSupdate"><span class="text">최종 RSS 갱신일</span></dt>
								<dd class="lastRSSupdate"><span class="text"><?php echo misc::getBlogSetting('LatestRSSrefresh',null)!=null ? Timestamp::format5(misc::getBlogSetting('LatestRSSrefresh',null)) : '정보가 갱신되지 않았습니다';?></dd>
								<dt class="updatedAggregators"><span class="text">이후 갱신된 RSS 구독기</span></dt>
								<dd class="updatedAggregators"><span class="text"><?php echo $updatedSubscribers;?></span></dd>
							</dl>
						</div>

						<hr class="hidden" />

						<div id="part-statistics-rank" class="part">
							<h2 class="caption"><span class="main-text">피드 구독 순위</span></h2>
							<div class="main-explain-box">
								<p class="explain">
									크롤러에 구독자 수 정보를 넣지 않는 웹 RSS 리더의 경우 정상적인 구독자수를 판별할 수 없습니다.<br />
									강조 표시된 구독기는 검색 엔진 및 메타 프로그램을 의미합니다.
								</p>
							</div>
							<table class="data-inbox" cellspacing="0" cellpadding="0">
								<thead>
									<tr>
										<th class="rank"><span class="text">순위</span></th>
										<th class="aggregator"><span class="text">구독기</span></th>
										<th class="count"><span class="text">구독자 수</span></th>
										<th class="subscribed"><span class="text">구독 시작일</span></th>
										<th class="referred"><span class="text">최근 구독일</span></th>
									</tr>
								</thead>
								<tbody>
<?php
	$i = 0;
	foreach ($aggregatorInfo as $agent => $info) {
		$className = ($i % 2) == 1 ? 'even-line' : 'odd-line';
		$className .= ($i == sizeof($aggregatorInfo) - 1) ? ' last-line' : '';
?>
									<tr class="<?php echo $className;?> inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
										<td class="rank"><?php echo $i + 1;?></td>
										<td class="aggregator"><?php 
		if($info['isRobot'])
			echo '<span class="robot">'.$agent.'</span>';
		else echo $agent;
?></td>
										<td class="count"><?php echo $info["subscribers"];?>명</td>
										<td class="subscribed"><?php echo Timestamp::formatDate($info["subscribed"]);?></td>
										<td class="referred"><?php echo Timestamp::formatDate($info["referred"]);?></td>
									</tr>
<?php
		$i++;
	}
?>
								</tbody>
							</table>
						</div>
						
						<div class="clear"></div>
<?php 
}

function getAggregatorName($useragent)
{
	if($useragent=='') return '알 수 없는 구독기';
	$agentPattern = array(
		'Bloglines' => 'Bloglines',
		'Allblog.net' => '올블로그',
		'HanRSS' => '한RSS',
		'Netvibes' => 'Netvibes',
		'SharpReader' => 'Sharp Reader',
		'BlogBridge' => 'Blog Bridge',
		'Firefox' => 'Firefox 라이브북마크',
		'Sage' => 'Sage (Firefox 확장)',
		'Google Desktop' => '구글 데스크탑',
		'RSSOwl' => 'RSS Owl',
		'Eolin' => '텍스트큐브 리더',
		'Safari' => '사파리',
		'Feedfetcher-Google' => '구글 feedfetcher',
		'RssBandit' => 'RSS Bandit',
		'Yahoo! Slurp' => 'Yahoo! Slurp',
		'Mozilla/4.0 (compatible; MSIE 7.0' => 'MS 익스플로러 7',
		'FeedDemon' => 'FeedDemon',
		'UniversalFeedParser' => 'Universal Feed Parser',
		'nhn/1noon' => '첫눈',
		'MSIE 6.0' => 'MS 익스플로러 6',
		'YeonMo' => '연모',
		'RMOM' => '요줌',
		'msnbot' => 'MSN 검색엔진',
		'FeedOnFeeds' => 'Feed On Feeds Personal aggregator',
		'Technoratibot' => '테크노라티',
		'sproose' => 'sproose 봇'
	);
	$declinePattern = array(
		'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'=>'Internet Explorer 5.01'
	);
	foreach ($agentPattern as $agentName => $realname)
		if(strpos($useragent,$agentName)!==false) return $realname;
	foreach ($declinePattern as $agentName => $realname)
		if(strpos($useragent,$agentName)!==false) return false;
	return $useragent;
}

function getNumberOfSubscribers($useragent)
{
	$agentPattern = array(
		'Bloglines' => 'subscribers',
		'HanRSS' => 'subscribers',
		'Netvibes' => 'subscribers'
	);
	foreach ($agentPattern as $agentName => $keyword)
		if(preg_match('/([0-9]+)\s*'.$keyword.'/',$useragent,$matches)) return $matches[1];
	return 1;
}

function robotChecker($useragent)
{
	$robotPattern = array(
		'Allblog.net' => 1,
		'nhn/1noon' => 1,
		'Feedfetcher-Google' => 1,
		'Yahoo! Slurp' => 1,
		'RMOM' => 1,
		'msnbot' => 1,
		'Technoratibot' => 1,
		'sproose' => 1
	);
	foreach ($robotPattern as $agentName => $isRobot)
		if((strpos($useragent,$agentName)!==false)&&($isRobot)) return true;
	return false;
}

function organizeAggregatorInfo($info)
{
	requireComponent( "Textcube.Function.misc");
	global $totalSubscribers, $updatedSubscribers;
	$aggregatorInfo = array();
	$totalSubscribers = 0;
	$updatedSubscribers = 0;
	$latestUpdatedTime = misc::getBlogSetting('LatestRSSrefresh',null);
	for ($i=0; $i<sizeof($info); $i++) {
		$record = $info[$i];
		$aggregatorName = getAggregatorName($record['useragent']);
		if(!$aggregatorName) continue;
		$subscribers = getNumberOfSubscribers($record['useragent']);
		$startDate = $record['subscribed'];
		$referred = $record['referred'];
		if(time()- $referred > 259200) continue;
		if(array_key_exists($aggregatorName,$aggregatorInfo)) {
			if(($subscribers > $aggregatorInfo[$aggregatorName]['subscribers'])&&($subscribers!==1)) {
				$totalSubscribers -= $aggregatorInfo[$aggregatorName]['subscribers'];
				$totalSubscribers += $subscribers;
				if(isset($latestUpdatedTime) && $latestUpdatedTime - $referred < 0) {
					$updatedSubscribers -=$aggregatorInfo[$aggregatorName]['subscribers'];
					$updatedSubscribers += $subscribers;
				}
				$aggregatorInfo[$aggregatorName]['subscribers'] = $subscribers;
			} else if($subscribers==1) {
				$aggregatorInfo[$aggregatorName]['subscribers'] += $subscribers;
				$totalSubscribers += $subscribers;
				if(isset($latestUpdatedTime) && $latestUpdatedTime - $referred < 0) {
					$updatedSubscribers += $subscribers;
				}
			}

			if($aggregatorInfo[$aggregatorName]['subscribed'] > $startDate)
				$aggregatorInfo[$aggregatorName]['subscribed'] = $startDate;
			if($aggregatorInfo[$aggregatorName]['referred'] < $referred)
				$aggregatorInfo[$aggregatorName]['referred'] = $referred;
		}
		else {
			$aggregatorInfo[$aggregatorName]['subscribers'] = $subscribers;
			$aggregatorInfo[$aggregatorName]['subscribed'] = $startDate;
			$aggregatorInfo[$aggregatorName]['referred'] = $referred;
			$totalSubscribers += $subscribers;
		}
		$aggregatorInfo[$aggregatorName]['isRobot'] = robotChecker($record['useragent']);
	}
	arsort($aggregatorInfo);
	return $aggregatorInfo;
}

function organizeRobotInfo($info)
{
}

function getSubscriptionStatistics($blogid) {
	global $database;
	$statistics = array();
	if ($result = mysql_query("select ip, host, useragent, subscribed, referred from {$database['prefix']}SubscriptionStatistics where blogid = $blogid order by referred desc")) {
		while ($record = mysql_fetch_array($result))
			array_push($statistics, $record);
	}
	return $statistics;
}

function getSubscriptionLogsWithPage($page, $count) {  
	global $database;
	$blogid = getBlogId();
	requireComponent( "Textcube.Model.Statistics");
	return Statistics::fetchWithPaging("SELECT ip, host, useragent, referred FROM {$database['prefix']}SubscriptionLogs WHERE blogid = $blogid ORDER BY referred DESC", $page, $count);  
}  

function getSubscriptionLogs() {
	global $database;
	$blogid = getBlogId();
	return DBQuery::queryAll("SELECT ip, host, useragent, referred FROM {$database['prefix']}SubscriptionLogs WHERE blogid = $blogid ORDER BY referred DESC LIMIT 1000");
}

function updateSubscriptionStatistics($target, $mother) {
	global $database, $blogURL;
	$blogid = getBlogId();
	$period = Timestamp::getDate();
	requireComponent('Textcube.Data.Filter');
	if (Filter::isFiltered('ip', $_SERVER['REMOTE_ADDR']))
		return;
	$ip = mysql_tt_escape_string($_SERVER['REMOTE_ADDR']);
	$host = mysql_tt_escape_string(isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '');
	$useragent = mysql_tt_escape_string(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
	mysql_query("insert into {$database['prefix']}SubscriptionLogs values($blogid, '$ip', '$host', '$useragent', UNIX_TIMESTAMP())");
	mysql_query("delete from {$database['prefix']}SubscriptionLogs where referred < UNIX_TIMESTAMP() - 604800");
	if (!mysql_query("update {$database['prefix']}SubscriptionStatistics set referred = UNIX_TIMESTAMP() where blogid = $blogid and ip = '$ip' and host = '$host' and useragent = '$useragent'") || (mysql_affected_rows() == 0))
		mysql_query("insert into {$database['prefix']}SubscriptionStatistics values($blogid, '$ip', '$host', '$useragent', UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
	return $target;
}

function PN_Subscription_setTime($target) {
	requireComponent( "Textcube.Function.misc");
	misc::setBlogSetting('LatestRSSrefresh',time());
	return true;
}
?>
