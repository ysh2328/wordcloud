=== Booking Calendar ===
Contributors: wpdevelop
Donate link: http://wpbookingcalendar.com/buy/
Tags:  booking calendar, booking, bookings, to book, calendar, reservation, calendar, hotel, rooms, rent, appointment, scheduling, availability, availability calendar, event, events, event calendar, resource scheduling, rental, meeting scheduling, reservation plugin, accommodations, bookable, bookable events
Requires at least: 2.7
Tested up to: 3.5.2
Stable tag: 4.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Booking Calendar - its plugin for online reservation and availability checking service for your site.
== Description ==

This Wordpress plugin will enable <strong>online booking services</strong> for your site. Visitors to your site will be able to <strong>check availability</strong> of apartments, houses, hotel rooms, or services you offer. They can also <strong>make reservations and appointments</strong> with the ability to choose from multi-day, single day, or by the hour booking. Your clients can even view and register for upcoming events. With integrated payment support of popular payment systems your clients can pay online.

This plugin is extremely easy to use and very flexible, built with full Ajax and jQuery support.

You can view a live demo of Booking Calendar in action <a href="http://wpbookingcalendar.com/demo/" title="Booking Calendar Live Demo">here</a>.

<strong>WP Booking Calendar is great for:</strong>

* Resource scheduling (Rental, Rooms, houses, apartments, Equipment Car pools, Hotel rooms)
* Client scheduling ( Beauty salon, Spa management, Hairdresser, Massage therapist, Acupuncture)
* Meeting scheduling (Coaching, Phone advice)
* Event scheduling (Conference, Course, Fitness center, Yoga class, Gym)
* Patient scheduling (Doctor, Clinic, Medical)
* Or any other service, where can be done reservation for some days or hours.

<strong>Features</strong>:

* Make booking reservations by selecting dates at one or several calendar(s) 			
* Email notifications for administrator and site visitors 			
* Comfortable Admin panel for booking management 			
* Easy integration into posts/pages, using TinyMCE button. 			
* Booking calendar widget 			
* Validations of required form fields and email field 			
* Multi language support ( Check  all available languages at this <a href="http://wordpress.org/extend/plugins/booking/other_notes/">page</a> )
* Much more ... Check all other features <a href="http://wpbookingcalendar.com/features/" title="Booking Calendar Live Demo">here</a>.


== Installation ==
<strong style="color:#f50;">Because of update CSS and JS files, please clear browser cache, after you made this update!!!</strong>

1. Upload entire `booking` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure Settings at the submenu of "Booking" menu
5. Add shortcode to your post/page using new button at toolbar.

== Frequently Asked Questions ==

Please see <a href="http://wpbookingcalendar.com/faq/" title="faq">FAQ</a>.

If you have any further questions, please fill free to <a href="mailto:info@wpbookingcalendar.com" title="faq">contact directly</a>.

== Screenshots ==

1. Booking TimeLine Overview Listing page in admin panel. Showing bookings in nice calendar view mode.
2. Booking Listing (Actions tab) admin panel. Approve, decline, delete and make other actions for selected bookings.
3. Add new booking from admin panel
4. Comfortable inserting booking form into post or page, using Booking button at editor toolbar.
5. Example of booking form integration into the post or page.
6. Skins of availability calendars from booking form.
7. Example of Email, which is sending after specific actions.
8. Booking dashboard info and short links for showing of bookings.
9. General Booking Settings page. 
10. Booking Listing (Filters tab) admin panel. Apply filter, for looking of your required bookings.
== Changelog ==
= 4.1.4 =
* Fix several warning notices in the plugin, if the WP_DEBUG constant is set to  true at  the config.php file.
* Showing one calendar and the warning message if at the same visible page is showing booking forms or calendars of the same booking resource more than one time.
* Block loading of the unused JavaScript at the Booking Listing page.
= 4.1.3 =
* Translation of Booking Calendar Plugin, into Hebrew, by Eli Segev.
* Fix issue of not submiting the booking form, when the blog is installed not to the root of site (when Site URL and WordPress URL at the WordPress Settings are different)
* Define possibility to activate the 'https' mode for the site, by setting true value to the WP_BK_SSL constant at the wpdev-booking.php file
* Load the jquerymigrate and rechecking for the minimum jQuery 1.7.1 in any versions of plugin, untill now its was only  in Business Small or higher versions.
* Fix conflict issue (when mouse over specific buttons in the booking admin panel), which  is generated by the "Comprehensive Google Map Plugin"
= 4.1.2 =
* Fix issue with links in pagination at the Resources page (in paid versions).
* Correct of font size in the links of "Powered by" notice.
= 4.1.1 =
* Fix issue of showing the warning message "mktime() expects parameter 1 to be long, string given in" if the time is not selected.
* Fix issue of showing the "You do not have sufficient permissions to access this page" error.
= 4.1 =
* Features and issue fixings in All versions:
 * New Calendar Overview page (1 month/ 3 month / Year view mode) for the booking listing in the admin panel.
 * Showing the full booking info in the popover tooltip, when mouse over specific booking in the Calendar Overview page in admin panel.
 * Setting the default start page at the General Booking Settings page : Booking Listing or Booking Calendar Overview page.
 * Possibility to Sort the bookings by booking Dates in the Booking Listing page
 * Possibility  to  activate / deactivate and edit titles of specific items in the Legend under the calendar.
 * Showing the current date instead of "#" symbol at the cells of legend.
 * New technical section at the General Booking Settings page (for reindexing (possibility to sort by date field) the exist bookings)
 * Slovak translation by (<a href="http://webhostinggeeks.com/blog/">Branco, Slovak</a>)
 * Add possibility to send the "Approve" emails, if the "Auto-approve" feature is activated at the General Booking Settings page.
 * Use the "visitor email" from the booking form as a default "reply" email in the email template about the new booking, which  is sending to the booking administrator.
 * Set  the date and time of the booking(s) relative to the "Timezone", which  is set at the WordPress General Settings page.
 * Support of migration to the jQuery 1.9 or 2.0 
 * Fix issue of not sending emails at the "Add booking" admin page, if the "Not sending emails" checkbox is checked.
 * Many other small issue fixing and improvements...
