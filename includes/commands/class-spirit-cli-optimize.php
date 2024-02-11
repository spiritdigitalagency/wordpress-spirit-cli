<?php

class OptimizeCommand extends SpiritCliBase {

	protected $wpdb;

	/**
	 * WP_Optimization constructor.
	 *
	 * @param array $data initial data for optimization.
	 */
	public function __construct() {
		$wpdb = $GLOBALS['wpdb'];
		$this->wpdb = $wpdb;
		parent::__construct();
	}


	public function all( $args = array(), $assoc_args = array() ) {
		$this->revisions();
		$this->drafts();
		$this->trash();
		$this->pingbacks();
		$this->trackbacks();
		$this->comments();
		$this->commentmeta();
//		$this->terms();
		$this->postmeta();
		$this->usermeta();
		$this->scheduler();
		$this->transients();
	}

	/**
	 * Delete comments
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function comments( $args = array(), $assoc_args = array() ) {
		$spam_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->comments . "` WHERE comment_approved = 'spam';";
		$trash_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->comments . "` WHERE comment_approved = 'trash';";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($spam_count_query) . ' spam and ' . $this->wpdb->get_var($trash_count_query) . ' trash comments.');
		$clean = "DELETE c, cm FROM `" . $this->wpdb->comments . "` c LEFT JOIN `" . $this->wpdb->commentmeta . "` cm ON c.comment_ID = cm.comment_id WHERE c.comment_approved IN ('trash','spam');";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete orphan comment meta data
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function commentmeta( $args = array(), $assoc_args = array() ) {
		$orphan_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->commentmeta . "` WHERE comment_id NOT IN (SELECT comment_id FROM `" . $this->wpdb->comments . "`);";
		$akismet_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->commentmeta . "` WHERE meta_key LIKE '%akismet%';";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($orphan_count_query) . ' orphan and ' . $this->wpdb->get_var($akismet_count_query) . ' akismet comment meta.');
		$clean = "DELETE FROM `" . $this->wpdb->commentmeta . "` WHERE comment_id NOT IN (SELECT comment_id FROM `" . $this->wpdb->comments . "`);";
		$this->wpdb->query($clean);
		$clean = "DELETE FROM `" . $this->wpdb->commentmeta . "` WHERE meta_key LIKE '%akismet%'";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete track backs
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function trackbacks( $args = array(), $assoc_args = array() ) {
		$trackback_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->comments . "` WHERE comment_type = 'trackback';";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($trackback_count_query) . ' comment trackbacks.');
		$clean = "DELETE c, cm FROM `" . $this->wpdb->comments . "` c LEFT JOIN `" . $this->wpdb->commentmeta . "` cm ON c.comment_ID = cm.comment_id WHERE comment_type = 'trackback';";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete ping backs
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function pingbacks( $args = array(), $assoc_args = array() ) {
		$pingback_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->comments . "` WHERE comment_type = 'pingback';";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($pingback_count_query) . ' comment pingbacks.');
		$clean = "DELETE c, cm FROM `" . $this->wpdb->comments . "` c LEFT JOIN `" . $this->wpdb->commentmeta . "` cm ON c.comment_ID = cm.comment_id WHERE comment_type = 'pingback';";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete auto drafts
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function drafts( $args = array(), $assoc_args = array() ) {
		$draft_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->posts . "` WHERE post_status = 'auto-draft';";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($draft_count_query) . ' auto draft posts.');
		$clean = "DELETE FROM `" . $this->wpdb->posts . "` WHERE post_status = 'auto-draft';";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete auto drafts
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function trash( $args = array(), $assoc_args = array() ) {
		$trash_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->posts . "` WHERE post_status = 'trash';";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($trash_count_query) . ' trash posts.');
		$clean = "DELETE FROM `" . $this->wpdb->posts . "` WHERE post_status = 'trash';";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete post revisions
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function revisions( $args = array(), $assoc_args = array() ) {
		$revision_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->posts . "` WHERE post_type = 'revision';";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($revision_count_query) . ' post revisions.');
		$clean = "DELETE FROM `" . $this->wpdb->posts . "` WHERE post_type = 'revision';";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete orphan post meta
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function postmeta( $args = array(), $assoc_args = array() ) {
		$orphan_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->postmeta . "` pm LEFT JOIN `" . $this->wpdb->posts . "` p ON pm.post_id = p.ID WHERE p.ID IS NULL;";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($orphan_count_query) . ' orphan post meta.');
		$clean = "DELETE pm FROM `" . $this->wpdb->postmeta . "` pm LEFT JOIN `" . $this->wpdb->posts . "` p ON pm.post_id = p.ID WHERE p.ID IS NULL;";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete orphan term relations
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function terms( $args = array(), $assoc_args = array() ) {
		$orphan_count_query = "SELECT COUNT(*) FROM `" . $this->term_relationships . "` WHERE term_taxonomy_id=1 AND object_id NOT IN (SELECT id FROM `" . $this->wpdb->posts . "`);";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($orphan_count_query) . ' orphan term relations.');
		$clean = "DELETE FROM `" . $this->wpdb->term_relationships . "` WHERE term_taxonomy_id=1 AND object_id NOT IN (SELECT id FROM `" . $this->wpdb->posts . "`);";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete orphan user meta
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function usermeta( $args = array(), $assoc_args = array() ) {
		$orphan_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->usermeta . "` um LEFT JOIN `" . $this->wpdb->users . "` wu ON wu.ID = um.user_id WHERE wu.ID IS NULL;";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($orphan_count_query) . ' orphan user meta.');
		$clean = "DELETE um FROM `" . $this->wpdb->usermeta . "` um LEFT JOIN `" . $this->wpdb->users . "` wu ON wu.ID = um.user_id WHERE wu.ID IS NULL;";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete all transients
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function transients( $args = array(), $assoc_args = array() ) {
		$this->wp( 'transient delete --all');
	}

	/**
	 * Empty all action scheduler entries
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function scheduler( $args = array(), $assoc_args = array() ) {
		$schedules_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->actionscheduler_actions . "` WHERE `status` IN ('canceled','failed','complete');";
		$schedule_logs_count_query = "SELECT COUNT(*) FROM `" . $this->wpdb->actionscheduler_logs . "`;";
		WP_CLI::line('Deleting ' . $this->wpdb->get_var($schedules_count_query) . ' finished schedules and ' . $this->wpdb->get_var($schedule_logs_count_query) . ' logs.');
		$clean = "DELETE FROM `" . $this->wpdb->actionscheduler_actions . "` WHERE `status` IN ('canceled','failed','complete');";
		$this->wpdb->query($clean);
		$clean = "DELETE FROM `" . $this->wpdb->actionscheduler_logs . "`;";
		$this->wpdb->query($clean);
	}

	/**
	 * Delete all images not assigned to a post
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function images( $args = array(), $assoc_args = array() ) {
		WP_CLI::confirm( "Are you sure you want to delete images? This action will require backup rollback to reverse.", $assoc_args );
		$sql = "SELECT p.ID FROM `".$this->wpdb->posts."` p LEFT JOIN `".$this->wpdb->posts."` pp ON pp.ID = p.post_parent WHERE p.post_parent > 0 AND p.post_type = 'attachment' AND pp.ID IS NULL;";

		$attachment_ids = $this->wpdb->get_col($sql);
		$count_ids = count($attachment_ids);

		if ($count_ids > 0) {
			foreach ($attachment_ids as $attachment_id) {
				wp_delete_attachment($attachment_id, true);
			}
		}
	}

}

WP_CLI::add_command( 'spirit optimize', OptimizeCommand::class );
