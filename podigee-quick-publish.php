<?php
/**
 * Plugin Name: Podigee Wordpress Quick Publish – now with Gutenberg support!
 * Plugin URI:  https://podigee.com
 * Description: Let's you import metadata from your Podigee podcast feed right into the Wordpress post editor. Now also compatible to Gutenberg.
 * Text Domain: podigee-quick-publish
 * Version:     1.4.0
 * Author:      Podigee
 * Author URI:  https://podigee.com
 * License:     MIT
 * Copyright (c) 2024 Podigee
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/*
* We use too global variables in this plugin to reduce requests to our authentication service and the Wordpress database. If you have an idea that needs even less requests, let me know! ;-)
*/

$_PFEX_LOGIN_OKAY;
$_PFEX_POST_INSERTED;
$_PFEX_DEBUG = (isset($_GET['pfex-debug']) && $_GET['pfex-debug'] == "1" ? true : false);

// If this file is called directly, abort.
defined('ABSPATH') or die('No script kiddies please!');

function _isCurl()
{
    return function_exists('curl_version');
}

/**
 * The (admin-only) core plugin class.
 */
require plugin_dir_path(__FILE__).'admin/class-podigee-qp.php';

/**
 * Initializing and startin the plugin.
 */
function run_podigee_feedex()
{
    $plugin_admin = new Podigee_feedex_Admin('PODIGEE_WORDPRESS_QUICK_PUBLISH', '1.0.0');
}

run_podigee_feedex();

/**
 * Registering the shortcode for the Podigee audio player.
 */
if (!(function_exists('podigee_player'))) {
    function podigee_player($atts)
    {
        $atts = shortcode_atts(
            [
                'url' => '',
            ],
            $atts
        );

        /**
         * From the documentation (see: https://github.com/podigee/podigee-podcast-player#usage):
         * "By default the player is integrated into the page using a <script> HTML tag. This is necessary to render the player in an iframe to ensure it
         * does not interfere with the enclosing page's CSS and JS while still being able to resize the player interface dynamically."
         */
        return '<script class="podigee-podcast-player" src="https://player.podigee-cdn.net/podcast-player/javascripts/podigee-podcast-player.js" data-configuration="'
            .esc_url($atts['url']).'/embed?context=external"></script>';
    }

    if (!(shortcode_exists("podigee-player"))) {
        add_shortcode('podigee-player', 'podigee_player');
    }
}
/*
* Preparing translation
*/
function pfex_load_plugin_textdomain()
{
    load_plugin_textdomain('podigee-quick-publish', false, basename(dirname(__FILE__)).'/languages/');
}

add_action('plugins_loaded', 'pfex_load_plugin_textdomain');

/**
 * Registering an options page in the admin menu.
 */
function pfex_plugin_admin_add_page()
{
    add_menu_page(
        'Podigee Wordpress Quick Publish',
        'Podigee',
        'manage_options',
        'podigee-wpqp-plugin',
        'pfex_plugin_options_page',
        'dashicons-megaphone'
    );
}

add_action('admin_menu', 'pfex_plugin_admin_add_page');

/**
 * This is the main funtion that draws the Podigee options page in the Wordpress admin backend.
 */
function pfex_plugin_options_page()
{
    /**
     * Always display headline and top logo
     */
    _e(
        '<h1 class="pfex-site-title">Podigee Wordpress Quick Publish</h1> <span class="pfex-on-an-additional-note">(now Gutenberg-compatible!)</span>',
        'podigee-quick-publish'
    );
    pfex_plugin_section_head();

    /**
     * If one or more new posts have been saved, povide a message and links to them right on top of the page.
     * New post ids are stored in an array in $_PFEX_POST_INSERTED
     */
    global $_PFEX_POST_INSERTED;
    $pfex_backbtn = '<a class="button button-secondary" href="edit.php">';
    $pfex_backbtn .= __('&lt;- back to post overview', 'podigee-quick-publish');
    $pfex_backbtn .= '</a>';
    if (is_array($_PFEX_POST_INSERTED) && count($_PFEX_POST_INSERTED) > 0):
        echo '<div class="card div-pfex-success"><h2 class="title">';
        _e('Congratulations!', 'podigee-quick-publish');
        echo "</h2><p>";

        echo _n(
            'Your post has been saved as draft:',
            'Your posts have been saved as drafts:',
            count($_PFEX_POST_INSERTED),
            'podigee-quick-publish'
        );

        echo "</p><p><ul>";
        foreach ($_PFEX_POST_INSERTED as $newpost):
            echo "<li><strong>";
            $queried_post = get_post($newpost);
            echo $queried_post->post_title;
            echo '</strong>:<br /><br /><a class="button button-primary" href="'.get_site_url().'?p='.$newpost.'&preview=true">';
            _e('View it here -&gt;', 'podigee-quick-publish');
            echo '</a> <a class="button button-secondary" href="post.php?post='.$newpost.'&action=edit">';
            _e('Or edit it here -&gt', 'podigee-quick-publish');
            echo "</a><br />&nbsp;</li>";
        endforeach;
        echo "</ul></p>".$pfex_backbtn."</div>";
    elseif (is_string($_PFEX_POST_INSERTED) && substr_count(strtolower($_PFEX_POST_INSERTED), 'error')):
        echo '<div class="card div-pfex-error"><h2 class="title">';
        _e('Whoopsie.', 'podigee-quick-publish');
        echo "</h2><p>";
        _e('While saving your post(s), an error has occured: <br />', 'podigee-quick-publish');
        echo $_PFEX_POST_INSERTED;
        echo "</p>".$pfex_backbtn."</div>";
    endif;

    /**
     * Info section – maybe this can be removed in a future version.
     */
    pfex_plugin_section_text();
    /**
     * Feed item list.
     */
    pfex_plugin_section_feeditems();


    /**
     * And, finally, the option section:
     *    - Visible when options are not set yet or authentication failed.
     *    - Hidden when authentication was okay.
     *    – Comes with a jQuery-operated toggle-visibility button.
     */


    ?>
    <h2 class="pfex-subhead"><?php _e('Plugin settings', 'podigee-quick-publish'); ?></h2>
    <?php settings_errors(); ?>
    <button class="button button-secondary pfex-toggle-hidden" data-toggle="   <?php
    _e('Show options', 'podigee-quick-publish');
    ?>">
        <?php
        _e('Hide options', 'podigee-quick-publish');
        ?></button>
    <div class="pfex-option-section">
        <form action="options.php" method="post">
            <p><?php settings_fields('pfex_plugin_options'); ?>
                <?php do_settings_sections('podigee-wpqp-plugin'); ?>
            </p>
            <p>
                <input name="Submit" type="submit" class="button button-primary"
                       value="<?php esc_attr_e('Save Changes'); ?>"/>
            </p></form>
    </div>

    <?php
}

