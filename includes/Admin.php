<?php
require_once dirname(__FILE__) . '/PublishPost.php';

/**
 * User: Malith Priyashan
 */
class WpDraftPublishedAdminPage
{
	public function __construct()
	{
		$this->runDraftPublisher();
	}

	/*
	 * Just some styling for the admin page
	 */
	public function pageStyles()
	{
		echo '	<style>
			table.wp-draft-published {
				font-family: arial, sans-serif;
				border-collapse: collapse;
				margin-top: 20px;
				width: 96%;
			}

			.wp-draft-published td, th {
				border: 1px solid #dddddd;
				text-align: left;
				padding: 8px;
			}

			.wp-draft-published tr:first-child {
				background-color: #dddddd;
			}
		</style>';
	}
	/*
     * Get posts by post Type
     */
	public function getDraftPosts(){
		$this->pageStyles();
		$args = array(
			'post_type' => array('post', 'page', 'features', 'infographics', 'whitepaper'),
			'post_status' => 'draft',
			'posts_per_page' => 100
		);
		$draftPosts = new WP_Query($args);

		return $draftPosts;
	}
	/*
     * HTML for the admin page view
     */
	public function render()
	{
		$this->pageStyles();
		$draftPosts = $this->getDraftPosts();
		?>
		<h2>One click draft publisher</h2>
		<label>If you click publish button all the drafted pages below will be published.</label>
		<br/>
		<hr/>
		<table class="wp-draft-published">
			<tr>
				<th>Title</th>
				<th>Author</th>
				<th>Status</th>
			</tr>
			<?php
			if ($draftPosts->have_posts()) :
				while ($draftPosts->have_posts()) : $draftPosts->the_post();
					$publishedDraftId = get_post_meta(get_the_ID(), '_published_draftId', true);
					if ($publishedDraftId) {
						?>
						<tr>
							<td><?php echo get_the_title(); ?></td>
							<td><?php echo get_the_author(); ?></td>
							<td><?php echo get_post_status(get_the_ID()); ?></td>
						</tr>
						<?php
					}
				endwhile;
			endif;
			wp_reset_postdata();
			?>
			<form method="post" action="admin.php?page=draft-published">
				<input type="hidden" name="wp-draft-published" value="publish-drafts"/>
				<input type="hidden" name="publish" value="Publish"/>
				<input type="submit" name="submit" id="submit"  onclick="return confirm('Do you know what you are doing? If you are not sure please check again. If you press this by mistake you are making WP developers life twice harder than it is.');" class="button button-primary" value="Publish all drafts">
			</form>

		</table>
		<?php
	}

	public function runDraftPublisher(){
		$draftPosts = $this->getDraftPosts();
		$postData = $draftPosts->posts;

		foreach($postData as $post) {
			//var_dump($post);
			$publishedDraftId = get_post_meta($post->ID, '_published_draftId', true);
			if ($publishedDraftId) {
				$draftPostData = array(
					'menu_order' => $post->menu_order,
					'comment_status' => $post->comment_status,
					'ping_status' => $post->ping_status,
					'post_author' => get_the_author_meta( 'display_name' , $post->post_author ),
					'post_category' => get_the_category($post->ID),
					'content' => $post->post_content,
					'excerpt' => $post->post_excerpt,
					'parent_id' => $post->post_parent,
					'post_password' => $post->post_password,
					'post_status' => $post->post_status,
					'post_title' => $post->post_title,
					'post_type' => $post->post_type,
					'tags_input' => get_the_tags($post->ID)
				);
				$this->publishAllDrafts($post->ID, $draftPostData);
			}
		}
	}

	/*
	 *  publishAllDrafts - Will copy draft page or post type to original page.
	 *  @param { $id } string - The post id.
	 * */
	public function publishAllDrafts($id, $postData)
	{
		if (isset($_POST['wp-draft-published'])) {
			if ($_POST['wp-draft-published'] === 'publish-drafts') {
				$wpDraftPublishThePost = new WpDraftPublishPost;
				$wpDraftPublishThePost->publishDraftedPostById($id, true, $postData);
				echo '<div class="notice notice-info publishing-drafts"><p>Publishing...!</p></div>';
				echo "<br />";
				echo '<div class="notice notice-success published-drafts is-dismissible"><p>All drafts were published!</p></div>';
				?>
				<script type="text/javascript">
					// Add save draft button to published pages
					jQuery(function ($) {
						$('.published-drafts').hide();
						setTimeout(function () {
							$('.publishing-drafts').hide();
							$('.published-drafts').show();
						}, 1000);
					});

				</script>
				<?php
			}
		}
	}


}
