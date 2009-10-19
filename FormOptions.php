<?php
if (!current_user_can('manage_options'))
	wp_die(__('Cheatin&#8217; uh?', self::$plugin_textdom));
	
?>
<?php echo $this->pageStart(__('Calendar Settings', self::$plugin_textdom)); ?>
	<?php echo $this->pagePostContainerStart(75); ?>
	
	<form name="tb_post" method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>

	<?php echo $this->pagePostBoxStart('fse_global', __('Global Setting', self::$plugin_textdom)); ?>
		<table class="fs-table">
		<tr><th><?php _e('Events per Page', self::$plugin_textdom); ?></th><td><input type="text" value="<?php echo get_option('fse_epp'); ?>" size="5" name="fse_epp" /></td></tr>
		<tr><th class="label"><?php _e('Date Format', self::$plugin_textdom); ?></th><td>
		<input type="text" name="fse_df" id="fse_df" value="<?php echo get_option('fse_df'); ?>" />
		<input type="checkbox"
			   name="fse_df_wp" 
			   value="1"
			   id="fse_df_wp"
			   onClick="fse_toogleInputByCheckbox(this, 'fse_df', false);"
			   size="10" 
			   <?php echo (get_option('fse_df_wp') == 1 ? 'checked="checked"' : '' ); ?>/> 
		<label for="fse_df_wp"><?php _e('Use WP settings', self::$plugin_textdom)?></label><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', self::$plugin_textdom)?></small>
		</td></tr>
		<tr><th class="label"><?php _e('Time Format', self::$plugin_textdom); ?></th><td>
		<input type="text" name="fse_tf" id="fse_tf" value="<?php echo get_option('fse_tf'); ?>" />
		<input type="checkbox" 
			   name="fse_tf_wp"
			   value="1"
			   id="fse_tf_wp"
			   size="10"
			   onClick="fse_toogleInputByCheckbox(this, 'fse_tf', false);"  
			   <?php echo (get_option('fse_df_wp') == 1 ? 'checked="checked"' : '' ); ?>/> 
		<label for="fse_tf_wp"><?php _e('Use WP settings', self::$plugin_textdom)?></label><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', self::$plugin_textdom)?></small>
		</td></tr>
		<tr><th class="label"><?php _e('Weeks starts on', self::$plugin_textdom); ?></th><td>
		<select name="fse_ws" id="fse_ws">
		<?php 
		$days = array(__('Sunday', self::$plugin_textdom), 
					  __('Monday', self::$plugin_textdom),
					  __('Tuesday', self::$plugin_textdom), 
					  __('Wednesday', self::$plugin_textdom), 
					  __('Thursday', self::$plugin_textdom), 
					  __('Friday', self::$plugin_textdom), 
					  __('Saturday', self::$plugin_textdom));
		$s = get_option('fse_ws');
		foreach($days as $k => $d) {
			echo '<option value="'.$k.'" '.($k == $s ? 'selected="selected"' : '').'>'.$d.'</option>';
		}
		?>
		</select>
		<input type="checkbox" 
			   name="fse_ws_wp"
			   value="1"
			   id="fse_ws_wp"
			   onClick="fse_toogleInputByCheckbox(this, 'fse_ws', false);"  
			   <?php echo (get_option('fse_ws_wp') == 1 ? 'checked="checked"' : '' ); ?>/> 
		<label for="fse_ws_wp"><?php _e('Use WP settings', self::$plugin_textdom)?></label>
		</td></tr>
		<tr><th class="label"><?php _e('Date Format for Admin Interface', self::$plugin_textdom); ?></th><td>
		<select name="fse_df_admin">
		<?php 
		$format = array(
			'dmY' => __('Day', self::$plugin_textdom).' '.__('Month', self::$plugin_textdom).' '.__('Year', self::$plugin_textdom),
			'mdY' => __('Month', self::$plugin_textdom).' '.__('Day', self::$plugin_textdom).' '.__('Year', self::$plugin_textdom),
			'Ymd' => __('Year', self::$plugin_textdom).' '.__('Month', self::$plugin_textdom).' '.__('Day', self::$plugin_textdom),
			'Ydm' => __('Year', self::$plugin_textdom).' '.__('Day', self::$plugin_textdom).' '.__('Month', self::$plugin_textdom)
		);
		$s = get_option('fse_df_admin');
		foreach($format as $k => $f) {
			echo '<option value="'.$k.'" '.($s == $k ? 'selected="selected"' : '').'>'.$f.'</option>';
		}
		?>
		</select>
		<?php _e('separated by', self::$plugin_textdom)?> 
		<select name="fse_df_admin_sep">
		<?php 
		$sep = array('.', '-', '/'); 
		$o = get_option('fse_df_admin_sep');
		foreach($sep as $s) {
			echo '<option value="'.$s.'" '.($s == $o ? 'selected="selected"' : '').'>'.$s.'</option>';
		}
		?>
		</select>
		</td></tr>
		</table>
	<?php echo $this->pagePostBoxEnd(); ?>

	<?php echo $this->pagePostBoxStart('fse_display', __('Display', self::$plugin_textdom)); ?>
		<table class="fs-table">
		<tr><th colspan="2">
		<p><?php _e('There are several ways to display events on your blog. You can use different functions in your theme, or tags in your post and pages to display an event.', self::$plugin_textdom); ?></p>
		<p><?php _e('You can define a page for displaying a single event. This page will always be link to, when using a function of this plug-in to show an overview of events (list, graphical calendar,...). If you do not define a page for displaying a single event, make sure you change the template defined below, since it uses the paramter <code>{event_url}</code>, which is not available, if no single view page is defined.', self::$plugin_textdom); ?></p>
		<p><?php _e('Because this page normally should not be displayed in any page listing, you can hide it and it will disapear from any lists in your blog, provided that your theme and plug-ins use standard wordpress functions and are not reading directly from the database. By ticking the flag <i>Mark page</i> the selected page will be <span id="page_is_cal"><span>highlighted</span></span> in the page overview.', self::$plugin_textdom); ?></p>
		</th></tr>
		<tr><th class="label"><?php _e('Single view page', self::$plugin_textdom); ?></th><td>
		<select name="fse_page">
		<option value=""><?php _e('No single view page', self::$plugin_textdom)?></option>
		<?php
		$pages = get_pages();
		$s = get_option('fse_page');
		foreach($pages as $p) {
			echo '<option value="'.$p->ID.'"'.($s == $p->ID ? 'selected="selected"' : '').'>'.$p->post_title.'</option>';
		}
		?>
		</select><br />
		<input type="checkbox" value="1" name="fse_page_mark" id="fse_page_mark" <?php echo (get_option('fse_page_mark') == 1 ? 'checked="checked" ' : ''); ?>/> <label for="fse_page_mark"><?php _e('Mark page in page overvie', self::$plugin_textdom); ?></label><br />
		<input type="checkbox" value="1" name="fse_page_hide" id="fse_page_hide" <?php echo (get_option('fse_page_hide') == 1 ? 'checked="checked" ' : ''); ?>/> <label for="fse_page_hide"><?php _e('Set page as hidden', self::$plugin_textdom); ?></label>
		</td></tr>
		<tr><th colspan="2">
		<p><?php _e("If you're using any the functions described below, you can pass many options (e.g. number of events,...). Here you define the standard values, if not specified in the function call.", self::$plugin_textdom); ?></p>
		</th></tr>
		<tr><th><?php _e('Number of events', self::$plugin_textdom); ?></th><td><input type="text" value="<?php echo intval(get_option('fse_number')); ?>" size="3" name="fse_number" /></td></tr>
		<tr><th><?php _e('Show end date', self::$plugin_textdom); ?></th><td>
		<select name="fse_show_enddate">
		<option value="1"<?php echo (get_option('fse_show_enddate') == 1 ? ' selected="selected"' : ''); ?>><?php _e('Always show end date', self::$plugin_textdom)?></option>
		<option value="0"<?php echo (get_option('fse_show_enddate') == 0 ? ' selected="selected"' : ''); ?>><?php _e('Only show end date, if different from start date', self::$plugin_textdom)?></option>
		</select>
		</td></tr>
		<tr><th colspan="2">
		<p><?php _e('The supported functions can eighter return an array of post or directly print out the result. Define the template which is used to print an event without any hierarchy and the template which is used for a grouped list. The parameters are the same as for the posts and pages described <a href="#usage_posts">below</a>.', self::$plugin_textdom); ?></p>
		</th></tr>
		<tr><th><?php _e('Template', self::$plugin_textdom); ?></th><td><textarea rows="5" cols="80" name="fse_template" /><?php echo htmlentities(get_option('fse_template')); ?></textarea></td></tr>
		<tr><th><?php _e('Template for Listoutput', self::$plugin_textdom); ?><br /><small><?php _e('The whole template is automatically surrounded by the &lt;li&gt; tag.', self::$plugin_textdom)?>.</small></th><td><textarea rows="5" cols="80" name="fse_template_lst" /><?php echo htmlentities(get_option('fse_template_lst')); ?></textarea></td></tr>
		<tr><th colspan="2">
		<p><?php _e('The function <code>fse_list_events</code> allows the output in an unordered list, which allows an hierarchical grouping by date entities. You can define the default group entity and the output format for the grouped entity. Please refer to the php  <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all the output format options.', self::$plugin_textdom); ?></p>
		</th></tr>
		<tr><th><?php _e('Group by', self::$plugin_textdom); ?></th><td>
		<select name="fse_groupby">
		<option value="d"<?php echo (get_option('fse_groupby') == '' ? ' selected="selected"' : ''); ?>><?php _e('No grouping', self::$plugin_textdom); ?></option>
		<option value="d"<?php echo (get_option('fse_groupby') == 'd' ? ' selected="selected"' : ''); ?>><?php _e('Day', self::$plugin_textdom); ?></option>
		<option value="m"<?php echo (get_option('fse_groupby') == 'm' ? ' selected="selected"' : ''); ?>><?php _e('Month', self::$plugin_textdom); ?></option>
		<option value="y"<?php echo (get_option('fse_groupby') == 'y' ? ' selected="selected"' : ''); ?>><?php _e('Year', self::$plugin_textdom); ?></option>
		</select>
		</td></tr>
		<tr><th><?php _e('Header Format', self::$plugin_textdom); ?></th><td><input type="text" value="<?php echo get_option('fse_groupby_header'); ?>" size="10" name="fse_groupby_header" /></td></tr>
		</table>
	<?php echo $this->pagePostBoxEnd(); ?>
	
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes', self::$plugin_textdom); ?>" />  
	</p>
	<?php echo $this->pagePostBoxStart('fse_usage', __('Usage', self::$plugin_textdom)); ?>
		<table class="fs-table">
		<tr><th colspan="3"><a name="usage_themes"><h3><?php _e('Usage in Themes', self::$plugin_textdom); ?></a></h3>
		<p>
		<?php _e('At the moment there are four functions to use', self::$plugin_textdom); ?>:
		<ul>
			<li><code>fse_get_events($args = array())</code> - <?php _e('Returns an array of event objects for further processing by yourself', self::$plugin_textdom); ?></li>
			<li><code>fse_print_events($args = array())</code> - <?php _e('Prints a selection of events without any hierarchy', self::$plugin_textdom); ?></li>
			<li><code>fse_print_events_list($args = array())</code> - <?php _e('Prints an unordered list and allows grouping by a date entity', self::$plugin_textdom); ?></li>
			<li><code>fse_get_event($eventid)</code> - <?php _e("Just returns an event object an accepts only the event's ID to be passed", self::$plugin_textdom); ?></li>
		</ul> 
		<p><?php _e('The first three functions accept one parameter, which expects to be an associative array. Call a function like this', self::$plugin_textdom); ?>:</p> 
		<pre>
