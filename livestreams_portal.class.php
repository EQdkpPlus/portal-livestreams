<?php
/*	Project:	EQdkp-Plus
 *	Package:	Livestreams Portal Module
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( !defined('EQDKP_INC') ){
	header('HTTP/1.0 404 Not Found');exit;
}

class livestreams_portal extends portal_generic {

	protected static $path		= 'livestreams';
	protected static $data		= array(
		'name'			=> 'Livestreams Module',
		'version'		=> '0.1.0',
		'author'		=> 'GodMod',
		'contact'		=> EQDKP_PROJECT_URL,
		'icon'			=> 'fa-video-camera',
		'description'	=> 'Shows status of the users\' livestreams',
		'lang_prefix'	=> 'ls_',
		'multiple'		=> true,
	);
	
	public $template_file = 'livestreams.html';
	
	protected $settings	= array(
	);
	
	protected static $install	= array(
		'autoenable'		=> '0',
		'defaultposition'	=> 'left',
		'defaultnumber'		=> '2',
	);
	
	protected static $apiLevel = 20;
	
	public function get_settings($state){
		
		return $this->settings;
	}
	
	public static function install($child=false) {
		$intTwitchFieldID = register('pdh')->get('user_profilefields', 'field_by_name', array('twitch'));
		$intHitboxFieldID = register('pdh')->get('user_profilefields', 'field_by_name', array('hitbox'));
		
		//Create Twitch Profilefield
		if(!$intTwitchFieldID){
			$arrOptions = array(
				'name' 			=> 'Twitch',
				'lang_var'		=> '',
				'type' 			=> 'text',
				'length'		=> 30,
				'minlength' 	=> 3,
				'validation'	=> '[\w_\.]+',
				'required' 		=> 0,
				'show_on_registration' => 0,
				'enabled'		=> 1,
				'is_contact'	=> 1,
				'contact_url' 	=> 'http://www.twitch.tv/%s',
				'icon_or_image' => 'fa-twitch',
				'bridge_field'	=> null,
			);
			
			register('pdh')->put('user_profilefields', 'insert_field', array($arrOptions, array()));
		}
		
		//Create Hitbox Profilefield
		if(!$intHitboxFieldID) {
			$arrOptions = array(
					'name' 			=> 'Hitbox',
					'lang_var'		=> '',
					'type' 			=> 'text',
					'length'		=> 30,
					'minlength' 	=> 3,
					'validation'	=> '[\w_\.]+',
					'required' 		=> 0,
					'show_on_registration' => 0,
					'enabled'		=> 1,
					'is_contact'	=> 1,
					'contact_url' 	=> 'http://www.hitbox.tv/%s',
					'icon_or_image' => '',
					'bridge_field'	=> null,
			);
			
			register('pdh')->put('user_profilefields', 'insert_field', array($arrOptions, array()));
			
		}
		
		register('pdh')->process_hook_queue();
	}

	public function output() {
		$arrUserIDs = $this->pdh->sort($this->pdh->get('user', 'id_list'), 'user', 'name', 'asc');
		
		$this->tpl->add_js('
			$(".ls_status").each(function(){
				streamName = $(this).data("streamname");
				streamType = $(this).data("streamtype");
				
				switch(streamType){
					case "twitch":
						twitchStreamCheck(streamName);
						break;
					
					case "hb":
						hbStreamCheck(streamName);
						break;
				}
			});
			
			function twitchStreamCheck(streamName){
				$.ajax({
					url: "https://api.twitch.tv/kraken/streams/" + streamName,
					dataType: "jsonp",
					type: "get",
					complete: function(resp, textStatus){
						
						streamStatus = (resp.responseJSON.stream != null)? true : false;
						lsStreamResponse(streamName, "twitch", streamStatus);
					}
				});
			}
			
			function hbStreamCheck(streamName){
				$.ajax({
					url: "https://api.hitbox.tv/media/status/" + streamName + ".json",
					dataType: "json",
					type: "get",
					complete: function(resp, textStatus){
						
						streamStatus = (resp.responseJSON.media_is_live == "1")? true : false;
						lsStreamResponse(streamName, "hb", streamStatus);
					}
				});
			}
			
			function lsStreamResponse(streamName, streamType, streamStatus){
				if(streamStatus){
					$(".ls_status." + streamType + "_" + streamName).html("<i class=\"eqdkp-icon-offline blink_me\" style=\"color:red;\"></i> '.$this->jquery->sanitize($this->user->lang('ls_online')).'");
				}else{
					$(".ls_status." + streamType + "_" + streamName).html("<i class=\"fa fa-close\" style=\"color:red;\"></i> '.$this->jquery->sanitize($this->user->lang('ls_offline')).'");
				}
			}
		', 'docready');
		
		$this->tpl->add_css("
			.blink_me {
				-webkit-animation-name: blinker;
				-webkit-animation-duration: 3s;
				-webkit-animation-timing-function: linear;
				-webkit-animation-iteration-count: infinite;
			
				-moz-animation-name: blinker;
				-moz-animation-duration: 3s;
				-moz-animation-timing-function: linear;
				-moz-animation-iteration-count: infinite;
			
				animation-name: blinker;
				animation-duration: 3s;
				animation-timing-function: linear;
				animation-iteration-count: infinite;
			}
			
			@-moz-keyframes blinker {  
				0% { opacity: 1.0; }
				50% { opacity: 0.0; }
				100% { opacity: 1.0; }
			}
			
			@-webkit-keyframes blinker {  
				0% { opacity: 1.0; }
				50% { opacity: 0.0; }
				100% { opacity: 1.0; }
			}
			
			@keyframes blinker {  
				0% { opacity: 1.0; }
				50% { opacity: 0.0; }
				100% { opacity: 1.0; }
			}
		");
		
		foreach($arrUserIDs as $intUserID){
			
			$strTwitch = $this->pdh->get('user', 'profilefield_by_name', array($intUserID, 'twitch'));
			if($strTwitch && $strTwitch != ""){
				
				$strUsername = $this->pdh->get('user', 'name', array($intUserID));
				
				$this->tpl->assign_block_vars('ls_user_row', array(
					'USERNAME' 		=> $strUsername,
					'USERLINK' 		=> $this->routing->build('user', $strUsername, 'u'.$intUserID),
					'STREAM_TYPE'	=> 'twitch',
					'STREAM_NAME'	=> '<i class="fa fa-twitch" title="Twitch"></i>',
					'STREAM_LINK'	=> utf8_strtolower($strTwitch),
					'STREAM_USERNAME' => str_replace('http://www.twitch.tv/', '', sanitize(utf8_strtolower($strTwitch))),
				));
			}
			
			$strHitbox = $this->pdh->get('user', 'profilefield_by_name', array($intUserID, 'hitbox'));
			if($strHitbox && strlen($strHitbox)){
				
				$strUsername = $this->pdh->get('user', 'name', array($intUserID));
				
				$this->tpl->assign_block_vars('ls_user_row', array(
					'USERNAME' 		=> $strUsername,
					'USERLINK' 		=> $this->routing->build('user', $strUsername, 'u'.$intUserID),
					'STREAM_TYPE'	=> 'hb',
					'STREAM_NAME'	=> 'Hitbox',
					'STREAM_LINK'	=> utf8_strtolower($strHitbox),
					'STREAM_USERNAME' => str_replace('http://www.hitbox.tv/', '', sanitize(utf8_strtolower($strHitbox))),
				));
			}
		}
	}
}
?>