
# Explaination of the plugin


### The problem and the solution

Wordpress admins wants to see all the internal links of their homepage to improve their SEO. They also want to understand which links are displayed to their users when they visit their website. 

This plugin solve this problem by crawling the homepage for internal links and displaying them. It also generates a sitemap.html which can be used by the admin / user to see all the internal links. It also crawls every hour the homepage to keep the sitemap updated.

### Technical solution

I used a shortcut to get the content of the homepage by using `file_get_contents()`, using curl would have required much more code to do the same thing.

I had to extract all the internal links from the content. I decided to use a regex because they are fast and doesn't require a specific php extension to run.

Once the regex was done, I had to filter the values to get only the interanal links. I decided to keep only values that  :

* starts with a /
* starts with the domain name

Internal links sometimes doesn't start with the whole domain.

If internal links are found, I save the date and the links using the wordpress function `update_option()`. This option is used to display the last result to the user when he clicks in the admin button "Display crawl results".

To save the `homepage.html`, I use the shortcut function `file_put_contents()`, using fopen / fwrite / fclose would have required more coding.

To generate the sitemap and the plugin admin interface, I made 2 files that acts like template. I try to separate views from the code to have an MVC like plugin.

To run the cron, I used the function `wp_schedule_event()` which is a perfect solution to run repetitive actions and needs zero user action to be installed. 

To add the `sitemap.html`to the website, I decided to use the wordpress hook `wp_footer`with a high priority so the link would be added at the bottom of the page.

I added some small securities to the plugin :

* using nonce verification on the admin action : starting a crawl and displaying the crawl results.
* adding `defined( 'ABSPATH' ) || die( 'Blocked' );` at the top of the php scripts to prevent users / bots to access the script in a way that it's not supposed to.

Thanks for your time reviewing my code and application.