* Personal / Business Small / Business Medium / Business Large / MultiUser versions features:
 * Check-in and check-out days visible like in the other booking systems (mark half of day,  instead of the clock icon).  This feature is active if the "Use check in/out time" option is checked at the General Booking Settings page (Business Small/Medium/Large, MultiUser)
 * New type of season filter - just selecting the specific dates during a year(s). /Removing the time filter/ (Business Medium/Large, MultiUser)
 * Emails templates (in subject and content of email templates) support any shortcodes, which you are used at booking form. You can use the shortcodes in the same way as you are used it in the bottom form at Settings Fieds page. (Personal, Business Small/Medium/Large, MultiUser)
 * Possibility to use the "visitor email" shortcode in the email template at the field "From", so if the admin click  on reply button he will send email directly to the visitor  (Personal, Business Small/Medium/Large, MultiUser)
 * New shortcode [bookingselect] for selecting in selectbox the specific booking form, instead of using the customizations of PHP theme files. Example: [bookingselect type='2,3,4' form_type='standard' nummonths=1 label='Please select the resource: '] (Personal, Business Small/Medium/Large, MultiUser)
 * Search. Possibility to use new parameters in the seacrh form: searchresults="URL of search  results", noresultstitle="Title of search results, when  nothing fount", searchresultstitle="Title of search results" Example: [bookingsearch searchresults="http://server.com/custom-search-results/" noresultstitle="Nothing Found" searchresultstitle="Search results"] ( Business Large, MultiUser)
 * Search. Possibility to show the search results in a  new seperate page, diferent from search form page. Use this shortcode: [bookingsearchresults] for showing the search  results inside of this page. Inside of the search form you will need to use this parameter:  bookingsearchresults - for definition of URL of search  results page. ( Business Large, MultiUser)
 * Search. Search  widget (Business Large, MultiUser)
 * Search. Availability Search Form (Booking > Settings > Search page) can have as parameter any "CUSTOM FIELDS" from the posts/pages, where you are inserted the booking form shortcodes. Please use only the custom field(s) with the names, which is starting with the "booking_" term. Exmaple: In the post or page, you can have the custom field like this: booking_width : 50. In the Settings Search form you can use this selectbox: <select name="booking_width" id="booking_width" ><option value="">Any</option><option value="50">50</option><option value="75">75</option></select> ( Business Large, MultiUser)
 * Search. Limit the search results based on the availability per user. Showing availability in search  results only for the specific users. Example of usage search  form shortcode: [bookingsearch users="1,2"]  ; where 1,2 - its a ID list of users.
 * Search. Add the translation possibility  for the "[booking info]" and "[booking_resource_title]" section  at the search  results, using the [lang=LOCALE] shortcode (Business Large, MultiUser)
 * Search. Usage the translation(s) of the Subject and Excerpt for the search  result in a format: <!--[lang=en_US]English translation<!-- --><!--[lang=pl_PL]Polish translation<!-- --><!--[lang=ru_RU]Russian  translation<!-- -->
 * Search. Fix issue of not updating the cost hint in the booking form, when the visitor is redirected from search form (Business Large, MultiUser)
 * Form Fields. Define for the each CUSTOM FORM the CUSTOM CONTENT, which is showing in the email templates (shortcode - [content]), and in the booking listing page. (Business Medium/Large)
 * Form Fields. Define the default CUSTOM FORM for the specific resource at the Booking > Resources page. (Business Medium/Large)
 * Form fields. Several  new default templates for reseting of booking forms.  (Personal, Business Small/Medium/Large, MultiUser)
 * Possibility to use the "url" parameter in the shortcodes: [visitorbookingediturl], [visitorbookingcancelurl], [visitorbookingpayurl] on the Booking > Settings > Emails page, for setting different page URL of the specific action . Example: [visitorbookingpayurl url="http://www.server.com/custom-page/"]  (Personal, Business Small/Medium/Large, MultiUser)
 * Showing the correct  content of the booking form at the Booking Listing page, if for the booking was used the Custom form. (Business Medium/Large)
 * Fix issue of not using the custom form, when editing the booking, which  was done in the custom form. For correct using of this feature, you must  correctly define the default custom  form for the specific resource at the Booking > Resources page. ( Business Medium/Large, MultiUser)
 * Possibility to show booking details in payment form ( Business Small/Medium/Large, MultiUser)
 * Customization of the booking title (ID or Name,... of the booking form, etc) in the Calendar Overview page  (Personal, Business Small/Medium/Large, MultiUser)
 * Configuration of the shortcodes: search form, search  results and the selection of booking forms; in the popup configuration dialog at the edit post page, using the booking calendar button at edit toolbar. (Personal, Business Small/Medium/Large, MultiUser)
 * Show the inactive rates and season filters for the availability  as grayed at the Settings page ( Business Medium/Large, MultiUser)
 * Do not show the Cost in mouseover tooltip, if the cost = 0 ( Business Medium/Large, MultiUser)
 * Possibility to use the additional cost per each night in the same way as with days at the Resources - Advanced cost page ( Business Medium/Large, MultiUser)
 * Possibility  to set the different colors of the for the days with the different rates. Each such date cell (which have the different rate) is have the specific CSS class in format: rate_150 or rate_300, where 150 and 300 its rate (cost) for the specific date. So inside of the some CSS file (for example: ../booking/css/client.css ) you can use construction like this:  .datepick-inline table.datepick td.rate_300 a{ background: #F00; } ( Business Medium/Large, MultiUser)
 * Fix issue of not possibility to select  the booking resources at the Booking Calendar widget, if your booking resources displayed at the several  pages in the Booking - Resources page.  (Personal, Business Small/Medium/Large, MultiUser)
 * Fix of not possibility to use the HTML tags in excerpts for the [booking_info] shortcode in the search results. (Business Large, MultiUser)
 * Fix issue of not possibility to modify/save the "Cancel Return URL from PayPal" at the settings page. (Business Small/Medium/Large, MultiUser)
 * Fix correct link of edit users profile at the Settings Users menu page (MultiUser)
 * Fix issue of auto cancellation pending not successfully payed bookings after specific time  (Business Small/Medium/Large, MultiUser).
 * Fix issue of not showing correct translation of message: 'The booking is cancel successfully', if visitor is cancel the reservation.
 * Fix issue of showing the correct availability in calendar with specific capacity (higher than 1), if the "season  filter(s) availability" are applied to the "child" booking resources (Business Large, MultiUser)
 * Fix issue of generating the JavaScript error at the page, when using the conditional season  filter, where is not selected any week days, even if this filter do not apply to the any of the days.
 * Fix issue of not correct showing values of checkbox fields, if the booking is saved to the "child" resource (Business Large, MultiUser).
 * Fix issue of disappearing the booking resource, if at the Resource menu is assign to the booking resource the parent element as itself (Business Large, MultiUser).
 * Fix issue of selecting the dates in correct  format in calendar after redirection from the search results  (Business Large, MultiUser).
 * Fix issue of not applying to the all "regular users" the "Deposit activation" feature from the General Booking Settings page in MultiUser version. (MultiUser)
 * Fix issue when Regular Users can see the bookings from other user(s) in MultiUser version, after reconfiguration parameters in filter tab.  (MultiUser)
<br /><br />See full change logs at this <a href="http://wpbookingcalendar.com/changelog/" title="changelog">page</a>.

== Upgrade Notice ==
= 4.1 =
New stylish booking timeline admin interface. Check-in/out days, mark half of day in calendar. Booking Sort by dates. Show search results in a new seperate page. Form Fields,  define for the each CUSTOM FORM the CUSTOM CONTENT. Many other new features and fixes...

== Languages ==

Right now plugin is support languages:
<ul>
 <li>English</li>
 <li>Italian</li>
 <li>Spanish</li>
 <li>French</li>
 <li>German</li>
 <li>Danish</li>
 <li>Dutch</li>
 <li>Belarusian</li>
 <li>Russian</li>
 <li>Polish</li>
 <li>Croatian</li>
 <li>Slovak</li>
 <li>Hebrew</li>
</ul>
<strong> Many languages are partially translated, so please recheck your language before use of plugin. We are open for your help in new translations or correcting exist ones.</strong><br /> You can translate to new language or fix exist one, using this <a href="http://wpbookingcalendar.com/faq/make-translation-of-wp-plugin/" title="Tutorial of translation wordpress plugin">instruction</a> 
== Tech support ==

If you have some questions, which you do not found at <a href="http://wpbookingcalendar.com/faq/" title="FAQ">FAQ</a> you can post them at <a href="http://wpbookingcalendar.com/issues/" title="Help board">technical help board</a>

== New ideas ==

Please, fill free to propose new ideas or new features <a href="http://wpbookingcalendar.com/ideas/" title="new feature">here</a>