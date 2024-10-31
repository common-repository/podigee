# Podigee Wordpress Quick Publish
### Now with Gutenberg support!


Let's you import metadata from your Podigee podcast feed right into the Wordpress post editor. Finally (since 1.0) compatible to Gutenberg.

## Description

This plugin let's you fetch episode information from your Podigee podcast feed and copy it directly into the Wordpress editor. It automatically adds a shortcode for the open-source Podigee Podcast Player as well.

It is for Podigee users with premium plans – you can check [here](https://www.podigee.com/en/plans/) to see if your plan includes the use of this plugin. If you don't need all your podcast's meta information but only the Podigee web audio player, check out our free-for-all "Podigee Player Shortcode" plugin that uses shortcodes for rendering the Podigee Podcast in your Wordpress post.

Note: The plugin now works with the Gutenberg editor! Although the method on how you import your data into the post editor has changed. Please check the FAQs and the manual below.

## Installation

1. Make sure that your Podigee plan includes the use of this plugin – otherwise it won't work.
2. Check the red megaphone icon in your Wordpress backend (the one that says "Podigee" right next to it) and enter your podcast's Podigee subdomain and your auth token – the URL is: HTTP(S)://YOUR_WORDPRESS_URL/wp-admin/admin.php?page=podigee-wpqp-plugin.
3. Enter your subdomain and your auth token (auth token can be found here: https://app.podigee.com/settings#applications).

## Frequently Asked Questions 

### Help! Where is the Podigee box in the editor?

No worries, it's still there – but in future version it might not be – because everything you need can now be found here: HTTP(S)://YOUR_WORDPRESS_URL/wp-admin/admin.php?page=podigee-wpqp-plugin. Here you create new drafts, here you manage your settings.

### Do I need to have a paid Podigee plan to use this plugin? 

We offer this plugin as a feature for the users of our [premium plans](https://www.podigee.com/en/plans/). So please be fair and think about hosting your podcast at one of Germany's leading podcast hosters. Otherwise, this plugin's license allows you to build your own version for which, obviously, we don't give any support.

### Does this plugin add any junk to my Wordpress database? 

No – but it stores your podcast subdomain and your auth token in the Wordpress option table using the key 'pfex_plugin_options'.

### I've installed and activated the plugin but why don't I see it? 

Check HTTP(S)://YOUR_WORDPRESS_URL/wp-admin/admin.php?page=podigee-wpqp-plugin – everything here is in one place. After setup, you'll find here a list of all published episodes in your configured feed(s).

### I see a list of episodes ... but what do I do now? 

When hovering over a episodes title, you'll see ">> turn into post" (or some similar text) below the title. Click it. If everything works correctly, it will automagically save a new blog post (as draft) with all your episode's information in it. On the confirmation page you'll find a direkt link to preview and to edit the new episode – also the episode's title in the list should be linked to the post draft as of now.

### I only need the player shortcode, is this plugin any good for me? 
Yes! Just hover over the episode you need the shortcode for an click the ">> copy" link below the shortcode listed to have it right in your clipboard. You can then paste it in any post or page you want.

### Can I really create multiple posts at once? 
YES! Just select the episodes you need from the list and select "New posts from episodes" from the dropdown. As soo as you hit "apply", the bulk magic begins! If everything works out, you' get a list of newly created blog posts alogn with buttons for previewing and editing them. Tada!

### Why don't you set the episode's date as post date? 

Well, actually we do – but as we save the episode as draft (instead of publishing it right away), Wordpress overwrites the post date with the current date during publishing. You can click on "immediately" in the publish settings box and you will see, that the episode's date is already saved here correctly. All you have to do to have this date also as post date: click on the selected day in the calendar (and then on "publish"). If you change the date after publishing the post, please keep in mind that this could (depending on your permalink structure) also change the URL of your post.
