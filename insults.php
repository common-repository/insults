<?php
/*
Plugin Name: Insults
Version: 0.2
Plugin URI: http://www.beliefmedia.com/wp-plugins/insults.php
Description: Displays a random insult via the BeliefMedia API. Use as &#91;insult&#93;. Wrap in paragraph tags with &#91;insult p="1"&#93;. Wrap the insult in html with &#91;insult tags="strong,em"&#93; (no tags, separated by comma).
Author: Martin Khoury
Author URI: http://www.beliefmedia.com/
*/


/*
	Display a Random Insult on Your Website with PHP or WordPress Shortcode
	http://www.beliefmedia.com/display-random-insult
*/


function beliefmedia_insult($atts) {

  $atts = shortcode_atts(array(
    'tags' => false,
    'p' => false,
    'offline' => '<a href="http://www.beliefmedia/">API</a> Offline. Try again in a few minutes.',
    'cache_temp' => 300, /* If API offline, number of seconds to wait before trying again */
    'cache' => 3600
  ), $atts);

 $transient = 'bmis_' . md5(serialize($atts));
 $cachedposts = get_transient($transient);
 if ($cachedposts !== false) {
 return $cachedposts;

 } else {

 /* Tags? */
 if ($atts['tags'] !== false) {
  $tags = explode(',', $atts['tags']);
    foreach($tags as $tag) {
      $htmltag .= '<' . $tag . '>';  
    }
  $html_tags_closing = str_replace('<', '</', $htmltag);
 }

  /* Get data from BeliefMedia API */
  $json = @file_get_contents('http://api.beliefmedia.com/insults/random.php');
  if ($json !== false) $data = json_decode($json, true);

   if ($data['status'] == '200') {

     /* Get insult */
     $return = (string) $data['data']['insult'];
     if ($atts['tags'] !== false) $return = $htmltag . $return . $html_tags_closing;
     if ($atts['p'] !== false) $return = '<p>' . $return . '</p>';

     /* Set transient */
     set_transient($transient, $return, $atts['cache']);

     } else {

     $return = $atts['offline'];
     set_transient($transient, $return, $expiration = $atts['cache_temp']);
   }
  }

 return $return;
}
add_shortcode('insult','beliefmedia_insult');
	

/*
	Menu Links
*/


function beliefmedia_insults_action_links($links, $file) {
  static $this_plugin;
  if (!$this_plugin) {
   $this_plugin = plugin_basename(__FILE__);
  }

  if ($file == $this_plugin) {
	$links[] = '<a href="http://www.beliefmedia.com/wp-plugins/insult.php" target="_blank">Support</a>';
  }
 return $links;
}
add_filter('plugin_action_links', 'beliefmedia_insults_action_links', 10, 2);



/*
	Delete Transient Data on Deactivation
*/

	
function remove_beliefmedia_insults_options() {
  global $wpdb;
   $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE ('_transient%_beliefmedia_insult_%')" );
   $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE ('_transient_timeout%_beliefmedia_insult_%')" );
}
register_deactivation_hook( __FILE__, 'remove_beliefmedia_insults_options' );


/*
	Uncomment if shortcode isn't working in widgets
*/


// add_filter('widget_text', 'do_shortcode');