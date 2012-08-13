<?php
        
/*              
        Plugin Name: Newsletter
        Plugin URI: https://github.com/NoahY/q2a-newsletter
        Plugin Update Check URI: https://github.com/NoahY/q2a-newsletter/raw/master/qa-plugin.php
        Plugin Description: Sends out a regularly scheduled newsletter with top questions and answers
        Plugin Version: 0.1
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


		function qa_news_plugin_createNewsletter($return=false,$send=true) {

			$news = qa_opt('news_plugin_template');
			
			// static replacements
			
			$news = str_replace('[css]',qa_opt('news_plugin_css'),$news);

			$qhtml = '';
			$ahtml = '';
			$chtml = '';
			
			if(qa_opt('news_plugin_max_q') > 0) {
				$selectspec="SELECT postid, BINARY title AS title, BINARY content AS content, format, netvotes FROM ^posts WHERE type='Q' AND DATE_SUB(CURDATE(),INTERVAL # DAY) <= created ORDER BY netvotes DESC, created ASC LIMIT ".(int)qa_opt('news_plugin_max_q');
				
				$qsub = qa_db_query_sub(
					$selectspec,
					qa_opt('news_plugin_send_days')
				);
				
				while ( ($q=qa_db_read_one_assoc($qsub,true)) !== null ) {
					$qcontent = '';
					if(!empty($q['content'])) {
						$viewer=qa_load_viewer($q['content'], $q['format']);
						$qcontent = $viewer->get_html($q['content'], $q['format'], array());
					}

					$oneq = str_replace('[question-title]',$q['title'],qa_opt('news_plugin_template_question'));
					$oneq = str_replace('[anchor]','question'.$q['postid'],$oneq);
					$oneq = str_replace('[url]',qa_html(qa_q_request($q['postid'],$q['title'])),$oneq);
					$oneq = str_replace('[question]',$qcontent,$oneq);
					
					$votes = str_replace('[number]',$q['netvotes'],qa_opt('news_plugin_template_votes'));
					$voting = (abs($q['netvotes'])==1) ? qa_lang_html_sub('main/1_vote', $votes, '1') : qa_lang_html_sub('main/x_votes', $votes);
					
					$oneq = str_replace('[voting]',$voting,$oneq);
					 
					$qhtml .= $oneq;
				}
			}
			if(qa_opt('news_plugin_max_a') > 0) {
				$selectspec="SELECT a.postid AS postid, a.parentid AS parentid, BINARY a.content AS content, a.format AS format, a.netvotes AS netvotes, q.title AS qtitle FROM ^posts AS q, ^posts AS a WHERE a.type='A' AND q.postid=a.parentid AND DATE_SUB(CURDATE(),INTERVAL # DAY) <= a.created ORDER BY a.netvotes DESC, a.created ASC LIMIT ".(int)qa_opt('news_plugin_max_a');
				
				$sub = qa_db_query_sub(
					$selectspec,
					qa_opt('news_plugin_send_days')
				);
				
				while ( ($post=qa_db_read_one_assoc($sub,true)) !== null ) {
					$content = '';
					if(!empty($post['content'])) {
						$viewer=qa_load_viewer($post['content'], $post['format']);
						$content = $viewer->get_html($post['content'], $post['format'], array());
					}

					$anchor = qa_anchor('C', $post['postid']);
					$url = qa_path_html(qa_q_request($post['parentid'], $post['qtitle']), null, qa_opt('site_url'),null,$anchor);					
					$response = qa_lang_sub('newsletter/response_to_question',$post['qtitle']);
					$response = str_replace('[url]',$url,$response);
					
					$one = str_replace('[parent-ref]',$response,qa_opt('news_plugin_template_answer'));
					$one = str_replace('[anchor]','answer'.$post['postid'],$one);
					$one = str_replace('[answer]',$content,$one);

					$votes = str_replace('[number]',$post['netvotes'],qa_opt('news_plugin_template_votes'));
					$voting = (abs($post['netvotes'])==1) ? qa_lang_html_sub('main/1_vote', $votes, '1') : qa_lang_html_sub('main/x_votes', $votes);
					$one = str_replace('[voting]',$voting,$one);
					 
					$ahtml .= $one;
				}
			}
			if(qa_opt('news_plugin_max_c') > 0) {
				$selectspec="SELECT c.postid AS postid, c.parentid AS parentid, BINARY c.content AS content, c.format AS format, c.netvotes AS netvotes, p.title AS ptitle, p.parentid AS gpostid, g.title AS gtitle FROM ^posts AS c INNER JOIN ^posts AS p ON c.type='C' AND p.postid=c.parentid AND DATE_SUB(CURDATE(),INTERVAL # DAY) <= c.created LEFT JOIN ^posts AS g ON g.postid=p.parentid AND g.type='Q' ORDER BY c.netvotes DESC, c.created ASC LIMIT ".(int)qa_opt('news_plugin_max_a');
				
				$sub = qa_db_query_sub(
					$selectspec,
					qa_opt('news_plugin_send_days')
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
					}
					else {
						$parent = 'question';
						$title = $post['ptitle'];
						$parentid = $post['parentid'];
					}
					
					$anchor = qa_anchor('C', $post['postid']);
					$url = qa_path_html(qa_q_request($parentid, $title), null, qa_opt('site_url'),null,$anchor);					
					$response = qa_lang_sub('newsletter/response_to_'.$parent,$title);
					$response = str_replace('[url]',$url,$response);

					$one = str_replace('[parent-ref]',$response,qa_opt('news_plugin_template_comment'));
					$one = str_replace('[anchor]','comment'.$post['postid'],$one);
					$one = str_replace('[comment]',$content,$one);

					$votes = str_replace('[number]',$post['netvotes'],qa_opt('news_plugin_template_votes'));
					$voting = (abs($post['netvotes'])==1) ? qa_lang_html_sub('main/1_vote', $votes, '1') : qa_lang_html_sub('main/x_votes', $votes);
					$one = str_replace('[voting]',$voting,$one);
					 
					$chtml .= $one;
				}
			}
			$news = str_replace('[questions]',$qhtml,$news);
			$news = str_replace('[answers]',$ahtml,$news);
			$news = str_replace('[comments]',$chtml,$news);
			
			// misc subs
			
			$news = str_replace('[intro]',qa_lang('newsletter/intro'),$news);

			$news = str_replace('[site-title]',qa_opt('site_title'),$news);
			$news = str_replace('[site-url]',qa_opt('site_url'),$news);
			$news = str_replace('[date]',date('M j, Y'),$news);
			$news = str_replace('[days]',qa_opt('news_plugin_send_days'),$news);
			$news = str_replace('[profile-url]',qa_path('my-profile'),$news);
			
			qa_opt('news_plugin_send_last',time());
			
			error_log('Q2A Newsletter Created on '.date('M j, Y \a\t H\:i\:s'));
			
			if($return)
				return $news;
			
			file_put_contents(qa_opt('news_plugin_loc'),$news);
			
			if(qa_opt('news_plugin_pdf'))
				qa_news_plugin_create_pdf();
			
			if($send)	
				qa_news_plugin_send_newsletter($news);

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
			
			foreach($users as $userid)
				qa_send_notification($userid, '@', qa_get_user_name($userid), qa_opt('site_title').' '.qa_lang('newsletter/newsletter'), $news, array()); // $userid, $email, $handle, $subject, $body, $subs
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
                          

