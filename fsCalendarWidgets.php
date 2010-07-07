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
        
        fse_print_events_list($instance);
        
        echo $after_widget;
    }

    /* Update values */
    function update($new_instance, $old_instance) {				
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
    		'include'=>'',
    		'exclude'=>'',
    		'author'=>'',
    		'categories'=>''
    	);
    	
    	// Abmischen der Argumente
    	$instance = wp_parse_args((array)$instance, $defaults);
    	
        $title = esc_attr($instance['title']);
        $number = intval($instance['number']);
        $groupby_header = esc_attr($instance['groupby_header']);
        $template = esc_attr($instance['template']);
        $include = esc_attr($instance['include']);
        $exclude = esc_attr($instance['exclude']);
        $categories = esc_attr($instance['categories']);
        ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><b><?php _e('Title', fsCalendar::$plugin_textdom); ?>:</b></label><br />
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" style="width:100%;" />
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
			<textarea rows="5" style="width: 100%; font-size: 0.80em;" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo $template; ?></textarea><br />
			<small><?php _e('The whole template is automatically surrounded by the &lt;li&gt; tag.', fsCalendar::$plugin_textdom)?>.</small>
		</p>
		
		<p><b>Filters</b> <small><a href=""><?php _e('Show/hide', fsCalendar::$plugin_textdom); ?></a></small></p>
		<div id="wfilter-">
		<p>
			<label for="<?php echo $this->get_field_id( 'include' ); ?>"><?php _e('Event inclusion', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'include' ); ?>" name="<?php echo $this->get_field_name( 'include' ); ?>" value="<?php echo $include; ?>" style="width: 100%;" /><br />
			<small><?php _e('A comma separated list of event ids, which should be displayed.', fsCalendar::$plugin_textdom)?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'exclude' ); ?>"><?php _e('Event exclusion', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'exclude' ); ?>" name="<?php echo $this->get_field_name( 'exclude' ); ?>" value="<?php echo $exclude; ?>" style="width: 100%;" /><br />
			<small><?php _e('A comma separated list of event ids, which should <b>not</b> be displayed.', fsCalendar::$plugin_textdom)?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'categories' ); ?>"><?php _e('Categories', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'categories' ); ?>" name="<?php echo $this->get_field_name( 'categories' ); ?>" value="<?php echo $categories; ?>" style="width: 100%;" /><br />
			<small><?php _e('A comma separated list of category ids.', fsCalendar::$plugin_textdom)?></small>
		</p>
		</div>
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
        
        fse_print_events($instance);
        
        echo $after_widget;
    }

    /* Update values */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
	function form($instance) {
    	$defaults = array(
    		'title'=>__('Upcoming Events', fsCalendar::$plugin_textdom),
    		'number'=>get_option('fse_number'), 
    		'template'=>get_option('fse_template_lst'),
    		'showenddate'=>get_option('fse_show_enddate')
    	);
    	
    	// Abmischen der Argumente
    	$instance = wp_parse_args((array)$instance, $defaults);
    	
        $title = esc_attr($instance['title']);
        $number = intval($instance['number']);
        $template = esc_attr($instance['template']);
        ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e('Number of events', fsCalendar::$plugin_textdom); ?>:</label><br />
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" size="3" />
		</p>
				<p>
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e('Template', fsCalendar::$plugin_textdom); ?>:</label><br />
			<textarea rows="5" style="width: 100%; font-size: 0.80em;" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo $template; ?></textarea><br />
			<small><?php _e('The whole template is automatically surrounded by the &lt;li&gt; tag.', fsCalendar::$plugin_textdom)?>.</small>
		</p>
		
        <?php 
    }

}
?>