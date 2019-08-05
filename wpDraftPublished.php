<?php
require_once dirname(__FILE__) . '/includes/Admin.php';
require_once dirname(__FILE__) . '/includes/PublishPost.php';
/*
Plugin Name: WP Draft Published
Description: Save as draft published pages and posts
Author: Malith Priyashan
Version: 1.0.0
*/
/*  Copyright 2019 Malith Priyashan - email : malith.priyashan.dev@gmail.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if (!class_exists('wpDraftPublished')) {

	class wpDraftPublished
	{
		const WP_DRAFT_POST_TYPES = array('post', 'page', 'features', 'infographics', 'whitepaper'); //@todo: should be taken by admin option page

		public function __construct()
		{
			add_action('init', array($this, 'createDraft'));
			add_action('admin_menu', array($this, 'addTranslationsAdminMenu'));
		}

		public function addTranslationsAdminMenu()
		{

			add_menu_page(
				'Drafts Published',
				'Drafts',
				'manage_options',
				'draft-published',
				array(
					$this,
					'AdminPage'
				),
				'dashicons-welcome-write-blog',
				73
			);
		}

		/*
		* render AdminPage view
		* */
		public function AdminPage()
		{
			/*Show admin page UI*/
			$adminPage = new WpDraftPublishedAdminPage();
			$adminPage->render();
		}

		function createDraft()
		{

			// Admin head
			add_action('admin_head-post.php', array($this, 'adminHead'));
			// Pre-post update
			add_action('pre_post_update', array($this, 'prePostUpdate'));

			// Save post action
			add_action('save_post', array($this, 'postUpdate'), 10);
			add_action('publish_future_post', array($this, 'postUpdate'), 10);

		}

		function adminHead()
		{
			global $post;

			// Check for published pages
			if (in_array($post->post_type, wpDraftPublished::WP_DRAFT_POST_TYPES) && $post->post_status == 'publish') {
				?>
				<script type="text/javascript">
					// Add save draft button to published pages
					jQuery(function ($) {
						$('<input type="submit" class="button button-highlighted" tabindex="4" value="Save Draft" id="save-post" name="save">').prependTo('#save-action');
						$('#preview-action').hide();
					});

				</script>
				<?php
			}

		}

		function prePostUpdate($id)
		{

			// Check if this is an auto save routine. If it is we dont want to do anything
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				return $id;

			// Only continue if this request is for the post or page post type
			if (isset($_POST['post_type']) && sanitize_text_field($_POST['post_type']) && !in_array($_POST['post_type'], wpDraftPublished::WP_DRAFT_POST_TYPES)) {
				return $id;
			}

			// Check permissions
			if (isset($_POST['post_type']) && sanitize_text_field($_POST['post_type'])  && !current_user_can('edit_' . $_POST['post_type'], $id)) {
				return $id;
			}
			// Catch only when a draft is saved of a live page
			if (isset($_REQUEST['save']) && sanitize_text_field($_REQUEST['save']) && $_REQUEST['save'] == 'Save Draft' && sanitize_text_field($_REQUEST['post_status']) &&  $_REQUEST['post_status'] == 'publish') {

				// Prepare the post to duplicate and make it draft
				$draftPost = array('post_status' => 'draft');

				$draftPost['menu_order'] = isset($_REQUEST['menu_order']) ? intval($_REQUEST['menu_order']) : 0;
				$draftPost['comment_status'] = isset($_REQUEST['comment_status']) ? (sanitize_text_field($_REQUEST['ping_status']) == 'open' ? 'open' : 'closed') : '';
				$draftPost['ping_status'] = isset($_REQUEST['ping_status']) ? (sanitize_text_field($_REQUEST['ping_status']) == 'open' ? 'open' : 'closed') : 0;
				$draftPost['post_author'] = isset($_REQUEST['post_author']) ? sanitize_text_field($_REQUEST['post_author']) : '';
				$draftPost['post_category'] = isset($_REQUEST['post_category']) ? (array) $_REQUEST['post_category'] : array();
				$draftPost['post_content'] = isset($_REQUEST['content']) ? wp_kses_post($_REQUEST['content']) : '';
				$draftPost['post_excerpt'] = isset($_REQUEST['excerpt']) ? wp_kses_post($_REQUEST['excerpt']) : '';
				$draftPost['post_parent'] = isset($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
				$draftPost['post_password'] = isset($_REQUEST['post_password']) ? sanitize_text_field($_REQUEST['post_password']) : '';
				$draftPost['post_title'] = isset($_REQUEST['post_title']) ? sanitize_text_field($_REQUEST['post_title']) : '';
				$draftPost['post_type'] = isset($_REQUEST['post_type']) ? sanitize_text_field($_REQUEST['post_type']) : 'post';
				$draftPost['tags_input'] = isset($_REQUEST['tax_input']['post_tag']) ? $_REQUEST['tax_input']['post_tag'] : '';

				// Insert the post into the database
				$newId = wp_insert_post($draftPost);

				// Custom meta data
				$custom = get_post_custom($id);
				foreach ($custom as $ckey => $cvalue) {
					if ($ckey != '_edit_lock' && $ckey != '_edit_last') {
						foreach ($cvalue as $mvalue) {
							add_post_meta($newId, $ckey, $mvalue, true);
						}
					}
				}

				// Add a hidden meta data value to indicate that this is a draft of a live page
				update_post_meta($newId, '_published_draftId', $id);


				// Send user to new edit page
				wp_redirect(admin_url('post.php?action=edit&post=' . $newId));
				exit();

			}

		}

		function postUpdate($id)
		{
			$wpDraftPublishThePost = new WpDraftPublishPost;
			$wpDraftPublishThePost->publishDraftedPostById($id);
		}


	}

	// Create an object from the class when the admin_init action fires
	if (class_exists("wpDraftPublished")) {
		$wpDraftPublished = new wpDraftPublished;
	}
}
?>
