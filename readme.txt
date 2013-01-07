=== WP Calendar ===
Contributors: faebu
Tags: calendar, events, post, wp calendar, event, event calendar, termin, kalender, terminkalender, wp kalender
Donate link: http://www.faebusoft.ch/webentwicklung/wpcalendar/
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 1.5.3
WP Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage. 

== Description ==

WP Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage. 
The whole usage is extensive and completely documented. It supports all-day events, categorization and state management (draft, publish). 
To manage the event, the same authority checks as for post are used. The plug-in can be integrated in your blog using widgets, 
into any theme by using different functions and in any post and page by using different tags.

= Important Upgrade Notice for 1.5.1/2 =
Due to a jQuery update in Wordpress 3.5 the graphical Calendar stopped working and I had to update the external FullCalendar library. The newest 
version of FullCalendar also needed an update of the graphical calendars stylesheet.  If you upgrade to 1.5.1 and if you have a modified fullcalendar.css 
file in your theme directory, make sure you copy the fullcalendar.css file from the plug-in directory and adapt your modifications again!
The graphical calendar will probably not work with your modified stylesheet!

If you have any category specific styling, these style have to be slightly adjusted, because the DOM structure of the displayed events in the 
graphical calendar have change. Have a look at the FAQ section, which discribes how to style events by it's category.