/*
* Yeah we know: it's called "post" but actually it is a GET operation (initially it used to be "post").
*/
function pfex_handle_post_new($subdomain, $episodeGuid, $external = false)
{ //$post) {
    $feed = 'https://' . $subdomain . '.podigee.io/feed/mp3';
    if ($external) {
        $feed = $subdomain;
    }


    $feedcontent = feed2array($feed);
    $episode_to_post = false;
    if ($feedcontent != false && count($feedcontent) > 0) {
        foreach ($feedcontent as $episode):
            if ($episode['guid'] == $episodeGuid):
                $episode_to_post = $episode;
                break;
            endif;
            if (substr($episodeGuid, 0, 1) == 'b' && $episode['episodetype'] == 'bonus' && 'b' . $episode['guid'] == $episodeGuid):
                $episode_to_post = $episode;
                break;
            endif;
            if (substr($episodeGuid, 0, 1) == 't' && $episode['episodetype'] == 'teaser' && 't' . $episode['guid'] == $episodeGuid):
                $episode_to_post = $episode;
                break;
            endif;
        endforeach;
    }
    if ($episode_to_post == false) {
        return false;
    }
    $podcast = $subdomain;
    $content = isset($episode_to_post['content']) ? $episode_to_post['content'] : "";
    $subtitle = isset($episode_to_post['subtitle']) ? $episode_to_post['subtitle'] : "";
    $episodetype = $episode_to_post['episodetype'];
    $episodetpnumber = isset($episode_to_post['number']) ? $episode_to_post['number'] : false;
    $seasonnumber = isset($episode_to_post['season']) ? $episode_to_post['season'] : false;
    $pos = strpos($episode_to_post['link'], '.podigee.io/');

    $link = '';

    if (!$pos) {
        if ($episodetpnumber) {
            $domain_preffix = 'https://' . $podcast . '.podigee.io/';
            $episode_number_str = ($episodetype != "full" ? substr($episodetype, 0, 1) : '') . $episodetpnumber;

            if ($seasonnumber) {
                # format: s1(e|t|b)1
                $episode_number_str = ($episodetype != "full" ? substr($episodetype, 0, 1) : 'e') . $episodetpnumber;
                $link = $domain_preffix . 's' . $seasonnumber . $episode_number_str . '-wordpress';
            } else {
                $link = $domain_preffix . $episode_number_str . '-wordpress';
            }
        }
    } else {
        $link = $episode_to_post['link']; # Default is episode link
    }

    if ($external) {
        $link = $episode_to_post['link'];
    }

    $playershortcode = '[podigee-player url="' . $link . '"]';
    $me = wp_get_current_user();


    $episode_to_post['pubDate'] = strtotime($episode_to_post['pubDate']);

    $post = [
        'post_title' => ($episode_to_post['title']),
        'post_status' => 'draft',
        'post_author' => $me->ID,
        'post_date' => $episode_to_post['pubDate'],
        'post_content' => '<p><strong>' . $subtitle . '</strong></p><p>' . $playershortcode . '</p><p>' . ($content) . "</p>",
        //'edit_date'		=> true
    ];
    if ($episode_to_post['pubDate']) {
        $post['post_date'] = date("Y-m-d H:i:s", $episode_to_post['pubDate']);
    }

    $post_id = wp_insert_post($post, false);
    if (!is_wp_error($post_id)) {
        return $post_id;
    } else {
        return false;
    }
}

/*
* Actually, this one really is a POST operation, that calls the respective GET function above multiple times.
*/
function pfex_handle_post_new_bulk($post)
{
    if (!isset($post['cbepisode'])) {
        return false;
    }
    if (!is_array($post['cbepisode'])) {
        return false;
    }
    if (count($post['cbepisode']) == 0) {
        return false;
    }
    $return = [];
    foreach ($post['cbepisode'] as $episode) {

        $episodeData = explode('#', $episode);
        $subdomain = $episodeData[0];
        $episodeguid = $episodeData[1];
        $external = $episodeData[2];

        $postresult = pfex_handle_post_new($subdomain, $episodeguid, $external);
        if ($postresult != false) {
            $return[] = $postresult;
        }
    }

    return $return;
}