fse_print_events(
  array( 'number'   => 10,
         'exclude'  => array(387, 827),
         'before'   => '&lt;table cellpadding="0" cellspacing="0">',
         'after'    => '&lt;/table>',
         'template' => '&lt;tr>&lt;td>{event_subject}&lt;br />@{event_location}&lt;/td>&lt;/tr>'
);
		</pre>
		<p><?php _e("The allowed paramters are described below. Some parameters are not supported by all functions, since they won't make any sense", self::$plugin_textdom); ?>.</p>
		</th></tr>
		<tr><th colspan="3"><strong><?php _e('Output control', self::$plugin_textdom); ?></strong></th></tr>
		<tr><th><?php _e('Parameter', self::$plugin_textdom); ?></th><td><?php _e('Default', self::$plugin_textdom); ?></td><td><?php _e('Description', self::$plugin_textdom); ?></td></tr>
		<tr><th><code>echo</code></th><td>true</td><td><?php _e('The functions <code>fse_print_events</code> and <code>fse_print_events_list</code> normally echos the result. By setting the parameter <code>echo</code> to false, the result is returned instead', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>number</code></th><td><?php _e('Calendar Options', self::$plugin_textdom); ?></td><td><?php _e('The number of events to return or print', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>template</code></th><td><?php _e('Calendar Options', self::$plugin_textdom); ?></td><td><?php _e('The template used for processing the output of an event. You can use the same tags as in post and pages, described <a href="#usage_posts">here</a>', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>before</code></th><td>''</td><td><?php _e('Additional HTML code to print before', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>after</code></th><td>''</td><td><?php _e('Additional HTML code to print after', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>alwaysshowenddate</code></th><td><?php _e('Calendar Options', self::$plugin_textdom); ?></td><td><?php _e('If set to false, the enddate is left empty, if it is not differing from the start date', self::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3"><strong><?php _e('Standard filtering', self::$plugin_textdom); ?></strong></th></tr>
		<tr><th><code>include</code></th><td>-</td><td><?php _e('An array of event IDs to explicitly include. In combinaion with other filter the results always is an intersection', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>exclude</code></th><td>-</td><td><?php _e('An array of event IDs to explicitly exclude', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>author</code></th><td>''</td><td><?php _e('Only events of this author will be fetched', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>state</code></th><td>public</td><td><?php _e('Only events in this state will be fetched', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>categories</code></th><td>-</td><td><?php _e('Expects an <strong>array</strong> of categories, which the events must be linked to', self::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3"><strong><?php _e('Date/Time filtering', self::$plugin_textdom); ?></strong><br /><p>
		
		<?php _e('Since all events are valid for a certain period, the filtering needs an extended concept. For filtering you can define a start time or an end time or even both, which defines an interval. To define how theses times are handled, there are three constants', self::$plugin_textdom); ?>:</p>
		<ul>
		<li><code>FSE_DATE_MODE_ALL</code> (1) <?php _e('is the most general selector, since the event just has to be valid at any time of your selection (after the start time or before the end time or during the intervall)', self::$plugin_textdom); ?>.</li>
		<li><code>FSE_DATE_MODE_START</code> (2) <?php _e('sets the start time of the event to be relevant. If you define a start time and the event starts before this time, it is not selected even if it ends after this time', self::$plugin_textdom); ?>.</li>
		<li><code>FSE_DATE_MODE_END</code> (3) <?php _e('sets the end time of the event to be releavant. If you define an endtime and the events ends after this time, it is not selected even if it starts before this time', self::$plugin_textdom); ?>.</li>
		</ul>
		<p><?php _e('These constants are passed by the parameter <code>datemode</code>', self::$plugin_textdom); ?>.</p>
		</th></tr>
		<tr><th><code>datefrom</code></th><td><?php _e('Current time', self::$plugin_textdom); ?></td><td><?php _e('Timestamp of start time', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>dateto</code></th><td>''</td><td><?php _e('Timestamp of end time', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>datemode</code></th><td>FSE_DATE_MODE_START</td><td><?php _e('Use one of the above described constants', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>allday</code></th><td>-</td><td><?php _e('True or False to select eighter only allday or non-allday events', self::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3"><strong><?php _e('Date/Time filtering', self::$plugin_textdom); ?></strong></th></tr>
		<tr><th><code>orderby</code></th><td>datefrom</td><td><?php _e('An array of fields to be sorted. This parameter is not available for the function <code>fse_print_events_list</code>, when grouping is active', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>orderdir</code></th><td>ASC</td><td><?php _e('An array of sort directions (<code>asc</code> or <code>desc</code>). Use the same key as in the array <code>orderby</code> to join the right field', self::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3"><strong><?php _e('Grouping', self::$plugin_textdom); ?></strong><br />
		<p><?php _e('Grouping is only available for the function <code>fse_print_events_list</code>', self::$plugin_textdom); ?>.</p></th></tr>
		<tr><th><code>groupby</code></th><td><?php _e('Calendar options', self::$plugin_textdom); ?></td><td><?php _e('Use the following constants', self::$plugin_textdom); ?>:
		<ul>
		<li><code>FSE_GROUPBY_NONE</code> - <?php _e('No grouping', self::$plugin_textdom); ?></li>
		<li><code>FSE_GROUPBY_DAY</code> - <?php _e('Group by day', self::$plugin_textdom); ?></li>
		<li><code>FSE_GROUPBY_MONTH</code> - <?php _e('Group by month', self::$plugin_textdom); ?></li>
		<li><code>FSE_GROUPBY_YEAR</code> - <?php _e('Group by year', self::$plugin_textdom); ?></li>
		</ul>
		</td></tr>
		<tr><th><code>groupby_header</code></th><td><?php _e('Calendar options', self::$plugin_textdom); ?></td><td><?php _e('The header format when grouping, refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function', self::$plugin_textdom); ?>.</td></tr>
		<tr><th colspan="3">&nbsp;</th></tr>
		<tr><th colspan="3"><h3><a name="usage_posts"><?php _e('Single Event Usage in Posts and Pages', self::$plugin_textdom); ?></a></h3>
		<p><?php _e("You can display event's details in a post or page by using predefined tags. The eventid is passed by url or directly in the post by using a special tag", self::$plugin_textdom); ?>.</p>
		<p><?php _e('To pass the ID by url, just append the paramter <code>event</code> to your url (e.g. <code>www.yourblog.com/pages/myevent?event=37</code>). To load an event in your post without passing by url, use the tag <code>{event_id; id=x}</code> (e.g. <code>{event_id; id=538}</code> directly in your post before using the other tags. By using this tag it is also possible to load more than one event in a sequentiall mechanism. Everytime you insert the <code>event_id</code> tag another event can be loaded', self::$plugin_textdom); ?>.</p>
		<p><?php _e('Tags can also be used in the title of the post or page. The mechanism is the same as for the content', self::$plugin_textdom); ?>.</p>
		<p><?php _e('Some tags accept <b>optional</b> parameters to control the output. All parameters in the tag should by separated by a ";". Remember that all parameter values are trimmed. If you need some extra whitespaces at the begining or at the and of a value use quotes to surround the value (e.g. &quot;, &quot;)', self::$plugin_textdom); ?>. 
		</p>
		</th></tr>
		<tr><th><code>{event_id; id=x}</code></th><td colspan="2"><?php _e("Explicitly loads an event by passing it's ID. <b>If no ID is specified the current ID is printed out</b>", self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>{event_subject}</code></th><td colspan="2"><?php _e("The event's subject", self::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_location}</code></th><td colspan="2"><?php _e("The event's location", self::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_description}</code></th><td colspan="2"><?php _e("The event's description", self::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_startdate; fmt=x}</code></th><td colspan="2"><?php _e("The event's start date; You can pass the parameter <code>fmt</code> to define a differing format", self::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', self::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_enddate; fmt=x}</code></th><td colspan="2"><?php _e("The event's end date; You can pass the parameter <code>fmt</code> to define a differing format", self::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', self::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_starttime; fmt=x}</code></th><td colspan="2"><?php _e("The event's start time; You can pass the parameter <code>fmt</code> to define a differing format", self::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', self::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_endtime; fmt=x}</code></th><td colspan="2"><?php _e("The event's start time; You can pass the parameter <code>fmt</code> to define a differing format", self::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', self::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_duration; type=x; suffix=y; empty=z}</code></th><td colspan="2"><?php _e("The event's duration; Pass on of the values <code>d</code>, <code>h</code>, <code>m</code> to the argument 
			<code>type</code> to get the days, hours and minutes. You can add a suffix to the output by passing the argument <code>suffix</code>. By default empty values are not printed out, by setting the argument <code>empty</code> to 1 you can change that behaviour.", self::$plugin_textdom); ?><br />
		<tr><th><code>{event_categories; exclude=x; sep=y}</code></th><td colspan="2"><?php _e("The event's categories; Yous the paramter <code>exclude</code> to pass a comma-separated list of categories to exclude from displaying", self::$plugin_textdom); ?>.
		<?php _e("Use the paramter <code>sep</code> to define the separator (&quot;, &quot; is default). You can also pass the value <code>list</code>, which will force the output in unordered list", self::$plugin_textdom); ?> (&lt;ul&gt;&lt;li&gt;cat1&lt;/li&gt;&lt;li&gt;cat2&lt;/li&gt;&lt;/ul&gt;)
		<tr><th><code>{event_publishdate; fmt=x}</code></th><td colspan="2"><?php _e("The event's publish date; You can pass the parameter <code>fmt</code> to define a differing format", self::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', self::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_publishtime; fmt=x}</code></th><td colspan="2"><?php _e("The event's publish time; You can pass the parameter <code>fmt</code> to define a differing format", self::$plugin_textdom); ?><br />
		<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', self::$plugin_textdom)?></small></td></tr>
		<tr><th><code>{event_author}</code></th><td colspan="2"><?php _e("The event's author's display name", self::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_authorid}</code></th><td colspan="2"><?php _e("The event's author's user ID", self::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_publisher}</code></th><td colspan="2"><?php _e("The event's publisher's display name", self::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_publisherid}</code></th><td colspan="2"><?php _e("The event's publisher's user ID", self::$plugin_textdom); ?></td></tr>
		<tr><th><code>{event_url}</code></th><td colspan="2"><?php _e("The event's url for the single view", self::$plugin_textdom); ?></td></tr>
		<tr><th colspan="3"><?php _e("By using the following two tags you can print a list of events in the same way as in your themes.", self::$plugin_textdom); ?></th></tr>
		<tr><th><code>{events_print; number=x; template=y...}</code></th><td colspan="2"><?php _e('Please see the <a href="#usage_themes">documentation</a> of the function <code>fse_print_events</code>', self::$plugin_textdom); ?>.</td></tr>
		<tr><th><code>{events_printlist; number=x; template=y...}</code></th><td colspan="2"><?php _e('Please see the <a href="#usage_themes">documentation</a> of the function <code>fse_print_events_list</code>', self::$plugin_textdom); ?>.</td></tr>
		</table>
	<?php echo $this->pagePostBoxEnd(); ?>
					
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tb_action" value="tb_save_options" />
	<?php echo '<input type="hidden" name="page_options" value="';
	foreach(self::$plugin_options as $k => $v) {
		echo $k.',';
	}
	echo '" />'; ?>
	</form>
	<?php echo $this->pagePostContainerEnd(); ?>
	
	<?php echo $this->pagePostContainerStart(20); ?>				
		<?php echo $this->pagePostBoxStart('pb_about', __('About', self::$plugin_textdom)); ?>
			<p><?php _e('For further information please visit the', self::$plugin_textdom); ?> <a href="http://www.faebusoft.ch/downloads/thickbox-announcement"><?php _e('plugin homepage', self::$plugin_textdom);?></a>.<br /> 
		<?php echo $this->pagePostBoxEnd(); ?>
						
		<?php echo $this->pagePostBoxStart('pb_donate', __('Donation', self::$plugin_textdom)); ?>
			<p><?php _e('If you like my work please consider a small donation', self::$plugin_textdom); ?></p>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
			</form>
		<?php echo $this->pagePostBoxEnd(); ?>
	<?php echo $this->pagePostContainerEnd(); ?>
<?php echo $this->pageEnd(); ?>

<script type="text/javascript">
jQuery(document).ready(function() {
	fse_toogleInputByCheckbox(document.getElementById('fse_df_wp'), 'fse_df', false);
	fse_toogleInputByCheckbox(document.getElementById('fse_tf_wp'), 'fse_tf', false);
	fse_toogleInputByCheckbox(document.getElementById('fse_ws_wp'), 'fse_ws', false);
});
</script>