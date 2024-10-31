<?php
/**
 * Plugin Name: Pilotpress Custom Redirect
 * Plugin URI: https://www.itmooti.com/pilotpress-custom-redirect/
 * Description: Restrict Users from access to Posts/Pages using Day Counter
 * Version: 1.1
 * Stable tag: 1.1
 * Author: ITMOOTI
 * Author URI: http://www.itmooti.com/
 */
class pilotpress_custom_redirect {
	
	private $plugin_links, $pilotpress_custom_redirect_permalink;
	private $options;
	public function __construct(){
		$this->options = get_option( 'pilotpress_custom_redirect' );
		add_action('admin_menu', array($this, 'admin_add_page'));
		add_action('admin_init', array($this, 'admin_init'));
		add_action('init', array($this, 'init'));
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this, 'action_link'));
		add_filter( 'plugin_row_meta', array($this, 'meta_link'), 10, 2);
	}
	
	public function get_option($key){
		if(isset($this->options[$key]))
			return $this->options[$key];
	}
	function action_link( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="options-general.php?page=pilotpress-custom-redirect">Settings</a>'
			),
			$links
		);
	}
	
	function meta_link( $links, $file ) {
		$plugin = plugin_basename(__FILE__);
		if ( $file == $plugin ) {
			return array_merge(
				$links,
				array(
					'settings' => '<a href="options-general.php?page=pilotpress-custom-redirect">Settings</a>'
				)
			);
		}
		return $links;
	}
	
	public function admin_add_page() {
		add_options_page(
            'Settings', 
            'Pilotpress Custom Redirect', 
            'manage_options', 
            'pilotpress-custom-redirect', 
            array( $this, 'create_admin_page' )
        );
	}
	
	public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <h2>Pilotpress Custom Redirect</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'pilotpress_custom_redirect_group' );   
                do_settings_sections( 'pilotpress-custom-redirect' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }
	
	function admin_init(){
		register_setting(
            'pilotpress_custom_redirect_group',
            'pilotpress_custom_redirect',
            array( $this, 'sanitize' )
        );
		global $pilotpress;
        if(function_exists( 'is_plugin_active' ) && is_plugin_active( 'pilotpress/pilotpress.php' ) && isset($pilotpress) && is_admin()){
			add_settings_section(
				'pilotpress_custom_redirect_setting', // ID
				'Settings', // Title
				array( $this, 'section_text' ), // Callback
				'pilotpress-custom-redirect' // Page
			);
			$membership_levels=$pilotpress->get_setting("membership_levels", "oap");
			if(is_array($membership_levels)){
				foreach($membership_levels as $k=>$membership_level){
					add_settings_field(
						'pcr_membership_level_'.$k, // ID
						$membership_level, // Title 
						array( $this, 'field_name_callback' ), // Callback
						'pilotpress-custom-redirect', // Page
						'pilotpress_custom_redirect_setting', // Section           
						array('id'=>'pcr_membership_level_'.$k)
					);
				}
			}
		}
		else{
			add_settings_section(
				'pilotpress_custom_redirect_setting', // ID
				'Settings', // Title
				array( $this, 'section_text_error' ), // Callback
				'pilotpress-custom-redirect' // Page
			);
		}
	}
	public function sanitize( $input )
    {
       return $input;
    }
	
	public function section_text(){
		echo '<p>Assign Pages for each membership.</p>';
	}
	
	public function section_text_error(){
		echo '<p>This plugin only works with PilotPress. First install PilotPress plugin then use this page to configure custom redirect settings.</p>';
	}
		
	public function field_name_callback($args)
    {
        if(isset($args["id"])){
			$value=isset( $this->options[$args["id"]] ) ? esc_attr( $this->options[$args["id"]]) : '';
			?>
			<select name="pilotpress_custom_redirect[<?php echo $args["id"]?>]" id="<?php echo $args["id"]?>">
            	<option value=""<?php if($value=="") echo ' selected="selected"';?>>Default</option>
                <?php
                $pages=get_pages();
				foreach($pages as $page){
					?>
					<option value="<?php echo $page->ID?>"<?php if($value==$page->ID) echo ' selected="selected"';?>><?php echo $page->post_title?></option>
					<?php
				}
				?>
            </select>
			<?php
		}
    }
	
	public function init(){
		add_filter('page_link', array($this, 'custom_page_link'), 10, 3);
	}
	
	public function custom_page_link($url, $post, $leavename){
		global $pilotpress;
		if(function_exists( 'is_plugin_active' ) && is_plugin_active( 'pilotpress/pilotpress.php' ) && isset($pilotpress)){// && !is_admin()){
			if(($post == $pilotpress->get_setting("pilotpress_affiliate_plr") || $post == $pilotpress->get_setting("pilotpress_customer_plr")) && $this->pilotpress_custom_redirect_permalink!=true){
				$user_levels = $pilotpress->get_setting("levels","user",true);
				if(isset($_SESSION["user_levels"]))
					$user_levels = $_SESSION["user_levels"];
				if(is_array($user_levels) && count($user_levels)>0){
					$pilotpress->load_settings();
					$membership_levels=$pilotpress->get_setting("membership_levels", "oap");
					if(is_array($membership_levels)){
						foreach($membership_levels as $k=>$membership_level){
							if(in_array($membership_level, $user_levels)){
								$page_id=isset( $this->options['pcr_membership_level_'.$k] ) ? esc_attr( $this->options['pcr_membership_level_'.$k]) : '';
								if($page_id!=""){
									if(get_page($page_id)){
										$this->pilotpress_custom_redirect_permalink=true;
										$url=get_permalink($page_id);
										break;
									}
								}
							}
						}
					}
				}
				//print_r($user_levels); print_r($membership_levels); echo $url;die;
			}				
		}
		$this->pilotpress_custom_redirect_permalink=false;
		return $url;
	}
}
$pilotpress_custom_redirect=new pilotpress_custom_redirect();