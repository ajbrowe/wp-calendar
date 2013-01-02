<?php
class WPCalendarGrouped extends WP_Widget {
    function WPCalendarGrouped() {
    	$widget_ops = array(
    		'classname'=>'WPCalendarGrouped', 
    		'description'=>__('Display Events grouped by day/month/year', fsCalendar::$plugin_textdom)
    	);
    	
    	// Settings
		$control_ops = array(); 
		
		parent::WP_Widget(false, __('WP Calendar (Grouped)', fsCalendar::$plugin_textdom), $widget_ops, $control_ops);
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract($args);
        
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if ($title) {
			echo $before_title.$title.$after_title;
        }
        
        $cal_args = $instance;
    	if (isset($cal_args['evt_include'])) {
        	$cal_args['include'] = $cal_args['evt_include'];
        } else {
        	unset($cal_args['include']);
        }
        
        fse_print_events_list($cal_args);
        
        echo $after_widget;
    }

    /* Update values */
    function update($new_instance, $old_instance) {
		if (!isset($new_instance['pagination'])) {
			$new_instance['pagination'] = 0;	
		}
    	
        return $new_instance;
    }

    /** @see WP_Widget::form */
	function form($instance) {
    	$defaults = array(
    		'title'=>__('Upcoming Events', fsCalendar::$plugin_textdom),
    		'number'=>get_option('fse_number'), 
    		'groupby'=>get_option('fse_groupby'),
    		'groupby_header'=>get_option('fse_groupby_header'),
    		'template'=>get_option('fse_template_lst'),
    		'showenddate'=>get_option('fse_show_enddate'),
    		'evt_include'=>'',
    		'exclude'=>'',
    		'author'=>'',
    		'categories'=>'',
    		'pagination'=>get_option('fse_pagination'),
    		'pagination_use_dots'=>get_option('fse_pagination_usedots'),
    		'pagination_prev_text'=>get_option('fse_pagination_prev_text'),
    		'pagination_next_text'=>get_option('fse_pagination_next_text'),
    		'pagination_end_size'=>get_option('fse_pagination_end_size'),
    		'pagination_mid_size'=>get_option('fse_pagination_mid_size')
    	);
    	
    	// Abmischen der Argumente
    	$instance = wp_parse_args((array)$instance, $defaults);
    	    	
        $title = esc_attr($instance['title']);
        $number = intval($instance['number']);
        $groupby_header = esc_attr($instance['groupby_header']);
        $template = esc_attr($instance['template']);
        $include = esc_attr($instance['evt_include']);
        $exclude = esc_attr($instance['exclude']);
        $categories = esc_attr($instance['categories']);
        $pagination = ($instance['pagination'] ? true : false);
        $pagination_use_dots = ($instance['pagination_use_dots'] ? true : false);	
        $pagination_prev_text = esc_attr($instance['pagination_prev_text']);
        $pagination_next_text = esc_attr($instance['pagination_next_text']);
        $pagination_end_size = intval($instance['pagination_end_size']);
        $pagination_mid_size = intval($instance['pagination_mid_size']);
        
        
        $uid = substr(uniqid('i'), 0, 6);
        ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><b><?php _e('Title', fsCalendar::$plugin_textdom); ?>:</b></label><br />
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" style="width:96%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><b><?php _e('Number of events', fsCalendar::$plugin_textdom); ?>:</b></label><br />
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" size="3" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'groupby' ); ?>"><b><?php _e('Group by', fsCalendar::$plugin_textdom); ?>:</b></label><br />
			<select id="<?php echo $this->get_field_id( 'groupby' ); ?>" name="<?php echo $this->get_field_name( 'groupby' ); ?>">
				<option value="d"<?php echo ($instance['groupby'] == 'd' ? ' selected="selected"' : ''); ?>><?php _e('Day', fsCalendar::$plugin_textdom); ?></option>
				<option value="m"<?php echo ($instance['groupby'] == 'm' ? ' selected="selected"' : ''); ?>><?php _e('Month', fsCalendar::$plugin_textdom); ?></option>
				<option value="y"<?php echo ($instance['groupby'] == 'y' ? ' selected="selected"' : ''); ?>><?php _e('Year', fsCalendar::$plugin_textdom); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'groupby_header' ); ?>"><b><?php _e('Group Header Format', fsCalendar::$plugin_textdom); ?>:</b></label><br />
			<input id="<?php echo $this->get_field_id( 'groupby_header' ); ?>" name="<?php echo $this->get_field_name( 'groupby_header' ); ?>" value="<?php echo $groupby_header; ?>" size="10" /><br />
			<small><?php _e('Please refer to the php <a href="http://www.php.net/manual/function.date.php" target="_blank">date()</a> function for all valid parameters', fsCalendar::$plugin_textdom)?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><b><?php _e('Template', fsCalendar::$plugin_textdom); ?>:</b></label><br />
			<textarea rows="5" style="width: 96%; font-size: 0.80em;" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo $template; ?></textarea><br />
			<small><?php _e('The whole template is automatically surrounded by the &lt;li&gt; tag.', fsCalendar::$plugin_textdom)?>.</small>
		</p>
		
