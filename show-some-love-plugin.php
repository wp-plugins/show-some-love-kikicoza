<?php
/*
Plugin Name: Show Some Love
Plugin URI: http://www.kiki.co.za/
Description: Show some love to the people who make it possible to do what you do. This plugin remembers the details of each person you have added so you don't have to retype all the details in each post and displays it below the relevant posts. We make it easy to show some love.
Author: Sergio Pellegrini, Iaan van Niekerk
Version: 1.1.1
Author URI: http://www.kiki.co.za/
*/

/*  Copyright 2012  Sergio Pellegrini  (email : info@kiki.co.za)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Add an entry for our option page to the Posts menu
register_activation_hook( __FILE__, 'sp_install' ); 
function sp_install() {
	add_option('sp-target','true' );
	add_option('sp-style', 'ordered');
	add_option('sp-custom', '');
}

add_action('admin_menu', 'sp_opt_add_page');

function sp_opt_add_page() {
    add_options_page( 'Show Some Love', 'Show Some Love', 'manage_options','sp_opt', 'sp_opt_option_page' ); //Add Service Providers Option Page
}

// Catch any action parameter in query string
add_action( 'admin_init', 'sp_opt_do_action' );

//Saving Options Page
function sp_opt_do_action() {
    if( !isset( $_REQUEST['sp_action'] ) ) return; //no action requested
    if( !current_user_can( 'manage_options' ) ) wp_die( 'Insufficient privileges!' );//check user permissions
    $sp_target = $_REQUEST['sp-target']; //Request Link Target
	$sp_style = $_REQUEST['sp-style']; //Request List Style
	$sp_custom = $_REQUEST['sp-custom']; //Request Custom CSS
	$sp_display = $_REQUEST['sp-display']; //Request Custom CSS
    $action = $_REQUEST['sp-action']; //Reuest Action
    update_option('sp-display', $sp_display); //Update db option display
    update_option('sp-target', $sp_target ); //Update db option target
	update_option('sp-style', $sp_style); //Update db option style
	update_option('sp-custom', wp_strip_all_tags($sp_custom)); //update db option custom
	
	if( $action == 'update' ) { add_action( 'admin_notices', 'sp_opt_message' ); return;} //Confirmation of update notification
}

// Admin notice
function sp_opt_message() {
    echo "<div class='updated'><p>Action completed</p></div>";
}
// Draw the tag management page
function sp_opt_option_page() {
	define("SP_PATH", plugin_dir_path( __FILE__ ) );
	require_once(SP_PATH. "/includes/sp-options-page.php"); //Options Page
}
/* Vendors */
add_action( 'add_meta_boxes', 'dynamic_add_custom_box' );
add_action( 'save_post', 'dynamic_save_postdata' );

/* Adds a box to the main column on the Post*/
function dynamic_add_custom_box() {
    add_meta_box('dynamic_sectionid',__( 'Vendors', 'myplugin_textdomain' ),'dynamic_inner_custom_box','post');
}


