<?php

/***************************************************************************
 *
 *   OUGC In-line Recount & Rebuild plugin (/inc/plugins/ougc_inlire.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2013 Omar Gonzalez
 *   
 *   Website: http://omarg.me
 *
 *   Adds a moderation option to recount threads counters without ACP access.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Tell MyBB when to run the hook
if(!defined('IN_ADMINCP'))
{
	$plugins->add_hook('showthread_end', 'ougc_inlire_showthread');
	$plugins->add_hook('moderation_start', 'ougc_inlire_moderation');
}

// Plugin API
function ougc_inlire_info()
{
	global $lang;

	return array(
		'name'			=> 'OUGC In-line Recount & Rebuild',
		'description'	=> 'Adds a moderation option to recount threads counters without ACP access.',
		'website'		=> 'http://mods.mybb.com/view/ougc-inlire',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.0',
		'guid' 			=> '',
		'compatibility' => '16*'
	);
}

// _activate
function ougc_inlire_activate()
{
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	
	find_replace_templatesets('showthread_moderationoptions', '#'.preg_quote('{$approveunapprovethread}').'#i', '{$approveunapprovethread}<!--OUGC_INLIRE-->');
}

// _activate
function ougc_inlire_deactivate()
{
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	
	find_replace_templatesets('showthread_moderationoptions', '#'.preg_quote('<!--OUGC_INLIRE-->').'#i', '', 0);
}

// Add our moderation option
function ougc_inlire_showthread()
{
	global $ismod;

	if($ismod)
	{
		global $lang, $templates, $moderationoptions;

		isset($lang->ougc_inlire) or $lang->ougc_inlire = 'Recount Thread Counters';

		if(!isset($templates->cache['ougc_inlire']))
		{
			$templates->cache['ougc_inlire'] = '<option value="ougc_inlire">'.$lang->ougc_inlire.'</option>';
		}

		eval('$ougc_inlire = "'.$templates->get('ougc_inlire').'";');

		$moderationoptions = str_replace('<!--OUGC_INLIRE-->', $ougc_inlire, $moderationoptions);
	}
}

// Dark magic!
function ougc_inlire_moderation()
{
	global $mybb;

	isset($mybb->input['modtype']) or $mybb->input['modtype'] = '';
	isset($mybb->input['action']) or $mybb->input['action'] = '';

	if($mybb->input['modtype'] == 'thread' && $mybb->input['action'] == 'ougc_inlire')
	{
		global $lang;

		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		// Verify thread existense
		!empty($mybb->input['tid']) or error($lang->error_invalidthread);
		$thread = get_thread((int)$mybb->input['tid']);
		isset($thread['tid']) or error($lang->error_invalidthread);

		// Check if this forum is password protected and we have a valid password
		check_forum_password($thread['fid']);

		// Check if user is a moderator of this forum
		is_moderator($thread['fid'], 'canmanagethreads') or error_no_permission();

		//rebuild_thread_counters($thread['tid']);
		require_once MYBB_ROOT.'/inc/functions_rebuild.php';
		rebuild_thread_counters($thread['tid']);

		// Log moderation action
		log_moderator_action(array('fid' => $thread['fid'], 'tid' => $thread['tid']), $mybb->input['action']);

		// Redirect back to thread
		isset($lang->ougc_inlire_redirect_thread) or $lang->ougc_inlire_redirect_thread = 'The thread counters has been updated successfully.<br/>You will now be returned to the thread.';
		moderation_redirect(get_thread_link($thread['tid']), $lang->ougc_inlire_redirect_thread);
	}
}