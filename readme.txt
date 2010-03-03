=== WP Calendar ===
Contributors: faebu
Tags: calendar, events
Requires at least: 2.7
Tested up to: 2.9.2
Stable tag: 1.0.0_RC4
WP Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage. 

== Description ==

WP Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage. The whole
usage is extensive and completely documented. It supports all-day events, categorization and state management (draft, publish). 
To manage the event, the same authority checks as for post are used. The plug-in can be integrated in any theme by using 
different functions and integrated in any post and page by using different tags.

= Important Note =
This plugin is still a release candidate. All the functions have been tested and the translation is done by myself. If
you find any bugs and spelling or grammatical errors, please write a comment on the [plug-in website](http://www.faebusoft.ch/downloads/wp-calendar).

= Features =
* Normal and all-day events
* Event categorization (using WP categories)
* Event states (draft, published)
* Easy to use admin interface (Wordpress style)
* Event overview including filters and pagination
* Mass operations on events (delete, set state)
* Graphical calendar to choose dates
* Same authority checks as for post and pages
* Date format configuration
* Easy and fully documented integration
* Supporting special tags in post/page content and title
* Different functions for integration in your theme
* Single event output
* Multi event output with many options
* Event grouping in output (per day, month or year)
* Multi output allows filters on every field
* Output of all fields (author, publisher, dates, states,…)
* Internationalization including dates

= Planned =
* Repeating events
* Customer fields
* Google Maps integration
* Graphical ajax calendar
* Dashboard integration
* Calendar widgets


== Installation ==

1. Unpack the download package

2. Upload folder include all files to the `/wp-content/plugins/` directory.

3. Activate the plugin through the `Plugins` menu in WordPress

4. Go to `Options` > `Calendar` menu, check your settings and read the usage documentation

5. Go to `Calendar` > `Add new` to add a new event

== Frequently Asked Questions ==

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

= When i use the function `fse_get_event` the ouptut of its attribute has wrong characters =

= Can I refer to other events in an event's description =
Yes you can. The description of the content is filtered by the content filter `the_content`. You can use the same tags as for posts and pages (e.g. {event_subject}). 
You must pass the ID of this refered event by the tag `{event_id; id=x}` before using any other tags.

= No end date is printed out =
Check your setting. You can predefine, if you want an end date always to be displayed, or only if it differs from the start date. You can also pass the parameter `alwaysshowenddate` when 
using tags or functions. Please refer to the usage documentation in the calendar options.

== Screenshots ==

1. The options panel
2. Events overview
3. Single Event 

== Usage ==

Please refer to the usage documentation in the calendar's options page.

For all german speaker plese see also the post [Integration von WP Calendar](http://www.faebu.ch/integration-von-wp-calendar). This post 
will be translated in the next few months to english. 

== Changelog ==
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