function register_session()
{
    global $_PFEX_DEBUG;
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_GET['pfex-debug'])) {
        $_SESSION = $_GET['pfex-debug'];
    }
    if (isset($_SESSION['pfex-debug']) && $_SESSION['pfex-debug'] == "1") {
        $_PFEX_DEBUG = $_SESSION['pfex-debug'];
    }
}

add_action('init', 'register_session');


/**
 * Registering the plugin options, validation function, etc.
 */
function pfex_plugin_admin_init()
{
    global $_PFEX_POST_INSERTED;
    global $_PFEX_DEBUG;
    if ($_SERVER['REQUEST_METHOD'] == "GET" && !empty($_GET['action']) && $_GET['action'] == "new" && !empty($_GET['subdomain'])
        && !empty($_GET['guid'])) {
        // The GET request for creating a single new post.
        $postreturn = pfex_handle_post_new($_GET['subdomain'], $_GET['guid'], $_GET['external']);

        if ($postreturn) {
            $_PFEX_POST_INSERTED = [$postreturn];
        } else {
            $_PFEX_POST_INSERTED = __('Error while saving new post.', 'podigee-quick-publish');
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == "POST"
        && ((!empty($_POST['action']) && $_POST['action'] == "new post")
            || (!empty($_POST['action2'])
                && $_POST['action2'] == "new post"))
        && !empty($_POST['cbepisode'])) {
        // The POST request for creating several new posts.
        $postreturn = pfex_handle_post_new_bulk($_POST);
        if ($postreturn != false && is_array($postreturn)) {
            $_PFEX_POST_INSERTED = $postreturn;
        } else {
            $_PFEX_POST_INSERTED = __('Error while saving new posts.', 'podigee-quick-publish');
        }
    }

    $_SESSION['pfex-debug'] = $_PFEX_DEBUG;

    if (!empty($_PFEX_POST_INSERTED) && count($_PFEX_POST_INSERTED) > 0) {
        $redirectUrl = $_SERVER['PHP_SELF'] . '?page=' . $_REQUEST['page'] . (!empty($_GET['paged']) && is_numeric($_GET['paged']) ? "&paged="
                . $_GET['paged'] : "");
        $_SESSION['pfex-new-posts-added'] = $_PFEX_POST_INSERTED;
        if (wp_redirect($redirectUrl)) {
            exit;
        }
    }

    if (!empty($_SESSION['pfex-new-posts-added']) && count($_SESSION['pfex-new-posts-added']) > 0):
        $_PFEX_POST_INSERTED = $_SESSION['pfex-new-posts-added'];
        unset($_SESSION['pfex-new-posts-added']);
    endif;

    // Drawing the setup section
    register_setting('pfex_plugin_options', 'pfex_plugin_options', 'pfex_options_validate');
    add_settings_section('pfex_plugin_main', '', 'pfex_plugin_section_setting_fields', 'podigee-wpqp-plugin');
    add_settings_field(
        'pfex_slug',
        __('Your podcast&apos;s subdomain:', 'podigee-quick-publish'),
        'pfex_plugin_setting_slug',
        'podigee-wpqp-plugin',
        'pfex_plugin_main'
    );
    add_settings_field(
        'pfex_custom_domain',
        __('Custom feed url:', 'podigee-quick-publish'),
        'pfex_plugin_setting_custom_domain',
        'podigee-wpqp-plugin',
        'pfex_plugin_main'
    );
    add_settings_field(
        'pfex_api',
        __('Your Podigee auth token:', 'podigee-quick-publish'),
        'pfex_plugin_setting_token',
        'podigee-wpqp-plugin',
        'pfex_plugin_main'
    );
    add_settings_field(
        'pfex_welcome',
        __('Show welcome info screen:', 'podigee-quick-publish'),
        'pfex_plugin_setting_welcome',
        'podigee-wpqp-plugin',
        'pfex_plugin_main'
    );

    session_write_close();
}

add_action('admin_init', 'pfex_plugin_admin_init');

/**
 * The section to which the options field are attached to. Can obviously be empty though.
 */
function pfex_plugin_section_setting_fields()
{

}

/*
* This just draws the Podigee logo in the upper right corner.
*/
function pfex_plugin_section_head()
{
    echo '<a href="https://www.podigee.com/de" target="_blank"><img src="https://main.podigee-cdn.net/website-assets/footer-podigee-logo-text-horizontal.svg" class="pfex-podigee-img-right" /></a>';
}

/*
* This draws the info card.
*/
function pfex_plugin_section_text()
{
    echo '<div class="card" id="pfex-welcome-card"><h2 class="title" style="inline">';
    _e('Woohaaa?! What is happening here?', 'podigee-quick-publish');
    echo "</h2><p>";
    _e(
        'Hey there! We\'ve just upgraded your Podigee plugin to make Gutenberg-compatible blog posts based on your podcast content. ',
        'podigee-quick-publish'
    );
    _e(
        'We\'ve also changed the way you import podcast data. So don\'t worry if the plugin next to the post editor looks a bit different. ',
        'podigee-quick-publish'
    );
    _e(
        'We\'ve also moved your plugin options out of the settings menu here to make this page your one-stop Wordpress podcast shop. ',
        'podigee-quick-publish'
    );
    echo "</p><p>";
    _e(
        'So why don\'t you just click on the link below your newest episode to instantly copy your content over to the post editor. ',
        'podigee-quick-publish'
    );
    echo "</p>";

    echo '</p>';
    echo '</div>';
}

/*
* Draws a WP_List_Table and fills it with the feed items.
*/
function pfex_plugin_section_feeditems()
{
    $options = get_option('pfex_plugin_options');

    echo '<form action="?page=' . $_REQUEST['page'] . (!empty($_GET['paged']) && is_numeric($_GET['paged']) ? "&paged" . $_GET['paged'] : "")
        . '" method="POST" id="pfex-bulk-form">';
    $podigeeTable = new My_List_Table();

    if (isset($options['pfex_slug']) && trim($options['pfex_slug']) != ""):
        $subdomains = explode(",", $options['pfex_slug']);
        if (count($subdomains) > 0):
            foreach ($subdomains as $subdomain):
                $auth = check_authorization($subdomain, $options['pfex_token'], true);
                if (!$auth) {
                    continue;
                }
                $feed = "https://" . trim($subdomain) . ".podigee.io/feed/mp3/";
                $items = feed2array($feed);
                if (count($items) > 0) {
                    foreach ($items as $episode):
                        $row = [];
                        $playershortcode = 'https://' . trim($subdomain) . '.podigee.io/' . ($episode['episodetype'] != "full" ? substr(
                                $episode['episodetype'],
                                0,
                                1
                            ) : '') . $episode['number'] . '-wordpress'; // $_POST['link'];

                        $episodetpnumber = isset($episode['number']) ? $episode['number'] : false;
                        $seasonnumber = isset($episode['season']) ? $episode['season'] : false;
                        $episodetype = $episode['episodetype'];
                        $pos = strpos($episode['link'], '.podigee.io/');

                        if (!$pos) {
                            # external episode link
                            if ($episodetpnumber) {
                                $domain_preffix = 'https://' . $subdomain . '.podigee.io/';
                                $episode_number_str = ($episodetype != "full" ? substr($episodetype, 0, 1) : '') . $episodetpnumber;

                                if ($seasonnumber) {
                                    # format: s1(e|t|b)1
                                    $episode_number_str = ($episodetype != "full" ? substr($episodetype, 0, 1) : 'e') . $episodetpnumber;
                                    $link = $domain_preffix . 's' . $seasonnumber . $episode_number_str . '-wordpress';
                                } else {
                                    $link = $domain_preffix . $episode_number_str . '-wordpress';
                                }

                                $playershortcode = '[podigee-player url="' . $link . '"]';
                            } else {
                                $playershortcode = '<strong>Warning:</strong> Unfortunately using an external episode URL and leaving episode numbers in the feed deactivated are not supported at the moment. Please go to the Podigee interface and copy the correct episode url from the embed code (inside data-configuration; leaving out "/embed?...." and everything that follows) for this episode. The link should then look like: https://mypodcast.podigee.io/10-my-great-episode.';
                            }
                        } else {
                            # Default is episode link
                            $playershortcode = '[podigee-player url="' . $episode['link'] . '"]';
                        }

                        $row['podcast'] = $subdomain;
                        $row['pubdate'] = date("Y-m-d", strtotime($episode['pubDate']));
                        $row['episodetype'] = $episode['episodetype'];

                        if ($seasonnumber) {
                            $row['episodenumber'] = 's' . $seasonnumber . ($episode['episodetype'] != "full" ? substr($episode['episodetype'], 0, 1)
                                    : 'e') . $episode['number'];
                        } else {
                            $row['episodenumber'] = ($episode['episodetype'] != "full" ? substr($episode['episodetype'], 0, 1) : '')
                                . $episode['number'];
                        }

                        $row['shortcode'] = $playershortcode;
                        $row['title'] = $episode['title'];
                        $row['link'] = $episode['link'];
                        $row['guid'] = $episode['guid'];

                        $foundposts = (query_posts(
                            [
                                'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'],
                                's' => $row['title'],
                                'orderby' => 'date',
                                'order' => 'DESC',
                                'posts_per_page' => 1,
                            ]
                        ));

                        if ($foundposts && count($foundposts) > 0) {
                            $foundid = ($foundposts[0]->ID);
                            $row['editlink'] = 'post.php?post=' . $foundid . '&action=edit';
                            $row['previewlink'] = '?p=' . $foundid . '&preview=true';
                            $row['title'] = '<a href="' . $row['editlink'] . '">' . $row['title'] . '</a>';
                        }
                        $row['external'] = false;
                        $row['externalFeed'] = null;
                        $podigeeTable->addData($row);
                    endforeach;
                }
            endforeach;
        endif;
    endif;

    feed_external_items($podigeeTable);
    echo '<div class="wrap"><h3>';
    _e('These are the episodes in your connected feeds:', 'podigee-quick-publish');
    echo '</h3>';
    $podigeeTable->prepare_items();
    $podigeeTable->display();
    echo '</div></form>';

}