		<p><b><?php _e('Filters', fsCalendar::$plugin_textdom); ?></b> <small><a id="wfilterlink-<?php echo $uid; ?>" style="cursor: pointer;"><?php _e('Show/hide', fsCalendar::$plugin_textdom); ?></a></small></p>
		
		
		<div id="wfilter-<?php echo $uid; ?>">
		<p>
			<label for="<?php echo $this->get_field_id( 'evt_include' ); ?>"><?php _e('Event inclusion', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'evt_include' ); ?>" name="<?php echo $this->get_field_name( 'evt_include' ); ?>" value="<?php echo $include; ?>" style="width: 96%;" /><br />
			<small><?php _e('A comma separated list of event ids, which should be displayed.', fsCalendar::$plugin_textdom)?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'exclude' ); ?>"><?php _e('Event exclusion', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'exclude' ); ?>" name="<?php echo $this->get_field_name( 'exclude' ); ?>" value="<?php echo $exclude; ?>" style="width: 96%;" /><br />
			<small><?php _e('A comma separated list of event ids, which should <b>not</b> be displayed.', fsCalendar::$plugin_textdom)?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'categories' ); ?>"><?php _e('Categories', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'categories' ); ?>" name="<?php echo $this->get_field_name( 'categories' ); ?>" value="<?php echo $categories; ?>" style="width: 96%;" /><br />
			<small><?php _e('A comma separated list of category ids.', fsCalendar::$plugin_textdom)?></small>
		</p>
		</div>
		
		
		<p><b><?php _e('Pagination', fsCalendar::$plugin_textdom); ?></b> <small><a id="wpaglink-<?php echo $uid; ?>" style="cursor: pointer;"><?php _e('Show/hide', fsCalendar::$plugin_textdom); ?></a></small></p>
		
		<div id="wpag-<?php echo $uid; ?>">
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'pagination' ); ?>" name="<?php echo $this->get_field_name( 'pagination' ); ?>" value="1" <?php echo ($pagination ? 'checked="checked"' : ''); ?>/>
			<label for="<?php echo $this->get_field_id( 'pagination' ); ?>"><?php _e('Enable pagination', fsCalendar::$plugin_textdom); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_use_dots' ); ?>"><?php _e('Appearance', fsCalendar::$plugin_textdom); ?>:</label><br />
			<select id="<?php echo $this->get_field_id( 'pagination_use_dots' ); ?>" name="<?php echo $this->get_field_name( 'pagination_use_dots' ); ?>">
			<option value="1"<?php echo ($pagination_use_dots == true ? ' selected="selected"' : ''); ?>><?php _e('Use dots (...)', fsCalendar::$plugin_textdom); ?></option>
			<option value="0"<?php echo ($pagination_use_dots == false ? ' selected="selected"' : ''); ?>><?php _e('Show all pages', fsCalendar::$plugin_textdom); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_prev_text' ); ?>"><?php _e('Text for prev link', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'pagination_prev_text' ); ?>" name="<?php echo $this->get_field_name( 'pagination_prev_text' ); ?>" value="<?php echo $pagination_prev_text; ?>" style="width: 96%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_next_text' ); ?>"><?php _e('Text for next link', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'pagination_next_text' ); ?>" name="<?php echo $this->get_field_name( 'pagination_next_text' ); ?>" value="<?php echo $pagination_next_text; ?>" style="width: 96%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_end_size' ); ?>"><?php _e('Number of pages at the beginning/end', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'pagination_end_size' ); ?>" name="<?php echo $this->get_field_name( 'pagination_end_size' ); ?>" value="<?php echo $pagination_end_size; ?>" size="5" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_mid_size' ); ?>"><?php _e('Number of pages before/after current page', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'pagination_mid_size' ); ?>" name="<?php echo $this->get_field_name( 'pagination_mid_size' ); ?>" value="<?php echo $pagination_mid_size; ?>" size="5" />
		</p>
		</div>
		
		<script>
		jQuery('#wfilterlink-<?php echo $uid; ?>').click(
			function() {
				if (document.getElementById('wfilter-<?php echo $uid; ?>').style.display == 'none') {
					jQuery('#wfilter-<?php echo $uid; ?>').slideDown("slow");
				} else {
					jQuery('#wfilter-<?php echo $uid; ?>').slideUp("slow");
				}
			}
		);
		jQuery('#wpaglink-<?php echo $uid; ?>').click(
				function() {
					if (document.getElementById('wpag-<?php echo $uid; ?>').style.display == 'none') {
						jQuery('#wpag-<?php echo $uid; ?>').slideDown("slow");
					} else {
						jQuery('#wpag-<?php echo $uid; ?>').slideUp("slow");
					}
				}
			);

		// Hide per default
		jQuery('#wfilter-<?php echo $uid; ?>').hide();
		jQuery('#wpag-<?php echo $uid; ?>').hide();
		</script>
        <?php 
    }

}

