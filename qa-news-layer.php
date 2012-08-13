<?php

	class qa_html_theme_layer extends qa_html_theme_base {

		function main_parts($content)
		{
			$userid = qa_get_logged_in_userid();

			if (qa_opt('news_plugin_active') && $this->template == 'user' && qa_opt('news_plugin_send') && !qa_get('tab') && $content['raw']['userid'] == $userid)
				$content['form-newsletter'] = $this->newsletterForm();

			qa_html_theme_base::main_parts($content);

		}
		function newsletterForm() {

			
			$fields = array();
			$ok = null;
			$tags = null;
			$buttons = array();
			
			$userid = qa_get_logged_in_userid();
			
			require_once QA_INCLUDE_DIR.'qa-db-metas.php';
			if(qa_clicked('newsletter_send_save')) {
				qa_db_usermeta_set($userid, 'newsletter',(bool)qa_post_text('newsletter_send_me'));
				$ok = qa_lang('badges/badge_notified_email_me');
			}
			$selected = (bool)qa_db_usermeta_get($userid, 'newsletter');

			$tags = 'id="badge-form" action="'.qa_self_html().'#signature_text" method="POST"';
			
			$fields[] = array(
				'type' => 'blank',
			);

			$text = str_replace('^email',$this->getEmail($userid),qa_lang('newsletter/newsletter_send_me'));
			$text = str_replace('^newsletter',qa_opt('site_url').qa_opt('news_plugin_request'),$text);
			$text = str_replace('^days',qa_opt('news_plugin_send_days'),$text);
			
			$fields[] = array(
				'label' => $text,
				'type' => 'checkbox',
				'tags' => 'NAME="newsletter_send_me"',
				'value' => $selected,
			);
								
			$buttons[] = array(
				'label' => qa_lang_html('main/save_button'),
				'tags' => 'NAME="newsletter_send_save"',
			);

			return array(				
				'ok' => ($ok && !isset($error)) ? $ok : null,
				'style' => 'tall',
				'tags' => $tags,
				'title' => qa_lang('newsletter/newsletter'),
				'fields'=>$fields,
				'buttons'=>$buttons,
			);
			
		}
		function getEmail($userid) {
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			if (QA_FINAL_EXTERNAL_USERS) {
					$email=qa_get_user_email($userid);
			
			} else {
				$useraccount=qa_db_select_with_pending(
					qa_db_user_account_selectspec($userid, true)
				);
				$email=@$useraccount['email'];
			}
			return $email;
		}
	}
	