function feed_external_items(My_List_Table $podigeeTable)
{
    $options = get_option('pfex_plugin_options');
    $customDomainsSettings = $options['pfex_custom_domain'] ?? null;
    if (!$customDomainsSettings) {
        return;
    }

    $customDomains = explode(',', $customDomainsSettings);

    foreach ($customDomains as $feed) {
        $auth = checkCustomDomain($feed, $options['pfex_token']);
        if (!$auth) {
            continue;
        }

        $items = feed2array($feed);

        if ($items < 1) {
            continue;
        }
        foreach ($items as $episode) {
            $row = [];
            $seasonnumber = isset($episode['season']) ? $episode['season'] : false;
            $playershortcode = '[podigee-player url="' . $episode['link'] . '"]';

            $row['pubdate'] = date("Y-m-d", strtotime($episode['pubDate']));
            $row['episodetype'] = $episode['episodetype'];

            if ($seasonnumber) {
                $row['episodenumber'] = 's' . $seasonnumber . ($episode['episodetype'] != "full" ? substr($episode['episodetype'], 0, 1)
                        : 'e') . $episode['number'];
            } else {
                $row['episodenumber'] = ($episode['episodetype'] != "full" ? substr($episode['episodetype'], 0, 1) : '')
                    . $episode['number'];
            }

            $row['podcast'] = $episode['podcastTitle'];
            $row['shortcode'] = $playershortcode;
            $row['title'] = $episode['title'];
            $row['link'] = $episode['link'];
            $row['guid'] = $episode['guid'];
            $row['external'] = true;
            $row['externalFeed'] = $feed;

            $foundposts = (query_posts(
                [
                    'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'],
                    's' => $row['title'],
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'posts_per_page' => 1,
                ]
            ));

            if ($foundposts && count($foundposts) > 0) {
                $foundid = ($foundposts[0]->ID);
                $row['editlink'] = 'post.php?post=' . $foundid . '&action=edit';
                $row['previewlink'] = '?p=' . $foundid . '&preview=true';
                $row['title'] = '<a href="' . $row['editlink'] . '">' . $row['title'] . '</a>';
            }

            $podigeeTable->addData($row);
        }

    }
}

