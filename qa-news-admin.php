<?php
	class qa_news_admin {

		function option_default($option) {
			
			switch($option) {
			case 'news_plugin_loc':
				return dirname(__FILE__).'/news.html';
			case 'news_plugin_loc_pdf':
				return dirname(__FILE__).'/news.pdf';
			case 'news_plugin_max_q':
				return 5;
			case 'news_plugin_max_a':
				return 5;
			case 'news_plugin_max_c':
				return 5;
			case 'news_plugin_send_days':
				return 7;
			case 'news_plugin_request':
				return 'news';
			case 'news_plugin_request_pdf':
				return 'news.pdf';
			case 'news_plugin_cron_rand':
				return $this->makeRandomString();
			case 'news_plugin_css':
				return file_get_contents(dirname(__FILE__).'/news.css');
			case 'news_plugin_template':
				return file_get_contents(dirname(__FILE__).'/template.html');
			case 'news_plugin_template_question':
				return file_get_contents(dirname(__FILE__).'/question.html');
			case 'news_plugin_template_answer':
				return file_get_contents(dirname(__FILE__).'/answer.html');
			case 'news_plugin_template_comment':
				return file_get_contents(dirname(__FILE__).'/comment.html');
			case 'news_plugin_template_votes':
				return file_get_contents(dirname(__FILE__).'/votes.html');
			default:
				return null;				
			}
			
		}
		
		function allow_template($template)
		{
			return ($template!='admin');
		}	   
			
		function admin_form(&$qa_content)
		{					   
			// Process form input
				
				$ok = null;
				
				if (qa_clicked('news_plugin_process') || qa_clicked('news_plugin_save')) {
			
					qa_opt('news_plugin_active',(bool)qa_post_text('news_plugin_active'));
					
					qa_opt('news_plugin_max_q',(int)qa_post_text('news_plugin_max_q'));
					qa_opt('news_plugin_max_a',(int)qa_post_text('news_plugin_max_a'));
					qa_opt('news_plugin_max_c',(int)qa_post_text('news_plugin_max_c'));
					
					qa_opt('news_plugin_static',(bool)qa_post_text('news_plugin_static'));
					qa_opt('news_plugin_pdf',(bool)qa_post_text('news_plugin_pdf'));
					qa_opt('news_plugin_loc',qa_post_text('news_plugin_loc'));
					qa_opt('news_plugin_loc_pdf',qa_post_text('news_plugin_loc_pdf'));

					qa_opt('news_plugin_send',(bool)qa_post_text('news_plugin_send'));
					qa_opt('news_plugin_send_time',(bool)qa_post_text('news_plugin_send_time'));
					qa_opt('news_plugin_send_cron',(bool)qa_post_text('news_plugin_send_cron'));
					qa_opt('news_plugin_send_days',(int)qa_post_text('news_plugin_send_days'));
					
					qa_opt('news_plugin_request',qa_post_text('news_plugin_request'));
					qa_opt('news_plugin_request_pdf',qa_post_text('news_plugin_request_pdf'));
					
					qa_opt('news_plugin_css',qa_post_text('news_plugin_css'));
					
					qa_opt('news_plugin_template',qa_post_text('news_plugin_template'));
					qa_opt('news_plugin_template_question',qa_post_text('news_plugin_template_question'));
					qa_opt('news_plugin_template_answer',qa_post_text('news_plugin_template_answer'));
					qa_opt('news_plugin_template_comment',qa_post_text('news_plugin_template_comment'));
					qa_opt('news_plugin_template_votes',qa_post_text('news_plugin_template_votes'));
					
					if(qa_clicked('news_plugin_process') && qa_opt('news_plugin_static'))
						$ok = qa_news_plugin_createNewsletter(false,false);
					else
						$ok = qa_lang('admin/options_saved');
				}
				else if (qa_clicked('news_plugin_reset')) {
					foreach($_POST as $i => $v) {
						$def = $this->option_default($i);
						if($def !== null) qa_opt($i,$def);
					}
					qa_opt('news_plugin_cron_rand', $this->option_default('news_plugin_cron_rand'));
				}
				else if (qa_clicked('news_plugin_reset_template')) {
					foreach($_POST as $i => $v) {
						if(strpos($i,'news_plugin_template') === 0 || $i == 'news_plugin_css') {
							$def = $this->option_default($i);
							if($def !== null) qa_opt($i,$def);
						}
					}					
				}

			// Create the form for display
				
			$fields = array();
			
			$fields[] = array(
				'label' => 'Activate Plugin',
				'tags' => 'NAME="news_plugin_active"',
				'value' => qa_opt('news_plugin_active'),
				'type' => 'checkbox',
			);
			$fields[] = array(
				'type' => 'blank',
			);
			
			
			$fields[] = array(
				'label' => 'Maximum number of questions to include',
				'tags' => 'NAME="news_plugin_max_q"',
				'value' => qa_opt('news_plugin_max_q'),
				'type' => 'number',
			);
			$fields[] = array(
				'label' => 'Maximum number of answers to include',
				'tags' => 'NAME="news_plugin_max_a"',
				'value' => qa_opt('news_plugin_max_a'),
				'type' => 'number',
			);
			$fields[] = array(
				'label' => 'Maximum number of comments to include',
				'tags' => 'NAME="news_plugin_max_c"',
				'value' => qa_opt('news_plugin_max_c'),
				'type' => 'number',
			);			

			$fields[] = array(
				'label' => 'Show votes',
				'tags' => 'NAME="news_plugin_include_votes"',
				'value' => qa_opt('news_plugin_template_votes'),
				'type' => 'checkbox',
			);

			$fields[] = array(
				'type' => 'blank',
			);

			$fields[] = array(
				'label' => 'Newsletter Location (must be writable)',
				'tags' => 'NAME="news_plugin_loc"',
				'value' => qa_opt('news_plugin_loc'),
			);
			$fields[] = array(
				'type' => 'blank',
			);
			
			$fields[] = array(
				'label' => 'Create Static PDF',
				'note' => '<i>requires wkhtmltopdf - see README.rst</i>',
				'tags' => 'onclick="if(this.checked) $(\'#news_plugin_loc_pdf\').show(); else $(\'#news_plugin_loc_pdf\').hide();" NAME="news_plugin_pdf"',
				'value' => qa_opt('news_plugin_pdf'),
				'type' => 'checkbox',
			);
			$fields[] = array(
				'value' => '<span id="news_plugin_loc_pdf" style="display:'.(qa_opt('news_plugin_pdf')?'block':'none').'">Location (must be writable): <input name="news_plugin_loc_pdf" value="'.qa_opt('news_plugin_loc_pdf').'"></span>',
				'type' => 'static',
			);
			$fields[] = array(
				'type' => 'blank',
			);
			$fields[] = array(
				'label' => 'Send Newsletter',
				'note' => '<i>Allow subscribing to and sending out newsletter</i>',
				'tags' => 'onclick="if(this.checked) $(\'#news_plugin_send_days\').show(); else $(\'#news_plugin_send_days\').hide();" NAME="news_plugin_send"',
				'value' => qa_opt('news_plugin_send'),
				'type' => 'checkbox',
			);
			
			$cron_url = qa_opt('site_url').qa_opt('news_plugin_request').'?cron='.qa_opt('news_plugin_cron_rand');
			
			$fields[] = array(
				'value' => '<div id="news_plugin_send_days" style="display:'.(qa_opt('news_plugin_send')?'block':'none').'">Interval after which cron will be called:&nbsp;<input name="news_plugin_send_days" value="'.qa_opt('news_plugin_send_days').'" size="3">&nbsp;days<br/><i>Set this to the interval at which you will run cron jobs (will show on the user profile page).</i><br/><br/>The newsletter will be sent on accessing the cron url below<br/><span style="font-style:italic;">url is currently <a href="'.$cron_url.'">'.$cron_url.'</a><br/><i>As a security precaution, cron can only be run maximum once per day.  This url is reset when you reset the options for this plugin.</span></div>',
				'type' => 'static',
			);
			$fields[] = array(
				'type' => 'blank',
			);

			$fields[] = array(
				'label' => 'Newsletter Permalink',
				'note' => '<i>the url used to access the news, either via static file, or on the fly</i>',
				'tags' => 'NAME="news_plugin_request"',
				'value' => qa_opt('news_plugin_request'),
			);

			$fields[] = array(
				'label' => 'Newsletter PDF Permalink',
				'note' => '<i>the url used to access the PDF file; should correspond with static PDF location above</i>',
				'tags' => 'NAME="news_plugin_request_pdf"',
				'value' => qa_opt('news_plugin_request_pdf'),
			);
			$fields[] = array(
				'type' => 'blank',
			);

			$fields[] = array(
				'label' => 'Newsletter CSS',
				'note' => '<i>news.css</i>',
				'tags' => 'NAME="news_plugin_css"',
				'value' => qa_opt('news_plugin_css'),
				'type' => 'textarea',
				'rows' => '10',
			);

			$fields[] = array(
				'type' => 'blank',
			);

			$fields[] = array(
				'label' => 'Newsletter Template',
				'note' => '<i>template.html</i>',
				'tags' => 'NAME="news_plugin_template"',
				'value' => qa_opt('news_plugin_template'),
				'type' => 'textarea',
				'rows' => '10',
			);
			$fields[] = array(
				'label' => 'Question Template',
				'note' => '<i>question.html</i>',
				'tags' => 'NAME="news_plugin_template_question"',
				'value' => qa_opt('news_plugin_template_question'),
				'type' => 'textarea',
				'rows' => '10',
			);
			$fields[] = array(
				'label' => 'Answer Template',
				'note' => '<i>answer.html</i>',
				'tags' => 'NAME="news_plugin_template_answer"',
				'value' => qa_opt('news_plugin_template_answer'),
				'type' => 'textarea',
				'rows' => '10',
			);
			$fields[] = array(
				'label' => 'Comment Template',
				'note' => '<i>comment.html</i>',
				'tags' => 'NAME="news_plugin_template_comment"',
				'value' => qa_opt('news_plugin_template_comment'),
				'type' => 'textarea',
				'rows' => '10',
			);
			$fields[] = array(
				'label' => 'Votes Template',
				'note' => '<i>votes.html</i>',
				'tags' => 'NAME="news_plugin_template_votes"',
				'value' => qa_opt('news_plugin_template_votes'),
				'type' => 'textarea',
				'rows' => '10',
			);

			return array(		   
				'ok' => ($ok && !isset($error)) ? $ok : null,
					
				'fields' => $fields,
			 
				'buttons' => array(
					array(
						'label' => qa_lang_html('admin/save_options_button'),
						'tags' => 'NAME="news_plugin_save"',
					),
					array(
						'label' => 'Process',
						'tags' => 'NAME="news_plugin_process"',
					),
                    array(
                        'label' => qa_lang_html('admin/reset_options_button'),
                        'tags' => 'NAME="news_plugin_reset"',
                    ),
                    array(
                        'label' => 'Reset template only',
                        'tags' => 'NAME="news_plugin_reset_template"',
                    ),
				),
			);
		}

		function makeRandomString($bits = 64) {
			$n = rand(10e16, 10e20);
			return base_convert($n, 10, 36);
			return $return;
		}

	}

