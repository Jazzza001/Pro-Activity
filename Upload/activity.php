<?php
/**
 * Pro Activity 1.1
 * Logging and reporting forum activity like social networks.
 * 
 * Page: Full view.
 *  
 * By Jared Williams
 * Copyright 2012
 * 
 * Website: http://www.jazzza001.com
 *  
 * Please do not redistribute or sell this plugin.
 */

define("IN_MYBB", 1);
require_once "global.php";

define('PROACTIVITY_URL_ACTIVITY',	 'activity.php');


//If the plugin is ready...
if (!function_exists('proactivity_activate')) {
	die('Plugin has not been activated! Please contact your administrator!');
}


$lang->load("proactivity");


//If not a member, do not allow them...
if (!$mybb->user['uid'] && $mybb->settings['proactivity_guest_view'] != '1') {
	error_no_permission();
}


//Check if a moderator...
if ($mybb->usergroup['canmodcp'] == 1 || $mybb->usergroup['issupermod'] == 1) {
	$ismod = true;
}


add_breadcrumb($lang->title_root, PROACTIVITY_URL_ACTIVITY);


//******************************************************[ BACKEND ]
if ($mybb->input['action'] == 'backend') {
	if ($ismod) {
		add_breadcrumb($lang->title_backend, PROACTIVITY_URL_ACTIVITY.'?action=backend');
		
		eval("\$proactivity = \"".$templates->get('proactivity_backend')."\";");
		
		$proactivity = 'sdfsdf';
		
		output_page($proactivity);
	} else {
		#error($lang->error_not_moderator);
		
		error_no_permission();
	}
}

//*******************************************************[ DO ADD ]
if ($mybb->input['action'] == 'do_add') {
	if ($ismod) {
		add_breadcrumb($lang->title_backend, PROACTIVITY_URL_ACTIVITY.'?action=backend');
		
		$insertnotification = array(
			'tid' => '',
			'pids' => $db->escape_string('1'),
			'prodcost' => $db->escape_string('97.33'),
			'postcost' => $db->escape_string('10.00'),
			'dateadded' => time(),
			'dateupdated' => time(),
			'whoadded' => $mybb->user['uid'],
			'whoupdated' => $mybb->user['uid'],
			'status' => '2'	//Paid
		);
		$lastnid = $db->insert_query("proactivity", $insertnotification);
		
		eval("\$proactivity = \"".$templates->get('proactivity_add_success')."\";");
		
		output_page($proactivity);
	} else {
		#error($lang->error_not_moderator);
		
		error_no_permission();
	}
}

//*********************************************************[ VIEW ]
if ($mybb->input['action'] == '') {
	//If we are to filter the notifications...
	switch ($mybb->input['show']) {
		case 'mine':
			
		break;
	}
	
	//Can we generate links...
	if ($mybb->settings['proactivity_enable_links'] == "1") {
		$dolinks = true;
	} else {
		$dolinks = false;
	}
	
	//Get all notifications...
	$allitems = proactivity_get_notifications(30);
	
	//If some were found...
	if ($allitems) {
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

			eval("\$items .= \"".$templates->get('proactivity_fullview_items_item')."\";");
		}
	} else {
		eval("\$items .= \"".$templates->get('proactivity_fullview_items_none')."\";");
	}

	eval("\$fullview = \"".$templates->get('proactivity_fullview')."\";");
		
	output_page($fullview);
}
?>