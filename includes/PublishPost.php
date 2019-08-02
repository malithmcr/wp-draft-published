<?php

if (!class_exists('WpDraftPublishPost')) {
	class WpDraftPublishPost
	{
		/*
		 *  publishDraftedPostById - Duplicate current page or post type and make it as a draft then redirect to the new draft.
		 *  @param { $id } string - The post id.
		 *  @param { $redirect } bool - Redirect after copying draft page content to original and delete.
		 *  @param { $location } mix -  optional, false (default) | direct | outside. Direct
		 *  @param { $postData } Array - optional
		 * */
		public function publishDraftedPostById($id, $location = false, $postData = array())
		{
			if ((isset($_REQUEST['publish']) && $_REQUEST['publish'] != 'Schedule') || (defined('DOING_CRON') && DOING_CRON) || $location) {

				// Check for post meta that identifies this as a 'live draft'
				$_published_draftId = get_post_meta($id, '_published_draftId', true);

				// If post meta exists then replace live page
				if ($_published_draftId != false) {
					// Duplicate post and set as a draft
					if (!$location) {
						$updatedPost = array(
							'ID' => $_published_draftId,
							'menu_order' => $_REQUEST['menu_order'],
							'comment_status' => ($_REQUEST['comment_status'] == 'open' ? 'open' : 'closed'),
							'ping_status' => ($_REQUEST['ping_status'] == 'open' ? 'open' : 'closed'),
							'post_author' => $_REQUEST['post_author'],
							'post_category' => (isset($_REQUEST['post_category']) ? $_REQUEST['post_category'] : array()),
							'post_content' => $_REQUEST['content'],
							'post_excerpt' => $_REQUEST['excerpt'],
							'post_parent' => $_REQUEST['parent_id'],
							'post_password' => $_REQUEST['post_password'],
							'post_status' => 'publish',
							'post_title' => $_REQUEST['post_title'],
							'post_type' => $_REQUEST['post_type'],
							'tags_input' => (isset($_REQUEST['tax_input']['post_tag']) ? $_REQUEST['tax_input']['post_tag'] : '')
						);
					} else {
						$updatedPost = array(
							'ID' => $_published_draftId,
							'menu_order' => $postData['menu_order'],
							'comment_status' => ($postData['comment_status'] == 'open' ? 'open' : 'closed'),
							'ping_status' => ($postData['ping_status'] == 'open' ? 'open' : 'closed'),
							'post_author' => $postData['post_author'],
							'post_category' => (isset($postData['post_category']) ? $postData['post_category'] : array()),
							'post_content' => $postData['content'],
							'post_excerpt' => $postData['excerpt'],
							'post_parent' => $postData['parent_id'],
							'post_password' => $postData['post_password'],
							'post_status' => 'publish',
							'post_title' => $postData['post_title'],
							'post_type' => $postData['post_type'],
							'tags_input' => (isset($postData['tax_input']['post_tag']) ? $postData['tax_input']['post_tag'] : '')
						);
					}

					// Insert the post into the database
					wp_update_post($updatedPost);

					// Clear existing meta data
					$existing = get_post_custom($_published_draftId);
					foreach ($existing as $ekey => $evalue) {
						delete_post_meta($_published_draftId, $ekey);
					}

					// New custom meta data - from draft
					$custom = get_post_custom($id);
					foreach ($custom as $ckey => $cvalue) {
						if ($ckey != '_edit_lock' && $ckey != '_edit_last' && $ckey != '_published_draftId') {
							foreach ($cvalue as $mvalue) {
								add_post_meta($_published_draftId, $ckey, $mvalue, true);
							}
						}
					}

					// Delete draft post, force delete since 2.9, no sending to trash
					wp_delete_post($id, true);

					// Send user to live edit page if this function not called from the outside
					if (!$location) {
						wp_redirect(admin_url('post.php?action=edit&post=' . $_published_draftId));
						exit();
					}
				}

			}
		}
	}
}