/**
 * Options and explanation for the settings page
 */
function pfex_plugin_setting_slug()
{
    $options = get_option('pfex_plugin_options');
    $subdomainValue = ($options) && isset($options['pfex_slug']) ? $options['pfex_slug'] : "";

    echo "<input id='pfex_slug' name='pfex_plugin_options[pfex_slug]' size='40' type='text' value='{$subdomainValue}' />";
    _e(
        "<p>Please do not enter the full podcast URL here – only the subdomain as configured in the <i>General</i> section of your podcast&apos;s settings.<br /><i>Example</i>: If your Podcast is located at <strong>https://mypreciouspodcast.podigee.io</strong> – you would only need to enter <strong>mypreciouspodcast</strong>.</p>",
        'podigee-quick-publish'
    );
    if (isset($options['pfex_slug']) && trim($options['pfex_slug']) != "") {
        $subdomains = explode(",", $options['pfex_slug']);
        $auth = check_authorization($options['pfex_slug'], $options['pfex_token'], true);
        if (count($subdomains) > 0 && $auth) {
            echo "<br /><p>";
            _e('If configured correctly, you should be able to reach your feed(s) at:', 'podigee-quick-publish');
            echo "<br /><ul>";
            foreach ($subdomains as $subdomain) {
                $mp3feed = "https://" . $subdomain . ".podigee.io/feed/mp3/";
                echo "<li><a href=\"$mp3feed\" target=\"_blank\">$mp3feed</a>" .
                    (pfex_check_url($mp3feed) ? " <div style=\"display: inline\" class=\"pfex-auth-success\"><span>[OK]</span></div>"
                        : " <div style=\"display: inline\" class=\"pfex-auth-failed\"><span>[X]</span></div>") .
                    "</li>";
            }
            echo "</ul></p>";
        }
    }
    _e("<p>Did you know? You can add multiple subdomains in a comma-separated list.</p>", 'podigee-quick-publish');
}

function pfex_plugin_setting_custom_domain()
{
    $options = get_option('pfex_plugin_options');

    $customDomainValue = ($options) && isset($options['pfex_custom_domain']) ? $options['pfex_custom_domain'] : "";

    echo "<input id='pfex_custom_domain' name='pfex_plugin_options[pfex_custom_domain]' size='40' type='text' value='{$customDomainValue}' />";
    _e(
        "<p>If you use your own domain for your podcast and therefore the podigee blog, please enter it here.</p>",
        'podigee-quick-publish'
    );
    if (isset($options['pfex_custom_domain']) && trim($options['pfex_custom_domain']) != "") {
        $customerDomain = explode(",", $options['pfex_custom_domain']);
        if (count($customerDomain) > 0) {
            echo "<br /><p>";
            _e('If configured correctly, you should be able to reach your feed(s) at:', 'podigee-quick-publish');
            echo "<br /><ul>";
            foreach ($customerDomain as $mp3feed) {

                echo "<li><a href=\"$mp3feed\" target=\"_blank\">$mp3feed</a>" .
                    (pfex_check_url($mp3feed) ? " <div style=\"display: inline\" class=\"pfex-auth-success\"><span>[OK]</span></div>"
                        : " <div style=\"display: inline\" class=\"pfex-auth-failed\"><span>[X]</span></div>") .
                    "</li>";
            }
            echo "</ul></p>";
        }
    }
    _e("<p>Did you know? You can add multiple domains in a comma-separated list.</p>", 'podigee-quick-publish');

}

/**
 * Checking feed availability
 */
