<?php
/*
  Controller name: Buddypress Read
  Controller description: Buddypress controller for reading actions
 */

require_once BUDDYPRESS_JSON_API_HOME . '/library/functions.class.php';

class JSON_API_BuddypressRead_Controller {

	function __construct() {
	   header("Access-Control-Allow-Origin: *");
	}
   
	public function members_get_members() 
	 {
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		$oReturn->total = 0;
		$bp_members = array();
		$member_data = array();
		global $wpdb,$table_prefix;
		
		$keyword = trim($_GET['keyword']);
		if($keyword==''){ $oReturn->error = __('Please enter keyword to search.','aheadzen'); return $oReturn;}
		
		$sql = "select DISTINCT(user_id) from ".$table_prefix."bp_xprofile_data where MATCH (value) AGAINST('".$keyword."*' IN BOOLEAN MODE) limit 10";
		$members = $wpdb->get_col($sql);
		if($members){
			$counter = 0;
			for($m=0;$m<count($members);$m++){
				$uid = $members[$m];
				$user = new BP_Core_User($uid);
				
				if($user){
					$username = $avatar_big = $avatar_thumb = '';
					if($user->user_url){
						$username = str_replace('/','',str_replace(site_url('/members/'),'',$user->user_url));
					}
					if($user->avatar){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar, $user_avatar_result);
						$avatar_big = str_replace('"','',$user_avatar_result[2][0]);
					}
					if($user->avatar_thumb){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
						$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
					}					
					$oReturn->members[$counter]->id 		= $user->id;
					$oReturn->members[$counter]->username 	= $username;
					$oReturn->members[$counter]->fullname 	= $user->fullname;
					$oReturn->members[$counter]->email 		= $user->email;
					$oReturn->members[$counter]->user_url 	= $user->user_url;
					$oReturn->members[$counter]->last_active= $user->last_active;
					$oReturn->members[$counter]->avatar_big = $avatar_big;
					$oReturn->members[$counter]->avatar_thumb = $avatar_thumb;
					
					$profile_data = $user->profile_data;
					if($profile_data){
						foreach($profile_data as $sFieldName => $val){
							if(is_array($val)){
								$oReturn->members[$counter]->$sFieldName = $val['field_data'];
							}
						}
					}
					$counter++;
				}
			}			
			
		}else{$oReturn->error = __('No Members Available To Display.','aheadzen');}
		
