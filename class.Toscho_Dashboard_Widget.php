<?php
abstract class Toscho_Dashboard_Widget
{
	protected
		$widget_id    = __CLASS__
	,	$widget_name  = 'Toscho’s Dashboard Widget'
	,	$form_id      = ''
	,	$p_base       = ''
	,	$restriction  = 'edit_others_posts'
	,	$use_control_callback = FALSE
	;

	public function __construct()
	{
		// used by control_callback
		$this->form_id = strtolower(
			str_replace('_', '-', $this->widget_id)
		);
		$this->p_base = plugin_basename( __FILE__ );

		add_action(
			'wp_dashboard_setup'
		,	array ( $this, 'add_to_dashboard')
		);

		add_filter(
			'http_request_args'
		,	array ( $this, 'prevent_plugin_upgrade_check' )
		, 5, 2
		);
	}

	/**
	 * Registers the widget.
	 *
	 * Called on the action 'wp_dashboard_setup'.
	 * If you call wp_add_dashboard_widget() earlier, you
	 * get a fatal error.
	 *
	 * @return void
	 */
	public function add_to_dashboard()
	{
		if ( current_user_can($this->restriction) )
		{
			wp_add_dashboard_widget(
				$this->widget_id
			,	$this->widget_name
			,	array ( $this, 'callback' )
			,	$this->use_control_callback
					? array ( $this, 'control_callback') : NULL
			);
		}
	}

	abstract public function callback();

	public function control_callback()
	{
		print 'Extend me! Extend me! Extend me!';
	}

	/**
	 * Blocks update checks for this plugin.
	 *
	 * @author Mark Jaquith http://markjaquith.wordpress.com
	 * @see    http://wp.me/p56-65
	 * @param  array $r
	 * @param  string $url
	 * @return array
	 */
	public function prevent_plugin_upgrade_check($r, $url)
	{
		if ( 0 !== strpos(
				$url
			,	'http://api.wordpress.org/plugins/update-check') )
		{
			return $r; // Not a plugin update request. Stop.
		}

		$plugins = unserialize( $r['body']['plugins'] );

		unset (
			$plugins->plugins[$this->p_base],
			$plugins->active[array_search($this->p_base, $plugins->active)]
		);

		$r['body']['plugins'] = serialize($plugins);

		return $r;
	}
}