function pfex_check_url($url)
{
    global $_PFEX_DEBUG;
    if (_isCurl()):
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($httpCode == 200) {
            if ($_PFEX_DEBUG) {
                pfex_log(true, "URL check with curl was successful", ["url" => $url]);
            }

            return true;
        } else {
            if ($_PFEX_DEBUG) {
                pfex_log(false, "URL check with curl was not successful.", ["url" => $url, "httpCode" => $httpCode]);
            } else {
                return false;
            }
        }
    else:
        if ($_PFEX_DEBUG) {
            pfex_log(false, "Curl is not installed for URL check.");
        }
    endif;
    try {
        $devnull = file_get_contents($url);
        if (!($devnull)) {
            if ($_PFEX_DEBUG) {
                pfex_log(false, "URL check with file_get_contents failed.", ["url" => $url, "devnull" => $devnull]);
            }

            return false;
        } else {
            if ($_PFEX_DEBUG) {
                pfex_log(false, "URL check with file_get_contents successful.", ["url" => $url]);
            }

            return true;
        }
    } catch (Exception $e) {
        if ($_PFEX_DEBUG) {
            pfex_log(false, "An exception occurred while URL checking with file_get_contents.", ["url" => $url, "exception" => $e]);
        }

        return false;
    }
    if ($_PFEX_DEBUG) {
        pfex_log(false, "Something went wrong while URL checking.", ["url" => $url]);
    }

    return false;
}

/**
 * Options and explanation for the settings page
 */
function pfex_plugin_setting_token()
{
    $options = get_option('pfex_plugin_options');
    $tokenValue = ($options) && isset($options['pfex_token']) ? $options['pfex_token'] : "";

    echo "<input id='pfex_token' name='pfex_plugin_options[pfex_token]' size='40' type='text' value='{$tokenValue}' /><br />";
    _e(
        "Please enter the auth token as displayed <a href=\"https://app.podigee.com/settings#applications\" target=\"_blank\">here</a>.",
        'podigee-quick-publish'
    );
}

/**
 * Options and explanation for the settings page
 */
function pfex_plugin_setting_welcome()
{
    $options = get_option('pfex_plugin_options');
    $welcomeValue = ($options) && isset($options['pfex_welcome']) ? $options['pfex_welcome'] : false;

    echo "<input type='checkbox' id='pfex_welcome' name='pfex_plugin_options[pfex_welcome]' value='1' " . ($welcomeValue == true ? "checked"
            : "") . " /><br />";
}

/**
 * Validation for the plugin settings
 */
function pfex_options_validate($input)
{
    $options = get_option('pfex_plugin_options');
    $options['pfex_token'] = strtolower(trim($input['pfex_token']));
    if (!preg_match('/^[a-z0-9]{32}$/i', $options['pfex_token'])) {
        $options['pfex_token'] = '';
    }
    $options['pfex_slug'] = strtolower(trim(str_replace(" ", "", $input['pfex_slug'])));
    if (!preg_match('/^[a-z0-9-_,]+$/i', $options['pfex_slug'])) {
        $options['pfex_slug'] = '';
    }

    if (isset($input['pfex_custom_domain'])) {
        $options['pfex_custom_domain'] = $input['pfex_custom_domain'];
        checkCustomDomain($input['pfex_custom_domain'], $input['pfex_token']);
    }

    $options['pfex_welcome'] = (isset($input['pfex_welcome']) && $input['pfex_welcome'] == true ? true : false);
    global $_PFEX_DEBUG;
    if ($_PFEX_DEBUG) {
        pfex_log(true, "Options saved.", $options);
    }

    return $options;
}

function checkCustomDomain(?string $urlString = null, ?string $token = null)
{
    if (!$urlString) {
        return false;
    }

    $urls = explode(',', $urlString);

    foreach ($urls as $url) {
        $checkUrl = "https://app.podigee.io/apps/wordpress-quick-publish/authorize";
        $data = ["custom_domain" => $url];
        $data_string = json_encode($data);

        $data = wp_remote_post(
            $checkUrl,
            [
                'headers' => ['Content-Type' => 'application/json', 'Token' => $token],
                'body' => $data_string,
                'method' => 'POST',
                'data_format' => 'body',
                'sslverify' => false,
            ]
        );

        if (is_array($data) && isset($data['response']['code']) && $data['response']['code'] === 200) {
            return true;
        }

        $message = __(sprintf('Problem with authorisation for feed: %s ', $url));
        add_settings_error('pfex_custom_domain_notice', 'pfex_custom_domain_notice', $message, 'error');

        return false;
    }

    return false;

}

/**
 * Fetches podcast feed.
 */
