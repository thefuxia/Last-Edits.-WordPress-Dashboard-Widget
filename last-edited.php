<?php
/*
Plugin Name: Last Edits
Plugin URI:  http://toscho.de/
Description: Adds a dashboard widget to show your last edited posts and pages.
Version:     0.3
Author:      Thomas Scholz
Author URI:  http://toscho.de
Created:     14.06.2010

Changelog

v0.3 (08.09.2010)
	* Fixed unknown authors
	* include all post status
	* cut off long titles

v0.2 (0.3.09.2010)
	* Added author

v0.1 (14.06.2010)
	* Initial release
*/

if ( ! class_exists('Toscho_Dashboard_Widget') )
{
	require_once dirname(__FILE__) . DIRECTORY_SEPARATOR
		. 'class.Toscho_Dashboard_Widget.php';
}
/**
 * @todo Track changes on profiles, comments and links.
 */
class Last_Edited extends Toscho_Dashboard_Widget
{
	protected
		$widget_id   = __CLASS__
	,	$widget_name = 'Letzte Änderungen'
	,	$unknown_user = '<i>Unbekannt</i>'
	;

	public function callback()
	{
		$lastposts = $this->query();

		if ( ! $lastposts)
		{
			print 'Konnte Liste nicht abrufen.';
			return;
		}

		print '<table style="width:100%;line-height:1.4">';

		foreach($lastposts as $post)
		{
			print $this->create_row($post);
		}

		print '</table>';
	}

	protected function query($limit = 80)
	{
		global $wpdb;
		$now = current_time('mysql');

		return $wpdb->get_results(
			"SELECT ID, post_title, post_modified, post_status, post_parent
				FROM $wpdb->posts
				WHERE post_modified_gmt < '$now'
			ORDER BY post_modified_gmt DESC
			LIMIT $limit");
	}

	/**
	 * A table row.
	 *
	 * @param  object $post
	 * @return string
	 */
	protected function create_row($post)
	{
		static $cache = array();

		/* First automatic save. Usually without user name.
		 * The next save contains the user name. */
		if ( $this->unknown_user == $this->last_author($post->ID)
		and 'inherit' == $post->post_status
		)
		{
			return '';
		}

		/* Revisions have another post id, their parent is the id we need. */
		$cache_id = (0 != $post->post_parent) ? $post->post_parent : $post->ID;
		$cache_id .= $this->last_author($post->ID);

		/* Okay, we have a later entry seen already. */
		if ( isset ( $cache[$cache_id] ) )
		{
			return '';
		}

		$permalink = get_permalink($post->ID);
		$author    = $this->last_author($post->ID);
		$edit_link = get_edit_post_link( $post->ID );
		$date      = mysql2date('d.m.&#160;H:i', $post->post_modified);
		$status    = $this->german_status($post->post_status);
		$title     = $this->shorttitle($post);

		$out       = "<tr><td>$author</td><td><a title='Ansehen'
			href='$permalink'>$title</a></td><td><a title='Bearbeiten'
			href='$edit_link'>✐</a></td><td>$status </td>
			<td> $date</td></tr>";

		$cache[$cache_id] = 1;

		return $out;
	}

	/**
	 * Translates the WP status into an icon and a title attribute.
	 *
	 * @param  string $status
	 * @return string
	 */
	protected function german_status($status)
	{
		if ( 'publish' == $status ) return '<span title="Publiziert">✔</span>';
		if ( 'draft' == $status ) return '<span title="Entwurf">✄</span>';
		if ( 'trash' == $status ) return '<span title="Mülleimer">✕</span>';

		return $status;
	}

	/**
	 * Shortens the post title to the specified length.
	 *
	 * @param  object $post
	 * @param  int $length
	 * @return string
	 */
	protected function shorttitle($post, $length = 25)
	{
		$link    = get_permalink($post->ID);
		$tooltip = '';
		$title   = $post->post_title;

		/* Maybe an error or NULL. */
		if ( ! is_string($title) or empty ( $title ) )
		{
			$title = "Post-ID $post->ID";
		}

		/* Title is too long. */
		if ( mb_strlen($title, 'UTF-8') > $length )
		{
			$tooltip = ' title="' . esc_attr($title) . '"';
			$title   = mb_substr($post->post_title, 0, $length-3, 'UTF-8')
				. ' …';
		}

		return "<a href='$link'$tooltip>$title</a>";
	}

	/**
	 * Returns the author of the current post.
	 * Highlights the current active user (in an ugly way, yes).
	 *
	 * @param  int    $post_id
	 * @param  string $before
	 * @param  string $after
	 * @return string
	 */
	protected function last_author(
		$post_id
	,	$before = '<b>'
	,	$after = '</b> '
	)
	{
		static $cache = array ();

		/* Sometimes, importet articles don’t have an author. */
		if ( ! $author_id = get_post_meta($post_id, '_edit_last', true) )
		{
			return $this->unknown_user;
		}

		if ( isset ( $cache[$author_id] ) )
		{
			return $before . $cache[$author_id] . $after;
		}

		$last_user         = get_userdata($author_id);

		global $current_user;
		$author = $current_user->ID == $author_id
			? "<span style='background:#fdb;padding:0 3px'>$last_user->display_name</span>"
			: $last_user->display_name;

		if ( ! is_string($author) or empty ( $author ) )
		{
			$author = $this->unknown_user;
		}

		$cache[$author_id] = apply_filters(
			'the_modified_author'
		,	$author
		);

		return $before . $cache[$author_id] . $after;
	}

	public function control_callback() {}
}

new Last_Edited;