<?php
/*
Plugin Name: WP Lynx
Plugin URI: http://mtekk.weblogs.us/code/wp-lynx/
Description: Adds Facebook-esq extended link information to your WordPress pages and posts. For details on how to use this plugin visit <a href="http://mtekk.weblogs.us/code/wp-lynx/">WP Lynx</a>. 
Version: 0.1.3
Author: John Havlik
Author URI: http://mtekk.weblogs.us/
*/
/*  
	Copyright 2010  John Havlik  (email : mtekkmonkey@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
//Include admin base class
if(!class_exists('mtekk_admin'))
{
	require_once(dirname(__FILE__) . '/includes/mtekk_admin_class.php');
}
//Include llynxScrape class
if(!class_exists('llynxScrape'))
{
	require_once(dirname(__FILE__) . '/llynx_scrape.php');
}
/**
 * The administrative interface class 
 */
class linksLynx extends mtekk_admin
{
	/**
	 * local store for breadcrumb version
	 * 
	 * @var   string
	 */
	protected $version = '0.1.3';
	protected $full_name = 'WP Lynx Settings';
	protected $short_name = 'WP Lynx';
	protected $access_level = 'manage_options';
	protected $identifier = 'wp_lynx';
	protected $unique_prefix = 'llynx';
	protected $plugin_basename = 'wp-lynx/wp_lynx.php';
	protected $llynx_scrape;
	protected $opt = array(
					'global_style' => true,
					'p_max_count' => 5,
					'p_min_length' => 120,
					'p_max_length' => 180,
					'img_max_count' => 20,
					'img_min_x' => 50, 
					'img_min_y' => 50,
					'img_max_range' => 256,
					'curl_agent' => 'WP Links Bot',
					'curl_embrowser' => false,
					'curl_timeout' => 3,
					'cache_type' => 'original',
					'cache_quality' => 80,
					'cache_max_x' => 100,
					'cache_max_y' => 100,
					'cache_crop' => false);
	/**
	 * linksLynx
	 * 
	 * Class default constructor
	 */
	function linksLynx()
	{
		$this->llynx_scrape = new llynxScrape(null);
		//We set the plugin basename here, could manually set it, but this is for demonstration purposes
		$this->plugin_base = plugin_basename(__FILE__);
		//We'll include a defualt style that everyone can enjoy
		add_action('wp_head', array($this, 'head_style'));
		//We're going to make sure we load the parent's constructor
		parent::__construct();
	}
	/**
	 * admin initialisation callback function
	 * 
	 * is bound to wpordpress action 'admin_init' on instantiation
	 * 
	 * @return void
	 */
	function init()
	{
		//We're going to make sure we run the parent's version of this function as well
		parent::init();
		//We can not synchronize our database options untill after the parent init runs (the reset routine must run first if it needs to)
		$this->opt = $this->get_option('llynx_options');
		$this->llynx_scrape->opt = $this->opt;
		//If we are emulating the user's browser, we should update our user agent accordingly
		if($this->opt['curl_embrowser'])
		{
			$this->llynx_scrape->opt['curl_agent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		//Add javascript enqeueing callback
		add_action('wp_print_scripts', array($this, 'javascript'));
		add_action('media_buttons_context', array($this, 'media_buttons_context'));
		add_action('media_upload_wp_links_lynx', array($this, 'media_upload'));
	}
	/**
	 * security
	 * 
	 * Makes sure the current user can manage options to proceed
	 */
	function security()
	{
		//If the user can not manage options we will die on them
		if(!current_user_can($this->access_level))
		{
			_e('Insufficient privileges to proceed.', 'wp_links_lynx');
			die();
		}
	}
	/**
	 * install
	 * 
	 * This sets up and upgrades the database settings, runs on every activation
	 */
	function install()
	{
		//Call our little security function
		$this->security();
		//Reduce db queries by saving this
		$db_version = $this->get_option('llynx_version');
		//If our version is not the same as in the db, time to update
		if($db_version !== $this->version)
		{
			//Split up the db version into it's components
			list($major, $minor, $release) = explode('.', $db_version);
			$this->opt = $this->get_option('llynx_options');
			if(!$this->opt)
			{
				$this->opt = array(
					'global_style' => true,
					'p_max_count' => 5,
					'p_min_length' => 120,
					'p_max_length' => 180,
					'img_max_count' => 20,
					'img_min_x' => 50, 
					'img_min_y' => 50,
					'img_max_range' => 256,
					'curl_agent' => 'WP Links Bot',
					'curl_embrowser' => false,
					'curl_timeout' => 3,
					'backlink' => false,
					'trends' => false,
					'cache_type' => 'original',
					'cache_quality' => 80,
					'cache_max_x' => 100,
					'cache_max_y' => 100,
					'cache_crop' => false);
			}
			//Always have to update the version
			$this->update_option('llynx_version', $this->version);
			//Store the options
			$this->add_option('llynx_options', $this->opt);
		}
	}
	/**
	 * ops_update
	 * 
	 * Updates the database settings from the webform
	 */
	function opts_update()
	{
		global $wp_taxonomies;
		//Do some security related thigns as we are not using the normal WP settings API
		$this->security();
		//Do a nonce check, prevent malicious link/form problems
		check_admin_referer('llynx_options-options');
		//Grab our incomming array (the data is dirty)
		$input = $_POST['llynx_options'];
		//Loop through all of the existing options (avoids random setting injection)
		foreach($this->opt as $option => $value)
		{
			//Handle all of our boolean options first
			if($option == 'trends' || $option == 'backlink' || $option == 'cache_crop' || $option == 'global_style' || $option == 'curl_embrowser')
			{
				$this->opt[$option] = isset($input[$option]);
			}
			//Now handle all of the integers
			else if(strpos($option, 'img_m') === 0 || strpos($option, 'p_m') === 0 || strpos($option, 'cache_m') === 0 || $option == 'cache_quality')
			{
				$this->opt[$option] = (int) stripslashes($input[$option]);
			}
			//Now handle anything that can't be blank
			else if(strpos($option, 'curl_') === 0)
			{
				//Only save a new anchor if not blank
				if(isset($input[$option]))
				{
					//Do excess slash removal sanitation
					$this->opt[$option] = stripslashes($input[$option]);
				}
			}
			//Now everything else
			else
			{
				$this->opt[$option] = stripslashes($input[$option]);
			}
		}
		//Commit the option changes
		$this->update_option('llynx_options', $this->opt);
		//Let the user know everything went ok
		$this->message['updated fade'][] = __('Settings successfully saved.', $this->identifier);
		add_action('admin_notices', array($this, 'message'));
	}
	/**
	 * media_buttons_context
	 * 
	 * Adds a nice link button next to the Upload/Insert buttons in the edit pannel
	 * 
	 * @return 
	 */
	function media_buttons_context($context)
	{
		global $post_ID, $temp_ID;
		//We may be in a temporary post, so we can't rely on post_ID
		$curID = (int) (0 == $post_ID) ? $temp_ID : $post_ID;
		//Assemble the link to our special uploader
		$url = 'media-upload.php?post_id=' . $curID;
		//Find the url for the image, use nice functions
		$imgSrc = plugins_url('wp-lynx/llynx.png');
		//The hyperlink title
		$title = __('Add a Lynx Print', 'wp_links_lynx');
		//Append our link to the current context
		$context .= sprintf('<a title="%s" href="%s&amp;type=wp_links_lynx&amp;TB_iframe=true" id="add_lynx_print" class="thickbox"><img src="%s" alt="%s"/></a>', $title, $url, $imgSrc, $this->short_name);
		return $context;
	}
	/**
	 * media_upload
	 * 
	 * Handles all of the special media iframe stuff
	 * 
	 * @param object $mode [optional]
	 * @return 
	 */
	function media_upload($mode = 'default')
	{
		//We have to manually enqueue all dependency styles as wp doens't do them in the correct order see bug #12415
		wp_enqueue_style('global');
		wp_enqueue_style('wp-admin');
		//The style we're actually after
		wp_enqueue_style('media');
		//We're going to override some WP styles in this
		add_action('admin_head', array($this, 'admin_head_style'));
		//We need this to do the nice sorting and other things
		wp_enqueue_script('admin-gallery');
		$location = WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__));
		wp_enqueue_script('llynx_javascript', $location . 'wp_lynx.js.php', array('jquery'));
		add_action('wp_links_lynx_media_upload_header', 'media_upload_header');
		wp_iframe(array(&$this, 'url_tab'));
	}
	/**
	 * resize_image
	 * 
	 * Resizes the given image
	 * 
	 * @param bitstream $data
	 * @param int $nW
	 * @param int $nH
	 * @return GDImage 
	 */
	function resize_image($data, &$nW, &$nH)
	{
		//Time to resize the image
		$imgRaw = imagecreatefromstring($data);
		//Get the image dimensions and aspect ratio
		$w = imagesx($imgRaw);
		$h = imagesy($imgRaw);
		$r = $w/$h;
		//If we will be cropping the image we need to do some calculations
		if($this->opt['cache_crop'])
		{
			//If we are wider, hight is more important
			if($w > $h)
			{
				$w = ceil($w - ($w * ($r - $this->opt['cache_max_x'] / $this->opt['cache_max_y'])));
			}
			//If we are taller, width is more important
			else
			{
				$h = ceil($h - ($h * ($r - $this->opt['cache_max_x'] / $this->opt['cache_max_y'])));
			}
			//Out new height and widths are simple as we are cropping
			$nH = $this->opt['cache_max_y'];
			$nW = $this->opt['cache_max_x'];
		}
		//Otherwise we're just resizing
		else
		{
			//If the destination ration is wider than the source we need to adjust accordingly
			if($this->opt['cache_max_x']/$this->opt['cache_max_y'] > $r)
			{
				//We are height limited, maintain aspect ratio
				$nW = $this->opt['cache_max_y'] * $r;
				$nH = $this->opt['cache_max_y'];
			}
			else
			{
				//We are width limited, maintain aspect ratio
				$nW = $this->opt['cache_max_x'];
				$nH = $this->opt['cache_max_x'] / $r;
			}
		}
		//Create the destination image
		$imgThumb = imagecreatetruecolor($nW, $nH);
		//Do the resizing/cropping
		imagecopyresampled($imgThumb, $imgRaw, 0, 0, 0, 0, $nW, $nH, $w, $h);
		//Return the thumbnail
		return $imgThumb;
	}
	/**
	 * url_insert_handler
	 * 
	 * Handles inserting a link print into the current post editor screen
	 * @param array $data array of $_POST data
	 * @return string compiled HTML
	 */
	function url_insert_handler($data)
	{
		$imgHtml = '';
		//Assemble our title
		$title = __('Go to', 'wp_links_lynx') . ' ' . stripslashes($data['title']);
		//Built the image component, if needed
		if(!isset($data['nothumb']) && $data['img'] !== NULL)
		{
			//Grab the image (raw data), use a referrer to avoid issues with anti-hotlinking scripts
			//If we recieved an error, then we have no image
			if($imgData = $this->llynx_scrape->getContent($data['img'], $data['url']))
			{
				//We need to manipulate the url to get the image name
				$imgName = explode('/', $data['img']);
				$imgName = end($imgName);
				$imgExt = explode('.',$imgName);
				//The extension should be the stuff after the last '.', make sure its lower case
				$imgExt = strtolower(end($imgExt));
				//Get the upload location
				$uploadDir = wp_upload_dir();
				//Make sure we use a unique filename
				$fileName = wp_unique_filename($uploadDir[path], $imgName);
				//Compile our image location and image URL
				$imgLoc = $uploadDir[path] . "/$fileName";
				$imgURL = $uploadDir[url] . "/$fileName";
				//Generate the thumbnail
				$nH = 0;
				$nW = 0;
				$imgThumb = $this->resize_image($imgData, $nW, $nH);
				//If we will be saving as jpeg
				if($this->opt['cache_type'] == 'jpeg' || ($this->opt['cache_type'] == 'original' && ($imgExt == 'jpg' || $imgExt == 'jpeg')))
				{
					//Save as JPEG
					imagejpeg($imgThumb, $imgLoc, $this->opt['cache_quality']);
				}
				//If we will be saving as png
				else if($this->opt['cache_type'] == 'png' || ($this->opt['cache_type'] == 'original' && $imgExt == 'png'))
				{
					//Save as PNG
					imagepng($imgThumb, $imgLoc);
				}
				//If we will be saving as gif
				else
				{
					//Save as GIF
					imagegif($imgThumb, $imgLoc);
				}
				//Verify we have the correct permissions of new file
				$stat = @stat(dirname($imgLoc));
				$perms = $stat['mode'] & 0007777;
				$perms = $perms & 0000666;
				@chmod($imgLoc, $perms);
				//Assemble the image and link it, if it exists
				$imgHtml = sprintf('<a title="%s" href="%s"><img alt="%s" src="%s" width="%s" height="%s" /></a>', $title, $data['url'], stripslashes($data['title']), $imgURL, $nW, $nH);
			}
		}
		$urlHtml = sprintf('<a title="%s" href="%s">%s</a>', $title, $data['url'], stripslashes($data['title']));
		//Built the entire html link print and return it
		return sprintf('<div class="llynx_print">%s <div class="llynx_text">%s <small>%s</small><span>%s</span></div></div>', $imgHtml, $urlHtml, $data['url'], stripslashes($data['content']));
	}
	function url_tab()
	{
		global $post_ID, $temp_ID;
		//We may be in a temporary post, so we can't rely on post_ID
		$curID = (int) (0 == $post_ID) ? $temp_ID : $post_ID;
		//Assemble the link to our special uploader
		$formUrl = 'media-upload.php?post_id=' . $curID;
		//Handle the case when we want to insert only one lynx print
		if($_POST['prints_send'])
		{
			//Grab the keys of llynx send
			$keys = array_keys($_POST['prints_send']);
			//Grab the first, and probably only id
			$key = (int) array_shift($keys);
			//Grab our dirty data badges[$key][post_content]
			$data = $_POST['prints'][$key];
			//Use the WP function to send this html to the editor
			media_send_to_editor($this->url_insert_handler($data));
		}
		//Handle the case when we want to insert all of the lynx prints
		if($_POST['prints_send_all'])
		{
			$html = '';
			//Loop through all of our prints, adding them to our compiled html
			foreach($_POST['prints'] as $data)
			{
				//Grab the compiled data
				$html .= $this->url_insert_handler($data);
			}
			//Use the WP function to send this html to the editor
			media_send_to_editor($html);
		}
		//We want to keep the urls we've grabbed in the get field
		$urlString = '';
		if(isset($_POST['llynx_get_url']['url']))
		{
			$urlString = $_POST['llynx_get_url']['url'];
		}
		?>
		<script type="text/javascript">
		//post_id = <?php echo $curID; ?>;
		llynx_imgs = new Array();
		llynx_cimgs = new Array();
		llynx_cont = new Array();
		llynx_ccont = new Array();
		<!--
		jQuery(function($){
			var preloaded = $(".media-item.preloaded");
			if ( preloaded.length > 0 ) {
				preloaded.each(function(){prepareMediaItem({id:this.id.replace(/[^0-9]/g, '')},'');});
				updateMediaForm();
			}
		});
		-->
		</script>
		<form action="<?php echo $formUrl; ?>&amp;type=wp_links_lynx&amp;TB_iframe=true" method="post" id="llynx_get_url" class="media-upload-form type-form validate">
			<?php wp_nonce_field('llynx_get_url');?>
			<h3 class="media-title"><?php _e('Add a Lynx Print','wp_links_lynx'); ?></h3>
			<div class="media-item media-blank">
				<table class="describe">
					<tbody>
						<tr>
							<th class="label" valign="top" scope="row">
								<span class="alignleft"><?php _e('URL:')?></span>
							</th>
							<td class="field">
								<input id="llynx_get_url[url]" type="text" aria-required="true" value="<?php echo $urlString; ?>" name="llynx_get_url[url]" /><input class="button" type="submit" value="<?php _e('Get','wp_links_lynx');?>" name="llynx_get_url_button"/>
							</td>
						</tr>
					</tbody>
				</table>	
			</div>
		</form>
		<?php
		if(isset($_POST['llynx_get_url']))
		{
			//Get urls if any were sent
			$urls = explode(' ', $_POST['llynx_get_url']['url']);
		?>
		<div class="hide-if-no-js" id="sort-buttons">
			<span><?php _e('All Tabs:'); ?><a id="showall" href="#" style="display: inline;"><?php _e('Show'); ?></a><a style="display: none;" id="hideall" href="#"><?php _e('Hide'); ?></a></span>
			<?php _e('Sort Order:'); ?><a id="asc" href="#"><?php _e('Ascending');?></a> | <a id="desc" href="#"><?php _e('Descending'); ?></a> | <a id="clear" href="#"><?php _e('Clear'); ?></a>
		</div>
		<form action="<?php echo $formUrl; ?>&amp;type=wp_links_lynx&amp;TB_iframe=true" method="post" id="llynx_insert_print" class="media-upload-form type-form validate">
			<?php wp_nonce_field('llynx_insert_print');?>
			<table cellspacing="0" class="widefat">
				<thead>
					<tr>
						<th><?php _e('Website'); ?></th>
						<th class="order-head"><?php _e('Order'); ?></th>
						<th class="actions-head"><?php _e('Actions'); ?></th>
					</tr>
				</thead>
			</table>
			<div id="media-items" class="ui-sortable">
				<?php
				foreach($urls as $key => $url)
				{
					//If we recieve a blank URL, skip to next iteration
					if($url == NULL)
					{
						continue;
					}
					//Let's clean up the url before using it
					$url = html_entity_decode(clean_url($url), ENT_COMPAT, 'UTF-8');
					//Let's get some data from that url
					$this->llynx_scrape->scrapeContent($url);
					//If we didn't get anything, throw error, continue on our way
					if(count($this->llynx_scrape->images) < 1 && $this->llynx_scrape->title == '' && count($this->llynx_scrape->text) < 1)
					{
						?>
						<div class="media-item child-of-<?php echo $curID; ?> preloaded" id="media-item-<?php echo $key; ?>">
							<?php printf(__('Error while retrieving %s', 'wp_links_lynx'), $url);?>
							<blockquote>
								<?php
									if(is_array($this->llynx_scrape->error))
									{
										var_dump($this->llynx_scrape->error);
									}
									else
									{
										echo $this->llynx_scrape->error;
									}?>
							</blockquote>
						</div>
						<?php
						continue;
					}
					?>
				<div class="media-item child-of-<?php echo $curID; ?> preloaded" id="media-item-<?php echo $key; ?>">
					<input type="hidden" value="image" id="type-of-<?php echo $key; ?>">
					<input type="hidden" value="<?php echo $this->llynx_scrape->images[0]; ?>" id="prints<?php echo $key; ?>img" name="prints[<?php echo $key; ?>][img]">
					<input type="hidden" value="<?php echo $url; ?>" id="prints[<?php echo $key;?>][url]" name="prints[<?php echo $key;?>][url]">
					<a href="#" class="toggle describe-toggle-on" style="display: block;"><?php _e('Show'); ?></a>
					<a href="#" class="toggle describe-toggle-off" style="display: none;"><?php _e('Hide'); ?></a>
					<div class="menu_order"> <input type="text" value="0" name="prints[<?php echo $key; ?>][menu_order]" id="prints[<?php echo $key; ?>][menu_order]" class="menu_order_input"></div>
					<div class="filename new"><span class="title"><?php echo $this->llynx_scrape->title; ?></span></div>
					<table class="slidetoggle describe startclosed" style="display: none;">
						<thead id="media-head-llynx<?php echo $key; ?>" class="media-item-info">
						<tr valign="top">
							<td id="thumbnail-head-llynx-<?php echo $key; ?>" class="A1B1">
								<p class="llynx_thumb"><img style="margin-top: 3px;" alt="" src="<?php echo $this->llynx_scrape->images[0]; ?>" class="thumbnail"></p>
									<script type="text/javascript">
										llynx_imgs[<?php echo $key; ?>] = new Array();
										llynx_cimgs[<?php echo $key; ?>] = 0;
									<?php
									//Since our keys are not continuous, we need to keep track of them ourselves
									$kId = 0;
									//Now output all of the images, hide all of them
									foreach($this->llynx_scrape->images as $image)
									{
										?>llynx_imgs[<?php echo $key; ?>][<?php echo $kId; ?>] = '<?php echo $image;?>';<?php
										echo "\n";
										$kId++;
									}
									?></script>
								<p>
									<input type="button" value="&lt;" class="button disabled" disabled="disabled" onclick="prev_thumb(<?php echo $key; ?>)" id="imgprev-btn-<?php echo $key; ?>">
									<input type="button" value="&gt;" <?php if(count($this->llynx_scrape->images) <= 1){echo 'disabled="disabled" class="disabled button"';}else{echo 'class="button"';}?> onclick="next_thumb(<?php echo $key; ?>)" id="imgnext-btn-<?php echo $key; ?>">
									<span id="icount-<?php echo $key; ?>"><?php if(count($this->llynx_scrape->images) < 1){echo '0';}else{echo '1';}?> / <?php echo count($this->llynx_scrape->images); ?></span>
								</p>
								<p>
									<input type="checkbox" <?php if(count($this->llynx_scrape->images) < 1){echo 'checked="checked"';}?> onclick="img_toggle(<?php echo $key; ?>)" value="none" id="prints[<?php echo $key; ?>][nothumb]" name="prints[<?php echo $key; ?>][nothumb]"><label for="prints[<?php echo $key; ?>][nothumb]"><?php _e('No Thumbnail', 'wp_links_lynx'); ?></label>
								</p>
							</td>
							<td>
								<p><input type="text" aria-required="true" value="<?php echo $this->llynx_scrape->title; ?>" name="prints[<?php echo $key; ?>][title]" id="prints[<?php echo $key; ?>][title]" class="text"><br />
								<small><?php echo $url; ?></small></p>
								<p><textarea name="prints[<?php echo $key; ?>][content]" id="prints<?php echo $key; ?>content" type="text"><?php echo $this->llynx_scrape->text[0];?></textarea></p>
								<p>
									<input type="button" value="&lt;" class="button disabled" disabled="disabled" onclick="prev_content(<?php echo $key; ?>)" id="contprev-btn-<?php echo $key; ?>">
									<input type="button" value="&gt;" <?php if(count($this->llynx_scrape->text) <= 1){echo 'disabled="disabled" class="disabled button"';}else{echo 'class="button"';}?> onclick="next_content(<?php echo $key; ?>)" id="contnext-btn-<?php echo $key; ?>">
									<span id="ccount-<?php echo $key; ?>"><?php if(count($this->llynx_scrape->text) < 1){echo '0';}else{echo '1';}?> / <?php echo count($this->llynx_scrape->text); ?></span>
								</p>
								<script type="text/javascript">
									llynx_cont[<?php echo $key; ?>] = new Array();
									llynx_ccont[<?php echo $key; ?>] = 0;
								<?php
									//Since our keys are not continuous, we need to keep track of them ourselves
									$kId = 0;
									//Now output all of the text, hide all of them
									foreach($this->llynx_scrape->text as $text)
									{
										printf("llynx_cont[%s][%s] = '%s';\n", $key, $kId, addslashes(html_entity_decode($text, ENT_COMPAT, 'UTF-8')));
										$kId++;
									}
								?></script>
							</td>
						</tr>
						</thead>
						<tbody>
						<tr class="submit">
							<td></td>
							<td class="savesend">
								<input type="submit" value="<?php _e('Insert into Post');?>" name="prints_send[<?php echo $key; ?>]" class="button">  <a onclick="document.getElementById('media-items').removeChild(document.getElementById('media-item-<?php echo $key; ?>'));return false;" class="del-link" href="#"><?php _e('Delete'); ?></a>
							</td>
						</tr>
					</tbody>
					</table>
				</div>
				<?php } ?>
			</div>
			<div>
				<p class="ml-submit">
					<input type="submit" value="<?php _e('Insert All'); ?>" id="prints_send_all" name="prints_send_all" class="button">
				</p>
			</div>
		</form><?php
		}
	}
	function admin_head_style()
	{
		?>
<style type="text/css">
/*WP Links Lynx Admin Styles*/
.describe td{vertical-align:top;}
.describe textarea{height:5em;}
.A1B1{width:128px;float:left;}
.llynx_thumb{height:138px;overflow:hidden;border-bottom:1px solid #dfdfdf;margin-bottom:5px;}
</style>
		<?php
	}
	function head_style()
	{
		//Sync our options
		$this->opt = $this->get_option('llynx_options');
		//Only print if enabled
		if($this->opt['global_style'])
		{
		?>
<style type="text/css">
/*WP Links Lynx Admin Styles*/
.llynx_print{margin:10px;padding:5px;display:block;float:left;border:1px solid #999;}
.llynx_print img{padding:5px;border:none;width:20%;float:left;}
.llynx_text{float:right;width:70%;}
.llynx_text a{text-decoration:none;font-weight:bolder;font-size:1em;float:left;width:100%;}
.llynx_text small{padding:3px 0;float:left;width:100%;}
.llynx_text span{float:left;width:100%;}
</style>
		<?php
		}
	}
	/**
	 * javascript
	 *
	 * Enqueues JS dependencies (jquery) for the tabs
	 * 
	 * @see admin_init()
	 * @return void
	 */
	function javascript()
	{
		//Enqueue ui-tabs
		wp_enqueue_script('jquery-ui-tabs');
	}
	/**
	 * get help text
	 * 
	 * @return string
	 */
	protected function _get_help_text()
	{
		return sprintf(__('Tips for the settings are located below select options. Please refer to the %sdocumentation%s for more information.', 'wp_links_lynx'), 
			'<a title="' . __('Go to the Links Lynx online documentation', 'wp_links_lynx') . '" href="http://mtekk.weblogs.us/code/wp-lynx/wp-lynx-doc/">', '</a>');
	}
	/**
	 * admin_head
	 *
	 * Adds in the JavaScript and CSS for the tabs in the adminsitrative 
	 * interface
	 * 
	 */
	function admin_head()
	{	
		// print style and script element (should go into head element) 
		?>
<style type="text/css">
	/**
	 * Tabbed Admin Page (CSS)
	 * 
	 * @see Breadcrumb NavXT (Wordpress Plugin)
	 * @author Tom Klingenberg 
	 * @colordef #c6d9e9 light-blue (older tabs border color, obsolete)
	 * @colordef #dfdfdf light-grey (tabs border color)
	 * @colordef #f9f9f9 very-light-grey (admin standard background color)
	 * @colordef #fff    white (active tab background color)
	 */
#hasadmintabs ul.ui-tabs-nav {border-bottom:1px solid #dfdfdf; font-size:12px; height:29px; list-style-image:none; list-style-position:outside; list-style-type:none; margin:13px 0 0; overflow:visible; padding:0 0 0 8px;}
#hasadmintabs ul.ui-tabs-nav li {display:block; float:left; line-height:200%; list-style-image:none; list-style-position:outside; list-style-type:none; margin:0; padding:0; position:relative; text-align:center; white-space:nowrap; width:auto;}
#hasadmintabs ul.ui-tabs-nav li a {background:transparent none no-repeat scroll 0 50%; border-bottom:1px solid #dfdfdf; display:block; float:left; line-height:28px; padding:1px 13px 0; position:relative; text-decoration:none;}
#hasadmintabs ul.ui-tabs-nav li.ui-tabs-selected a{-moz-border-radius-topleft:4px; -moz-border-radius-topright:4px;border:1px solid #dfdfdf; border-bottom-color:#f9f9f9; color:#333333; font-weight:normal; padding:0 12px;}
#hasadmintabs ul.ui-tabs-nav a:focus, a:active {outline-color:-moz-use-text-color; outline-style:none; outline-width:medium;}
#screen-options-wrap p.submit {margin:0; padding:0;}
</style>
<script type="text/javascript">
/* <![CDATA[ */
	jQuery(function()
	{
		llynx_context_init();
		llynx_tabulator_init();		
	 });
	function llynx_confirm(type)
	{
		if(type == 'reset'){
			var answer = confirm("<?php _e('All of your current WP Lynx settings will be overwritten with the default values. Are you sure you want to continue?', 'wp_links_lynx'); ?>");
		}
		else{
			var answer = confirm("<?php _e('All of your current WP Lynx settings will be overwritten with the imported values. Are you sure you want to continue?', 'wp_links_lynx'); ?>");
		}
		if(answer)
			return true;
		else
			return false;
	}
	/**
	 * Tabulator Bootup
	 */
	function llynx_tabulator_init(){
		if (!jQuery("#hasadmintabs").length) return;		
		/* init markup for tabs */
		jQuery('#hasadmintabs').prepend("<ul><\/ul>");
		jQuery('#hasadmintabs > fieldset').each(function(i){
		    id      = jQuery(this).attr('id');
		    caption = jQuery(this).find('h3').text();
		    jQuery('#hasadmintabs > ul').append('<li><a href="#'+id+'"><span>'+caption+"<\/span><\/a><\/li>");
		    jQuery(this).find('h3').hide();					    
	    });	
		/* init the tabs plugin */
		jQuery("#hasadmintabs").tabs();
		/* handler for opening the last tab after submit (compability version) */
		jQuery('#hasadmintabs ul a').click(function(i){
			var form   = jQuery('#llynx-options');
			var action = form.attr("action").split('#', 1) + jQuery(this).attr('href');				
			form.get(0).setAttribute("action", action);
		});
	}
	/**
	 * context screen options for import/export
	 */
	 function llynx_context_init(){
		if (!jQuery("#llynx_import_export_relocate").length) return;
		jQuery('#screen-meta').prepend(
				'<div id="screen-options-wrap" class="hidden"></div>'
		);
		jQuery('#screen-meta-links').append(
				'<div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">' +
				'<a class="show-settings" id="show-settings-link" href="#screen-options"><?php printf('%s/%s/%s', __('Import', 'wp_links_lynx'), __('Export', 'wp_links_lynx'), __('Reset', 'wp_links_lynx')); ?></a>' + 
				'</div>'
		);
		var code = jQuery('#llynx_import_export_relocate').html();
		jQuery('#llynx_import_export_relocate').html('');
		code = code.replace(/h3>/gi, 'h5>');		
		jQuery('#screen-options-wrap').prepend(code);		
	 }
/* ]]> */
</script>
<?php
	} //function admin_head()
	/**
	 * admin_page
	 * 
	 * The administrative page for Links Lynx
	 * 
	 */
	function admin_page()
	{
		global $wp_taxonomies;
		$this->security();
		?>
		<div class="wrap"><h2><?php _e('WP Lynx Settings', 'wp_links_lynx'); ?></h2>		
		<p<?php if($this->_has_contextual_help): ?> class="hide-if-js"<?php endif; ?>><?php 
			print $this->_get_help_text();			 
		?></p>
		<form action="options-general.php?page=wp_lynx" method="post" id="llynx-options">
			<?php settings_fields('llynx_options');?>
			<div id="hasadmintabs">
			<fieldset id="general" class="llynx_options">
				<h3><?php _e('General', 'wp_links_lynx'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_check(__('Default Style', 'wp_links_lynx'), 'global_style', __('Enable the default Lynx Prints styling on your blog.', 'wp_links_lynx'));
						$this->input_text(__('Maximum Image Width', 'wp_links_lynx'), 'cache_max_x', '10', false, __('Maximum cached image width in pixels.', 'wp_links_lynx'));
						$this->input_text(__('Maximum Image Height', 'wp_links_lynx'), 'cache_max_y', '10', false, __('Maximum cached image height in pixels.', 'wp_links_lynx'));
						$this->input_check(__('Crop Image', 'wp_links_lynx'), 'cache_crop', __('Crop images in the cache to the above dimensions.', 'wp_links_lynx'));
					?>
					<tr valign="top">
						<th scope="row">
							<?php _e('Cached Image Format', 'wp_links_lynx'); ?>
						</th>
						<td>
							<?php
								$this->input_radio('cache_type', 'original', __('Same as source format', 'wp_links_lynx'));
								$this->input_radio('cache_type', 'png', __('PNG'));
								$this->input_radio('cache_type', 'jpeg', __('JPEG'));
								$this->input_radio('cache_type', 'gif', __('GIF'));
							?>
							<span class="setting-description"><?php _e('The image format to use in the local image cache.', 'wp_links_lynx'); ?></span>
						</td>
					</tr>
					<?php
						$this->input_text(__('Cache Image Quality', 'wp_links_lynx'), 'cache_quality', '10', false, __('Image quality when cached images are saved as JPEG.', 'wp_links_lynx'));
					?>
				</table>
			</fieldset>
			<fieldset id="images" class="llynx_options">
				<h3><?php _e('Images', 'wp_links_lynx'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Minimum Image Width', 'wp_links_lynx'), 'img_min_x', '10', false, __('Minimum width of images to scrape in pixels.', 'wp_links_lynx'));
						$this->input_text(__('Minimum Image Height', 'wp_links_lynx'), 'img_min_y', '10', false, __('Minimum hieght of images to scrape in pixels.', 'wp_links_lynx'));
						$this->input_text(__('Maximum Image Count', 'wp_links_lynx'), 'img_max_count', '10', false, __('Maximum number of images to scrape.', 'wp_links_lynx'));
						$this->input_text(__('Maximum Image Scrape Size', 'wp_links_lynx'), 'img_max_range', '10', false, __('Maximum number of bytes to download when determining the dimensions of JPEG images.', 'wp_links_lynx'));
					?>
				</table>
			</fieldset>
			<fieldset id="text" class="llynx_options">
				<h3><?php _e('Text', 'wp_links_lynx'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Minimum Paragraph Length', 'wp_links_lynx'), 'p_min_length', '10', false, __('Minimum paragraph length to be scraped (in characters).', 'wp_links_lynx'));
						$this->input_text(__('Maximum Paragraph Length', 'wp_links_lynx'), 'p_max_length', '10', false, __('Maximum paragraph length before it is cutt off (in characters).', 'wp_links_lynx'));
						$this->input_text(__('Minimum Paragraph Count', 'wp_links_lynx'), 'p_max_count', '10', false, __('Maximum number of paragraphs to scrape.', 'wp_links_lynx'));
					?>
				</table>
			</fieldset>
			<fieldset id="advanced" class="llynx_options">
				<h3><?php _e('Advanced', 'wp_links_lynx'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Timeout', 'wp_links_lynx'), 'curl_timeout', '10', false, __('Maximum time for scrape execution in seconds.', 'wp_links_lynx'));
						$this->input_text(__('Useragent', 'wp_links_lynx'), 'curl_agent', '32', $this->opt['curl_embrowser'], __('Useragent to use during scrape execution.', 'wp_links_lynx'));
						$this->input_check(__('Emulate Browser', 'wp_links_lynx'), 'curl_embrowser', __("Useragent will be exactly as the users's browser.", 'wp_links_lynx'));
					?>
				</table>
			</fieldset>
			</div>
			<p class="submit"><input type="submit" class="button-primary" name="llynx_admin_options" value="<?php esc_attr_e('Save Changes') ?>" /></p>
		</form>
		<?php $this->import_form(); ?>
		</div>
		<?php
	}
	/**
	 * add_option
	 *
	 * This inserts the value into the option name, WPMU safe
	 *
	 * @param (string) key name where to save the value in $value
	 * @param (mixed) value to insert into the options db
	 * @return (bool)
	 */
	function add_option($key, $value)
	{
		return add_option($key, $value);
	}
	/**
	 * delete_option
	 *
	 * This removes the option name, WPMU safe
	 *
	 * @param (string) key name of the option to remove
	 * @return (bool)
	 */
	function delete_option($key)
	{
		return delete_option($key);
	}
	/**
	 * update_option
	 *
	 * This updates the value into the option name, WPMU safe
	 *
	 * @param (string) key name where to save the value in $value
	 * @param (mixed) value to insert into the options db
	 * @return (bool)
	 */
	function update_option($key, $value)
	{
		return update_option($key, $value);
	}
	/**
	 * get_option
	 *
	 * This grabs the the data from the db it is WPMU safe and can place the data 
	 * in a HTML form safe manner.
	 *
	 * @param  (string) key name of the wordpress option to get
	 * @return (mixed)  value of option
	 */
	function get_option($key)
	{
		return get_option($key);
	}
}
//Let's make an instance of our object takes care of everything
$linksLynx = new linksLynx;