function feed2array($url)
{
    global $_PFEX_DEBUG;

    if (class_exists("DOMdocument")) {
        if ($_PFEX_DEBUG) {
            pfex_log(true, "DOMdocument exists –> using it.");
        }
        $xml = pfex_url_get_contents($url);

        if (!$xml) {
            add_settings_error(
                'pfex_custom_domain_notice',
                'pfex_custom_domain_notice',
                sprintf('Could not load feed: %s', $url),
                'error'
            );

            return [];
        }

        $rss = new DOMDocument();
        $loaded = @$rss->loadXML($xml);
        if (!$loaded) {
            add_settings_error(
                'pfex_custom_domain_notice',
                'pfex_custom_domain_notice',
                sprintf('Could not load feed: %s', $url),
                'error'
            );

            return [];
        }
        $feed = [];
        //echo $url."<br />";
        foreach ($rss->getElementsByTagName('item') as $node) {
            //echo "  ".trim(@$node->getElementsByTagName('title')->item(0)->nodeValue)."<br />";
            if (count($node->getElementsByTagName('enclosure')) > 0):
                $episode = [
                    'title' => trim(@$node->getElementsByTagName('title')->item(0)->nodeValue),
                    'link' => trim(@$node->getElementsByTagName('link')->item(0)->nodeValue),
                    'pubDate' => trim(@$node->getElementsByTagName('pubDate')->item(0)->nodeValue),
                    'description' => trim(@$node->getElementsByTagName('description')->item(0)->nodeValue),
                    'content' => trim(@$node->getElementsByTagName('encoded')->item(0)->nodeValue),
                    'media' => trim(@$node->getElementsByTagName('enclosure')->item(0)->getAttribute('url')),
                    'number' => trim(@$node->getElementsByTagName('episode')->item(0)->nodeValue),
                    'episodetype' => trim(@$node->getElementsByTagName('episodeType')->item(0)->nodeValue),
                    'season' => trim(@$node->getElementsByTagName('season')->item(0)->nodeValue),
                    'guid' => trim(@$node->getElementsByTagName('guid')->item(0)->nodeValue),
                ];
                if ($rss->getElementsByTagName('title')->item(0)) {
                    $episode['podcastTitle'] = $rss->getElementsByTagName('title')->item(0)->nodeValue;
                }
                array_push($feed, $episode);
            else:
                if ($_PFEX_DEBUG) {
                    pfex_log(
                        false,
                        "Feed node has no enclosure.",
                        [
                            "title" => trim(@$node->getElementsByTagName('title')->item(0)->nodeValue),
                            "link" => trim(@$node->getElementsByTagName('link')->item(0)->nodeValue),
                        ]
                    );
                }
            endif;
        }
        if ($_PFEX_DEBUG) {
            pfex_log(true, "DOMdocument worked and retrieved " . count($feed) . " feed entries.", ["url" => $url]);
        }
    } else {
        try {
            if ($_PFEX_DEBUG) {
                pfex_log(false, "DOMdocument does not exist – trying SimpleXML instead.");
            }
            $rss = file_get_contents($url);
            $xml = simplexml_load_string($rss, 'SimpleXMLElement', LIBXML_NOCDATA);
            $feed = [];
            foreach ($xml->channel->item as $item) {
                $itunes = ($item->children("itunes", true));
                $episode = [
                    'title' => trim(@$item->title),
                    'link' => trim(@$item->link),
                    'pubDate' => trim(@$item->pubDate),
                    'description' => trim(@$item->description),
                    'content' => trim(@$item->children("content", true)),
                    'media' => trim(@$item->enclosure['url']),
                    'number' => trim(@$itunes->episode),
                    'episodetype' => trim(@$itunes->episodeType),
                    'season' => trim(@$itunes->season),
                    'guid' => trim(@$itunes->guid),
                ];
                array_push($feed, $episode);
            }
            if ($_PFEX_DEBUG) {
                pfex_log(true, "SimpleXML worked and retrieved " . count($feed) . " feed entries.", ["url" => $url]);
            }
        } catch (Exception $e) {
            if ($_PFEX_DEBUG) {
                pfex_log(false, "SimpleXML threw an error.", ["error" => $e]);
            }
            wp_die('error');
        }
    }

    return $feed;
}

/**
 * Checks if the auth token is valid.
 */
function check_authorization($subdomain, $token, $remoteCheck = false)
{
    global $_PFEX_LOGIN_OKAY;
    global $_PFEX_DEBUG;
    if ($_PFEX_LOGIN_OKAY) {
        return true;
    }
    if (!isset($subdomain) || !isset($token) || $subdomain == false || $token == false):
        $_PFEX_LOGIN_OKAY = false;
        if ($_PFEX_DEBUG) {
            pfex_log(false, "No subdomain or no token set.");
        }

        add_settings_error('pfex_plugin_setting_slug_notice', 'pfex_plugin_setting_slug_notice', 'No subdomain or no token set.', 'error');
        return false;
    endif;
    if (!is_array($subdomain)):
        if (is_string($subdomain)):
            if (substr_count($subdomain, ",") == 0):
                $subdomain = [$subdomain];
            else:
                $subdomain = explode(",", $subdomain);
            endif;
        else:
            $_PFEX_LOGIN_OKAY = false;
            if ($_PFEX_DEBUG) {
                pfex_log(false, "Subdomain not an array but also not a string.", $subdomain);
            }

            return false;
        endif;
    endif;
    if (count($subdomain) == 0):
        $_PFEX_LOGIN_OKAY = false;
        if ($_PFEX_DEBUG) {
            pfex_log(false, "Subdomain-Array has length 0.");
        }

        return false;
    endif;
    $authorization = false;
    if ($remoteCheck) {
        foreach ($subdomain as $slug):
            $url = "https://app.podigee.io/apps/wordpress-quick-publish/authorize";
            $data = ["subdomain" => $slug];
            $data_string = json_encode($data);

            $data = wp_remote_post(
                $url,
                [
                    'headers' => ['Content-Type' => 'application/json', 'Token' => $token],
                    'body' => $data_string,
                    'method' => 'POST',
                    'data_format' => 'body',
                    'sslverify' => false,
                ]
            );

            if (is_wp_error($data)) {
                $error_string = $data->get_error_message();
                if ($_PFEX_DEBUG) {
                    pfex_log(false, $error_string);
                }
                add_settings_error('pfex_plugin_setting_slug_notice', 'pfex_plugin_setting_slug_notice', $error_string, 'error');
                die($error_string);
            } else if (is_array($data) && isset($data['response']['code']) && $data['response']['code'] == 200):
                $_PFEX_LOGIN_OKAY = true;
                if ($_PFEX_DEBUG) {
                    pfex_log(true, "Authorization was successful.", $subdomain, ["token" => $token]);
                }

                return true;
            endif;

            $message = __(sprintf('Problem with authorisation for subdomain: %s ', $slug));
            add_settings_error('pfex_plugin_setting_slug_notice', 'pfex_plugin_setting_slug_notice', $message, 'error');
        endforeach;
    }
    $_PFEX_LOGIN_OKAY = false;
    if ($_PFEX_DEBUG) {
        pfex_log(false, "Authorization failed: out of options.", $subdomain, ["token" => $token]);
    }

    return false;
}