class WPCalendarSimple extends WP_Widget {
    function WPCalendarSimple() {
    	$widget_ops = array(
    		'classname'=>'WPCalendarSimple', 
    		'description'=>__('Shows a number of events', fsCalendar::$plugin_textdom)
    	);
    	
    	// Settings
		$control_ops = array(); 
		
		parent::WP_Widget(false, __('WP Calendar (Simple)', fsCalendar::$plugin_textdom), $widget_ops, $control_ops);
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if ($title) {
			echo $before_title.$title.$after_title;
        }
        
        $cal_args = $instance;
    	if (isset($cal_args['evt_include'])) {
        	$cal_args['include'] = $cal_args['evt_include'];
        } else {
        	unset($cal_args['include']);
        }
        
        fse_print_events($cal_args);
        
        echo $after_widget;
    }

    /* Update values */
    function update($new_instance, $old_instance) {				
    	if (!isset($new_instance['pagination'])) {
			$new_instance['pagination'] = 0;	
		}
    	
        return $new_instance;
    }

    /** @see WP_Widget::form */
	function form($instance) {
    	$defaults = array(
    		'title'=>__('Upcoming Events', fsCalendar::$plugin_textdom),
    		'number'=>get_option('fse_number'), 
    		'template'=>get_option('fse_template_lst'),
    		'showenddate'=>get_option('fse_show_enddate'),
    		'evt_include'=>'',
    		'exclude'=>'',
    		'author'=>'',
    		'categories'=>'',
    		'pagination'=>get_option('fse_pagination'),
    		'pagination_use_dots'=>get_option('fse_pagination_usedots'),
    		'pagination_prev_text'=>get_option('fse_pagination_prev_text'),
    		'pagination_next_text'=>get_option('fse_pagination_next_text'),
    		'pagination_end_size'=>get_option('fse_pagination_end_size'),
    		'pagination_mid_size'=>get_option('fse_pagination_mid_size')
    	);
    	
    	// Abmischen der Argumente
    	$instance = wp_parse_args((array)$instance, $defaults);
    	
        $title = esc_attr($instance['title']);
        $number = intval($instance['number']);
        $template = esc_attr($instance['template']);
        $include = esc_attr($instance['evt_include']);
        $exclude = esc_attr($instance['exclude']);
        $categories = esc_attr($instance['categories']);
        $pagination = $instance['pagination'] == true ? true : false;
        $pagination_use_dots = $instance['pagination_use_dots'] == true ? true : false;
        $pagination_prev_text = esc_attr($instance['pagination_prev_text']);
        $pagination_next_text = esc_attr($instance['pagination_next_text']);
        $pagination_end_size = intval($instance['pagination_end_size']);
        $pagination_mid_size = intval($instance['pagination_mid_size']);
        
        $uid = substr(uniqid('i'), 0, 6);
        ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" style="width:96%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e('Number of events', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" size="3" />
		</p>
				<p>
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e('Template', fsCalendar::$plugin_textdom); ?>:</label><br />
			<textarea rows="5" style="width: 96%; font-size: 0.80em;" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo $template; ?></textarea>
		</p>
		
		<p><b><?php _e('Filters', fsCalendar::$plugin_textdom); ?></b> <small><a id="wfilterlink-<?php echo $uid; ?>" style="cursor: pointer;"><?php _e('Show/hide', fsCalendar::$plugin_textdom); ?></a></small></p>
		
		
		<div id="wfilter-<?php echo $uid; ?>">
		<p>
			<label for="<?php echo $this->get_field_id( 'evt_include' ); ?>"><?php _e('Event inclusion', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'evt_include' ); ?>" name="<?php echo $this->get_field_name( 'evt_include' ); ?>" value="<?php echo $include; ?>" style="width: 96%;" /><br />
			<small><?php _e('A comma separated list of event ids, which should be displayed.', fsCalendar::$plugin_textdom)?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'exclude' ); ?>"><?php _e('Event exclusion', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'exclude' ); ?>" name="<?php echo $this->get_field_name( 'exclude' ); ?>" value="<?php echo $exclude; ?>" style="width: 96%;" /><br />
			<small><?php _e('A comma separated list of event ids, which should <b>not</b> be displayed.', fsCalendar::$plugin_textdom)?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'categories' ); ?>"><?php _e('Categories', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'categories' ); ?>" name="<?php echo $this->get_field_name( 'categories' ); ?>" value="<?php echo $categories; ?>" style="width: 96%;" /><br />
			<small><?php _e('A comma separated list of category ids.', fsCalendar::$plugin_textdom)?></small>
		</p>
		</div>
		
		
		<p><b><?php _e('Pagination', fsCalendar::$plugin_textdom); ?></b> <small><a id="wpaglink-<?php echo $uid; ?>" style="cursor: pointer;"><?php _e('Show/hide', fsCalendar::$plugin_textdom); ?></a></small></p>
		
