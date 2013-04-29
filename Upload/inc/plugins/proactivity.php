<?php
/**
 * Pro Activity 1.1
 * Logging and reporting forum activity like social networks.
 *  
 * By Jared Williams
 * Copyright 2012
 * 
 * Website: http://www.jazzza001.com
 *  
 * Please do not redistribute or sell this plugin.
 */


//This file should be used as a plugin!
if(!defined("IN_MYBB")) {
	die("This file cannot be accessed directly.");
}


//Make the function available to all
$plugins->add_hook('global_start',		'proactivity_generate_dropdown');


//For some reason we need to declare it global first to make it work...
global $hooks;

//Hook into as many actions as we can...
$hooks = array(
	//Members
	'member_do_register_end'						=> array('type' => 'user',			'get' => 'signup',	'isallowed' => ''),
	'member_activate_accountactivated'	=> array('type' => '',															'isallowed' => ''),
		
	'member_do_login_end'						=> array('type' => 'login',			'get' => 'last',	'isallowed' => ''),						//TODO: Is user hidden?
	'member_logout_end'							=> array('type' => '',														'isallowed' => ''),						//TODO: Is user hidden?
	
	//Reputation
	//'reputation_do_add_end'					=> array('type' => 'reputation',			'get' => 'last',			'isallowed' => ''),		//TODO: Buggy - needs fixing!
		
	//User CP
	'usercp_do_profile_end'					=> array('type' => '',						'isallowed' => ''),					//TODO: User profile setting.
	'usercp_do_changename_end'			=> array('type' => '',						'isallowed' => ''),
	'usercp_do_editsig_end'					=> array('type' => '',						'isallowed' => ''),
	'usercp_do_avatar_end'					=> array('type' => '',						'isallowed' => ''),
	'usercp_usergroups_change_displaygroup'			=> array('type' => '',						'isallowed' => ''),
	'usercp_usergroups_leave_group'	=> array('type' => '',						'isallowed' => ''),
	'usercp_usergroups_join_group'	=> array('type' => '',						'isallowed' => ''),
		
	//Posts
	'newreply_do_newreply_end'			=> array('type' => 'post',	'get' => 'last',			'isallowed' => ''),
	//'editpost_do_editpost_end'			=> array('type' => 'post',	'get' => 'last',			'isallowed' => ''), //TODO: Buggy - needs fixing!
		
	//Threads
	'newthread_do_newthread_end'		=> array('type' => 'thread',	'get' => 'last',		'isallowed' => ''),							//TODO: Use forum permissions.
		
	//Polls
	'polls_do_newpoll_end'					=> array('type' => 'poll',		'get' => 'last',		'isallowed' => ''),						//TODO: Use forum permissions.
	//'polls_do_editpoll_end'					=> array('type' => 'poll',						'isallowed' => ''),
	'polls_vote_end'								=> array('type' => 'poll',		'get' => 'last',		'isallowed' => ''),
		
	//Calendar
	//TODO: Support these!
//	'calendar_do_addevent_end',
//	'calendar_do_editevent_end',
//	'calendar_do_move_end',
//	'calendar_approve_end',
//	'calendar_unapprove_end',

	//Moderating - users
//	'warnings_do_warn_start',
//	'warnings_do_revoke_start',			//TODO: Runs BEFORE attempt?
//		
//	'modcp_do_editprofile_end',
//	'modcp_do_banuser_end',
//	'modcp_do_modnotes_end',
//	'modcp_liftban_end',						//TODO: Runs BEFORE attempt?
);


//Add the hooks...
foreach ($hooks as $name => $value) {
	$plugins->add_hook($name, 'proactivity_log_action');
}


//FUNCTION: Plugin info
function proactivity_info() {
	return array(
		"name"						=> "Pro Activity",
		"description"			=> "Logging and reporting forum activity like social networks.",
		"author"					=> "Jazza",
		"authorsite"			=> "http://www.jazzza001.com/",
		"version"					=> "1.1",
		"compatibility"		=> "6"
	);
}


//FUNCTION: Is it installed
function proactivity_is_installed() {
	global $mybb, $db;
	
	//TODO: Use wildcard!
	$tables = array(
		'proactivity'
	);

	//Loop through all tables and if one exists, it is installed...
	foreach ($tables as $tablename) {
		if ($db->table_exists($tablename)) {
			return true;
		}
	}
	
	return false;
}
 

