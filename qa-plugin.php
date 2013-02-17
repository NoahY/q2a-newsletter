<?php
        
/*              
        Plugin Name: Newsletter
        Plugin URI: https://github.com/NoahY/q2a-newsletter
        Plugin Update Check URI: https://raw.github.com/NoahY/q2a-newsletter/master/qa-plugin.php
        Plugin Description: Sends out a regularly scheduled newsletter with top questions and answers
        Plugin Version: 0.3
        Plugin Date: 2012-08-12
        Plugin Author: NoahY
        Plugin Author URI:                              
        Plugin License: GPLv3+                           
        Plugin Minimum Question2Answer Version: 1.5
*/                      
                        
                        
        if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
			header('Location: ../../');
			exit;   
        }               

        qa_register_plugin_layer('qa-news-layer.php', 'News Layer');

        qa_register_plugin_module('module', 'qa-news-admin.php', 'qa_news_admin', 'Newsletter');
        
        qa_register_plugin_overrides('qa-news-overrides.php');

		qa_register_plugin_phrases('qa-news-lang-*.php', 'newsletter');


		function qa_news_plugin_createNewsletter($send) {
			$news = qa_opt('news_plugin_template');
			
			// static replacements
			
			$news = str_replace('[css]',qa_opt('news_plugin_css'),$news);
			
				$lastdate = time()-qa_opt('news_plugin_send_days')*24*60*60;
			
			if(qa_opt('news_plugin_max_q') > 0) {
				$selectspec="SELECT postid, BINARY title AS title, BINARY content AS content, format, netvotes, userid FROM ^posts WHERE type='Q' AND FROM_UNIXTIME(#) <= created AND netvotes > 0 ORDER BY netvotes DESC, created ASC LIMIT ".(int)qa_opt('news_plugin_max_q');
				
				$sub = qa_db_query_sub(
					$selectspec,
					$lastdate
				);
				
				while ( ($post=qa_db_read_one_assoc($sub,true)) !== null ) {
					$qcontent = '';
					if(!empty($post['content'])) {
						$viewer=qa_load_viewer($post['content'], $post['format']);
						$content = $viewer->get_html($post['content'], $post['format'], array());
					}

					$one = str_replace('[question-title]',$post['title'],qa_opt('news_plugin_template_question'));
					$one = str_replace('[anchor]','question'.$post['postid'],$one);
					$one = str_replace('[url]',qa_html(qa_q_request($post['postid'],$post['title'])),$one);
					$one = str_replace('[question]',$content,$one);
					
					$votes = str_replace('[number]',($post['netvotes']>0?'+':($post['netvotes']<0?'-':'')).$post['netvotes'],qa_opt('news_plugin_template_votes'));
					$one = str_replace('[voting]',$votes,$one);
					
					$uid = $post['userid'];
					$handles = qa_userids_to_handles(array($uid));
					$handle = $handles[$uid];
					$one = str_replace('[meta]',qa_lang_sub('newsletter/meta','<a href="'.qa_opt('site_url').'user/'.$handle.'">'.$handle.'</a>'),$one);
					 
					$qhtml[] = $one;
				}
			}
			if(qa_opt('news_plugin_max_a') > 0) {
				$selectspec="SELECT a.postid AS postid, a.parentid AS parentid, BINARY a.content AS content, a.format AS format, a.netvotes AS netvotes, a.userid as userid, q.title AS qtitle FROM ^posts AS q, ^posts AS a WHERE a.type='A' AND q.postid=a.parentid AND FROM_UNIXTIME(#) <= a.created AND a.netvotes > 0 ORDER BY a.netvotes DESC, a.created ASC LIMIT ".(int)qa_opt('news_plugin_max_a');
				
				$sub = qa_db_query_sub(
					$selectspec,
					$lastdate
				);
				
				while ( ($post=qa_db_read_one_assoc($sub,true)) !== null ) {
					$content = '';
					if(!empty($post['content'])) {
						$viewer=qa_load_viewer($post['content'], $post['format']);
						$content = $viewer->get_html($post['content'], $post['format'], array());
					}

					$anchor = qa_anchor('C', $post['postid']);
					$purl = qa_path_html(qa_q_request($post['parentid'], $post['qtitle']), null, qa_opt('site_url'));
					$url = qa_path_html(qa_q_request($post['parentid'], $post['qtitle']), null, qa_opt('site_url'),null,$anchor);					
					$response = qa_lang_sub('newsletter/response_to_question','<a href="'.$purl.'">'.$post['qtitle'].'</a>');
					$response = str_replace('[url]',$url,$response);
					
					$one = str_replace('[parent-ref]',$response,qa_opt('news_plugin_template_answer'));
					$one = str_replace('[anchor]','answer'.$post['postid'],$one);
					$one = str_replace('[answer]',$content,$one);

					$votes = str_replace('[number]',($post['netvotes']>0?'+':($post['netvotes']<0?'-':'')).$post['netvotes'],qa_opt('news_plugin_template_votes'));
					$one = str_replace('[voting]',$votes,$one);
					
					$uid = $post['userid'];
					$handles = qa_userids_to_handles(array($uid));
					$handle = $handles[$uid];
					$one = str_replace('[meta]',qa_lang_sub('newsletter/meta','<a href="'.qa_opt('site_url').'user/'.$handle.'">'.$handle.'</a>'),$one);
					 
					$ahtml[] = $one;
				}
			}
			if(qa_opt('news_plugin_max_c') > 0) {
				$selectspec="SELECT c.postid AS postid, c.parentid AS parentid, BINARY c.content AS content, c.format AS format, c.netvotes AS netvotes, c.userid as userid, p.title AS ptitle, p.parentid AS gpostid, g.title AS gtitle FROM ^posts AS c INNER JOIN ^posts AS p ON c.type='C' AND p.postid=c.parentid AND FROM_UNIXTIME(#) <= c.created LEFT JOIN ^posts AS g ON g.postid=p.parentid AND g.type='Q' AND c.netvotes > 0 ORDER BY c.netvotes DESC, c.created ASC LIMIT ".(int)qa_opt('news_plugin_max_a');
				
				$sub = qa_db_query_sub(
					$selectspec,
					$lastdate
				);
				
				while ( ($post=qa_db_read_one_assoc($sub,true)) !== null ) {
					$content = '';
					if(!empty($post['content'])) {
						$viewer=qa_load_viewer($post['content'], $post['format']);
						$content = $viewer->get_html($post['content'], $post['format'], array());
					}
					
					if(isset($post['gtitle'])) {
						$parent = 'answer';
						$title = $post['gtitle'];
						$parentid = $post['gpostid'];
						$aurl = qa_path_html(qa_q_request($post['parentid'], $title), null, qa_opt('site_url'),null,qa_anchor('A',$post['parentid']));
					}
					else {
						$parent = 'question';
						$title = $post['ptitle'];
						$parentid = $post['parentid'];
					}
					
					$anchor = qa_anchor('C', $post['postid']);
					$purl = qa_path_html(qa_q_request($parentid, $title), null, qa_opt('site_url'));
					$url = qa_path_html(qa_q_request($parentid, $title), null, qa_opt('site_url'),null,$anchor);
					$response = qa_lang_sub('newsletter/response_to_'.$parent,'<a href="'.$purl.'">'.$title.'</a>');
					$response = str_replace('[url]',$url,$response);
					if(isset($aurl))
						$response = str_replace('[aurl]',$aurl,$response);
						

					$one = str_replace('[parent-ref]',$response,qa_opt('news_plugin_template_comment'));
					$one = str_replace('[anchor]','comment'.$post['postid'],$one);
					$one = str_replace('[comment]',$content,$one);

					$votes = str_replace('[number]',($post['netvotes']>0?'+':($post['netvotes']<0?'-':'')).$post['netvotes'],qa_opt('news_plugin_template_votes'));
					$one = str_replace('[voting]',$votes,$one);
					
					$uid = $post['userid'];
					$handles = qa_userids_to_handles(array($uid));
					$handle = $handles[$uid];
					$one = str_replace('[meta]',qa_lang_sub('newsletter/meta','<a href="'.qa_opt('site_url').'user/'.$handle.'">'.$handle.'</a>'),$one);
										 
					$chtml[]= $one;
				}
			}
			$news = str_replace('[questions]',implode('<hr class="inner">',$qhtml),$news);
			$news = str_replace('[answers]',implode('<hr class="inner">',$ahtml),$news);
			$news = str_replace('[comments]',implode('<hr class="inner">',$chtml),$news);
			
			// misc subs
			
			$news = str_replace('[intro]',qa_lang('newsletter/intro'),$news);
			$news = str_replace('[footer]',qa_lang('newsletter/footer'),$news);

			$news = str_replace('[site-title]',qa_opt('site_title'),$news);
			$news = str_replace('[site-url]',qa_opt('site_url'),$news);
			$news = str_replace('[last-date]',date('M j, Y',$lastdate),$news);
			$news = str_replace('[date]',date('M j, Y'),$news);
			$news = str_replace('[days]',qa_opt('news_plugin_send_days'),$news);
			$news = str_replace('[profile-url]',qa_path('my-profile'),$news);
			
			error_log('Q2A Newsletter Created on '.date('M j, Y \a\t H\:i\:s'));
			
			file_put_contents(qa_opt('news_plugin_loc'),$news);
			
			if(qa_opt('news_plugin_pdf'))
				qa_news_plugin_create_pdf();

			if($send) {
				qa_news_plugin_send_newsletter($news);
				qa_opt('news_plugin_send_last',time());
				return 'Newsletter Sent';
			}

			return 'Newsletter Created';
		    
		    //return 'Error creating '.qa_opt('news_plugin_loc').'; check the error log.';
		}
		function qa_news_plugin_send_newsletter($news){
			
			$users = qa_db_read_all_values(
				qa_db_query_sub("SELECT userid FROM qa_usermetas WHERE title = $ AND content = $",
				'newsletter','1'
				)
			);
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';

			$handles = qa_userids_to_handles($users);
			
			foreach($users as $userid) {
				$handle = $handles[$userid];

				if (QA_FINAL_EXTERNAL_USERS) {
					$email=qa_get_user_email($userid);
				
				} else {
					$useraccount=qa_db_select_with_pending(
						qa_db_user_account_selectspec($userid, true)
					);
					$email=@$useraccount['email'];
				}
				
				qa_send_email(array(
					'fromemail' => qa_opt('from_email'),
					'fromname' => qa_opt('site_title'),
					'toemail' => $email,
					'toname' => $handle,
					'subject' => qa_opt('site_title').' '.qa_lang('newsletter/newsletter'),
					'body' => $news,
					'html' => true,
				));
			}
			error_log('Q2A Newsletter Sent on '.date('M j, Y \a\t H\:i\:s'));
		}
	
		function qa_news_plugin_create_pdf($return=false) {
				
			include 'wkhtmltopdf.php';

			//echo $html;

			$pdf = new WKPDF();

			$pdf->render_q2a();
			
			if($return)
				$pdf->output(WKPDF::$PDF_DOWNLOAD,'news.pdf'); 
			else
				$pdf->output(WKPDF::$PDF_SAVEFILE,qa_opt('news_plugin_loc_pdf')); 

			error_log('Q2A PDF Newsletter Created on '.date('M j, Y \a\t H\:i\:s'));
		}
		if(!function_exists('qa_get_user_name')) {
			function qa_get_user_name($uid) {

				$handles = qa_userids_to_handles(array($uid));
				$handle = $handles[$uid];

				if(QA_FINAL_EXTERNAL_USERS) {
					$user_info = get_userdata($uid);
					if ($user_info->display_name)
						$name = $user_info->display_name;
				}
				else {
					$name = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT title AS name FROM ^userprofile '.
							'WHERE userid=# AND title=$',
							$uid, 'name'
						),
						true
					);
				}
				if(!@$name)
					$name = $handle;

				return strlen($handle) ? ('<A HREF="'.qa_path_html('user/'.$handle).
					'" CLASS="qa-user-link">'.qa_html($name).'</A>') : 'Anonymous';
			}
		}

                        
/*                              
        Omit PHP closing tag to help avoid accidental output
*/                              
                          