		//echo '<pre>';print_r($oReturn);exit;
		return $oReturn;
	 }
	 
   /**
     * Returns an Array with all mentions
     * @param int pages: number of pages to display (default 1)
     * @param int maxlimit: number of maximum results (default 20)
	 * @param String sort: sort ASC or DESC (default DESC)
     * @param String username: username to filter on, comma-separated for more than one ID (default unset)
     * @return array mentions: an array containing the mentions
     */
    public function activity_get_mentions() {
        header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		
		if(!$_GET['username']){$oReturn->error = __('Missing parameter username.','aheadzen'); return $oReturn;}
		
		$username = $_GET['username'];
		$maxlimit = $_GET['maxlimit'];
		$page = $_GET['pages'];
		$orderby = $_GET['sort'];
		
		if(!$page){$page=1;}
		if(!$maxlimit){$maxlimit=20;}
		if(!$orderby){$orderby='DESC';}
		if(!$username){$oReturn->error = __('Wrong User Name.','aheadzen'); return $oReturn;}
		if(!username_exists($username)){return $this->error('xprofile', 1);}
		
		$start = $maxlimit*($page-1);
		$end = $maxlimit;
		global $wpdb,$table_prefix;
		$total_count = $wpdb->get_var("select count(id) from ".$table_prefix."bp_activity where content like \"%@".$username."%\"");
		$sql = "select id,user_id,component,type,content,date_recorded from ".$table_prefix."bp_activity where content like \"%@".$username."%\" order by date_recorded $orderby limit $start,$end";
		$res = $wpdb->get_results($sql);
		 $oReturn->total_count = $total_count;
		 $oReturn->total_pages = ceil($total_count/$maxlimit);
		if($res){
			$counter=0;
			foreach($res as $oMentions){
				$user = new BP_Core_User($oMentions->user_id);
				if($user && $user->avatar){
					$oMentions->fullname = $user->fullname;
					$oMentions->email = $user->email;
					$oMentions->user_url = $user->user_url;
					if($user->user_url){
						$oMentions->username = str_replace('/','',str_replace(site_url('/members/'),'',$user->user_url));
					}
					if($user->avatar){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar, $user_avatar_result);
						$oMentions->avatar_big = str_replace('"','',$user_avatar_result[2][0]);
					}
					if($user->avatar_thumb){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
						$oMentions->avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
					}
				}
				
				$oReturn->mentions[$counter]->id = $oMentions->id;
				$oReturn->mentions[$counter]->component = $oMentions->component;
				$oReturn->mentions[$counter]->type = $oMentions->type;
				$oReturn->mentions[$counter]->content = $oMentions->content;
				$oReturn->mentions[$counter]->time = $oMentions->date_recorded;
				$oReturn->mentions[$counter]->user->id = $oMentions->user_id;
				$oReturn->mentions[$counter]->user->fullname = $oMentions->fullname;
				$oReturn->mentions[$counter]->user->email = $oMentions->email;
				$oReturn->mentions[$counter]->user->username = $oMentions->username;
				$oReturn->mentions[$counter]->user->user_url = $oMentions->user_url;
				$oReturn->mentions[$counter]->user->avatar_thumb = $oMentions->avatar_thumb;
				$oReturn->mentions[$counter]->user->avatar_big = $oMentions->avatar_big;
				
				$counter++;
			}
		}else{
			$oReturn->msg = __('No Mentions Available To Display.','aheadzen');
		}
		
		return $oReturn;
    }
	
	
	public function activity_comments_delete()
	{
		$error = '';
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['commentid']){$oReturn->error = __('Wrong Comment Id.','aheadzen'); return $oReturn;}
		if(!$_POST['activityid']){$oReturn->error = __('Wrong Activity Id.','aheadzen'); return $oReturn;}
		
		$comment_id = (int)$_POST['commentid'];
		$activity_id = (int)$_POST['activityid'];
		
		if(bp_activity_delete_comment( $activity_id, $comment_id ))
		{
			$oReturn->success->message = __('Activity comment deleted successfully.','aheadzen');			
		}else{
			$error = __('Something wrong to delete activity comment.','aheadzen');
		}
		
		$oReturn->error = $error;
		return  $oReturn;
	}
	
	/**
     * Supply post data
     * @param int userid: User ID
     * @param String content: Activity comment content
	 * @param int activityid: Activity Id for which you want to add comments
     * @return array message: success or error message & added activity comment ID
     */
	public function activity_comments_add_edit()
	{		
		header("Access-Control-Allow-Origin: *");
		/*//The data only for testing purpose.
		$_POST['content'] = '123 HELLO THIS IS TEST ACTIVITY Comments FOR ME';
		$_POST['userid'] = 1;
		$_POST['activityid'] = 47;
		*/		
		$error = '';
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['content']){$oReturn->error = __('Please do not leave the comment area blank.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->error = __('Wrong User Id.','aheadzen'); return $oReturn;}
		if(!$_POST['activityid']){$oReturn->error = __('Wrong Activity Id.','aheadzen'); return $oReturn;}
		
		$content = $_POST['content'];
		$user_id = (int)$_POST['userid'];
		$activity_id = (int)$_POST['activityid'];
		$commentid = (int)$_POST['commentid'];
		
		$arg = array(
			'content'    	=> $content,
			'activity_id' 	=> $activity_id,
			'user_id' 		=> $user_id,
			'parent_id'   => false
		);
		
		if($commentid){$arg['id'] = $commentid;} //update activity comment
		if($comment_id = bp_activity_new_comment($arg))
		{
			$oReturn->success->id = $comment_id;
			if($activityid){
				$oReturn->success->message = __('Activity comments updated successfully.','aheadzen');
			}else{
				$oReturn->success->message = __('Activity comments added successfully.','aheadzen');
			}
		}else{
			$error = __('Something wrong to updated activity comments.','aheadzen');
		}
		$oReturn->error = $error;
		return  $oReturn;
	}
	
	/**
     * Supply post data
     * @param int userid: User ID
     * @param String content: Activity content
	 * @param int activityid: Activity Id for update
     * @return array message: success or error message
     */
	public function activity_add_edit()
	{
		/*
		//The data only for testing purpose.
		$_POST['content'] = '123 HELLO THIS IS TEST ACTIVITY FOR ME 456';
		$_POST['userid'] = 1;
		$_POST['activityid'] = 48;
		*/
		$error = '';
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['content']){$oReturn->error = __('Empty content.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->error = __('Wrong User Id.','aheadzen'); return $oReturn;}
		$content = $_POST['content'];
		$user_id = $_POST['userid'];
		$activityid = (int)$_POST['activityid'];
		
		$arg = array(
					'user_id'   => $user_id,
					'component' => 'activity',
					'type'      => 'activity_update',
					'content'   => $content
				);
		if($activityid){$arg['id'] = $activityid;} //update activity
		if($activity_id = bp_activity_add($arg)){
			$oReturn->success->id = $activity_id;
			if($activityid){
				$oReturn->success->message = __('Activity updated successfully.','aheadzen');
			}else{
				$oReturn->success->message = __('Activity added successfully.','aheadzen');
			}
		}else{
			if($activityid){
				$error = __('Something wrong to add activity.','aheadzen');
			}else{
				$error = __('Something wrong to updated activity.','aheadzen');
			}
		}
		$oReturn->error = $error;
		return  $oReturn;
	}
	
	/**
     * Supply post data
     * @param int userid: User ID
     * @param int activityid: Activity Id for update
     * @return array message: success or error message
     */
	public function activity_delete()
	{
		/*
		//The data only for testing purpose.
		$_POST['userid'] = 1;
		$_POST['activityid'] = 47;
		*/
		
		$error = '';
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['activityid']){$oReturn->error = __('Wrong activity Id.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->error = __('Wrong user Id.','aheadzen'); return $oReturn;}
		$user_id = $_POST['userid'];
		$activityid = (int)$_POST['activityid'];
		
		$arg = array(
					'id'  		 => $activityid,
					'user_id' 	=> $user_id
				);
		if ( bp_activity_delete($arg)){
			$oReturn->success->message = __( 'Activity deleted successfully', 'aheadzen');
		}else{
			$error =  __( 'There was an error when deleting that activity', 'aheadzen' );
		}
		$oReturn->error = $error;
		return  $oReturn;
	}
	
	public function profile_upload_photo()
	{
		/*
		//below details are only for testing purpose.
		$_POST['clicked_pic'] = 'profile_pic'; //'profile_pic'; //'cover_pic';
		$_POST['user_id'] = 1;
		$imageDataEncoded = base64_encode(file_get_contents('http://localhost/profile_pic_192063.jpg'));
		$_POST['picture_code']=$imageDataEncoded;
		*/		
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['picture_code']){$oReturn->message = __('Wrong picture.','aheadzen'); return $oReturn;}
		
		$clicked_pic = $_POST['clicked_pic'];
		$user_id = $_POST['user_id'];
		$picture_code = $_POST['picture_code'];	
		$bp_upload = xprofile_avatar_upload_dir('',$user_id);	
		
		if(strstr($picture_code,'data:image/')){
			 $picture_code_arr = explode(',', $picture_code);
			$picture_code = $picture_code_arr[1];
		}
		$basedir = $bp_upload['path'];
		$baseurl = $bp_upload['url'];
		if(!file_exists($basedir)){@wp_mkdir_p( $basedir );}
		$filename = $clicked_pic.'_'.$user_id.'.jpg';
		$outputFile = $basedir.'/'.$filename;
		$imageurl = $outputFileURL = $baseurl.'/'.$filename;
		
		$quality = 70;
		if(file_exists($outputFile)){@unlink($outputFile);}
		$data = base64_decode($picture_code);
		$image = imagecreatefromstring($data);
		$imageSave = imagejpeg($image, $outputFile, $quality);
		imagedestroy($image);
		if($outputFile && $clicked_pic=='cover_pic'){
			update_user_meta( $user_id, 'bbp_cover_pic', $imageurl);
		}elseif($outputFile && $clicked_pic=='profile_pic'){
			$imgdata = @getimagesize( $outputFile );
			$img_width = $imgdata[0];
			$img_height = $imgdata[1];
			$upload_dir = wp_upload_dir();
			$existing_avatar_path = str_replace( $upload_dir['basedir'], '', $outputFile );
			$args = array(
				'item_id'       => $user_id,
				'original_file' => $existing_avatar_path,
				'crop_x'        => 0,
				'crop_y'        => 0,
				'crop_w'        => $img_width,
				'crop_h'        => $img_height
			);
			
			if (bp_core_avatar_handle_crop( $args ) ) {
				$imageurl = bp_core_fetch_avatar( array( 'item_id' => $user_id,'html'=>false,'type' => 'full'));
				// Add the activity
				bp_activity_add( array(
					'user_id'   => $user_id,
					'component' => 'profile',
					'type'      => 'new_avatar'
				) );
				$oReturn->success->msg = 'Image uploaded successfully.';
			}else{
				$error = 'Upload error';
			}
		}
		$oReturn->imageurl = $imageurl;
		$oReturn->error = $error;
		return  $oReturn;
	
	}
	/************************************************
	EDIT PROFILE API
	The filed name should be like thefieldid_1, thefieldid_2,thefieldid_3,thefieldid_4.........
	where "thefieldid_" == is prefix variable and 1,2,3.... are the field id to store in buddypress db.
	api url : http://siteurl.com/api/buddypressread/profile_set_profile/
	************************************************/
	 public function profile_set_profile() {		
		
		//The data only for testing purpose.
		//$_POST['data']='{"1":"Test UserName","5":"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#039;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.\n","2":"Male","3":"Native American","4":"Average","21":"Fit","32":"Kosher","39":"Sometimes","43":"Sometimes","47":"English","6":"Afghanistan","7":"Surat"}';
		//$_POST['userid'] = 1;
		
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['data']){$oReturn->message = __('Wrong post data.','aheadzen'); return $oReturn;}
		$userid = $_POST['userid'];
		if(!$userid){$oReturn->message = 'Wrong user ID.'; return $oReturn;}
		if (!bp_has_profile(array('user_id' => $userid))) {
			return $this->error('xprofile', 0);
		}
		$data = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $_POST['data'] );
		$data = json_decode( stripslashes($data) );		
		
		foreach($data as $fieldid=>$val)
		{
			$fieldid = (int)$fieldid;
			if($fieldid && $fieldid >0){
				$field_updated = xprofile_set_field_data( $fieldid, $userid, $val);
			}
		}
		
		// Add the activity
		bp_activity_add( array(
			'user_id'   => $userid,
			'component' => 'xprofile',
			'type'      => 'updated_profile'
		) );
		$oReturn->success->id = $userid;
		$oReturn->success->message = __('User Profile Updated Successfully.','aheadzen');
		return  $oReturn;
	 }
	 
	/**
     * Returns an Array with all activities
     * @param int pages: number of pages to display (default unset)
     * @param int offset: number of entries per page (default 10 if pages is set, otherwise unset)
     * @param int limit: number of maximum results (default 0 for unlimited)
     * @param String sort: sort ASC or DESC (default DESC)
     * @param String comments: 'stream' for within stream display, 'threaded' for below each activity item (default unset)
     * @param Int userid: userID to filter on, comma-separated for more than one ID (default unset)
     * @param String component: object to filter on e.g. groups, profile, status, friends (default unset)
     * @param String type: action to filter on e.g. activity_update, profile_updated (default unset)
     * @param int itemid: object ID to filter on e.g. a group_id or forum_id or blog_id etc. (default unset)
     * @param int secondaryitemid: secondary object ID to filter on e.g. a post_id (default unset)
     * @return array activities: an array containing the activities
     */
	 public function activity_get_activities() {
		header("Access-Control-Allow-Origin: *");
        $oReturn = new stdClass();
		$oReturn->success = '';
        $this->init('activity', 'see_activity');
		
		global $table_prefix,$wpdb;
		if(!$this->userid && $_GET['username']){
			$oUser = get_user_by('login', $_GET['username']);
			if($oUser){$this->userid = $oUser->data->ID;}
		}
		
		//$this->userid='1';
		
		$mentionid = $_GET['mentionid'];
		
		if($mentionid){
			global $wpdb,$table_prefix;
			$parent_activity = $wpdb->get_var("select item_id from ".$table_prefix."bp_activity where id=\"$mentionid\"");
			if($parent_activity==0){
				$parent_activity = $mentionid;
			}
			$aParams = array();
			$aParams ['display_comments'] = true;
			$aParams['in'] = array($parent_activity);
			//$aTempActivities = bp_activity_get($aParams);
		}else{		
			if (!bp_has_activities())
				return $this->error('activity');
			if ($this->pages !== 1) {
				$aParams ['max'] = true;
				$aParams ['per_page'] = $this->offset;
				$iPages = $this->pages;
			}

			$aParams ['display_comments'] = $this->comments;
			$aParams ['sort'] = $this->sort;		
			
			if($this->userid){
				$aParams ['filter'] ['user_id'] = $this->userid;
				$aParams ['filter'] ['object'] = $this->component;
				$aParams ['filter'] ['type'] = $this->type;
				$aParams ['filter'] ['primary_id'] = $this->itemid;
				$aParams ['filter'] ['secondary_id'] = $this->secondaryitemid;
			}
			$iLimit = $this->limit;
			
			$page = $_GET['thepage'];
			if(!$page){$page=1;}
			$per_page = $_GET['per_page'];
			if(!$per_page){$per_page=10;}
			$count_total = $_GET['count_total'];
			if(!$count_total){$count_total=100;}
			
			$aParams['page']=$page;
			$aParams['per_page']=$per_page;
			$aParams['count_total']=$count_total;
		}
		
		$aTempActivities = bp_activity_get($aParams);
		
		if (!empty($aTempActivities['activities'])) {
				$acounter=0;
                foreach ($aTempActivities['activities'] as $oActivity) {
					
					if($oActivity->type=='activity_comment'){
						
					}else{
						$user = new BP_Core_User($oActivity->user_id);
						if($user && $user->avatar){
							if($user->avatar){
								preg_match_all('/(src)=("[^"]*")/i',$user->avatar, $user_avatar_result);
								$oActivity->avatar_big = str_replace('"','',$user_avatar_result[2][0]);
							}
							if($user->avatar_thumb){
								preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
								$oActivity->avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
							}
							//preg_match_all('/(src)=("[^"]*")/i',$user->avatar_mini, $user_avatar_result);
							//$oActivity->avatar_mini = str_replace('"','',$user_avatar_result[2][0]);
						}
						$oReturn->activities[$acounter]->id = $oActivity->id;
						$oReturn->activities[$acounter]->component = $oActivity->component;
						$oReturn->activities[$acounter]->user[(int) $oActivity->user_id]->id = $oActivity->user_id;
						$oReturn->activities[$acounter]->user[(int) $oActivity->user_id]->username = $oActivity->user_login;
						$oReturn->activities[$acounter]->user[(int) $oActivity->user_id]->mail = $oActivity->user_email;
						$oReturn->activities[$acounter]->user[(int) $oActivity->user_id]->display_name = $oActivity->user_fullname;
						$oReturn->activities[$acounter]->user[(int) $oActivity->user_id]->avatar_big = $oActivity->avatar_big;
						$oReturn->activities[$acounter]->user[(int) $oActivity->user_id]->avatar_thumb = $oActivity->avatar_thumb;
						$oReturn->activities[$acounter]->type = $oActivity->type;
						$oReturn->activities[$acounter]->time = $oActivity->date_recorded;
						
						if($oActivity->type=='new_avatar'){
							//$oActivity->action = '<a href="'.$oActivity->primary_link.'">'.$oActivity->user_fullname.'</a> changed their profile picture. <br /><img src="'.$oActivity->avatar_thumb.'" alt="" />';
							$oActivity->action = 'Changed their profile picture. <br /><img src="'.$oActivity->avatar_thumb.'" alt="" />';
						}else if($oActivity->type=='updated_profile'){
							if($oActivity->action=='' && $oActivity->content==''){
								//$oActivity->action = '<a href="'.$oActivity->primary_link.'">'.$oActivity->user_fullname.'</a> changed their profile';
								$oActivity->action = 'Changed their profile';
							}										
						}
						$oReturn->activities[$acounter]->action = $oActivity->action;
						$oReturn->activities[$acounter]->content = $oActivity->content;
						$oReturn->activities[$acounter]->is_hidden = $oActivity->hide_sitewide === "0" ? false : true;
						$oReturn->activities[$acounter]->is_spam = $oActivity->is_spam === "0" ? false : true;
						
						$total_votes = $total_up = $total_down = 0;
						$uplink = $downlink = '#';
						$voteed_action = 'up';
						if(class_exists('VoterPluginClass'))
						{
							$arg = array(
								'item_id'=>$oActivity->id,
								'user_id'=>$oActivity->user_id,
								'type'=>'activity',
								);
							
							$votes_str = VoterPluginClass::aheadzen_get_post_all_vote_details($arg);
							if($votes_str){
							$votes = json_decode($votes_str);
							$total_votes = $votes->total_votes;
							$total_up = $votes->total_up;
							$total_down = $votes->total_down;
							$uplink = $votes->post_voter_links->up;
							$downlink = $votes->post_voter_links->down;
							}
							if($_GET['userid']){
								$user_id = $oActivity->user_id;
								$secondary_item_id = $oActivity->id;
								$type = 'activity';
								$item_id = 0;
								$component = 'buddypress';
								$voteed_action = $wpdb->get_var("SELECT action FROM `".$table_prefix."ask_votes` WHERE user_id=\"$user_id\" AND item_id=\"$item_id\" AND component=\"$component\" AND type=\"$type\" AND secondary_item_id=\"$secondary_item_id\"");
							}
						}
						
						$oReturn->activities[$acounter]->vote->total_votes = $total_votes;
						$oReturn->activities[$acounter]->vote->total_up = $total_up;
						$oReturn->activities[$acounter]->vote->total_down = $total_down;
						$oReturn->activities[$acounter]->vote->uplink = $uplink;
						$oReturn->activities[$acounter]->vote->downlink = $downlink;
						$oReturn->activities[$acounter]->vote->action = $voteed_action;
						
					
						if($oActivity->children){
							/*children*/
							$counter=0;
							foreach($oActivity->children as $childoActivity){
							$childuser = new BP_Core_User($childoActivity->user_id);
							if($childuser && $childuser->avatar){
								if($childuser->avatar){
									preg_match_all('/(src)=("[^"]*")/i',$childuser->avatar, $user_avatar_result);
									$childoActivity->avatar_big = str_replace('"','',$user_avatar_result[2][0]);
								}
								if($childuser->avatar_thumb){
									preg_match_all('/(src)=("[^"]*")/i',$childuser->avatar_thumb, $user_avatar_result);
									$childoActivity->avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
								}
							}
							$oReturn->activities[$acounter]->children->$counter->id = $childoActivity->id;
							$oReturn->activities[$acounter]->children->$counter->item_id = $childoActivity->item_id;
							$oReturn->activities[$acounter]->children->$counter->component = $childoActivity->component;
							$oReturn->activities[$acounter]->children->$counter->user->id = $childoActivity->user_id;
							$oReturn->activities[$acounter]->children->$counter->user->username = $childoActivity->user_login;
							$oReturn->activities[$acounter]->children->$counter->user->mail = $childoActivity->user_email;
							$oReturn->activities[$acounter]->children->$counter->user->display_name = $childoActivity->display_name;
							$oReturn->activities[$acounter]->children->$counter->user->avatar_big = $childoActivity->avatar_big;
							$oReturn->activities[$acounter]->children->$counter->user->avatar_thumb = $childoActivity->avatar_thumb;
							$oReturn->activities[$acounter]->children->$counter->type = $childoActivity->type;
							$oReturn->activities[$acounter]->children->$counter->time = $childoActivity->date_recorded;
							$oReturn->activities[$acounter]->children->$counter->action = $childoActivity->action;
							$oReturn->activities[$acounter]->children->$counter->content = $childoActivity->content;
							$oReturn->activities[$acounter]->children->$counter->is_hidden = $childoActivity->hide_sitewide === "0" ? false : true;
							$oReturn->activities[$acounter]->children->$counter->is_spam = $childoActivity->is_spam === "0" ? false : true;
							$user = new BP_Core_User($childoActivity->user_id);
							
							$total_votes = $total_up = $total_down = 0;
							$uplink = $downlink = '#';
							$voteed_action = '';
							if(class_exists('VoterPluginClass'))
							{
								$arg = array(
									'item_id'=>$childoActivity->id,
									'user_id'=>$childoActivity->user_id,
									'type'=>'activity',
									//'component'=>'buddypress',
									);					
								$votes_str = VoterPluginClass::aheadzen_get_post_all_vote_details($arg);
								$votes = json_decode($votes_str);
								
								$total_votes = $votes->total_votes;
								$total_up = $votes->total_up;
								$total_down = $votes->total_down;
								$uplink = $votes->post_voter_links->up;
								$downlink = $votes->post_voter_links->down;
								
								if($_GET['userid']){
									$user_id = $childoActivity->user_id;
									$secondary_item_id = $childoActivity->id;
									$type = $childoActivity->type;
									$item_id = 0;
									$component = 'buddypress';
									$voteed_action = $wpdb->get_var("SELECT action FROM `".$table_prefix."ask_votes` WHERE user_id=\"$user_id\" AND item_id=\"$item_id\" AND component=\"$component\" AND type=\"$type\" AND secondary_item_id=\"$secondary_item_id\"");
									
								}
							}
							
							$oReturn->activities[$acounter]->children->$counter->vote->total_votes = $total_votes;
							$oReturn->activities[$acounter]->children->$counter->vote->total_up = $total_up;
							$oReturn->activities[$acounter]->children->$counter->vote->total_down = $total_down;
							$oReturn->activities[$acounter]->children->$counter->vote->uplink = $uplink;
							$oReturn->activities[$acounter]->children->$counter->vote->downlink = $downlink;
							$oReturn->activities[$acounter]->children->$counter->vote->action = $voteed_action;
							
							$counter++;
							}
							
						}
						$acounter++;
					}
				}
				
				//echo '<pre>';print_r($oReturn);exit;
				$oReturn->total_pages = ceil($aTempActivities['total']/$per_page);
				$oReturn->total_count = $aTempActivities['total'];
            } else {
                return $this->error('activity');
            }
			
			//echo '<pre>';print_r($oReturn);echo '</pre>';
            return $oReturn;
	}
	
	public function activity_mark_spam()
	{
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		
		$activity_id = $_GET['activityid'];
		if(!$activity_id){$oReturn->error = __('No Activity Id.','aheadzen'); return $oReturn;}
		
		/*$activity_data = bp_activity_get(array('in'=>$activity_id));
		if(!$activity_data['activities']){$oReturn->error = __('Wrong Activity.','aheadzen'); return $oReturn;}
		
		$activity = $activity_data['activities'][0];
		bp_activity_mark_as_spam($activity);
		*/
		
		global $wpdb,$table_prefix;
		$res = $wpdb->query("update ".$table_prefix."bp_activity set is_spam=1 where id=\"$activity_id\"");
		if($res){
			$oReturn->success->msg = __('Activity marked as spam successfully.','aheadzen');
			$oReturn->success->id = $activity_id;
		}else{
			$oReturn->error = __('May be wrong activity Id or already spammed.','aheadzen');		
		}		
		return $oReturn;
	}
	
	/**
		 * Returns an array with the profile's fields
		 * @param String username: the username you want information from (required)
		 * @return array profilefields: an array containing the profilefields
		 */
		public function profile_get_profile() {
			header("Access-Control-Allow-Origin: *");
			$this->userid = $_GET['userid'];
			$this->username = $_GET['username'];
			$this->init('xprofile');
			$oReturn = new stdClass();
			$oReturn->success = '';
			$error=0;
			
			if(($this->userid=='' && $this->username === false) || ($this->username && !username_exists($this->username))) {
				return $this->error('xprofile', 1);
			}
			if($this->userid){
				$userid = $this->userid;
			}else{
				$oUser = get_user_by('login', $this->username);
				$userid = $oUser->data->ID;
			}
			
			if (!bp_has_profile(array('user_id' => $userid))) {
				return $this->error('xprofile', 0);
			}
			while (bp_profile_groups(array('user_id' => $userid))) {
				bp_the_profile_group();
				if (bp_profile_group_has_fields()) {
					$sGroupName = bp_get_the_profile_group_name();
					while (bp_profile_fields()) {
						bp_the_profile_field();
						$sFieldName = bp_get_the_profile_field_name();
						if (bp_field_has_data()) {
						   $sFieldValue = strip_tags(bp_get_the_profile_field_value());
						}
						$oReturn->profilefields->$sGroupName->$sFieldName = $sFieldValue;
					}
				}
			}
			/* CUstom changes VAJ - 09-06-2015*/
			$user = new BP_Core_User( $userid );
			if($user->avatar){
				$user_avatar = $user->avatar;
				$avatar_thumb = $user->avatar_thumb;
				$avatar_mini = $user->avatar_mini;
				preg_match_all('/(src)=("[^"]*")/i',$user_avatar, $user_avatar_result);
				$user_avatar_src = str_replace('"','',$user_avatar_result[2][0]);
				preg_match_all('/(src)=("[^"]*")/i',$avatar_mini, $avatar_mini_result);
				$avatar_mini_src = str_replace('"','',$avatar_mini_result[2][0]);
				preg_match_all('/(src)=("[^"]*")/i',$avatar_thumb, $avatar_thumb_result);
				$avatar_thumb_src = str_replace('"','',$avatar_thumb_result[2][0]);
				
				$bbp_cover_pic = get_user_meta( $userid, 'bbp_cover_pic',true);
				if(!$bbp_cover_pic){$bbp_cover_pic=$user_avatar_src;}
				$oReturn->profilefields->photo->avatar = $bbp_cover_pic;
				$oReturn->profilefields->photo->avatar_big = $user_avatar_src;
				$oReturn->profilefields->photo->avatar_thumb = $avatar_thumb_src;
				$oReturn->profilefields->photo->avatar_mini = $avatar_mini_src;
				$oReturn->profilefields->user->username = $user->profile_data['user_login'];
				$oReturn->profilefields->user->userid = $userid;			
				
			}
			/* CUstom changes VAJ - 09-06-2015*/
			return $oReturn;
		}

    /**
     * Returns an array with messages for the current username
     * @param String box: the box you the messages are in (possible values are 'inbox', 'sentbox', 'notices', default is 'inbox')
     * @param int per_page: items to be displayed per page (default 10)
     * @param boolean limit: maximum numbers of emtries (default no limit)
     * @return array messages: contains the messages
     */
    public function messages_get_messages() {
        $this->init('messages');
        $oReturn = new stdClass();

        $aParams ['box'] = $this->box;
        $aParams ['per_page'] = $this->per_page;
        $aParams ['max'] = $this->limit;

        if (bp_has_message_threads($aParams)) {
            while (bp_message_threads()) {
                bp_message_thread();
                $aTemp = new stdClass();
                preg_match("#>(.*?)<#", bp_get_message_thread_from(), $aFrom);
                $oUser = get_user_by('login', $aFrom[1]);
                $aTemp->from[(int) $oUser->data->ID]->username = $aFrom[1];
                $aTemp->from[(int) $oUser->data->ID]->mail = $oUser->data->user_email;
                $aTemp->from[(int) $oUser->data->ID]->display_name = $oUser->data->display_name;
                preg_match("#>(.*?)<#", bp_get_message_thread_to(), $aTo);
                $oUser = get_user_by('login', $aTo[1]);
                $aTemp->to[(int) $oUser->data->ID]->username = $aTo[1];
                $aTemp->to[(int) $oUser->data->ID]->mail = $oUser->data->user_email;
                $aTemp->to[(int) $oUser->data->ID]->display_name = $oUser->data->display_name;
                $aTemp->subject = bp_get_message_thread_subject();
                $aTemp->excerpt = bp_get_message_thread_excerpt();
                $aTemp->link = bp_get_message_thread_view_link();

                $oReturn->messages [(int) bp_get_message_thread_id()] = $aTemp;
            }
        } else {
            return $this->error('messages');
        }
        return $oReturn;
    }

    /**
     * Returns an array with notifications for the current user
     * @param none there are no parameters to be used
     * @return array notifications: the notifications as a link
     */
    public function notifications_get_notifications() {
        $this->init('notifications');
        $oReturn = new stdClass();

        $aNotifications = bp_core_get_notifications_for_user(get_current_user_id());

        if (empty($aNotifications)) {
            return $this->error('notifications');
        }

        foreach ($aNotifications as $sNotificationMessage) {
            $oReturn->notifications [] = $sNotificationMessage;
        }
        $oReturn->count = count($aNotifications);

        return $oReturn;
    }

    /**
     * Returns an array with friends for the given user
     * @param String username: the username you want information from (required)
     * @return array friends: array with the friends the user got
     */
    public function friends_get_friends() {
        $this->init('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }

        $oUser = get_user_by('login', $this->username);

        $sFriends = bp_get_friend_ids($oUser->data->ID);
        $aFriends = explode(",", $sFriends);
        if ($aFriends[0] == "")
            return $this->error('friends', 1);
        foreach ($aFriends as $sFriendID) {
            $oUser = get_user_by('id', $sFriendID);
            $oReturn->friends [(int) $sFriendID]->username = $oUser->data->user_login;
            $oReturn->friends [(int) $sFriendID]->display_name = $oUser->data->display_name;
            $oReturn->friends [(int) $sFriendID]->mail = $oUser->data->user_email;
        }
        $oReturn->count = count($aFriends);
        return $oReturn;
    }

    /**
     * Returns an array with friendship requests for the given user
     * @params String username: the username you want information from (required)
     * @return array friends: an array containing friends with some mor info
     */
    public function friends_get_friendship_request() {
        $this->init('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }
        $oUser = get_user_by('login', $this->username);

        if (!is_user_logged_in() || get_current_user_id() != $oUser->data->ID)
            return $this->error('base', 0);

        $sFriends = bp_get_friendship_requests($oUser->data->ID);
        $aFriends = explode(",", $sFriends);

        if ($aFriends[0] == "0")
            return $this->error('friends', 2);
        foreach ($aFriends as $sFriendID) {
            $oUser = get_user_by('id', $sFriendID);
            $oReturn->friends [(int) $sFriendID]->username = $oUser->data->user_login;
            $oReturn->friends [(int) $sFriendID]->display_name = $oUser->data->display_name;
            $oReturn->friends [(int) $sFriendID]->mail = $oUser->data->user_email;
        }
        $oReturn->count = count($oReturn->friends);
        return $oReturn;
    }

    /**
     * Returns a string with the status of friendship of the two users
     * @param String username: the username you want information from (required)
     * @param String friendname: the name of the possible friend (required)
     * @return string friendshipstatus: 'is_friend', 'not_friends' or 'pending'
     */
    public function friends_get_friendship_status() {
        $this->init('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }

        if ($this->friendname === false || !username_exists($this->friendname)) {
            return $this->error('friends', 3);
        }

        $oUser = get_user_by('login', $this->username);
        $oUserFriend = get_user_by('login', $this->friendname);

        $oReturn->friendshipstatus = friends_check_friendship_status($oUser->data->ID, $oUserFriend->data->ID);
        return $oReturn;
    }
	
	function groups_get_groupdetail()
	{
		$this->init('forums');
		$oReturn = new stdClass();
		
		$group_id = $_GET['groupId'];
		if(!$group_id){ $oReturn->error = __('Wrong group id.','aheadzen'); return $oReturn;}
		$aGroup = groups_get_group( array( 'group_id' => $group_id ) );
		if($aGroup){
			$oReturn->groupfields->id = $aGroup->id;
			$oReturn->groupfields->name = $aGroup->name;
            $oReturn->groupfields->description = $aGroup->description;
            $oReturn->groupfields->status = $aGroup->status;
           
			$oUser = get_user_by('id', $aGroup->creator_id);
			$useravatar_url = bp_core_fetch_avatar(array('object'=>'user','item_id'=>$aGroup->creator_id, 'html'=>false, 'type'=>'full'));
            $oReturn->groupfields->creator->userid = $aGroup->creator_id;
			$oReturn->groupfields->creator->username = $oUser->data->user_login;
            $oReturn->groupfields->creator->mail = $oUser->data->user_email;
            $oReturn->groupfields->creator->display_name = $oUser->data->display_name;
			$oReturn->groupfields->creator->avatar = $useravatar_url;
            $oReturn->groupfields->slug = $aGroup->slug;
            $oReturn->groupfields->is_forum_enabled = $aGroup->enable_forum == "1" ? true : false;
            $oReturn->groupfields->date_created = $aGroup->date_created;
            $oReturn->groupfields->count_member = $aGroup->total_member_count;
			
			$avatar_url = bp_core_fetch_avatar(array('object'=>'group','item_id'=>$aGroup->id, 'html'=>false, 'type'=>'full'));
			$oReturn->groupfields->avatar = $avatar_url;
			
			$iForumId = groups_get_groupmeta($aGroup->id, 'forum_id');
			if(is_array($iForumId)){
				$iForumId = $iForumId[0];
			}
			if($iForumId){
				$oForum = bp_forums_get_forum((int) $iForumId);
				if($oForum){
					$oReturn->groupfields->forum->id = $oForum->forum_id;
					$oReturn->groupfields->forum->name = $oForum->forum_name;
					$oReturn->groupfields->forum->slug = $oForum->forum_slug;
					$oReturn->groupfields->forum->description = $oForum->forum_desc;
					$oReturn->groupfields->forum->topics_count = (int) $oForum->topics;
					$oReturn->groupfields->forum->post_count = (int) $oForum->posts;
				}
			}
		}
		
		return $oReturn;
	}
    /**
     * Returns an array with groups matching to the given parameters
     * @param String username: the username you want information from (default => all groups)
     * @param Boolean show_hidden: Show hidden groups to non-admins (default: false)
     * @param String type: active, newest, alphabetical, random, popular, most-forum-topics or most-forum-posts (default active)
     * @param int page: The page to return if limiting per page (default 1)
     * @param int per_page: The number of results to return per page (default 20)
     * @return array groups: array with meta infos
     */
    public function groups_get_groups() {
        //$this->init('groups');
		$this->init('forums');
		$oReturn = new stdClass();

        if ($this->username !== false || username_exists($this->username)) {
            $oUser = get_user_by('login', $this->username);
            $aParams ['user_id'] = $oUser->data->ID;
        }

        $aParams ['show_hidden'] = $this->show_hidden;
        $aParams ['type'] = $this->type;
        $aParams ['page'] = $this->page;
        $aParams ['per_page'] = $this->per_page;

        $aGroups = groups_get_groups($aParams);
		
		if ($aGroups['total'] == "0")
            return $this->error('groups', 0);
		
		$counter = 0;
        foreach ($aGroups['groups'] as $aGroup) {
			$oReturn->groups[$counter]->id = $aGroup->id;
			$oReturn->groups[$counter]->name = $aGroup->name;
            $oReturn->groups[$counter]->description = $aGroup->description;
            $oReturn->groups[$counter]->status = $aGroup->status;
            if ($aGroup->status == "private" && !is_user_logged_in() && !$aGroup->is_member === true)
                continue;
            $oUser = get_user_by('id', $aGroup->creator_id);
			$useravatar_url = bp_core_fetch_avatar(array('object'=>'user','item_id'=>$aGroup->creator_id, 'html'=>false, 'type'=>'full'));
            $oReturn->groups[$counter]->creator->userid = $aGroup->creator_id;
			$oReturn->groups[$counter]->creator->username = $oUser->data->user_login;
            $oReturn->groups[$counter]->creator->mail = $oUser->data->user_email;
            $oReturn->groups[$counter]->creator->display_name = $oUser->data->display_name;
			$oReturn->groups[$counter]->creator->avatar = $useravatar_url;
            $oReturn->groups[$counter]->slug = $aGroup->slug;
            $oReturn->groups[$counter]->is_forum_enabled = $aGroup->enable_forum == "1" ? true : false;
            $oReturn->groups[$counter]->date_created = $aGroup->date_created;
            $oReturn->groups[$counter]->count_member = $aGroup->total_member_count;
			
			$avatar_url = bp_core_fetch_avatar(array('object'=>'group','item_id'=>$aGroup->id, 'html'=>false, 'type'=>'full'));
			$oReturn->groups[$counter]->avatar = $avatar_url;
			
			$iForumId = groups_get_groupmeta($aGroup->id, 'forum_id');
			if(is_array($iForumId)){
				$iForumId = $iForumId[0];
			}
			if($iForumId){
				$oForum = bp_forums_get_forum((int) $iForumId);
				if($oForum){
					$oReturn->groups[$counter]->forum->id = $oForum->forum_id;
					$oReturn->groups[$counter]->forum->name = $oForum->forum_name;
					$oReturn->groups[$counter]->forum->slug = $oForum->forum_slug;
					$oReturn->groups[$counter]->forum->description = $oForum->forum_desc;
					$oReturn->groups[$counter]->forum->topics_count = (int) $oForum->topics;
					$oReturn->groups[$counter]->forum->post_count = (int) $oForum->posts;
				}
			}
			$counter++;
        }
		
		$oReturn->count = count($aGroups['groups']);

        return $oReturn;
    }

    /**
     * Returns a boolean depending on an existing invite
     * @param String username: the username you want information from (required)
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @param String type: sent to check for sent invites, all to check for all
     * @return boolean is_invited: true if invited, else false
     */
    public function groups_check_user_has_invite_to_group() {
        $this->init('groups');

        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('groups', 1);
        }
        $oUser = get_user_by('login', $this->username);

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('groups', $mGroupName);

        if ($this->type === false || $this->type != "sent" || $this->type != "all")
            $this->type = 'sent';

        $oReturn->is_invited = groups_check_user_has_invite((int) $oUser->data->ID, $this->groupid, $this->type);
        $oReturn->is_invited = is_null($oReturn->is_invited) ? false : true;

        return $oReturn;
    }

    /**
     * Returns a boolean depending on an existing memebership request
     * @param String username: the username you want information from (required)
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return boolean membership_requested: true if requested, else false
     */
    public function groups_check_user_membership_request_to_group() {
        $this->init('groups');

        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('groups', 1);
        }
        $oUser = get_user_by('login', $this->username);

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('groups', $mGroupName);

        $oReturn->membership_requested = groups_check_for_membership_request((int) $oUser->data->ID, $this->groupid);
        $oReturn->membership_requested = is_null($oReturn->membership_requested) ? false : true;

        return $oReturn;
    }

    /**
     * Returns an array containing all admins for the given group
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array group_admins: array containing the admins
     */
    public function groups_get_group_admins() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);

        $aGroupAdmins = groups_get_group_admins($this->groupid);
        foreach ($aGroupAdmins as $oGroupAdmin) {
            $oUser = get_user_by('id', $oGroupAdmin->user_id);
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->username = $oUser->data->user_login;
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->mail = $oUser->data->user_email;
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->display_name = $oUser->data->display_name;
        }
        $oReturn->count = count($aGroupAdmins);
        return $oReturn;
    }

    /**
     * Returns an array containing all mods for the given group
     * @params int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @params String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array group_mods: array containing the mods
     */
    public function groups_get_group_mods() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);

        $oReturn->group_mods = groups_get_group_mods($this->groupid);
        $aGroupMods = groups_get_group_mods($this->groupid);
        foreach ($aGroupMods as $aGroupMod) {
            $oUser = get_user_by('id', $aGroupMod->user_id);
            $oReturn->group_mods[(int) $aGroupMod->user_id]->username = $oUser->data->user_login;
            $oReturn->group_mods[(int) $aGroupMod->user_id]->mail = $oUser->data->user_email;
            $oReturn->group_mods[(int) $aGroupMod->user_id]->display_name = $oUser->data->display_name;
        }
        return $oReturn;
    }

    /**
     * Returns an array containing all members for the given group
     * @params int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @params String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @params int limit: maximum members displayed
     * @return array group_members: group members with some more info
     */
    public function groups_get_group_members() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();
		
		if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);
		
		$aMembers = groups_get_group_members($this->groupid, $this->limit);
		
        if ($aMembers === false) {
            $oReturn->group_members = array();
            $oReturn->count = 0;
            return $oReturn;
        }
		$counter=0;
        foreach ($aMembers['members'] as $aMember) {
            $oReturn->group_members[$counter]->id = $aMember->user_id;
			$oReturn->group_members[$counter]->username = $aMember->user_login;
            $oReturn->group_members[$counter]->mail = $aMember->user_email;
            $oReturn->group_members[$counter]->display_name = $aMember->display_name;
			//$oReturn->group_members[$counter]->fullname = $aMember->fullname;
			$oReturn->group_members[$counter]->nicename = $aMember->user_nicename;
			$oReturn->group_members[$counter]->registered = $aMember->user_registered;
			$oReturn->group_members[$counter]->last_activity = $aMember->last_activity;
			$oReturn->group_members[$counter]->friend_count = $aMember->total_friend_count;
			$avatar_url = bp_core_fetch_avatar(array('object'=>'user','item_id'=>$aMember->user_id, 'html'=>false, 'type'=>'full'));
			$oReturn->group_members[$counter]->avatar = $avatar_url;
			$counter++;
        }
		$oReturn->count = $aMembers['count'];

        return $oReturn;
    }

    /**
     * Returns an array containing info about the group forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @return array forums: the group forum with metainfo
     */
    public function groupforum_get_forum() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->groupforum_check_forum_existence();

        if ($mForumExists === false)
            return $this->error('base', 0);
        else if (is_int($mForumExists) && $mForumExists !== true)
            return $this->error('forums', $mForumExists);

        $oForum = bp_forums_get_forum((int) $this->forumid);

        $oReturn->forums[(int) $oForum->forum_id]->name = $oForum->forum_name;
        $oReturn->forums[(int) $oForum->forum_id]->slug = $oForum->forum_slug;
        $oReturn->forums[(int) $oForum->forum_id]->description = $oForum->forum_desc;
        $oReturn->forums[(int) $oForum->forum_id]->topics_count = (int) $oForum->topics;
        $oReturn->forums[(int) $oForum->forum_id]->post_count = (int) $oForum->posts;
        return $oReturn;
    }

    /**
     * Returns an array containing info about the group forum
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array forums: the group forum for the group
     */
    public function groupforum_get_forum_by_group() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('forums', $mGroupExists);

        $oGroup = groups_get_group(array('group_id' => $this->groupid));
        if ($oGroup->enable_forum == "0")
            return $this->error('forums', 0);
        $iForumId = groups_get_groupmeta($oGroup->id, 'forum_id');
        if ($iForumId == "0")
            return $this->error('forums', 1);
        $oForum = bp_forums_get_forum((int) $iForumId);

        $oReturn->forums[(int) $oForum->forum_id]->name = $oForum->forum_name;
        $oReturn->forums[(int) $oForum->forum_id]->slug = $oForum->forum_slug;
        $oReturn->forums[(int) $oForum->forum_id]->description = $oForum->forum_desc;
        $oReturn->forums[(int) $oForum->forum_id]->topics_count = (int) $oForum->topics;
        $oReturn->forums[(int) $oForum->forum_id]->post_count = (int) $oForum->posts;
        return $oReturn;
    }

    /**
     * Returns an array containing the topics from a group's forum
     * @param int forumid: the forumid you are searching for (if not set, forumid is searched; forumid or forumslug required)
     * @param String forumslug: the forumslug to search for (just used if forumid is not set; forumid or forumslug required)
     * @param int page: the page number you want to display (default 1)
     * @param int per_page: the number of results you want per page (default 15)
     * @param String type: newest, popular, unreplied, tag (default newest)
     * @param String tagname: just used if type = tag
     * @param boolean detailed: true for detailed view (default false)
     * @return array topics: all the group forum topics found
     */
    public function groupforum_get_forum_topics() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->groupforum_check_forum_existence();

        if ($mForumExists === false)
            return $this->error('base', 0);
        else if (is_int($mForumExists) && $mForumExists !== true)
            return $this->error('forums', $mForumExists);

        $aConfig = array();
        $aConfig['type'] = $this->type;
        $aConfig['filter'] = $this->type == 'tag' ? $this->tagname : false;
        $aConfig['forum_id'] = $this->forumid;
        $aConfig['page'] = $this->page;
        $aConfig['per_page'] = $this->per_page;

        $aTopics = bp_forums_get_forum_topics($aConfig);
        if (is_null($aTopics))
            $this->error('forums', 7);
        foreach ($aTopics as $aTopic) {
            $oReturn->topics[(int) $aTopic->topic_id]->title = $aTopic->topic_title;
            $oReturn->topics[(int) $aTopic->topic_id]->slug = $aTopic->topic_slug;
            $oUser = get_user_by('id', $aTopic->topic_poster);
            $oReturn->topics[(int) $aTopic->topic_id]->poster[(int) $oUser->data->ID]->username = $oUser->data->user_login;
            $oReturn->topics[(int) $aTopic->topic_id]->poster[(int) $oUser->data->ID]->mail = $oUser->data->user_email;
            $oReturn->topics[(int) $aTopic->topic_id]->poster[(int) $oUser->data->ID]->display_name = $oUser->data->display_name;
            $oReturn->topics[(int) $aTopic->topic_id]->post_count = (int) $aTopic->topic_posts;
            if ($this->detailed === true) {
                $oTopic = bp_forums_get_topic_details($aTopic->topic_id);

                $oUser = get_user_by('id', $oTopic->topic_last_poster);
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster[(int) $oTopic->topic_last_poster]->username = $oUser->data->user_login;
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster[(int) $oTopic->topic_last_poster]->mail = $oUser->data->user_email;
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster[(int) $oTopic->topic_last_poster]->display_name = $oUser->data->display_name;
                $oReturn->topics[(int) $aTopic->topic_id]->start_time = $oTopic->topic_start_time;
                $oReturn->topics[(int) $aTopic->topic_id]->forum_id = (int) $oTopic->forum_id;
                $oReturn->topics[(int) $aTopic->topic_id]->topic_status = $oTopic->topic_status;
                $oReturn->topics[(int) $aTopic->topic_id]->is_open = (int) $oTopic->topic_open === 1 ? true : false;
                $oReturn->topics[(int) $aTopic->topic_id]->is_sticky = (int) $oTopic->topic_sticky === 1 ? true : false;
            }
        }
        $oReturn->count = count($aTopics);
        return $oReturn;
    }

    /**
     * Returns an array containing the posts from a group's forum
     * @param int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicslug required)
     * @param String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslugs required)
     * @param int page: the page number you want to display (default 1)
     * @param int per_page: the number of results you want per page (default 15)
     * @param String order: desc for descending or asc for ascending (default asc)
     * @return array posts: all the group forum posts found
     */
    public function groupforum_get_topic_posts() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mTopicExists = $this->groupforum_check_topic_existence();
        if ($mTopicExists === false)
            return $this->error('base', 0);
        else if (is_int($mTopicExists) && $mTopicExists !== true)
            return $this->error('forums', $mTopicExists);

        $aConfig = array();
        $aConfig['topic_id'] = $this->topicid;
        $aConfig['page'] = $this->page;
        $aConfig['per_page'] = $this->per_page;
        $aConfig['order'] = $this->order;
        $aPosts = bp_forums_get_topic_posts($aConfig);

        foreach ($aPosts as $oPost) {
            $oReturn->posts[(int) $oPost->post_id]->topicid = (int) $oPost->topic_id;
            $oUser = get_user_by('id', (int) $oPost->poster_id);
            $oReturn->posts[(int) $oPost->post_id]->poster[(int) $oPost->poster_id]->username = $oUser->data->user_login;
            $oReturn->posts[(int) $oPost->post_id]->poster[(int) $oPost->poster_id]->mail = $oUser->data->user_email;
            $oReturn->posts[(int) $oPost->post_id]->poster[(int) $oPost->poster_id]->display_name = $oUser->data->display_name;
            $oReturn->posts[(int) $oPost->post_id]->post_text = $oPost->post_text;
            $oReturn->posts[(int) $oPost->post_id]->post_time = $oPost->post_time;
            $oReturn->posts[(int) $oPost->post_id]->post_position = (int) $oPost->post_position;
        }
        $oReturn->count = count($aPosts);

        return $oReturn;
    }

    /**
     * Returns an array containing info about the sitewide forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @return array forums: sitewide forum with some infos
     */
    public function sitewideforum_get_forum() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        foreach ($this->forumid as $iId) {
            $oForum = bbp_get_forum((int) $iId);
            $oReturn->forums[$iId]->title = $oForum->post_title;
            $oReturn->forums[$iId]->name = $oForum->post_name;
            $oUser = get_user_by('id', $oForum->post_author);
            $oReturn->forums[$iId]->author[$oForum->post_author]->username = $oUser->data->user_login;
            $oReturn->forums[$iId]->author[$oForum->post_author]->mail = $oUser->data->user_email;
            $oReturn->forums[$iId]->author[$oForum->post_author]->display_name = $oUser->data->display_name;
            $oReturn->forums[$iId]->date = $oForum->post_date;
            $oReturn->forums[$iId]->last_change = $oForum->post_modified;
            $oReturn->forums[$iId]->status = $oForum->post_status;
            $oReturn->forums[$iId]->name = $oForum->post_name;
            $iTopicCount = bbp_get_forum_topic_count((int) $this->forumid);
            $oReturn->forums[$iId]->topics_count = is_null($iTopicCount) ? 0 : (int) $iTopicCount;
            $iPostCount = bbp_get_forum_post_count((int) $this->forumid);
            $oReturn->forums[$iId]->post_count = is_null($iPostCount) ? 0 : (int) $iPostCount;
        }

        return $oReturn;
    }

    /**
     * Returns an array containing all sitewide forums
     * @params int parentid: all children of the given id (default 0 = all)
     * @return array forums: all sitewide forums
     */
    public function sitewideforum_get_all_forums() {
        $this->init('forums');

        $oReturn = new stdClass();
        global $wpdb;
        $sParentQuery = $this->parentid === false ? "" : " AND post_parent=" . (int) $this->parentid;
        $aForums = $wpdb->get_results($wpdb->prepare(
                        "SELECT ID, post_parent, post_author, post_title, post_date, post_modified
                 FROM   $wpdb->posts
                 WHERE  post_type='forum'" . $sParentQuery
                ));

        if (empty($aForums))
            return $this->error('forums', 9);

        foreach ($aForums as $aForum) {
            $iId = (int) $aForum->ID;
            $oUser = get_user_by('id', (int) $aForum->post_author);
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->username = $oUser->data->user_login;
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->mail = $oUser->data->user_email;
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->display_name = $oUser->data->display_name;
            $oReturn->forums[$iId]->date = $aForum->post_date;
            $oReturn->forums[$iId]->last_changes = $aForum->post_modified;
            $oReturn->forums[$iId]->title = $aForum->post_title;
            $oReturn->forums[$iId]->parent = (int) $aForum->post_parent;
        }
        $oReturn->count = count($aForums);
        return $oReturn;
    }

    /**
     * Returns an array containing all topics of a sitewide forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @param boolean display_content: set this to true if you want the content to be displayed too (default false)
     * @return array forums->topics: array of sitewide forums with the topics in it
     */
    public function sitewideforum_get_forum_topics() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        global $wpdb;
        foreach ($this->forumid as $iId) {
            $aTopics = $wpdb->get_results($wpdb->prepare(
                            "SELECT ID, post_parent, post_author, post_title, post_date, post_modified, post_content
                     FROM   $wpdb->posts
                     WHERE  post_type='topic'
                     AND post_parent='" . $iId . "'"
                    ));
            if (empty($aTopics)) {
                $oReturn->forums[(int) $iId]->topics = "";
                continue;
            }
            foreach ($aTopics as $aTopic) {
                $oUser = get_user_by('id', (int) $aTopic->post_author);
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->username = $oUser->data->user_login;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->mail = $oUser->data->user_email;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->display_name = $oUser->data->display_name;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->date = $aTopic->post_date;
                if ($this->display_content !== false)
                    $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->content = $aTopic->post_content;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->last_changes = $aTopic->post_modified;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->title = $aTopic->post_title;
            }
            $oReturn->forums[(int) $iId]->count = count($aTopics);
        }
        return $oReturn;
    }

    /**
     * Returns an array containing all replies to a topic from a sitewide forum
     * @param int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicsslug required)
     * @param String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslug required)
     * @param boolean display_content: set this to true if you want the content to be displayed too (default false)
     * @return array topics->replies: an array containing the replies
     */
    public function sitewideforum_get_topic_replies() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_topic_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        foreach ($this->topicid as $iId) {
            global $wpdb;
            $aReplies = $wpdb->get_results($wpdb->prepare(
                            "SELECT ID, post_parent, post_author, post_title, post_date, post_modified, post_content
                     FROM   $wpdb->posts
                     WHERE  post_type='reply'
                     AND post_parent='" . $iId . "'"
                    ));

            if (empty($aReplies)) {
                $oReturn->topics[$iId]->replies = "";
                $oReturn->topics[$iId]->count = 0;
                continue;
            }
            foreach ($aReplies as $oReply) {
                $oUser = get_user_by('id', (int) $oReply->post_author);
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->username = $oUser->data->user_login;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->mail = $oUser->data->user_email;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->display_name = $oUser->data->display_name;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->date = $oReply->post_date;
                if ($this->display_content !== false)
                    $oReturn->topics[$iId]->replies[(int) $oReply->ID]->content = $oReply->post_content;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->last_changes = $oReply->post_modified;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->title = $oReply->post_title;
            }
            $oReturn->topics[$iId]->count = count($aReplies);
        }

        return $oReturn;
    }

    /**
     * Returns the settings for the current user
     * @params none no parameters
     * @return object settings: an object full of the settings
     */
    public function settings_get_settings() {
        $this->init('settings');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('settings', 0);
        }

        $oUser = get_user_by('login', $this->username);

        if (!is_user_logged_in() || get_current_user_id() != $oUser->data->ID)
            return $this->error('base', 0);

        $oReturn->user->mail = $oUser->data->user_email;

        $sNewMention = bp_get_user_meta($oUser->data->ID, 'notification_activity_new_mention', true);
        $sNewReply = bp_get_user_meta($oUser->data->ID, 'notification_activity_new_reply', true);
        $sSendRequests = bp_get_user_meta($oUser->data->ID, 'notification_friends_friendship_request', true);
        $sAcceptRequests = bp_get_user_meta($oUser->data->ID, 'notification_friends_friendship_accepted', true);
        $sGroupInvite = bp_get_user_meta($oUser->data->ID, 'notification_groups_invite', true);
        $sGroupUpdate = bp_get_user_meta($oUser->data->ID, 'notification_groups_group_updated', true);
        $sGroupPromo = bp_get_user_meta($oUser->data->ID, 'notification_groups_admin_promotion', true);
        $sGroupRequest = bp_get_user_meta($oUser->data->ID, 'notification_groups_membership_request', true);
        $sNewMessages = bp_get_user_meta($oUser->data->ID, 'notification_messages_new_message', true);
        $sNewNotices = bp_get_user_meta($oUser->data->ID, 'notification_messages_new_notice', true);

        $oReturn->settings->new_mention = $sNewMention == 'yes' ? true : false;
        $oReturn->settings->new_reply = $sNewReply == 'yes' ? true : false;
        $oReturn->settings->send_requests = $sSendRequests == 'yes' ? true : false;
        $oReturn->settings->accept_requests = $sAcceptRequests == 'yes' ? true : false;
        $oReturn->settings->group_invite = $sGroupInvite == 'yes' ? true : false;
        $oReturn->settings->group_update = $sGroupUpdate == 'yes' ? true : false;
        $oReturn->settings->group_promo = $sGroupPromo == 'yes' ? true : false;
        $oReturn->settings->group_request = $sGroupRequest == 'yes' ? true : false;
        $oReturn->settings->new_message = $sNewMessages == 'yes' ? true : false;
        $oReturn->settings->new_notice = $sNewNotices == 'yes' ? true : false;

        return $oReturn;
    }

    public function __call($sName, $aArguments) {
        if (class_exists("BUDDYPRESS_JSON_API_FUNCTION") &&
                method_exists(BUDDYPRESS_JSON_API_FUNCTION, $sName) &&
                is_callable("BUDDYPRESS_JSON_API_FUNCTION::" . $sName)) {
            try {
                return call_user_func_array("BUDDYPRESS_JSON_API_FUNCTION::" . $sName, $aArguments);
            } catch (Exception $e) {
                $oReturn = new stdClass();
                $oReturn->status = "error";
                $oReturn->msg = $e->getMessage();
                die(json_encode($oReturn));
            }
        }
        else
            return NULL;
    }

    public function __get($sName) {
        return isset(BUDDYPRESS_JSON_API_FUNCTION::$sVars[$sName]) ? BUDDYPRESS_JSON_API_FUNCTION::$sVars[$sName] : NULL;
    }

}