/**
 * Custom logging function.
 */
function pfex_log($allgood, $str, $data = false, $data2 = false)
{
    $logfile = dirname(__FILE__) . "/log.txt";
    touch($logfile);
    $str = ($allgood ? "[OK]\t" : "[ERR]\t") . date("Y-m-d H:i:s") . "\t" . $str . "\n";
    if ($data) {
        if (is_string($data)) {
            $str .= "  |-> " . $data . "\n";
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $str .= "  |-> " . $key . ":\t" . $value . "\n";
            }
        }
    }
    if ($data2) {
        if (is_string($data2)) {
            $str .= "  |-> " . $data . "\n";
        }
        if (is_array($data2)) {
            foreach ($data2 as $key => $value) {
                $str .= "  |-> " . $key . ":\t" . $value . "\n";
            }
        }
    }
    file_put_contents($logfile, $str, FILE_APPEND);
}

/*
* Had to add this download function to fix the few cases in which the XML would return empty.
*/
function pfex_url_get_contents($url)
{
    if (!function_exists('curl_init')) {
        pfex_log(false, "CURL is not installed – try using file_get_contents instead");

        return file_get_contents(($url));
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
}

/*
* This is the class for our custom table that displays the feed items.
*/

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class My_List_Table extends WP_List_Table
{

    public function addData($array)
    {
        if (is_array($array) == false) {
            return false;
        }
        if (count($array) == 0) {
            return false;
        }
        $this->items[] = $array;
    }

    public function setData($array)
    {
        if (is_array($array) == false) {
            return false;
        }
        if (count($array) == 0) {
            return false;
        }
        $this->items = [];
        foreach ($array as $dataset):
            if (is_array($dataset) == false) {
                continue;
            }
            if (count($dataset) == 0) {
                continue;
            }
            $row = [];
            foreach ($dataset as $key => $value) {
                $row[$key] = $value;
            }
            $this->items[] = $row;
        endforeach;
    }

    function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'pubdate' => __('Published', 'podigee-quick-publish'),
            'title' => __('Episode title', 'podigee-quick-publish'),
            'podcast' => __('Podcast', 'podigee-quick-publish'),
            'episodetype' => __('Type', 'podigee-quick-publish'),
            'episodenumber' => __('E#', 'podigee-quick-publish'),
            'shortcode' => __('Shortcode', 'podigee-quick-publish'),

        ];

        return $columns;
    }

    function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        if ($this->items):
            usort($this->items, [&$this, 'usort_reorder']);

            $per_page = 15;
            $current_page = $this->get_pagenum();
            $total_items = count($this->items);

            $found_data = array_slice($this->items, (($current_page - 1) * $per_page), $per_page);

            $this->set_pagination_args(
                [
                    'total_items' => $total_items,
                    'per_page' => $per_page,
                ]
            );
            $this->items = $found_data;
        endif;
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'podcast':
            case 'pubdate':
            case 'episodenumber':
            case 'shortcode':
            case 'title':
            case 'episodetype':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    function get_sortable_columns()
    {
        $sortable_columns = [
            'pubdate' => ['pubdate', false],
            'title' => ['title', false],
            'podcast' => ['podcast', false],
            'episodenumber' => ['episodenumber', false],
        ];

        return $sortable_columns;
    }

    function usort_reorder($a, $b)
    {
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'pubdate';
        if (empty($_GET['orderby'])) {
            $_GET['order'] = 'desc';
        }
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
        $result = strnatcmp($a[$orderby], $b[$orderby]);

        return ($order === 'asc') ? $result : -$result;
    }

    function column_title($item)
    {
        $pagination = "";
        if (!empty($_GET['paged']) && is_numeric($_GET['paged'])) {
            $pagination = "&paged=" . $_GET['paged'];
        }
        $podcast = $item['external'] ? $item['externalFeed'] : $item['podcast'];
        $actions = [
            'new post' => sprintf(
                '<a href="%s?page=%s&action=%s&subdomain=%s&guid=%s&external=%s%s">%s</a>',
                $_SERVER['PHP_SELF'],
                $_REQUEST['page'],
                'new',
                $podcast,
                $item['guid'],
                $item['external'],
                $pagination,

                __('&gt;&gt; turn into post', 'podigee-quick-publish')
            ),
        ];

        return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions));
    }

    function column_shortcode($item)
    {
        $actions = [
            'copy' => sprintf('<a href="javascript:void(0);" class="pfex-copy-shortcode">%s</a>', __('&gt;&gt; copy', 'podigee-quick-publish')),
        ];

        return sprintf('%1$s %2$s', $item['shortcode'], $this->row_actions($actions));
    }

    function get_bulk_actions()
    {
        $actions = [
            'new post' => __('New posts from episodes', 'podigee-quick-publish'),
        ];

        return $actions;
    }

    function column_cb($item)
    {
        $podcast = $item['external'] ? $item['externalFeed'] : $item['podcast'];

        return sprintf(
            '<input type="checkbox" name="cbepisode[]" value="%s#%s#%s" />',
            $podcast,
            $item['guid'],
            (int)$item['external']
        );
    }

}