/* Prints the box content */
function dynamic_inner_custom_box() {
    global $post;
    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'dynamicMeta_noncename' );
	$sp_head = get_post_meta($post->ID,'sp_head',true);
	$sp_vendors = get_post_meta($post->ID,'sp_vendors',true);
	$auto_arr = unserialize(get_option('sp_auto_suggest_list'));
    $c = 0;
	if (empty($sp_head))
	{
		$sp_head="Service Providers";
	}
	echo '<div id="meta_inner">
	<table class="metaboxes_table">
		<tbody>
		<tr>		
			<th class="metabox_names"><label for="Array">Heading:</label></th>
			<td><input type="text" class="input_text" name="sp-head" value="'.$sp_head.'"><span class="metabox_desc">Enter the Heading for the Service Provider Listings (default:Service Providers)</span></td>		
		</tr>	
		</tbody>
	</table>';
	echo '<ol id="sp-meta">
			<li id="headtab">	
				<div class="meta-category">Name</div>  
				<div class="meta-name">Category</div>
				<div class="meta-link">Link</div> 
			</li>
		';
    if(is_array($sp_vendors)){
        foreach($sp_vendors as $track ){
		if (isset($track['awesome']) || isset($track['name']) || isset($track['category']) || isset($track['link']) ){
	            echo '<li> 
               	<input type="text" class="meta-name" name="sp_vendors['.$c.'][name]" value="'.$track['name'].'" />
				<input type="text" class="meta-category" name="sp_vendors['.$c.'][category]" value="'.$track['category'].'" /> 
				<input type="text" class="meta-link" name="sp_vendors['.$c.'][link]" value="'.$track['link'].'" />
				<span class="button remove" title="Delete">Remove</span>
				</li>';
                $c = $c +1;
            }	
        }
	}
	echo '</ol>';
    ?>
	<span id="here" ></span>
	<span class="add"><?php echo __('Add Vendor'); ?></span>
	<script type="text/javascript">
		jQuery(function() {
			var projects = [<?php $auto_arr = unserialize(get_option('sp_auto_suggest_list'));
			if (empty($auto_arr))
 			{
 			$auto_arr[0] = 'Kiki Photography,Photography Community,http://www.kiki.co.za';
 			}
			foreach ($auto_arr as $key ) {
				$st=explode(",",$key);
				echo '{ value:"'.$st[0].'",label:"',$st[0].'",tax:"'.$st[1].'",desc:"'.$st[2].'"},';
			} ?> ];
		jQuery(".meta-name").live("focus", function(){
		jQuery( ".meta-name" ).autocomplete({
			minLength: 0,
			source: projects,
			focus: function( event, ui ) {
				jQuery( this ).parent().children('.meta-name').val( ui.item.label );
				return false;
			},
			select: function( event, ui ) {
				jQuery( this ).parent().children('.meta-name').val( ui.item.label );
				jQuery( this ).parent().children('.meta-link').val( ui.item.desc );
				jQuery( this ).parent().children('.meta-category').val( ui.item.tax );
				return false;
			} }); });
		});

    jQuery(document).ready(function() {
        jQuery(".meta-link").blur(function() {
  		var field = jQuery(this).val();
  		var result = field.search(new RegExp(/^http:\/\//i));
			if( !result ) {	} 
			else { 
				field = 'http://'+field;
				jQuery(this).val(field);
			}
		});
        var count = <?php echo $c; ?>;
        jQuery(".add").click(function() {
            count = count + 1;
            jQuery('#sp-meta').append('<li><input type="text" class="meta-name" name="sp_vendors['+count+'][name]" value="" /><input type="text" class="meta-category" name="sp_vendors['+count+'][category]" value="" /><input type="text" class="meta-link" name="sp_vendors['+count+'][link]" value=""/><span class="button remove" title="Delete">Remove</span></li>' );
            return false;
        });
        jQuery(".remove").live('click', function() {
            jQuery(this).parent().remove();
        });
    });
    </script></div><?php
}

/* When the post is saved, saves our custom data */
function dynamic_save_postdata( $post_id ) {
    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        return;
	if ( is_int( wp_is_post_revision( $post_id ) ) )
    return;
	if( is_int( wp_is_post_autosave( $post_id ) ) )
    return;
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if (isset($_POST['dynamicMeta_noncename'])){
        if ( !wp_verify_nonce( $_POST['dynamicMeta_noncename'], plugin_basename( __FILE__ ) ) )
            return;
    }else{return;}
    // OK, we're authenticated: we need to find and save the data
 	$sp_vendors = $_POST['sp_vendors'];
 	$sp_head = $_POST['sp-head'];
 	$auto_sugg = get_option('sp_auto_suggest_list');
 	$arr_auto_sugg = unserialize($auto_sugg);
 	if (empty($arr_auto_sugg))
 	{
 		$arr_auto_sugg[0] = 'Kiki Photography,Photography Community,http://www.kiki.co.za';
 	}
 	$c=0;
 	while ($c <= count($sp_vendors)) {
 		if (!empty($sp_vendors[$c]))
			{
 			$st = implode(",",$sp_vendors[$c]);
    		array_push($arr_auto_sugg,$st);
			}
		$c = $c + 1;
 	}
 update_option('sp_auto_suggest_list',serialize(array_unique($arr_auto_sugg)));
 update_post_meta($post_id,'sp_vendors',$sp_vendors);
 update_post_meta($post_id,'sp_head',$sp_head);
}

add_action( 'admin_head', 'sp_vendors_css' );
function sp_vendors_css() {
    ?>
<link type="text/css" href="<?php echo plugins_url( '/css/ui-lightness/jquery-ui-1.8.16.custom.css' , __FILE__ )?>" rel="stylesheet" />
<link type="text/css" href="<?php echo plugins_url( '/css/sp-vendors-style.css' , __FILE__ )?>" rel="stylesheet" />  
<?php } 

function sp_content($content) {
if ( !is_singular( 'post' )) return $content;
		global $post;
        $original = $content;
		$str="";
		$str .= $original;
		$prov=get_post_meta($post->ID,'sp_vendors',true);
		$sp_head=get_post_meta($post->ID,'sp_head',true);
		$listclass = 'sp-'.get_option('sp-style');
		$linktarget =get_option('sp-target');
		if ($linktarget)
		{
			$linktarget_2 = 'target="_blank"';
		}
		else {
			$linktarget_2 ='';
		}
			if(is_array($prov)){
		$str .= '<div id="sp-container">';
	
        			foreach($prov as $data ){
					if (!empty($data['name']))
					{
							$cat = $data['category'];
							$str2 .= '<li class="sp-list-item '.$listclass.'">'.$cat.': ';
							if (!empty($data['link']))
							{
								
								$str2 .= '<a href="'.$data['link'].'" '.$linktarget_2.' title="'.$data['name'].'" >'.$data['name']."</a></li>";
							}
							else {
								$str2 .= $data['name'].'</li>';
							}							
						}
					}
					$str .= '<h4 class="sp-heading">'.$sp_head.'</h4>';
					$str .= '<ul class="sp-list '.$listclass.'">'.$str2.'</ul>';
					$str .= '</div>';
					return $str;
					}
			else
				{
					return $content;
				}
				
}
if (get_option('sp-display') == true)
{
add_filter( 'the_content', 'sp_content' );
}

function show_some_love_sc( $atts ){
 global $post;
        $original = $content;
		$str="";
		$str .= $original;
		$prov=get_post_meta($post->ID,'sp_vendors',true);
		$sp_head=get_post_meta($post->ID,'sp_head',true);
		$listclass = 'sp-'.get_option('sp-style');
		$linktarget =get_option('sp-target');
		if ($linktarget)
		{
			$linktarget_2 = 'target="_blank"';
		}
		else {
			$linktarget_2 ='';
		}
			if(is_array($prov)){
		$str .= '<div id="sp-container">';
	
        			foreach($prov as $data ){
					if (!empty($data['name']))
					{
							$cat = $data['category'];
							$str2 .= '<li class="sp-list-item '.$listclass.'">'.$cat.': ';
							if (!empty($data['link']))
							{
								
								$str2 .= '<a href="'.$data['link'].'" '.$linktarget_2.' title="'.$data['name'].'" >'.$data['name']."</a></li>";
							}
							else {
								$str2 .= $data['name'].'</li>';
							}							
						}
					}
					$str .= '<h4 class="sp-heading">'.$sp_head.'</h4>';
					$str .= '<ul class="sp-list '.$listclass.'">'.$str2.'</ul>';
					$str .= '</div>';
					return $str;
					}
}
add_shortcode( 'show-some-love', 'show_some_love_sc' );


function sp_wp_stylesheets()
{
	$sp_custom = get_option('sp-custom');
	echo '<link type="text/css" href="'.plugins_url( '/css/sp-listing-style.css' , __FILE__ ).'" rel="stylesheet" />';
	echo '<style type="text/css">'.$sp_custom.'</style>'; 
}
add_action('wp_head', 'sp_wp_stylesheets');

function sp_load_my_scripts() {  
     
    
    wp_enqueue_script('jquery');  
    
    wp_enqueue_script('jquery-ui-autocomplete');  
}  
add_action('init', 'sp_load_my_scripts');  