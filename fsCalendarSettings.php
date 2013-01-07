<?php
class fsCalendarSettings {
	
	function fsCalendarSettings() {
		add_action('admin_init',           array(&$this, 'hookRegisterSettings'));
		add_action('admin_menu',           array(&$this, 'hookAddAdminMenu'));
	}
	
	/**
	 * Registers all options using the WP Settings API
	 * @return void
	 */
	function hookRegisterSettings() {
		foreach(fsCalendar::$plugin_options as $k => $v) {
			register_setting('fse', $k);
		}
		/*register_setting('fse_global', 'fse_number');
		register_setting('fse_global', 'fse_df_wp');
		register_setting('fse_global', 'fse_df');
		register_setting('fse_global', 'fse_tf_wp');
		register_setting('fse_global', 'fse_tf');
		register_setting('fse_global', 'fse_ws_wp');
		register_setting('fse_global', 'fse_ws');
		register_setting('fse_global', 'fse_df_admin');
		register_setting('fse_global', 'fse_df_admin_sep');
		register_setting('fse_global', 'fse_template');
		register_setting('fse_global', 'fse_template_lst');
		register_setting('fse_global', 'fse_show_enddate');
		register_setting('fse_global', 'fse_groupby');
		register_setting('fse_global', 'fse_groupby_header');
		register_setting('fse_global', 'fse_page_create_notice');
		
		register_setting('fse_sp', 'fse_page');
		register_setting('fse_sp', 'fse_page_mark');
		register_setting('fse_sp', 'fse_page_hide');
		
		register_setting('fse_admin', 'fse_adm_gc_enabled');
		register_setting('fse_admin', 'fse_adm_gc_mode');
		register_setting('fse_admin', 'fse_adm_gc_show_week');
		register_setting('fse_admin', 'fse_adm_gc_show_sel');
		
		register_setting('fse_fc', 'fse_fc_tit_week_fmt');
		register_setting('fse_fc', 'fse_fc_tit_month_fmt');
		register_setting('fse_fc', 'fse_fc_tit_day_fmt');
		register_setting('fse_fc', 'fse_fc_col_week_fmt');
		register_setting('fse_fc', 'fse_fc_col_month_fmt');
		register_setting('fse_fc', 'fse_fc_col_day_fmt');
		register_setting('fse', 'fse_load_jquery');
		register_setting('fse', 'fse_load_jqueryui');*/
		
		// add_settings_section($id, $title, $callback, $pagename
		
		// We do not need sections. We need different pages!
		add_settings_section('fse_global', 
							 '', 
							 array($this, 'hookSettingHeader_Global1'), 
							 'fse_global');
		{
			// add_settings_field($id, $title, $callback, $pagename, $section);
			add_settings_field('fse_number', 
							   __('Number of events<br /><small>Number of events to display by default.</small>', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_number'), 
							   'fse_global', 'fse_global');
			add_settings_field('fse_df', 
							   __('Date Format', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_df'), 
							   'fse_global', 'fse_global');
			add_settings_field('fse_tf', 
							   __('Time Format', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_tf'), 
							   'fse_global', 'fse_global');
			add_settings_field('fse_ws', 
							   __('Weeks starts on', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_ws'), 
							   'fse_global', 'fse_global');
			add_settings_field('fse_show_enddate', 
							   __('Show end date', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_show_enddate'), 
							   'fse_global', 'fse_global');
			add_settings_field('fse_allday_hide_time', 
							   __('Show time for all-day events', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_allday_hide_time'), 
							   'fse_global', 'fse_global');
		}
		
		add_settings_section('fse_global5', 
							 '',  
							 array(&$this, 'hookSettingHeader_Global5'), 
							 'fse_global');
		{

		}
					
		add_settings_section('fse_global2', 
							 '',  
							 array(&$this, 'hookSettingHeader_Global2'), 
							 'fse_global');
		{
			add_settings_field('fse_template', 
							   __('Template', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_template'), 
							   'fse_global', 'fse_global2');
							   
			add_settings_field('fse_template_lst', 
							   __('Template for Listoutput', fsCalendar::$plugin_textdom).'<br /><small>'.__('The whole template is automatically surrounded by the &lt;li&gt; tag when a grouped list of events is created.', fsCalendar::$plugin_textdom).'</small>', 
							   array(&$this, 'hookSettingOption_fse_template_lst'), 
							   'fse_global', 'fse_global2');
		}
		
		add_settings_section('fse_global3', 
							 '',  
							 array(&$this, 'hookSettingHeader_Global3'), 
							 'fse_global');
		{
			add_settings_field('fse_groupby', 
							   __('Group by', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_groupby'), 
							   'fse_global', 'fse_global3');
			add_settings_field('fse_groupby_header', 
							   __('Header Format', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_groupby_header'), 
							   'fse_global', 'fse_global3');
		}
		
		add_settings_section('fse_global4', 
							 '',  
							 array(&$this, 'hookSettingHeader_Global4'), 
							 'fse_global');
		{
			add_settings_field('fse_load_jquery', 
							   __('Loading of jQuery library', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_load_jquery'), 
							   'fse_global', 'fse_global4');
			add_settings_field('fse_load_jqueryui', 
							   __('Loading of jQuery UI library', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_load_jqueryui'), 
							   'fse_global', 'fse_global4');
			add_settings_field('fse_load_fc_libs', 
							   __('Loading of FullCalendar files', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_load_fc_libs'), 
							   'fse_global', 'fse_global4');
		}
		
		add_settings_section('fse_pagination', 
							 '',  
							 array(&$this, 'hookSettingVoid'), 
							 'fse_pagination');
		{
			add_settings_field('fse_pagination', 
							   __('Enable pagination by default', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_pagination'), 
							   'fse_pagination', 'fse_pagination');
			add_settings_field('fse_pagination_prev_text', 
							   __('Text for prev link', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_pagination_prev_text'), 
							   'fse_pagination', 'fse_pagination');
			add_settings_field('fse_pagination_next_text', 
							   __('Text for next link', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_pagination_next_text'), 
							   'fse_pagination', 'fse_pagination');
			add_settings_field('fse_pagination_usedots', 
							   __('Appearance', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_pagination_usedots'), 
							   'fse_pagination', 'fse_pagination');
			add_settings_field('fse_pagination_end_size', 
							   __('Number of pages at the beginning/end', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_pagination_end_size'), 
							   'fse_pagination', 'fse_pagination');
			add_settings_field('fse_pagination_mid_size', 
							   __('Number of pages before/after current page', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_pagination_mid_size'), 
							   'fse_pagination', 'fse_pagination');
		}
		
		add_settings_section('fse_admin', 
							 '',  
							 array(&$this, 'hookSettingVoid'), 
							 'fse_admin');
		{
			add_settings_field('fse_df_admin', 
							   __('Date Format for Admin Interface', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_df_admin'), 
							   'fse_admin', 'fse_admin');
			add_settings_field('fse_adm_gc_enabled', 
							   __('Date chooser', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_adm_gc_enabled'), 
							   'fse_admin', 'fse_admin');
			add_settings_field('fse_adm_default_start_time', 
							   __('Default start time', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_adm_default_start_time'), 
							   'fse_admin', 'fse_admin');
			add_settings_field('fse_adm_default_end_time', 
							   __('Default end time', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_adm_default_end_time'), 
							   'fse_admin', 'fse_admin');
			add_settings_field('fse_adm_gc_mode', 
							   __('Mode', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_adm_gc_mode'), 
							   'fse_admin', 'fse_admin');
			add_settings_field('fse_adm_gc_show_week', 
							   __('Week number', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_adm_gc_show_week'), 
							   'fse_admin', 'fse_admin');
			add_settings_field('fse_adm_gc_show_sel', 
							   __('Month/Year Selector', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_adm_gc_show_sel'), 
							   'fse_admin', 'fse_admin');
		}
		
		add_settings_section('fse_sp', 
							 '',  
							 array(&$this, 'hookSettingHeader_SinglePage'), 
							 'fse_sp');
		{
			add_settings_field('fse_sp', 
							   __('Single view page', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_page'), 
							   'fse_sp', 'fse_sp');
		}
		
		add_settings_section('fse_fc', 
							 '',  
							 array(&$this, 'hookSettingHeader_Fc'), 
							 'fse_fc');
		{
			add_settings_field('fse_fc_tit_month_fmt', 
							   __('Title format month view', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_fc_tit_month_fmt'), 
							   'fse_fc', 'fse_fc');
			add_settings_field('fse_fc_tit_week_fmt', 
							   __('Title format week view', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_fc_tit_week_fmt'), 
							   'fse_fc', 'fse_fc');
			add_settings_field('fse_fc_tit_day_fmt', 
							   __('Title format day view', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_fc_tit_day_fmt'), 
							   'fse_fc', 'fse_fc');
			add_settings_field('fse_fc_col_month_fmt', 
							   __('Colum header format month view', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_fc_col_month_fmt'), 
							   'fse_fc', 'fse_fc');
			add_settings_field('fse_fc_col_week_fmt', 
							   __('Colum format week view', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_fc_col_week_fmt'), 
							   'fse_fc', 'fse_fc');
			add_settings_field('fse_fc_col_day_fmt', 
							   __('Colum format day view', fsCalendar::$plugin_textdom), 
							   array(&$this, 'hookSettingOption_fse_fc_col_day_fmt'), 
							   'fse_fc', 'fse_fc');
		}
	}
	
	/**
	 * Creates a menu entry in the settings menu
	 * @return void
	 */
	function hookAddAdminMenu() {
		// Options
		$menutitle = __('Calendar', fsCalendar::$plugin_textdom);
		add_options_page(__('Calendar', fsCalendar::$plugin_textdom), 
						 $menutitle, 
						 'manage_options', 
						 'wp-cal-settings', 
						 array(&$this, 'createCalendarSettingsPage'));
	}
	
	/**
	 * Creates the settings pages
	 * @return void
	 */
	function createCalendarSettingsPage() {
		global $wpdb;
		global $user_ID;
		?>
		
		<div class="wrap">
		<div id="icon-options-general" class="icon32"><br /></div>
		<div id="otc"><h2><?php _e('Calendar Settings', fsCalendar::$plugin_textdom); ?></h2>
		</div>
		
		<div style="width: 100%; text-align: right; vertical-align: middle; display: block;">
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
		<input type="image"  align="middle"  src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
	    <img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
		</form>
		</div>
		
		<form action="options.php" method="post">
		<?php settings_fields('fse'); // Groupname ?>
		
		<div id="fs_tabs">
			<ul>
				<li><a href="#fs_tab1"><?php _e('Global Setting', fsCalendar::$plugin_textdom); ?></a></li>
				<li><a href="#fs_tab2"><?php _e('Pagination', fsCalendar::$plugin_textdom); ?></a></li>
				<li><a href="#fs_tab3"><?php _e('Event Page', fsCalendar::$plugin_textdom); ?></a></li>
				<li><a href="#fs_tab4"><?php _e('Graphical calendar (FullCalendar)', fsCalendar::$plugin_textdom); ?></a></li>
				<li><a href="#fs_tab5"><?php _e('Admin settings', fsCalendar::$plugin_textdom); ?></a></li>
				<li><a href="#fs_tab9"><?php _e('Documentation', fsCalendar::$plugin_textdom); ?></a></li>
				<li><a href="#fs_tab10"><?php _e('About', fsCalendar::$plugin_textdom); ?></a></li>
			</ul>
			<div id="fs_tab1">
				<?php do_settings_sections('fse_global'); ?>
			</div>
			<div id="fs_tab2">
				<?php do_settings_sections('fse_pagination'); ?>
			</div>
			<div id="fs_tab3">
				<?php do_settings_sections('fse_sp'); ?>
			</div>
			<div id="fs_tab4">
				<?php do_settings_sections('fse_fc'); ?>
			</div>
			<div id="fs_tab5">
				<?php do_settings_sections('fse_admin'); ?>
			</div>
			<div id="fs_tab9">
				<?php $this->hookSettingsUsage(); ?>
			</div>
			<div id="fs_tab10">
				<?php $this->hookSettingsAbout(); ?>
			</div>
		</div>
		<p>
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save all changes'); ?>" />
		</p>
		</form></div>

		<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#fs_tabs').tabs( );
			fse_toogleInputByCheckbox(document.getElementById('fse_df_wp'), 'fse_df', false);
			fse_toogleInputByCheckbox(document.getElementById('fse_tf_wp'), 'fse_tf', false);
			fse_toogleInputByCheckbox(document.getElementById('fse_ws_wp'), 'fse_ws', false);
		});
		</script>

		<?php
	}
	function hookSettingHeader_SinglePage() {
		?>
		<p><?php _e('You can define a page for displaying a single event. This page will always be link to, when using a function of this plug-in to show an overview of events (list, graphical calendar,...). If you do not define a page for displaying a single event, make sure you change the template defined below, since it uses the paramter <code>{event_url}</code>, which is not available, if no single view page is defined.', fsCalendar::$plugin_textdom); ?></p>
		<p><?php _e('Because this page normally should not be displayed in any page listing, you can hide it and it will disapear from any lists in your blog, provided that your theme and plug-ins use standard wordpress functions and are not reading directly from the database. By ticking the flag <i>Mark page</i> the selected page will be <span id="page_is_cal"><span>highlighted</span></span> in the page overview.', fsCalendar::$plugin_textdom); ?></p>
		<?php
	}
	function hookSettingHeader_Global1() {
		?>
		<p>
		<strong>
		<?php _e('Please notice, that the most of these settings are just defaults for your widgets, for the usage in post and pages using tags (e.g. <code>{events_list}</code>) and for the integration in your theme '.
			'using WP Calendar functions', fsCalendar::$plugin_textdom); ?>.
		<?php _e('When using widgets some of them can be overriden, when using tags or built-in function <u>all</u> these settings can be overriden. For more information please have '.
			'a look a the <a href="#fs_tab9">usage documentation</a>', fsCalendar::$plugin_textdom); ?>.
		</strong>
		</p>
		<?php
	}
	function hookSettingHeader_Global2() {
		?>
		<p><?php _e('When displaying a list of events using a widget, a function or a short tag, the single events are printed using the following templates.', fsCalendar::$plugin_textdom); ?>
		<?php _e('The first template is used for a &quot;flat&quot; list, make sure you have line breaks using the paragraph or line-break tag. The second template is used for a list, which allows the grouping of events.', fsCalendar::$plugin_textdom); ?>
		<?php _e('For more information about the short tags have a look at the usage documentation.', fsCalendar::$plugin_textdom); ?></p>
		<?php
	}
	function hookSettingHeader_Global3() {
		?>
		<p>
		<?php _e('If you are displaying your events using a list (e.g. the Grouped Calendar Widget), the events can be grouped by a date entity, which you can define below.', fsCalendar::$plugin_textdom); ?>
		<?php _e('Please refer to the php  <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all the output format options.', fsCalendar::$plugin_textdom); ?>
		</p>
		<?php	
	}
	function hookSettingHeader_Global4() {
		?>
		<p>
		<?php _e('WP Calendar needs the jQuery and the jQuery UI library to work properly. These libraries are loaded '.
				 'using the recommended WP function which make sure that all libraries are loaded once only.', fsCalendar::$plugin_textdom); ?>
		<?php _e('Since some plugins do not use these function, there may be compatibility issues preventing WP calendar to work as expected. '.
				 'If the required libraries are loaded by another plug-in you can disable the loading by WP calendar.', fsCalendar::$plugin_textdom); ?>
		<?php _e('If you are using other plugins which load these libraries <b>using the WP functions</b>, these settings have not effect!', fsCalendar::$plugin_textdom); ?>
		</p>
		<?php	
	}
	function hookSettingHeader_Global5() {
		?>
		<p>Using WP Calendar you can easily share your event on facebook and show who's attending on your event on your website.<br />To activate
		Facebook integration, you have to authenticate first. Click on the <i>Connect</i> Button to connect now.</p>
		
		
		<?php 	
	}
	function hookSettingHeader_Fc() {
		?>
		<p><?php _e('WP Calendar allows an easy integration of <a href="http://arshaw.com/fullcalendar/" target="_blank">FullCalendar</a>, a nice graphical calendar. '.
					'This graphical calendar has many features and options, which can all be set when integration it in your blog (see usage documentation). '.
					'All the options, which are relevant for localization are passed by WP Calenar (first day of week, time format, all translations like daynames). '.
					'Since the date format varies depending on the view, you can define all the date formats here. ', fsCalendar::$plugin_textdom); ?>
			<?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?>,
			<?php _e('but refer to the <a href="http://arshaw.com/fullcalendar/docs/utilities/formatDates/" target="_blank">documentation</a> of FullCalendar for the correct syntax (how to use brackets).', fsCalendar::$plugin_textdom)?>					
		</p>
		<?php	
	}
	
	function hookSettingOption_fse_fc_col_day_fmt() {
		?>
		<input type="text" value="<?php echo esc_attr(get_option('fse_fc_col_day_fmt')); ?>" size="15" name="fse_fc_col_day_fmt" />
		<?php	
	}
	
	function hookSettingOption_fse_fc_col_week_fmt() {
		?>
		<input type="text" value="<?php echo esc_attr(get_option('fse_fc_col_week_fmt')); ?>" size="15" name="fse_fc_col_week_fmt" />
		<?php	
	}
	
	function hookSettingOption_fse_fc_col_month_fmt() {
		?>
		<input type="text" value="<?php echo esc_attr(get_option('fse_fc_col_month_fmt')); ?>" size="15" name="fse_fc_col_month_fmt" />
		<?php 
	}
	function hookSettingOption_fse_fc_tit_day_fmt() {
		?>
		<input type="text" value="<?php echo esc_attr(get_option('fse_fc_tit_day_fmt')); ?>" size="15" name="fse_fc_tit_day_fmt" />
		<?php	
	}
	
	function hookSettingOption_fse_fc_tit_week_fmt() {
		?>
		<input type="text" value="<?php echo esc_attr(get_option('fse_fc_tit_week_fmt')); ?>" size="25" name="fse_fc_tit_week_fmt" />
		<?php	
	}
	
	function hookSettingOption_fse_fc_tit_month_fmt() {
		?>
		<input type="text" value="<?php echo esc_attr(get_option('fse_fc_tit_month_fmt')); ?>" size="15" name="fse_fc_tit_month_fmt" />
		<?php	
	}
	
	function hookSettingOption_fse_groupby_header() {
		?>
		<input type="text" value="<?php echo esc_attr(get_option('fse_groupby_header')); ?>" size="10" name="fse_groupby_header" />
		<?php
	}
	
	function hookSettingOption_fse_groupby() {
		?>
		<select name="fse_groupby">
		<option value="" <?php echo (get_option('fse_groupby') == ''  ? ' selected="selected"' : ''); ?>><?php _e('None', fsCalendar::$plugin_textdom); ?></option>
		<option value="d"<?php echo (get_option('fse_groupby') == 'd' ? ' selected="selected"' : ''); ?>><?php _e('Day', fsCalendar::$plugin_textdom); ?></option>
		<option value="m"<?php echo (get_option('fse_groupby') == 'm' ? ' selected="selected"' : ''); ?>><?php _e('Month', fsCalendar::$plugin_textdom); ?></option>
		<option value="y"<?php echo (get_option('fse_groupby') == 'y' ? ' selected="selected"' : ''); ?>><?php _e('Year', fsCalendar::$plugin_textdom); ?></option>
		</select>
		<?php 
	}
	
	function hookSettingOption_fse_load_jquery() {
		?>
		<select name="fse_load_jquery">
		<option value="1"<?php echo (get_option('fse_load_jquery') == true ? ' selected="selected"' : ''); ?>><?php _e('Enabled', fsCalendar::$plugin_textdom); ?></option>
		<option value="0"<?php echo (get_option('fse_load_jquery') == false ? ' selected="selected"' : ''); ?>><?php _e('Disabled', fsCalendar::$plugin_textdom); ?></option>
		</select>
		<?php
	}
	
	function hookSettingOption_fse_load_jqueryui() {
		?>
		<select name="fse_load_jqueryui">
		<option value="1"<?php echo (get_option('fse_load_jqueryui') == true ? ' selected="selected"' : ''); ?>><?php _e('Enabled', fsCalendar::$plugin_textdom); ?></option>
		<option value="0"<?php echo (get_option('fse_load_jqueryui') == false ? ' selected="selected"' : ''); ?>><?php _e('Disabled', fsCalendar::$plugin_textdom); ?></option>
		</select>
		<?php
	}
	
	function hookSettingOption_fse_load_fc_libs() {
		?>
		<select name="fse_load_fc_libs">
		<option value="1"<?php echo (get_option('fse_load_fc_libs') == true ? ' selected="selected"' : ''); ?>><?php _e('Enabled', fsCalendar::$plugin_textdom); ?></option>
		<option value="0"<?php echo (get_option('fse_load_fc_libs') == false ? ' selected="selected"' : ''); ?>><?php _e('Disabled', fsCalendar::$plugin_textdom); ?></option>
		</select>
		<?php
	}
	
	function hookSettingOption_fse_template_lst() {
		?>
		<textarea rows="5" cols="80" name="fse_template_lst"><?php echo htmlentities(get_option('fse_template_lst')); ?></textarea>
		<?php	
	}
	function hookSettingOption_fse_template() {
		?>
		<textarea rows="5" cols="80" name="fse_template"><?php echo htmlentities(get_option('fse_template')); ?></textarea>
		<?php	
	}
	
	function hookSettingOption_fse_show_enddate() {
		?>
		<select name="fse_show_enddate">
		<option value="1"<?php echo (get_option('fse_show_enddate') == true ? ' selected="selected"' : ''); ?>><?php _e('Always show end date', fsCalendar::$plugin_textdom)?></option>
		<option value="0"<?php echo (get_option('fse_show_enddate') == false ? ' selected="selected"' : ''); ?>><?php _e('Only show end date, if different from start date', fsCalendar::$plugin_textdom)?></option>
		</select>
		<?php
	}
	
	function hookSettingOption_fse_allday_hide_time() {
		?>
		<select name="fse_allday_hide_time">
		<option value="1"<?php echo (get_option('fse_allday_hide_time') == true ? ' selected="selected"' : ''); ?>><?php _e('Hide time when all-day event', fsCalendar::$plugin_textdom)?></option>
		<option value="0"<?php echo (get_option('fse_allday_hide_time') == false ? ' selected="selected"' : ''); ?>><?php _e('Always show time', fsCalendar::$plugin_textdom)?></option>
		</select>
		<?php		
	}
	
	function hookSettingOption_fse_page() {
		?>
		<select name="fse_page">
		<option value=""><?php _e('No single view page', fsCalendar::$plugin_textdom)?></option>
		<?php
		$pages = get_pages();
		$s = get_option('fse_page');
		foreach($pages as $p) {
			echo '<option value="'.esc_attr($p->ID).'"'.($s == $p->ID ? ' selected="selected"' : '').'>'.esc_attr($p->post_title).'</option>';
		}
		?>
		</select><br />
		<input type="checkbox" value="1" name="fse_page_mark" id="fse_page_mark" <?php echo (get_option('fse_page_mark') == true ? 'checked="checked" ' : ''); ?>/> <label for="fse_page_mark"><?php _e('Mark page in page overview', fsCalendar::$plugin_textdom); ?></label><br />
		<input type="checkbox" value="1" name="fse_page_hide" id="fse_page_hide" <?php echo (get_option('fse_page_hide') == true ? 'checked="checked" ' : ''); ?>/> <label for="fse_page_hide"><?php _e('Set page as hidden', fsCalendar::$plugin_textdom); ?></label>
		<?php
	}
	
	function hookSettingOption_fse_number() {
		?>
		<input type="text" value="<?php echo intval(get_option('fse_number')); ?>" size="3" name="fse_number" />
		<?php 	
	}
	
	
	function hookSettingVoid() { }

	function hookSettingOption_fse_df() {
		?>
		<input type="text" name="fse_df" id="fse_df" value="<?php echo esc_attr(get_option('fse_df')); ?>" />
		<input type="checkbox"
			   name="fse_df_wp" 
			   value="1"
			   id="fse_df_wp"
			   onclick="fse_toogleInputByCheckbox(this, 'fse_df', false);"
			   size="10" 
			   <?php echo (get_option('fse_df_wp') == true ? 'checked="checked"' : '' ); ?>/> 
		<label for="fse_df_wp"><?php _e('Use WP settings', fsCalendar::$plugin_textdom)?></label><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small>
		<?php
	}
	
	function hookSettingOption_fse_tf() {
		?>
		<input type="text" name="fse_tf" id="fse_tf" value="<?php echo esc_attr(get_option('fse_tf')); ?>" />
		<input type="checkbox" 
			   name="fse_tf_wp"
			   value="1"
			   id="fse_tf_wp"
			   size="10"
			   onclick="fse_toogleInputByCheckbox(this, 'fse_tf', false);"  
			   <?php echo (get_option('fse_df_wp') == true ? 'checked="checked"' : '' ); ?>/> 
		<label for="fse_tf_wp"><?php _e('Use WP settings', fsCalendar::$plugin_textdom)?></label><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small>
		<?php
	}
	
	function hookSettingOption_fse_ws() {
		?>
		<select name="fse_ws" id="fse_ws">
		<?php
		$days = array(__('Sunday', fsCalendar::$plugin_textdom), 
					  __('Monday', fsCalendar::$plugin_textdom),
					  __('Tuesday', fsCalendar::$plugin_textdom), 
					  __('Wednesday', fsCalendar::$plugin_textdom), 
					  __('Thursday', fsCalendar::$plugin_textdom), 
					  __('Friday', fsCalendar::$plugin_textdom), 
					  __('Saturday', fsCalendar::$plugin_textdom));
		$s = get_option('fse_ws');
		foreach($days as $k => $d) {
			echo '<option value="'.esc_attr($k).'" '.($k == $s ? 'selected="selected"' : '').'>'.esc_attr($d).'</option>';
		}
		?>
		</select>
		<input type="checkbox" 
			   name="fse_ws_wp"
			   value="1"
			   id="fse_ws_wp"
			   onclick="fse_toogleInputByCheckbox(this, 'fse_ws', false);"  
			   <?php echo (get_option('fse_ws_wp') == true ? 'checked="checked"' : '' ); ?>/> 
		<label for="fse_ws_wp"><?php _e('Use WP settings', fsCalendar::$plugin_textdom)?></label>
		<?php	
	}
	
	function hookSettingOption_fse_df_admin() {
		?>
		<select name="fse_df_admin">
		<?php 
		$format = array(
			'dmY' => __('Day', fsCalendar::$plugin_textdom).' '.__('Month', fsCalendar::$plugin_textdom).' '.__('Year', fsCalendar::$plugin_textdom),
			'mdY' => __('Month', fsCalendar::$plugin_textdom).' '.__('Day', fsCalendar::$plugin_textdom).' '.__('Year', fsCalendar::$plugin_textdom),
			'Ymd' => __('Year', fsCalendar::$plugin_textdom).' '.__('Month', fsCalendar::$plugin_textdom).' '.__('Day', fsCalendar::$plugin_textdom),
			'Ydm' => __('Year', fsCalendar::$plugin_textdom).' '.__('Day', fsCalendar::$plugin_textdom).' '.__('Month', fsCalendar::$plugin_textdom)
		);
		$s = get_option('fse_df_admin');
		foreach($format as $k => $f) {
			echo '<option value="'.esc_attr($k).'" '.($s == $k ? 'selected="selected"' : '').'>'.esc_attr($f).'</option>';
		}
		?>
		</select>
		<?php _e('separated by', fsCalendar::$plugin_textdom)?> 
		<select name="fse_df_admin_sep">
		<?php 
		$sep = array('.', '-', '/'); 
		$o = get_option('fse_df_admin_sep');
		foreach($sep as $s) {
			echo '<option value="'.esc_attr($s).'" '.($s == $o ? 'selected="selected"' : '').'>'.esc_attr($s).'</option>';
		}
		?>
		</select>
		<?php	
	}
	
	function hookSettingOption_fse_adm_gc_enabled() {
		?>
		<select name="fse_adm_gc_enabled">
		<?php 
		$format = array(
			'1' => __('Graphical date chooser enabled', fsCalendar::$plugin_textdom),
			'0' => __('Graphical date chooser disabled', fsCalendar::$plugin_textdom)
		);
		$s = get_option('fse_adm_gc_enabled');
		foreach($format as $k => $f) {
			echo '<option value="'.esc_attr($k).'" '.($s == $k ? 'selected="selected"' : '').'>'.esc_attr($f).'</option>';
		}
		?>
		</select>
		<?php	
	}
	
	function hookSettingOption_fse_adm_default_start_time() {
		?>
		<input type="text" 
			value="<?php echo get_option('fse_adm_default_start_time'); ?>" 
			size="5" 
			name="fse_adm_default_start_time"
			onblur="if (fse_validateTime(this) == false) { 
	    		this.focus(); 
	    		this.value = ''; 
	    		alert('<?php _e('Please enter a valid time', fsCalendar::$plugin_textdom); ?>.'); 
	    		}" />
		<?php
	}
	
	function hookSettingOption_fse_adm_default_end_time() {
		?>
		<input type="text" 
			value="<?php echo get_option('fse_adm_default_end_time'); ?>" 
			size="5" 
			name="fse_adm_default_end_time"
			onblur="if (fse_validateTime(this) == false) { 
	    		this.focus(); 
	    		this.value = ''; 
	    		alert('<?php _e('Please enter a valid time', fsCalendar::$plugin_textdom); ?>.'); 
	    		}" />
		<?php
	}
	
	function hookSettingOption_fse_adm_gc_mode() {
		?>
		<select name="fse_adm_gc_mode">
		<?php 
		$format = array(
			'0' => __('Automatically open when enter field', fsCalendar::$plugin_textdom),
			'1' => __('Open manually by clicking the calendar icon', fsCalendar::$plugin_textdom),
			'2' => __('Mixed mode (Open automatically and provide icon)', fsCalendar::$plugin_textdom)
		);
		$s = get_option('fse_adm_gc_mode');
		foreach($format as $k => $f) {
			echo '<option value="'.esc_attr($k).'" '.($s == $k ? 'selected="selected"' : '').'>'.esc_attr($f).'</option>';
		}
		?>
		</select>
		<?php	
	}
	function hookSettingOption_fse_adm_gc_show_week() {
		?>
		<select name="fse_adm_gc_show_week">
		<?php 
		$format = array(
			'1' => __('Show', fsCalendar::$plugin_textdom),
			'0' => __('Hide', fsCalendar::$plugin_textdom)
		);
		$s = get_option('fse_adm_gc_show_week');
		foreach($format as $k => $f) {
			echo '<option value="'.esc_attr($k).'" '.($s == $k ? 'selected="selected"' : '').'>'.esc_attr($f).'</option>';
		}
		?>
		</select>
		<?php
	}
	
	function hookSettingOption_fse_adm_gc_show_sel() {
		?>
		<select name="fse_adm_gc_show_sel">
		<?php 
		$format = array(
			'1' => __('Show', fsCalendar::$plugin_textdom),
			'0' => __('Hide', fsCalendar::$plugin_textdom)
		);
		$s = get_option('fse_adm_gc_show_sel');
		foreach($format as $k => $f) {
			echo '<option value="'.esc_attr($k).'" '.($s == $k ? 'selected="selected"' : '').'>'.esc_attr($f).'</option>';
		}
		?>
		</select>
		<?php
	}
	
	function hookSettingOption_fse_pagination() {
		?>
		<select name="fse_pagination">
		<option value="1"<?php echo (get_option('fse_pagination') == true ? ' selected="selected"' : ''); ?>><?php _e('Enabled', fsCalendar::$plugin_textdom); ?></option>
		<option value="0"<?php echo (get_option('fse_pagination') == false ? ' selected="selected"' : ''); ?>><?php _e('Disabled', fsCalendar::$plugin_textdom); ?></option>
		</select>
		<?php
	}
	
	function hookSettingOption_fse_pagination_prev_text() {
		?>
		<input type="text" value="<?php echo (get_option('fse_pagination_prev_text')); ?>" size="15" name="fse_pagination_prev_text" />
		<?php 	
	}
	
	function hookSettingOption_fse_pagination_next_text() {
		?>
		<input type="text" value="<?php echo (get_option('fse_pagination_next_text')); ?>" size="15" name="fse_pagination_next_text" />
		<?php 	
	}
	
	function hookSettingOption_fse_pagination_end_size() {
		?>
		<input type="text" value="<?php echo intval(get_option('fse_pagination_end_size')); ?>" size="3" name="fse_pagination_end_size" />
		<?php 	
	}
	
	function hookSettingOption_fse_pagination_mid_size() {
		?>
		<input type="text" value="<?php echo intval(get_option('fse_pagination_mid_size')); ?>" size="3" name="fse_pagination_mid_size" />
		<?php 	
	}
	
	function hookSettingOption_fse_pagination_usedots() {
		?>
		<select name="fse_pagination_usedots">
		<option value="1"<?php echo (get_option('fse_pagination_usedots') == true ? ' selected="selected"' : ''); ?>><?php _e('Use dots (...)', fsCalendar::$plugin_textdom); ?></option>
		<option value="0"<?php echo (get_option('fse_pagination_usedots') == false ? ' selected="selected"' : ''); ?>><?php _e('Show all pages', fsCalendar::$plugin_textdom); ?></option>
		</select>
		<?php
	}
	
	function hookSettingsUsage() {
		?>
		<table class="form-table">
		<tr><th colspan="3"><a name="usage_top"></a><h2 class="show"><?php _e('Use Widget for easy integration!', fsCalendar::$plugin_textdom); ?></h2>
		<p>
		<?php _e('The easiest way to display your events in your blog is the usage of widgets. There are two widgets you can use. '.
		'One can be used for displaying an unordered list of your events, which are grouped by year, month or day. The other one just prints your events without any grouping.<br /> '.
		'In both widgets you can use certain filters and limit the number of events to display. '.
		'The template can be maintained in the widgets as well. For more information about templates, please read on.', fsCalendar::$plugin_textdom); ?>
		</p>
		<p>
		<?php _e('If you dont want to use widgets, you can integrate all data in post/pages with the use of special tags, or you can '.
		'integrate it directly in your theme using various php functions. Please read on for more information.', fsCalendar::$plugin_textdom); ?>
		</p>
		<p>
		Learn how to:
		</p>
		<ul>
			<li><a href="#usage_fullcalendar"><?php _e('Show a graphical calendar in your blog', fsCalendar::$plugin_textdom); ?></a></li>
			<li><a href="#usage_themes"><?php _e('Use WP Calendar functions in your theme', fsCalendar::$plugin_textdom); ?></a></li>
			<li><a href="#usage_posts"><?php _e('Use short tags in your post and pages', fsCalendar::$plugin_textdom); ?></a></li>
		</ul>
		</th></tr>
		
		<tr><th colspan="3"><a name="usage_fullcalendar"></a><h2 class="show"><?php _e('Graphical Calendar', fsCalendar::$plugin_textdom); ?></h2>
		<p>
		<?php _e('This plug-in comes with <a href="http://arshaw.com/fullcalendar/" target="_blank">FullCalendar</a>, a very nice, simple, customizeable ajax-based calendar. You can integrate this calendar by using the tag <code>{events_calendar}</code> in your page or post. '.
				 'WP Calendar will set some options for your, especially all the transaltions for day- and monthnames and some WP options like the time format or the first day of the week. '.
				 'All the date formatting options can be maintained in the FullCalendar settings tab. All FullCalendar <a href="http://arshaw.com/fullcalendar/docs/" target="_blank">options</a> can be passed as parameters of the tag. Nested options must be accessed using its path '.
				 '(e.g. Header->right: "today"). Along with the FullCalendar options, you can also pass various parameters for filtering the output (See <a href="#filters">filter options</a>).' , fsCalendar::$plugin_textdom); ?>
		</p>
		<p>
		<?php _e('Here is a sample tag, which only shows events of the categories 3 and 5, and sets some options of FullCalendar', fsCalendar::$plugin_textdom); ?>:
		</p>
		<p><code>{events_calendar; categories="3,5"; height=600; header->left="prev"; header->center="today, title"; header->right="next"; weekends=false}</code></p>
		<p>
		<?php _e('If you pass any parameters for date or time formatting use the <a href="http://arshaw.com/fullcalendar/docs/utilities/formatDate/" target="_blank">parameters</a> of FullCalendar and not the parameters of the php date function.', fsCalendar::$plugin_textdom); ?>
		</p>
		</th></tr>
		<tr><th><?php _e('Parameter', fsCalendar::$plugin_textdom); ?></th><td><?php _e('Default', fsCalendar::$plugin_textdom); ?></td><td><?php _e('Description', fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>linktopost</code></th><td>false</td><td><?php _e('If the event is synchronized with a post, clicking on the event redirects to the post and not to the single page view', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3"><a name="usage_themes"></a><h2 class="show"><?php _e('Usage in Themes', fsCalendar::$plugin_textdom); ?></h2>
		<p>
		<?php _e('At the moment there are four functions available', fsCalendar::$plugin_textdom); ?>:
		</p>
		<ul>
			<li><code>fse_get_events($args = array())</code> - <?php _e('Returns an array of event objects for further processing by yourself', fsCalendar::$plugin_textdom); ?></li>
			<li><code>fse_print_events($args = array())</code> - <?php _e('Prints a selection of events without any hierarchy', fsCalendar::$plugin_textdom); ?></li>
			<li><code>fse_print_events_list($args = array())</code> - <?php _e('Prints an unordered list and allows grouping by a date entity', fsCalendar::$plugin_textdom); ?></li>
			<li><code>fse_get_event($eventid)</code> - <?php _e("Just returns an event object corresponding to the passed Event-ID", fsCalendar::$plugin_textdom); ?></li>
		</ul> 
		<p><?php _e('The first three functions accept one parameter, which expects to be an associative array. Call a function like this', fsCalendar::$plugin_textdom); ?>:</p> 
		<pre>
fse_print_events(
  array( 'number'   =&gt; 10,
         'exclude'  =&gt; array(387, 827),
         'before'   =&gt; '&lt;table cellpadding="0" cellspacing="0"&gt;',
         'after'    =&gt; '&lt;/table&gt;',
         'template' =&gt; '&lt;tr>&lt;td&gt;{event_subject}&lt;br />@{event_location}&lt;/td&gt;&lt;/tr&gt;'
  )
);
		</pre>
		<p><?php _e("The allowed paramters are described below. Some parameters are not supported by all functions, since they won't make any sense", fsCalendar::$plugin_textdom); ?>. 
		<b><?php _e("Notice that every parameter, which expects a boolean value, must by supplied by 1 (true) or 0 (false).", fsCalendar::$plugin_textdom); ?>.</b>
		</p>
		</th></tr>
		<tr><th colspan="3"><strong><?php _e('Output control', fsCalendar::$plugin_textdom); ?></strong></th></tr>
		<tr><th><?php _e('Parameter', fsCalendar::$plugin_textdom); ?></th><td><?php _e('Default', fsCalendar::$plugin_textdom); ?></td><td><?php _e('Description', fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>echo</code></th><td>true</td><td><?php _e('The functions <code>fse_print_events</code> and <code>fse_print_events_list</code> normally echos the result. By setting the parameter <code>echo</code> to false, the result is returned instead', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>number</code></th><td><?php _e('Calendar Options', fsCalendar::$plugin_textdom); ?></td><td><?php _e('The number of events to return or print', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>template</code></th><td><?php _e('Calendar Options', fsCalendar::$plugin_textdom); ?></td><td><?php _e('The template used for processing the output of an event. You can use the same tags as in post and pages, described <a href="#usage_posts">here</a>', fsCalendar::$plugin_textdom); ?>.
		<?php _e('If you use the parameter `template` in a tags (e.g. {events_print; template="{event_subject}"}, make sure, you <b><u>escape</u></b> all the parentheses (e.g. {events_print; template="\{event_subject\}"})', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>before</code></th><td>''</td><td><?php _e('Additional HTML code to print before', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>after</code></th><td>''</td><td><?php _e('Additional HTML code to print after', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>alwaysshowenddate</code></th><td><?php _e('Calendar Options', fsCalendar::$plugin_textdom); ?></td><td><?php _e('If set to false, the enddate is left empty, if it is not differing from the start date', fsCalendar::$plugin_textdom); ?>.</td></tr>
		
		<tr><th colspan="3"><strong><?php _e('Pagination', fsCalendar::$plugin_textdom); ?></strong><br /><p>
		<?php _e('The pagination is only used in a list (grouped or ungrouped) of events. If all events can be displayed on one page the pagination bar will not appear', fsCalendar::$plugin_textdom); ?>.
		</p></th></tr>
		<tr><th><code>pagination</code></th><td>Calendar options</td>
			<td><?php _e('Set to true to enable pagination for a list of events, exceeding the maxium of events to show (parameter <code>number</code>', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>pagination_prev_link</code></th><td>Calendar options</td>
			<td><?php _e('Text to use for moving to the previous page. Leave empty to supress link', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>pagination_next_link</code></th><td>Calendar options</td>
			<td><?php _e('Text to use for moving to the next page. Leave empty to supress link', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>pagination_use_dots</code></th><td>Calendar options</td>
			<td><?php _e('If true, the list of pages uses dots (...). For more information see parameter <code>pagination_end_size</code>', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>pagination_end_size</code></th><td>Calendar options</td>
			<td><?php _e('The number of pages to show at the beginning and at the end of the page list, if the option <code>pagination_use_dots</code> is enabled', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>pagination_mid_size</code></th><td>Calendar options</td>
			<td><?php _e('The number of pages to show before and after the current page, if the option <code>pagination_use_dots</code> is enabled', fsCalendar::$plugin_textdom); ?>.</td></tr>
		
		<tr><th colspan="3"><strong><?php _e('Grouping', fsCalendar::$plugin_textdom); ?></strong><br />
		<p><?php _e('Grouping is only available for the function <code>fse_print_events_list</code>', fsCalendar::$plugin_textdom); ?>.</p></th></tr>
		<tr><th><code>groupby</code></th><td><?php _e('Calendar options', fsCalendar::$plugin_textdom); ?></td><td><?php _e('Use the following constants', fsCalendar::$plugin_textdom); ?>:
		<ul>
		<li><code>FSE_GROUPBY_NONE</code> - <?php _e('No grouping', fsCalendar::$plugin_textdom); ?></li>
		<li><code>FSE_GROUPBY_DAY</code> - <?php _e('Group by day', fsCalendar::$plugin_textdom); ?></li>
		<li><code>FSE_GROUPBY_MONTH</code> - <?php _e('Group by month', fsCalendar::$plugin_textdom); ?></li>
		<li><code>FSE_GROUPBY_YEAR</code> - <?php _e('Group by year', fsCalendar::$plugin_textdom); ?></li>
		</ul>
		</td></tr>
		<tr><th><code>groupby_header</code></th><td><?php _e('Calendar options', fsCalendar::$plugin_textdom); ?></td><td><?php _e('The header format when grouping, refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function', fsCalendar::$plugin_textdom); ?>.</td></tr>
		
		<tr><th colspan="3"><h2 class="show"><a name="filters"></a><?php _e('Filter', fsCalendar::$plugin_textdom); ?></h2></th></tr>
		<tr><th><code>include</code></th><td>-</td><td><?php _e('An array of event IDs to explicitly include. In combinaion with other filter the results always is an intersection', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>exclude</code></th><td>-</td><td><?php _e('An array of event IDs to explicitly exclude', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>author</code></th><td>''</td><td><?php _e('Only events of this author will be fetched', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>state</code></th><td>public</td><td><?php _e('Only events in this state will be fetched', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>categories</code></th><td>-</td><td><?php _e('Expects an <strong>array</strong> of categories, which the events must be linked to', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3"><strong><?php _e('Date/Time filtering', fsCalendar::$plugin_textdom); ?></strong><br /><p>
		
		<?php _e('Since all events are valid for a certain period, the filtering needs an extended concept. For filtering you can define a start time or an end time or even both, which defines an interval. To define how theses times are handled, there are three constants', fsCalendar::$plugin_textdom); ?>:</p>
		<ul>
		<li><code>FSE_DATE_MODE_ALL</code> (1) <?php _e('is the most general selector, since the event just has to be valid at any time of your selection (after the start time or before the end time or during the intervall)', fsCalendar::$plugin_textdom); ?>.</li>
		<li><code>FSE_DATE_MODE_START</code> (2) <?php _e('sets the start time of the event to be relevant. If you define a start time and the event starts before this time, it is not selected even if it ends after this time', fsCalendar::$plugin_textdom); ?>.</li>
		<li><code>FSE_DATE_MODE_END</code> (3) <?php _e('sets the end time of the event to be releavant. If you define an endtime and the events ends after this time, it is not selected even if it starts before this time', fsCalendar::$plugin_textdom); ?>.</li>
		</ul>
		<p><?php _e('These constants are passed by the parameter <code>datemode</code>', fsCalendar::$plugin_textdom); ?>.</p>
		</th></tr>
		<tr><th><code>datefrom</code></th><td><?php _e('Current time', fsCalendar::$plugin_textdom); ?></td><td><?php _e('Timestamp or MySQL Datetime value (YYYY-MM-DD HH:MM:SS). Additionally the keywords <code>now</code> for the current time and <code>today</code> '.
			'for the start of todays day are available. These two keyword can be extended by adding or subtracting hours (e.g. <code>today-24</code> for yesterday or <code>today+24</code>for the end of the current day). Other calculations are not supported, you can only add or subtract hours.', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>dateto</code></th><td>''</td><td><?php _e('See documentation of <code>datefrom</code> above', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>datemode</code></th><td>FSE_DATE_MODE_ALL</td><td><?php _e('Use one of the above described constants', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>allday</code></th><td>-</td><td><?php _e('True or False to select eighter only allday or non-allday events', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3"><strong><?php _e('Date/Time filtering', fsCalendar::$plugin_textdom); ?></strong></th></tr>
		<tr><th><code>orderby</code></th><td>datefrom</td><td><?php _e('An array of fields to be sorted. This parameter is not available for the function <code>fse_print_events_list</code>, when grouping is active', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>orderdir</code></th><td>ASC</td><td><?php _e('An array of sort directions (<code>asc</code> or <code>desc</code>). Use the same key as in the array <code>orderby</code> to join the right field', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3">&nbsp;</th></tr>
		<tr><th colspan="3"><a name="usage_posts"></a><h2 class="show"><?php _e('Single Event Usage in Posts and Pages', fsCalendar::$plugin_textdom); ?></h2>
		<p><?php _e("You can display event's details in a post or page by using predefined tags. The eventid is passed by url or directly in the post by using a special tag", fsCalendar::$plugin_textdom); ?>.</p>
		<p><?php _e('To pass the ID by url, just append the paramter <code>event</code> to your url (e.g. <code>www.yourblog.com/pages/myevent?event=37</code>). To load an event in your post without passing by url, use the tag <code>{event_id; id=x}</code> (e.g. <code>{event_id; id=538}</code> directly in your post before using the other tags. By using this tag it is also possible to load more than one event in a sequentiall mechanism. Everytime you insert the <code>event_id</code> tag another event can be loaded', fsCalendar::$plugin_textdom); ?>.</p>
		<p><?php _e('Tags can also be used in the title of the post or page. The mechanism is the same as for the content', fsCalendar::$plugin_textdom); ?>.</p>
		<p><?php _e('Some tags accept <b>optional</b> parameters to control the output. All parameters in the tag should by separated by a ";". Remember that all parameter values are trimmed. If you need some extra whitespaces at the begining or at the and of a value use quotes to surround the value (e.g. &quot;, &quot;)', fsCalendar::$plugin_textdom); ?>. 
		</p>
		</th></tr>
		<tr><th><code>{event_id; id=x}</code></th><td colspan="2"><?php _e("Explicitly loads an event by passing it's ID. <b>If no ID is specified the current ID is printed out</b>", fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>{event_subject}</code></th><td colspan="2"><?php _e("The event's subject", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_location}</code></th><td colspan="2"><?php _e("The event's location", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_description; truncate_more=x}</code></th><td colspan="2"><?php _e("The event's description. If the parameter <code>truncate_more</code> ist set to true (1), the text after the &lt;!--more--&gt; is truncated.", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_startdate; fmt=x}</code></th><td colspan="2"><?php _e("The event's start date; You can pass the parameter <code>fmt</code> to define a differing format", fsCalendar::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_enddate; fmt=x; alwaysshowenddate=y; before=z}</code></th><td colspan="2"><?php _e("The event's end date; You can pass the parameter <code>fmt</code> to define a differing format", fsCalendar::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_starttime; fmt=x; hideifallday=y}</code></th><td colspan="2"><?php _e("The event's start time; You can pass the parameter <code>fmt</code> to define a differing format. Use the parameter <code>hideifallday</code> (using 0 or 1) if you wanna hide the time, if it's an all-day event.", fsCalendar::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_endtime; fmt=x; alwaysshowenddate=y; hideifallday=z; before=z}</code></th><td colspan="2"><?php _e("The event's end time; You can pass the parameter <code>fmt</code> to define a differing format. Use the parameter <code>hideifallday</code> if you wanna hide the time, if it's an all-day event.", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_allday; text=x}</code></th><td colspan="2"><?php _e("Use this tag to print out the parameter <code>text</code> if it is an allday event.", fsCalendar::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_duration; type=x; suffix=y; empty=z}</code></th><td colspan="2"><?php _e("The event's duration; Pass on of the values <code>d</code>, <code>h</code>, <code>m</code> to the argument 
			<code>type</code> to get the days, hours and minutes. You can add a suffix to the output by passing the argument <code>suffix</code>. By default empty values are not printed out, by setting the argument <code>empty</code> to 1 you can change that behaviour.", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_categories; exclude=x; sep=y}</code></th><td colspan="2"><?php _e("The event's categories; Use the paramter <code>exclude</code> to pass a comma-separated list of categories to exclude from displaying", fsCalendar::$plugin_textdom); ?>.
		<?php _e("Use the paramter <code>sep</code> to define the separator (&quot;, &quot; is default). You can also pass the value <code>list</code>, which will force the output in unordered list", fsCalendar::$plugin_textdom); ?> (&lt;ul&gt;&lt;li&gt;cat1&lt;/li&gt;&lt;li&gt;cat2&lt;/li&gt;&lt;/ul&gt;)</td></tr>
		<tr><th><code>{event_publishdate; fmt=x}</code></th><td colspan="2"><?php _e("The event's publish date; You can pass the parameter <code>fmt</code> to define a differing format", fsCalendar::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_publishtime; fmt=x}</code></th><td colspan="2"><?php _e("The event's publish time; You can pass the parameter <code>fmt</code> to define a differing format", fsCalendar::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_author}</code></th><td colspan="2"><?php _e("The event's author's display name", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_authorid}</code></th><td colspan="2"><?php _e("The event's author's user ID", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_publisher}</code></th><td colspan="2"><?php _e("The event's publisher's display name", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_publisherid}</code></th><td colspan="2"><?php _e("The event's publisher's user ID", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_url; linktopost=x}</code></th><td colspan="2"><?php _e("The event's url for the single view. If set to 1 (true) the url points to the post if the event is synchronized.", fsCalendar::$plugin_textdom); ?></td></tr>
		<tr><th colspan="3"><?php _e("By using the following two tags you can print a list of events in the same way as in your themes.", fsCalendar::$plugin_textdom); ?></th></tr>
		<tr><th><code>{events_print; number=x; template=y...}</code></th><td colspan="2"><?php _e('Please see the <a href="#usage_themes">documentation</a> of the function <code>fse_print_events</code>', fsCalendar::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>{events_printlist; number=x; template=y...}</code></th><td colspan="2"><?php _e('Please see the <a href="#usage_themes">documentation</a> of the function <code>fse_print_events_list</code>', fsCalendar::$plugin_textdom); ?>.</td></tr>
		</table>
		<?php	
	}
	function hookSettingsAbout() {
		?>
		<h3 class="show"><?php _e('Plugin website', fsCalendar::$plugin_textdom); ?></h3>
		<p><?php _e('For further information please visit the', fsCalendar::$plugin_textdom); ?> <a href="http://www.faebusoft.ch/downloads/wp-calendar"><?php _e('plugin website', fsCalendar::$plugin_textdom);?></a>.</p>
		<h3 class="show"><?php _e('Support', fsCalendar::$plugin_textdom); ?></h3>
		<p><?php _e('If you have any questions or if you find a bug, pleas leave a comment at the', fsCalendar::$plugin_textdom); ?> <a href="http://www.faebusoft.ch/downloads/wp-calendar"><?php _e('plugin website', fsCalendar::$plugin_textdom);?></a>.<br />
		<?php _e("Please consider that I am doing this (and a lot of other things) all in my free time. So please be patient if you don't get a reply within a few days.",  fsCalendar::$plugin_textdom);?></p>
		<h3 class="show"><?php _e('Donation', fsCalendar::$plugin_textdom); ?></h3>
		<p><?php _e('Like this plugin? Consider a small donation :-) --&gt; Use the donation button on the top right corner.', fsCalendar::$plugin_textdom); ?></p>
		<?php
	}
}
?>