= Features =
* NEW: Ical Feed of your events!
* Graphical calendar
* Widget
* Event categorization (using WP categories)
* Event states (draft, published)
* Normal and all-day events
* Easy to use admin interface (Wordpress style)
* Event overview including filters and pagination
* Easy and fully documented integration
* Mass operations on events (delete, set state)
* Cloning of events
* Event creation from post including content synchronization
* [Comments for events](http://www.faebusoft.ch/webentwicklung/wp-calendar-1-4-0-freigegeben/)
* Same authority checks as for post and pages
* Supports special tags in post/page content and title
* Different functions for integration in your theme
* Event grouping in output (per day, month or year)
* Internationalization including date formats

= Planned =
* Repeating/Reccuring events
* Customer fields
* Google Maps integration
* Dashboard integration
* Location templates
* Content templates
* Search integration


== Installation ==

1. Unpack the download package

2. Upload folder include all files to the `/wp-content/plugins/` directory.

3. Activate the plugin through the `Plugins` menu in WordPress

4. Go to `Options` > `Calendar` menu, check your settings and read the usage documentation

5. Go to `Calendar` > `Add new` to add a new event

== Frequently Asked Questions ==

= Parse error: syntax error, unexpected T_STATIC, expecting T_OLD_FUNCTION... =
This plugin requires PHP 5!

= How do I integrate events in my blog =
The easiest way is to use the widgets. If you want to use a graphical calendar you have to use the tag `{event_calendar}`. There are many
other Tags and PHP functions (theme development) you can use for display information about events in your blog. Please refer to the plug-ins
options page, which discribes all of it.

= I want to customize the style of the calendar =
You can find the Stylesheet fullcalendar.css in the plugin directory, which is loaded by default. Just copy this file in your 
theme's directory (not in a subdirectory of it). It will be loaded instead of the plugin's stylesheet.

= How can i use custom colors in my graphical calendar =
All calendar entries are created using a class foreach assigned category (e.g. `category-3` for category 3). Override the the default colors using:
`.fc-event.category-3,
.fc-event.category-3 div {
	background-color: #c0c0c0 !important;
	border-color: red !important;
}`

= When i click on an event in the graphical calendar nothing happens =
Make sure you set up the page for the single event view. If this is not set up, nothing will happen when clicking on an event.

= How do I display a single event =
You can use any event details of one (or more) events in any of your posts and/or pages. All you have to do is to put the designated tags (e.g. {event_subject}) in your
post's or page's content. To determine the event you can eighter pass the ID by URL using the parameter `event` (e.g. www.yourdomain.com/mypage/?event=238) or you
define the ID(s) static in your content by using the tag `{event_id; id=x}`. Using the second method let you display more than one event, since you can use
the tag `{event_id}` every time you wish to load another event.

= How do I display a list of events =
Normally you show a list of events by including the function `fse_print_events` or `fse_print_events_list` in your theme. Please refer to the usage documentation
in the calendar options for all the possible parameters, which can be used to control the output.

= I don't want an event to be printed out, but i need its data for further use =
You should not read directly from the database. Instead use the function `fse_get_event` and pass an integer event id. If the event is not found, the function
returns false. Otherwise it returns an event object. Use the function `print_r` to get an overview of all the attributes. 

= I use the function `fse_get_event` but the content isn't filtered and has no line breaks =
When you access the attribute `description` all you get is the raw content. Use the method `getDescription` of your event object to get a filtered content.

= How do I get formatted dates when using the function `fse_get_event` =
You can eighter use the methods `getStart` and `getEnd` or you can use the php's `date` function passing the attributes `tsfrom` and `tsto`. The first method uses the format defined 
in the calendar object, but you can also pass your own date format as an optional parameter.

= The methods `getStart` any `getEnd` always return a date AND time =
The methods `getStart` and `getEnd` accept two parameters. With the first one you can pass a date format. If it is not supplied, the standard format from the options 
will be used. But there is also a second parameter, which accept one of the following integer values: 1=date+time, 2=date only, 3=time only. If you just want to 
have the time returned, but using the standard output format, call the function as follows: `echo $evt->getStart('', 3);`

= Can I refer to other events in an event's description =
Yes you can. The description of the content is filtered by the content filter `the_content`. You can use the same tags as for posts and pages (e.g. {event_subject}). 
You must pass the ID of this refered event by the tag `{event_id; id=x}` before using any other tags.

= No end date is printed out =
Check your setting. You can predefine, if you want an end date always to be displayed, or only if it differs from the start date. You can also pass the parameter `alwaysshowenddate` when 
using tags or functions. Please refer to the usage documentation in the calendar options.

= Hide Widget when there are no events to display =
Please check [this](http://wordpress.org/support/topic/plugin-wp-calendar-maybe-you-can-add-this-tip-in-faq-how-to-hide-event-widget?replies=2) forum post for a nice solution.

== Screenshots ==

1. Blog integration
2. Events overview
3. Single Event 

== Usage ==

Please refer to the usage documentation in the calendar's options page. Since version this plug-in has built-in widgets
for easy usage. If these widgets don't fit your needs, you may integration WP Calendar manually by using tags or php functions.

For all german speaker plese see also the post [Integration von WP Calendar](http://www.faebusoft.ch/webentwicklung/wp-calendar-integration/).

== Changelog ==

= 1.5.3 = 
* FEATURE: Set default start/end time in calendar settings

= 1.5.2 =
* FIXED: Database upgrade problem

= 1.5.1 = 
* FIXED: Full Calendar not working with WP 3.5 (Upgraded to newest FullCalendar Version)
* FIXED: Fixed various database upgrade problems including the message "Unexpected output" when activating
* FIXED: Sorting not working in events overview
* FIXED: Filter not working correctly in events overview
* FIXED: Default grouping to 'none' is missing
* FIXED: Filter by datefrom and dateto not working correctly
* FIXED: Pagination cannot be disabled in widget configuration
* FIXED: Keyword 'today' and 'now' not supported by dateto filter

= 1.5.0 =
* FEATURE: Completely changed from unix timestamps to MySQL Date/Time because of problems with time zones
* FEATURE: Filteroptions datefrom/dateto now supporting MySQL Datetime (YYYY-MM-DD HH:MM:SS). Timestamps are still supported.
* FEATURE: Filteroptions datefrom/dateto supporting two keywords 'now' and 'today' and very basic arithmetic caluculations
* FEATURE: iCalendar feed support
* FEATURE: New option `truncate_more` for tag `event_description` the truncate the description part after the more-tag
* FIXED: Settings link in plugin overview not working
* FIXED: Some people getting DB errors when saving a new event
* FIXED: Problem with timezones which have daylight saving 
* FIXED: Wrong unicode character for back navigation in graphical calendar
* FIXED: Editor not working anymore
* FIXED: Events could not be deleted
* FIXED: Filters not applied in admin interface when paginating
* FIXED: Single event view page not highlighted correctly in page overview
* FIXED: Filter in Admin Interface not working
* FIXED: Content lost, after chanigng synchornized post to draft
* FIXED: Create tables in same collation as WP

= 1.4.3 =
* FIXED: Uninstall gives an error
* FIXED: When adding a new post several functions are not working (tags, media, menu)
* FIXED: Wrong uninstall hook

= 1.4.2 =
* FIXED: HTML Editor not working correctly
* FIXED: Menu is broken when calendar settings are displayed
* FIXED: Tabs broken in settings screen

= 1.4.1 =
* FIXED: Optimized db upgrade process with notification about manual steps

= 1.4.0 =
* FEATURE: Comments for WP Calendar
* FEATURE: New parameter `linktopost` for tag `event_url` and `events_calendar` to create an url pointing to the synchronized post instead of the single view page
* FIXED: Dialogs of TinyMCE Editor not working with WP 3.1
* FIXED: Layout of WP dialogs scrambled
* FIXED: JavaScript/Css loading optimized in admin interface 
* Removed Icon in WP settings menu
* Updated the fullcalendar version 1.4.11 

= 1.3.1 =
* FIXED: Event cannot published anymore

= 1.3.0 =
* FEATURE: Create events from post and keep them synchronized
* FEATURE: New attribute `before` for the `endtime` and `enddate` paramters to display an additional text (e.g. a hyphen), when the end date/time is shown
* FIXED: Admin interface tries to load date.js, which does not exist
* FIXED: Added some missing authority checks
* FIXED: Added missing translation on date and time validation
* FIXED: Categories box not working correctly
* FIXED: Option to disable fullcalendar libraries
* FIXED: Datepicker not hiding on blur (because of animation)
* Updated Datepicker Library

= 1.2.4 =
* FIXED: Error parsing tag, when using a = in a tag value (e.g. in the template tag)

= 1.2.3 =
* FIXED: HTML error in default list template
* FIXED: HTML errors in setting page
* FIXED: Donation link not working

= 1.2.2 = 
* FEATURE: New Action `Duplicate` to copy an event
* FEATURE: New Action `View` for displaying an event in the admin interface or if a user hasn't the right to edit
* FIXED: Added a missing permission check
* FIXED: Date filter in admin overview not working properly
* CHANGE: Event overview default sort is by date but descending

= 1.2.1 = 
* FIXED: Graphical calendar show only a limited number of events
* FIXED: GMT Offset Hack, if problems with time zone
* FIXED: Load fullcalendar.min.js instead of fullcalendar.js
* FIXED: Removed `View` link in events overview 'cause it has no function (yet)

= 1.2.0 =
* FEATURE: List of events (flat and grouped) now support pagination
* FEATURE: New options to disable the jquery and jquery-ui library, if there are conflicts with other plugins (which do not use the WP built-in functions for library loading!)
* FIXED: Widget "Simple" has now the same filter options as the "Grouped" widget 
* FIXED: The parameter Include in the WP Calendar (Grouped) widget destroys the widget. It seems that i have used a reserved name for this parameter.
* FIXED: If the number of events are limited, the events with the id of this value is excluded (thx to Brecsi)
* FIXED: Boolean option handling optimized (See usage documentation)
* FIXED: Show/Hide link in Widget not working
* FIXED: Some errors in the documentation

= 1.1.5 =
* FIXED: SQL Statements are printed out in widgets

= 1.1.4 = 
* FIXED: Allday events are not displayed on the date they end
* FIXED: Removed unnecessary quotes in a href title of the events everview page

= 1.1.3 =
* FEATURE: Calendar entries are now created using category classes for custom styles (for more information see the FAQ page)
* FIXED: Not possible to create events in january
* FIXED: Graphical date choose breaks manual date input

= 1.1.2 =
* FIXED: Graphical calendar using wrong timezone

= 1.1.1 =
* FIXED: New events cannot be saved
* FIXED: Possible division by zero error in event overview 
* FEATURE: New option to hide time, if allday event. Also available as Parameter `hideifallday` for the tags `{event_starttime}` and `{event_endtime}`.
* FEATURE: New shorttag to print a text if it is a all-day event (Usage: `{event_allday; text="(All-day Event)"}`)

= 1.1.0 =
* FIXED: Removed duplicate option `Events per Page`
* FIXED: Settings page now using WP Settings API
* FEATURE: Settings page completely reworked

= 1.0.7 =
* FIXED: Page is not highlighted in admin page overview when using WP 3.x
* FEATURE: Use of own calendar css, when placing it in your current theme directory using the filename fullcalendar.css

= 1.0.6 =
* FIXED: Parsing Error, when using closing HTML-Tags in Inline-Templates
* FIXED: Quotes not removed from Shorttag Parameters
* FIXED: Error in Shorttag parameter parsing when using surrounding quotes
* FIXED: $ Sign in Content gets lost

= 1.0.5 =
* FIXED: Wrong date calculation when picking dates
* FIXED: URL not working in FullCalendar

= 1.0.4 =
* FIXED: Single wrong event url, when allready a question mark (?) in the url 

= 1.0.3 = 
* FIXED: Options page not working
* FIXED: TinyMCE not working when creating a new event

= 1.0.2 =
* FIXED: Error in user capabilities check
* FIXED: Month names not translated in event's overview filter options
* FIXED: Missing translations
* FIXED: Error in Template Parsing
* FEATURE: Filter functions in admin interface improved (selections are stored)
* FEATURE: Sorting functions in admin interface
* FEATURE: Option to show future post only in admin interface

= 1.0.1 =
* FIXED: Warning Messages when using Widgets 

= 1.0.0 =
* FIXED: Javascript date and time validation
* FIXED: Dates and Times now formatting according your settings
* FIXED: Dates and Times corrected if the finish date/time is before the start date/time
* FEATURE: More options for the graphical date chooser
* FEATURE: Integration of FullCalender, a nice graphical, ajax-base calender
* FEATURE: Widget support
* FIXED: Several Spelling errors 

= 1.0.0 RC 4 =
* FIXED: Parameter `alwaysshowenddate` not working
* FIXED: Error, when using a tag which uses a template as parameter, which uses tags aswell
* FIXED: Parameter are not parsed correctly, when using (escaped) quotes
* FIXED: Could not delete events
* FIXED: Table `fsevents_cats` is missing
* FIXED: Bulk Operation `Publish` is not working
* FIXED: Events are displayed event if they are in draft state
* FIXED: Enddate not showing even if different to the start date
* FIXED: Date selection concepts reworked, by default events are selected if they allready started, but not yet finished

= 1.0.0 RC 3 =
* FIXED: Date format in event's edit page
* FIXED: The description of the content is now filtered by the filter `the_content`
* FIXED: Removed code redundancy when printing start/end date/time
* FIXED: Tag {event_url} printed something, even if no ID was specified
* FIXED: Missing line breaks in content output
* FIXED: Slashes are not removed properly
* FIXED: Error in Code Example (usage documentation)

= 1.0.0 RC 2 =
* FIXED: Database Table has not been created
* FIXED: Events could not be saved
* FIXED: Using date_i18n instead of date function 

= 1.0.0 RC 1 =
* Initial Release Candidate