//FUNCTION: Install the plugin
function proactivity_install() {
	global $mybb, $db;
	
	$collation = $db->build_create_table_collation();
	
	//Create our notifications table if it does not exist...
	//TODO: Use proactivity_ prefix!
	if(!$db->table_exists('proactivity')) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."proactivity` (
			`nid` int(10) UNSIGNED NOT NULL auto_increment,
			`uid` int(10) NOT NULL default 0,
			`hookname` varchar(100) NOT NULL default '',
			`type` varchar(100) NOT NULL default '',
			`data` varchar(100) NOT NULL default '',
			`userfor` int(10) NOT NULL default 0,
			`lang` varchar(100) NOT NULL default '',
			`isallowed` varchar(100) NOT NULL default '',
			`dateline` int(10) NOT NULL default 0,
			PRIMARY KEY  (`nid`)
		) ENGINE=MyISAM{$collation}");
	}
	
	//Add templates...
	$templates = array(
		//TEMPLATE: JavaScript, CSS and main container
		'proactivity_dropdown'							=>'
<script type="text/javascript">
	function proactivity_toggle() {
		//Toggle it
		var e = document.getElementById(\'proactivity\');
		if(e.style.display == \'block\')
				e.style.display = \'none\';
		else
				e.style.display = \'block\';
	}

	function proactivity_markread() {
		//Reset the counter
		document.getElementById(\'proactivity_counter\').innerHTML = \'0\';

		//Reset the alert
		document.getElementById(\'proactivity_toggler\').setAttribute("class", "");

		//Set cookie
		proactivity_setcookie(\'proactivity\',\'{$listarray}\');
	}

	function proactivity_setcookie(name,value) {
		//Calculate 1 year in the future
		var CookieDate = new Date;
		CookieDate.setFullYear(CookieDate.getFullYear() + 1);

		//Set the cookie
		document.cookie = name + "=" + value + "; expires=" + CookieDate.toGMTString() + "; path=/;";
	}

	function proactivity_alert() {
		//Get counter
		var counter = document.getElementById(\'proactivity_counter\').innerHTML;

		if (counter > 0 || counter.indexOf("+") != -1) {
			document.getElementById(\'proactivity_toggler\').setAttribute("class", "alert");
		}
	}
</script>

<style type="text/css">
	.proactivity.dropdown {
		position: relative;
		float: right;
	}

	.proactivity.dropdown .items {
		width: 400px;
		position: absolute;
		right: 0;
		border: 1px solid #C2C2C2;
		background: #FFF;
		z-index: 100;
	}

	.proactivity.dropdown .alert {
		color: #FF0000;
	}

	.proactivity.dropdown .items .item {
		padding: 4px;
		/***border-top:1px solid #C2C2C2;***/
	}

	.proactivity.dropdown .items .item IMG {
		width: 30px;
		height: 30px;
		margin: 2px;
		margin-left: 0;
		vertical-align: middle;
	}

	.proactivity.dropdown .items .item .timeago {
		text-align: right;
		font-size: 0.8em;
		color: #C2C2C2;
	}

	.proactivity.dropdown .items .item.none {
		text-align: center;
		font-weight: bold;
		color: #C2C2C2;
	}
	
	.proactivity.dropdown .items .item.viewmore {
		text-align: center;
		font-weight: bold;
	}

	.proactivity.dropdown .items .item.highlight {
		background: #FFFFB4;
	}

	.proactivity.dropdown .items .item.read {
		background: #F3F3F3;
	}

	.proactivity.dropdown .counter {
		font-weight:bold;
	}
</style>

<div id="proactivity_container" class="proactivity dropdown">
	{$button}
	<div id="proactivity" class="items" style="display: none;">
		{$items}
	</div>
</div>',
			
		//NOTE: Keep on same line to avoid spaces!
		'proactivity_button'				=> '
<a href="javascript:;" id="proactivity_toggler" onClick="proactivity_toggle();proactivity_markread()">Notifications</a> {$counter}',
			
		'proactivity_counter'			=> '
(<span id="proactivity_counter" class="counter">{$displaycount}</span>)',
			
		'proactivity_dropdown_items_item'		=> '
<div class="item {$classes}">
	{$avatar}
	{$who}
	{$label}
	<span class="timeago">
		{$item[\'timeago\']} ago
	</span>
</div>',
			
		'proactivity_dropdown_items_viewmore'			=> '
<div class="item viewmore">
	<a href="{$mybb->settings[\'bburl\']}/activity.php">View More</a>
</div>',
			
		'proactivity_dropdown_items_none'					=> '
<div class="item none">
	None
</div>',
			
		//TEMPLATE: JavaScript alert function call
		'proactivity_alert'				=> '
<script type="text/javascript">
	//Now that we\'ve loaded notifications, try and alert of any new ones
	proactivity_alert();
</script>',
			
		'proactivity_guest_notice'	=> '',
			
		//TEMPLATE: Full view
		'proactivity_fullview'			=> '
<html>
	<head>
		<title>{$lang->title_root}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		
		<style type="text/css">
			.proactivity.fullview {

			}

			.proactivity.fullview .items {

			}

			.proactivity.fullview .items .item {
				border-top: 1px solid #C2C2C2;
				margin-bottom: 10px;
			}
			
			.proactivity.fullview .items .item IMG {
				width: 30px;
				height: 30px;
				margin: 2px;
				margin-left: 0;
				vertical-align: middle;
			}
			
			.proactivity.fullview .items .item .timeago {
				/* text-align: right; */
				font-size: 0.8em;
				color: #C2C2C2;
			}

			.proactivity.fullview .items .item.none {
				text-align: center;
				font-weight: bold;
				color: #C2C2C2;
			}
		</style>
		
		<div class="proactivity fullview">
			<div class="items">
				{$items}
			</div>
		</div>

		{$footer}
	</body>
</html>',

		'proactivity_fullview_items'			=> '
<div class="items">
	{$items}
</div>',
			
		'proactivity_fullview_items_item'			=> '
<div class="item {$classes}">
	{$avatar}
	{$who}
	{$label}
	<span class="timeago">
		{$item[\'timeago\']} ago
	</span>
</div>',
			
		'proactivity_fullview_items_none'				=> '
<div class="item none">
	No notifications to display
</div>
',
	);
	
	//Insert templates...
	foreach ($templates as $title => $data) {
		$insert = array(
			'title' => $db->escape_string($title),
			'template' => $db->escape_string($data),
			'sid' => "-1",
			'version' => '1',
			'dateline' => TIME_NOW
		);
		$db->insert_query('templates', $insert);
	}
	
	
	
	//Insert a new settings group...
	$insertarray = array(
		'name' => 'proactivity',
		'title' => 'Pro Activity',
		'description' => 'Settings for Pro Activity.',
		'disporder' => '60',
		'isdefault' => 0
	);
	$group['gid'] = $db->insert_query("settinggroups", $insertarray);
	
	$insertarray = array();
	
	$insertarray[] = array(
		'name' => 'proactivity_enabled',
		'title' => 'Enable notifications',
		'description' => $db->escape_string('Toggle if notifications should be generated and outputted.'),
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_display_avatars',
		'title' => 'Display avatars',
		'description' => $db->escape_string('Enable displaying avatars next to notifications.'),
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_default_avatar',
		'title' => 'Default avatar',
		'description' => $db->escape_string('Default avatar URL.'),
		'optionscode' => 'text',
		'value' => '/images/no_avatar.gif',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_highlight_user',
		'title' => 'Hightlight on user mention',
		'description' => $db->escape_string('Highlight a notification that mentions the current user.'),
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_enable_links',
		'title' => 'Enable links',
		'description' => $db->escape_string('Enable using multiple links in notifications.'),
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_max_list',
		'title' => 'Maximum to list',
		'description' => $db->escape_string('Number of notifications to list at one time (min 0, max 200).'),
		'optionscode' => 'text',
		'value' => '10',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_mark_read',
		'title' => 'Enable read marking',
		'description' => $db->escape_string('Enable the ability to mark as read.'),
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_show_own',
		'title' => 'Enable showing own',
		'description' => $db->escape_string('Enable showing user\'s own notifications.'),
		'optionscode' => 'yesno',
		'value' => '0',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_alert',
		'title' => 'Enable new alert',
		'description' => $db->escape_string('Enable an alert of new notifications.'),
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'proactivity_guest_view',
		'title' => 'Allow guests',
		'description' => $db->escape_string('Allow guests to view notifications.'),
		'optionscode' => 'yesno',
		'value' => '0',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	//Insert the settings...
	foreach ($insertarray as $settingarray) {
		$db->insert_query("settings", $settingarray);
	}
	
	//Update all settings...
	rebuild_settings();
}


//FUNCTION: Uninstall the plugin
function proactivity_uninstall() {
	global $mybb, $db;
	
	//Deactivate just to be sure...
	proactivity_deactivate();
	
	//Remove all settings from the database...
	$db->delete_query("settings", "name LIKE 'proactivity'");
	$db->delete_query("settinggroups", "name = 'proactivity'");

	//Update the settings...
	rebuild_settings();

	//TODO: Use wildcard!
	$tables = array(
		'proactivity'
	);

	//Drop tables if they exist...
	foreach ($tables as $tablename) {
		if ($db->table_exists($tablename)) {
			$db->drop_table($tablename);
		}
	}
	
	//Remove all other templates...
	$db->delete_query("templates", "`title` LIKE 'proactivity%'");
}


//FUNCTION: Activate the plugin
function proactivity_activate() {
	global $mybb, $db;
	
	//Deactivate it first so we start fresh...
	proactivity_deactivate();
	
	//Add the variable to templates...
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", '#{\$pm_notice}#', "{\$proactivity}\n{\$pm_notice}");
}


//FUNCTION: Deactivate the plugin
function proactivity_deactivate() {
	global $mybb, $db;
	
	//Remove the variable from templates...
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", '#{\$proactivity}(\n?)#', '', 0);
}


//FUNCTION: Log an action for later notification
function proactivity_log_action($Data='', $Userfor='', $Name='', $Type='', $Get='', $Lang='', $Isallowed='0') {
	global $mybb, $db, $plugins, $hooks;
	
	//If we allowed to log...
	if ($mybb->settings['proactivity_enabled'] == '1') {
		//Get the hook name currently in use (if available)...
		if ($plugins->current_hook) {
			$Name = $plugins->current_hook;
		}

		//If no type has been set for the hook, try and use the one passed to this function...
		if ($hooks[$Name]['type']) {
			$Type = $hooks[$Name]['type'];
		}

		//If no get has been set for the hook, try and use the one passed to this function...
		if ($hooks[$Name]['get']) {
			$Get = $hooks[$Name]['get'];
		}

		//Get the current user ID...
		$userid = $mybb->user['uid'];

		$where = null;

		//Every action is unique...
		switch ($Get) {
			case 'last':
				switch ($Type) {
					case 'thread':
						$tablename		= 'threads';
						$resulttype		= 'tid';
						$orderby			= 'tid';
						$where				= '`visible` = 1';	//No drafts!
					break;
					case 'post':
						$tablename		= 'posts';
						$resulttype		= 'pid';
						$orderby			= 'pid';
						
//						$second_tablename = 'threads';
//						$second_resulttype		= 'tid';
//						$second_orderby			= 'tid';
//						$second_where				= '`visible` = 1';	//No drafts!
					break;
					case 'poll':
						$tablename		= 'polls';
						$resulttype		= 'pid';
						$orderby			= 'pid';
					break;
					case 'user':
					case 'reputation':
						//Reputation is special...
						if ($Type == 'reputation') {
							$tablename		= 'reputation';
							$resulttype		= 'uid';
							$orderby			= 'rid';

							//Reset it...
							$Type = 'user';
						} else {
							$tablename		= 'users';
							$resulttype		= 'uid';
							$orderby			= 'uid';
						}
					break;
					case 'login':
						$tablename		= 'sessions';
						$resulttype		= '';
						$orderby			= 'time';
					break;
					default:
						$tablename		= '';
						$resulttype		= '';
						$orderby			= '';
				}

				//Get the data...
				$query			= $db->simple_select($tablename, "*", $where, array("order_by" => $orderby, "order_dir" => 'DESC', "limit" => 1));
				$result			= $db->fetch_array($query);
				
				//Get the specific data...
				$Data				= $result[$resulttype];
				$Userfor		= $result['uid'];

				//If no user ID was provided, try and use this user...
				if (!$userid)	$userid = $result['uid'];
				
//				//If we can find the original author of the parent...
//				if ($second_tablename && !$Userfor) {
//					$query			= $db->simple_select($second_tablename, "*", $second_where, array("order_by" => $second_orderby, "order_dir" => 'DESC', "limit" => 1));
//					$result			= $db->fetch_array($query);
//
//					//Get the specific data...
//					$Userfor		= $result['uid'];
//				} elseif (!$Userfor) {
//					$Userfor = 0;
//				}
			break;
			case 'signup':
				$query	= $db->simple_select("users","*",$where,array("order_by" => 'uid', "order_dir" => 'DESC', "limit" => 1));
				$result	= $db->fetch_array($query);
				$userid = $result['uid'];
			break;
		}

		$plugins->run_hooks("proactivity_add_start");

		//Insert into database...
		$sql_array = array(
			"uid" => $db->escape_string($userid),
			"hookname" => $db->escape_string($Name),
			"type" => $db->escape_string($Type),
			"data" => $db->escape_string($Data),
			"userfor" => '',
			"lang" => $db->escape_string($Lang),
			"isallowed" => $db->escape_string($Isallowed),
			"dateline" => TIME_NOW
		);
		$db->insert_query("proactivity", $sql_array);
		
		$plugins->run_hooks("proactivity_add_end");
	} else {
		//We shouldn't log anything...
	}
}


//FUNCTION: Calculate how long ago using timestamp
function proactivity_calc_timeago($Timestamp) {
	//TODO: Validate timestamp!
	
	//Difference from now and the provided timestamp...
	$timedifference = time() - $Timestamp;

	$tokens = array (
		31536000 => 'year',
		2592000 => 'month',
		604800 => 'week',
		86400 => 'day',
		3600 => 'hour',
		60 => 'minute',
		1 => 'second'
	);

	//TODO: Comment! What's happening here?!
	foreach ($tokens as $unit => $text) {
		if ($timedifference < $unit) continue;
		
		$numberOfUnits = floor($timedifference / $unit);
		return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
	}
}


//FUNCTION: Get notifications from the database
function proactivity_get_notifications($Limit=10, $Page=1) {
	global $mybb, $db, $lang, $templates;

		//Load the language files and special templates...
		$lang->load("proactivity");

		//If a guest...
		if (!$mybb->user['uid']) {
			$currentuserid = '0';
		} else {
			$currentuserid = $mybb->user['uid'];
		}

		//Get a string of forums that the user can actually view
		if (!$unviewable_forums = get_unviewable_forums()) {
			$unviewable_forums = '0';
		}

		//Setting: If to show own actions...
		if (($mybb->settings['proactivity_show_own']) == '0') {
			$showown  = ' AND (l.uid != \''.$currentuserid.'\') ';
		} else {
			$showown = '';
		}
		
		//If only mine...
		if ($_GET['get'] == 'mine') {
			$showown = ' AND (l.uid = \''.$currentuserid.'\') ';
		}
		
		//If only about me...
		if ($_GET['get'] == 'aboutme') {
			$showown = ' AND (userfor.uid = \''.$currentuserid.'\') ';
		}
		
		//If to group similar notifications together...
		if ($_GET['get'] == 'group') {
			#$showown = ' AND (userfor.uid = \''.$currentuserid.'\') ';
		}
		
		//Check the limit...
		if (!intval($Limit)) {
			$Limit = 10;
		}

		//Retrieve notifications with any other data we can find...
		$query = $db->query("
			SELECT l.*, 
				sender.uid as senderID, sender.username as senderUsername, sender.usergroup as senderUsergroup, sender.displaygroup as senderDisplaygroup, sender.avatar as senderAvatar,
				userfor.uid as userforID, userfor.username as userforUsername, userfor.usergroup as userforUsergroup, userfor.displaygroup as userforDisplaygroup,
				receiver.uid as receiverID, receiver.username as receiverUsername, receiver.usergroup as receiverUsergroup, receiver.displaygroup as receiverDisplaygroup,
				thread.tid as threadID, thread.subject as threadSubject,
				post.pid as postID, post.subject as postSubject, post.uid as postAuthorID,
				poll.pid as pollID,	poll.question as pollQuestion
			FROM ".TABLE_PREFIX."proactivity l 

			LEFT JOIN ".TABLE_PREFIX."users sender ON sender.uid=l.uid
			LEFT JOIN ".TABLE_PREFIX."users userfor ON userfor.uid=l.userfor

			LEFT JOIN ".TABLE_PREFIX."users receiver ON receiver.uid=l.data
			LEFT JOIN ".TABLE_PREFIX."threads thread ON thread.tid=l.data
			LEFT JOIN ".TABLE_PREFIX."posts post ON post.pid=l.data
			LEFT JOIN ".TABLE_PREFIX."polls poll ON poll.pid=l.data

			WHERE 
				(thread.fid IS NULL OR thread.fid NOT IN ($unviewable_forums)) AND
				(post.fid IS NULL OR post.fid NOT IN ($unviewable_forums))	
				$showown
				AND (l.uid != 0)
				AND (poll.pid IS NULL)
			ORDER BY nid DESC
			LIMIT 0,$Limit
		");

		//Set the counters to 0 to start off...
		$proactivity_counter		= 0;
		$displaycount						= 0;

		//If any notifications to display and we are allowed to output them...
		if ($db->num_rows($query) != 0 && $mybb->settings['proactivity_enabled'] == '1') {
			$listall = '';

			$allnotify = array();

			//Copy notifications to an easier to use array...
			while ($notify = $db->fetch_array($query)) {
				$allnotify[] = $notify;
			}

			//Our array that we'll return...
			$return = array();

			//Loop through all notifications and format them...
			foreach ($allnotify as $notify) {
				$data = $notify;
				
				//Use this array for any user friendly output...
				$friendly = array();

				//Build friendly dates and times...
				$notify_date = my_date($mybb->settings['dateformat'], $notify['dateline']);
				$notify_time = my_date($mybb->settings['timeformat'], $notify['dateline']);

				//If not you...
				if ($currentuserid != $notify['senderID'])	{
					//Build a simple array for formatting later...
					$user = array(
						'uid' => $notify['senderID'],
						'username' => $notify['senderUsername'],
						'usergroup' => $notify['senderUsergroup'],
						'displaygroup' => $notify['senderDisplaygroup'],
						'avatar' => $notify['senderAvatar']
					);
				} else {
					//All of the values are global anyway...
					$user = array(
						'uid' => $mybb->user['uid']
					);
				}
				
				//If there's an owner...
				if ($currentuserid != $notify['senderID'])	{
					//Build a simple array for formatting later...
					$userfor = array(
						'uid' => $notify['userforID'],
						'username' => $notify['userforUsername'],
						'usergroup' => $notify['userforUsergroup'],
						'displaygroup' => $notify['userforDisplaygroup']
					);
				} else {
					//Empty...
					$userfor = array(
						'uid' => ''
					);
				}

				//Get a URL for this notification...
				switch ($notify['type']) {
					case 'thread':
					case 'lastthread':
						$url = get_thread_link($notify['data']);
						$title = $notify['threadSubject'];
						$outputas = 'thread';
					break;
					case 'post':
					case 'lastpost':
						$url = get_post_link($notify['data']).'#post_'.$notify['data'];
						$title = $notify['postSubject'];
						$outputas = 'post';
					break;
					case 'user':
					case 'lastuser':
						$url = $mybb->settings['bburl'].'/member.php?action=profile&uid='.$notify['data'];
						$title = $notifyme['receiverUsername'];
						$outputas = 'user';
					break;
					case 'poll':
					case 'lastpoll':
						$url = $mybb->settings['bburl'].'/polls.php?action=showresults&pid='.$notify['data'];
						$title = $notify['pollQuestion'];
						$outputas = 'poll';
					break;
					default:
						//Error...
				}

				//Add any special classes to style specific notifications...
				$highlight = false;
				$read = false;

				//Setting: Concerns the current user...
				if ($mybb->settings['proactivity_highlight_user'] == "1") {
					if ($currentuserid == $notify['receiverID'] || $currentuserid == $notify['userforID'] || $currentuserid == $notify['senderID']) {
						$highlight = true;
					}
				}

				//Setting: Mark it as read...
				if ($mybb->settings['proactivity_mark_read'] == "1" && $notify['status'] == 'read') {
					//It's read...
					$read = true;
					
					//Override any highlighting...
					$highlight = true;
				}

				//Get the time ago for all notifications...
				$timeago = proactivity_calc_timeago($notify['dateline']);
				
				$return[] = array(
					'nid' => $data['nid'],
					'name' => $data['hookname'],
					'user' => $user,
					'raw' => $data,
					'outputas' => $outputas,
					'dataid' => $data['data'],
					'title' => $title,
					'url' => $url,
					'read' => $read,
					'userfor' => $userfor,
					'read' => $read,
					'highlight' => $highlight,
					'timeago' => $timeago
				);
			}
			
			return $return;
		} else {
			return false;
		}
}


//FUNCTION: Generate a dropdown
function proactivity_generate_dropdown() {
	global $mybb, $db, $lang, $templates;
	
	//Our variable that displays the notifications in templates...
	global $proactivity;
	
	//If you are logged in or guests can view...
	if ($mybb->user['uid'] || $mybb->settings['proactivity_guest_view'] == '1') {
		//Can we generate links...
		if ($mybb->settings['proactivity_enable_links'] == "1") {
			$dolinks = true;
		} else {
			$dolinks = false;
		}
		
		//How many notifications to retreive...
		if (is_numeric($mybb->settings['proactivity_max_list']) && $mybb->settings['proactivity_max_list'] > 0 && $mybb->settings['proactivity_max_list'] < 100) {
			$numget = $mybb->settings['proactivity_max_list'];
		}

		//Get all notifications...
		$allitems = proactivity_get_notifications($numget);
		
		//Get the new notification counter...
		$unread = proactivity_check_unread($allitems);
		
		//If more than the max, display it with a +...
		if ($unread['count'] >= $numget) {
			$displaycount = $numget.'+';
		} else {
			$displaycount = $unread['count'];
		}
		
		//If we have unread IDs...
		if (is_array($unread['ids'])) {
			//Dump them in an array for our JavaScript...
			$listarray = implode(',', $unread['ids']);
		}

		//If some were found...
		if (is_array($allitems) && $allitems > 0) {
			//Loop through them all...
			foreach ($allitems as $item) {
				$data = proactivity_generate_data_link($item['outputas'], $item['dataid'], $item['title']);

				//Build the user who created the notification...
				if ($item['user']['uid'] != $mybb->user['uid']) {
					//Format their name...
					$who = format_name($item['user']['username'], $item['user']['usergroup'], $item['user']['displaygroup']);

					//Build a link to their profile...
					if ($dolinks) {
						$who = build_profile_link($who, $item['user']['uid']);
					}
				} else {
					//You don't need a profile link!
					$who = 'You';
				}
				
				//If to display avatars...
				if ($mybb->settings['proactivity_display_avatars'] == "1") {
					//If the user actually has one (otherwise use default)...
					if ($item['user']['avatar']) {
						$avatar = '<img src="'.$item['user']['avatar'].'" />';
					} elseif ($mybb->settings['proactivity_default_avatar']) {
						$avatar = '<img src="'.$mybb->settings['proactivity_default_avatar'].'" />';
					}
				}

				//If we have an author of whatever the user interacted with...
				if ($item['userfor']['uid'] != $mybb->user['uid']) {
					//Format their name...
					$userfor = format_name($item['userfor']['username'], $item['userfor']['usergroup'], $item['userfor']['displaygroup']);

					//Build a link to their profile...
					if ($dolinks) {
						$userfor = build_profile_link($userfor, $item['userfor']['uid']);
					}

					//They own it...
					$userfor .= '\'s';
				} elseif ($item['userfor']['uid'] == $mybb->user['uid']) {
					$userfor = 'your';
				} else {
					$userfor = 'someone\'s';
				}

				//Dump everything into a template...
				$label = proactivity_replace_template($item['name'], $data, null, $item['raw']['lang']);

				#eval("\$itemlist .= \"".$templates->get('proactivity_list_itembit')."\";");
				
//				if ($mybb->settings['proactivity_highlight_user'] == "1") {
//					if ($currentuserid == $notify['receiverID'] || $currentuserid == $notify['userforID'] || $currentuserid == $notify['senderID']) {
//						$highlight = TRUE;
//					}
//				}
				
				$classes = '';

				//Mark it as read...
				if (in_array($item['nid'], $unread['read_ids'])) {
					$classes .= ' read';
				}

				//Add it to the list of all...
				eval("\$items .= \"".$templates->get("proactivity_dropdown_items_item")."\";");
			}
			
			//Add a read more button...
			eval("\$items .= \"".$templates->get("proactivity_dropdown_items_viewmore")."\";");
		} else {
			//Return that there are no notifications...
			eval("\$items = \"".$templates->get("proactivity_dropdown_items_none")."\";");
		}
	
		eval("\$counter = \"".$templates->get("proactivity_counter")."\";");

		//eval("\$items = \"".$templates->get("proactivity_dropdown_items")."\";");

		eval("\$button = \"".$templates->get("proactivity_button")."\";");
	
		//Wrap it all up...
		eval("\$proactivity = \"".$templates->get("proactivity_dropdown")."\";");

		//If to alert of new messages, add a JavaScript function to do it...
		if ($mybb->settings['proactivity_alert'] == "1") {
			eval("\$proactivity .= \"".$templates->get("proactivity_alert")."\";");
		}
	} else {
		//Display guest notice...
		eval("\$proactivity = \"".$templates->get("proactivity_guest_notice")."\";");
	}
}


//FUNCTION: Replace template variables
function proactivity_replace_template($Name, $Data, $Userfor='', $Custom='') {
	global $mybb, $db, $lang;
	
	$lang->load("proactivity");

	//Try and find a template to fill in data...
	if ($Custom) {
		//Use the passed one...
		$foo = $Custom;
	} elseif (property_exists($lang, $Name)) {
		//Use a predefined one...
		$foo = $lang->$Name;
	} else {
		//We need a template!
		return false;
	}

	//Replace the user variable...
	$foo = str_replace('[userfor]', $Userfor, $foo);
	
	//Replace the data variable...
	$foo = str_replace('[data]', $Data, $foo);
	
	return $foo;
}


//FUNCTION: Generate a link using a data value
function proactivity_generate_data_link($Type, $ID, $Label) {
	global $mybb, $db;
	
	//Can we generate links...
	if ($mybb->settings['proactivity_enable_links'] == "1") {
		$dolinks = true;
	} else {
		$dolinks = false;
	}
	
	//Build the link...
	if ($dolinks) {
		switch ($Type) {
			case 'thread':
			case 'lastthread':
				$url = get_thread_link($ID);
			break;
			case 'post':
			case 'lastpost':
				$url = get_post_link($ID).'#post_'.$ID;
			break;
			case 'user':
			case 'lastuser':
				$url = $mybb->settings['bburl'].'/member.php?action=profile&uid='.$ID;
			break;
			case 'poll':
			case 'lastpoll':
				$url = $mybb->settings['bburl'].'/polls.php?action=showresults&pid='.$ID;
			break;
			default:
				//Error...
		}
		
		$data  = '<a href="'.$url.'">';
		$data .= '	'.$Label;
		$data .= '</a>';
		
		return $data;
	} else {
		return $Label;
	}
}


//FUNCTION: Check if notifications are read
function proactivity_check_unread($allnotify) {
	global $mybb, $db;
	
	//Get any IDs saved as a cookie (set by the browser)...
	$ids_read = explode(',',$mybb->cookies['proactivity']);

	$ids_newunread = array();
	$unreadnum = 0;
	$read = array();

	//If any IDs are set as read...
	if (is_array($ids_read) && is_array($allnotify)) {
		//Loop through all arrays we have already...
		foreach ($allnotify as $notifyindex => $notify) {
			//If found an unread one, increase the counter...
			if (!in_array($notify['nid'], $ids_read)) {
				$unreadnum++;
			} else {
				//Otherwise mark it as read...
				$read[] = $notify['nid'];
			}

			//Dump it in an array for future cookie setting...
			$ids_newunread[] = $notify['nid'];
		}
		
		$array = array(
			'count' => $unreadnum,
			'ids' => $ids_newunread,
			'read_ids' => $read
		);
	} else {
		$array = array(
			'count' => 0,
			'ids' => array(),
			'read_ids' => array()
		);
	}

	return $array;
}
?>