		<div id="wpag-<?php echo $uid; ?>">
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'pagination' ); ?>" name="<?php echo $this->get_field_name( 'pagination' ); ?>" value="1" <?php echo ($pagination ? 'checked="checked"' : ''); ?>/>
			<label for="<?php echo $this->get_field_id( 'pagination' ); ?>"><?php _e('Enable pagination', fsCalendar::$plugin_textdom); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_use_dots' ); ?>"><?php _e('Appearance', fsCalendar::$plugin_textdom); ?>:</label><br />
			<select id="<?php echo $this->get_field_id( 'pagination_use_dots' ); ?>" name="<?php echo $this->get_field_name( 'pagination_use_dots' ); ?>">
			<option value="1"<?php echo ($pagination_use_dots == true ? ' selected="selected"' : ''); ?>><?php _e('Use dots (...)', fsCalendar::$plugin_textdom); ?></option>
			<option value="0"<?php echo ($pagination_use_dots == false ? ' selected="selected"' : ''); ?>><?php _e('Show all pages', fsCalendar::$plugin_textdom); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_prev_text' ); ?>"><?php _e('Text for prev link', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'pagination_prev_text' ); ?>" name="<?php echo $this->get_field_name( 'pagination_prev_text' ); ?>" value="<?php echo $pagination_prev_text; ?>" style="width: 96%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_next_text' ); ?>"><?php _e('Text for next link', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'pagination_next_text' ); ?>" name="<?php echo $this->get_field_name( 'pagination_next_text' ); ?>" value="<?php echo $pagination_next_text; ?>" style="width: 96%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_end_size' ); ?>"><?php _e('Number of pages at the beginning/end', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'pagination_end_size' ); ?>" name="<?php echo $this->get_field_name( 'pagination_end_size' ); ?>" value="<?php echo $pagination_end_size; ?>" size="5" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pagination_mid_size' ); ?>"><?php _e('Number of pages before/after current page', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'pagination_mid_size' ); ?>" name="<?php echo $this->get_field_name( 'pagination_mid_size' ); ?>" value="<?php echo $pagination_mid_size; ?>" size="5" />
		</p>
		</div>
		
		<script>
		jQuery('#wfilterlink-<?php echo $uid; ?>').click(
			function() {
				if (document.getElementById('wfilter-<?php echo $uid; ?>').style.display == 'none') {
					jQuery('#wfilter-<?php echo $uid; ?>').slideDown("slow");
				} else {
					jQuery('#wfilter-<?php echo $uid; ?>').slideUp("slow");
				}
			}
		);
		jQuery('#wpaglink-<?php echo $uid; ?>').click(
				function() {
					if (document.getElementById('wpag-<?php echo $uid; ?>').style.display == 'none') {
						jQuery('#wpag-<?php echo $uid; ?>').slideDown("slow");
					} else {
						jQuery('#wpag-<?php echo $uid; ?>').slideUp("slow");
					}
				}
			);

		// Hide per default
		jQuery('#wfilter-<?php echo $uid; ?>').hide();
		jQuery('#wpag-<?php echo $uid; ?>').hide();
		</script>
		
        <?php 
    }

}
?>