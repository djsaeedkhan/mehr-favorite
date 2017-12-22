<?php
/*
  Plugin Name: مدیریت علاقه مندی
  Plugin URI: http://plugin-nevis.ir/favorite
  Description: پلاگین نویس :: مدیریت علاقه مندی ها کاربران وردپرس
  Version: 1.95.9.23
  Author: سعید سروشیان
  Author URI: http://plugin-nevis.ir/
  License: GPLv2+
  Text Domain: MehrFavorite
*/
class MehrFavorite{
	function __construct() {
	global $wpdb;
	$this->pre='mehrfavo';
	
	//define table Name
	$this->tblFavList = $wpdb->prefix . $this->pre.'_favlist';
	$this->setting = get_option($this->pre."site");
	
	//define show basket in page
	add_filter('the_content',array($this,'shop_basket')); //
	add_filter('template_include', array($this,'WaitForComming'));
	
	add_action('admin_menu', array($this,'wpa_add_menu'));
	add_action('admin_enqueue_scripts', array($this, 'wpa_styles'));
	add_action('wp_enqueue_scripts', array($this, 'wpa_styles'));
	
	register_activation_hook(__FILE__, array($this, 'wpa_install'));
	register_deactivation_hook(__FILE__, array($this, 'wpa_uninstall'));
}
//------------------------------------------------------------------
function wpa_add_menu(){//Admin Menu
	add_menu_page( 'مدیریت علاقه مندی', 'علاقه مندی', 'manage_options', 'mehrfav-dashboard', array(__CLASS__,'wpa_page_file_path'), plugins_url('images/logo.jpg', __FILE__),'99.3.9');
	add_submenu_page( 'mehrfav-dashboard', 'داشبورد', ' داشبورد', 'manage_options', 'mehrfav-dashboard', array(__CLASS__,'wpa_page_file_path'));
	//add_submenu_page( 'mehrnews-dashboard', 'مدیریت خبرخوان', 'مدیریت خبرخوان', 'manage_options', 'mehrnews-feed', array(__CLASS__,'wpa_page_file_path'));
	//add_submenu_page( 'mehrnews-dashboard', 'پلان ها', 'پلان ها', 'manage_options', 'mehrnews-plan', array(__CLASS__,'wpa_page_file_path'));
	//add_submenu_page( 'mehrnews-dashboard', 'تنظیمات', 'تنظیمات', 'manage_options', 'mehrnews-setting', array(__CLASS__,'wpa_page_file_path'));
}
//------------------------------------------------------------------
function wpa_page_file_path() {
	include( dirname(__FILE__) . '/includes/dashboard.php' );
	/*$screen = get_current_screen();
	if(strpos($screen->base, 'mehrnews-setting')!== false){include( dirname(__FILE__) . '/includes/setting.php' );}
	elseif (strpos($screen->base, 'mehrnews-feed')!== false){include( dirname(__FILE__) . '/includes/feed.php' );}
	else {include( dirname(__FILE__) . '/includes/dashboard.php' );}*/
}
//------------------------------------------------------------------
function GetFavoriteList($action="all",$col='',$data='',$col2='',$data2='',$col3='',$data3='')
{
	if($action=="all") $results = $GLOBALS['wpdb']->get_results("SELECT * FROM {$this->tblFavList} order by id desc",ARRAY_A );
	if($action=="ListID") $results = $GLOBALS['wpdb']->get_results("SELECT * FROM {$this->tblFavList} where `$col`=$data order by id desc",ARRAY_A );
	if($action=="ListData") $results = $GLOBALS['wpdb']->get_results("SELECT * FROM {$this->tblFavList} where `$col`='$data' and `$col2`='$data2' order by id desc",ARRAY_A );
	if($action=="ListFull") $results = $GLOBALS['wpdb']->get_results("SELECT * FROM {$this->tblFavList} where `$col`=$data and `$col2`='$data2' and `$col3`='$data3' order by id desc",ARRAY_A );
	if($action=="row")   $results = $GLOBALS['wpdb']->get_row("SELECT * FROM {$this->tblFavList} where `$col`='$data' order by id desc",ARRAY_A );
	if($action=="Exists") $results = $GLOBALS['wpdb']->get_row("SELECT id FROM {$this->tblFavList} where `$col`='$data' and `$col2`='$data2' order by id desc",ARRAY_A);
	return $results;
}
//------------------------------------------------------------------
function WaitForComming($template)
{	
	if(isset($_POST['addtofav']))
	{
		 if(is_user_logged_in()){
			$current_user = wp_get_current_user();
			if(count($post = get_post(intval($_POST['addtofav']), ARRAY_A )))
			{
				if(count($this->GetFavoriteList('Exists','post_id',$post['ID'],'user_id',$current_user->id))==0)
				{$this->SaveFavorite($current_user,$post);}
				else
				echo"<script>alert('این مورد قبلا در لیست علاقه مندی های شما قرار گرفته است');</script>";
			}
		}
	}
	elseif(isset($_POST['rmvfromfav']))
	{
		 if(is_user_logged_in()){
			$current_user = wp_get_current_user();
			if(count($post = get_post(intval($_POST['rmvfromfav']), ARRAY_A )))
			{
				if(count($this->GetFavoriteList('Exists','post_id',$post['ID'],'user_id',$current_user->id))==1) 
				{$this->RemoveFavorite($current_user,$post);}
				else echo"<script>alert('این مورد قبلا از لیست علاقه مندی های شما حذف شده است');</script>";
			}
		}
	}
    return $template;
}
//------------------------------------------------------------------
function GetFavorite($user_id=0,$post_type='',$post_id='')
{
	if($user_id==0 and is_user_logged_in()){
		$current_user = wp_get_current_user();
		$user_id=$current_user->id;
	}	
	if($post_type=='')
		$list=$this->GetFavoriteList('ListID','user_id',intval($user_id));
	elseif($post_type!='')
		$list=$this->GetFavoriteList('ListData','user_id',intval($user_id),'post_type',$post_type);
	if(count($list)):return $list;endif;
}
//------------------------------------------------------------------
function GetFavoriteByPostID($post_id='',$post_type='')
{
	$list=$this->GetFavoriteList('ListID','post_id',intval($post_id));
	if(count($list)):return $list;endif;
}
//------------------------------------------------------------------
function IsLiked($post_id=0)
{
	if(is_user_logged_in()):
		$current_user = wp_get_current_user();
		$list=$this->GetFavoriteList('ListID','post_id',intval($post_id),'user_id',$post['ID']);
		return $list;
	else:
		return 0;
	endif;
}
//------------------------------------------------------------------
function SaveFavorite($current_user='',$post='')
{
	if($GLOBALS['wpdb']->insert($this->tblFavList, array( 'user_id' =>$current_user->id, 'post_id' =>$post['ID'], 'post_type' =>$post['post_type'],'created' => current_time('mysql'))))
		return 1;
	else
		return 0;
}
//------------------------------------------------------------------
function RemoveFavorite($current_user='',$post='')
{
	if($GLOBALS['wpdb']->delete($this->tblFavList, array( 'user_id' =>intval($current_user->id), 'post_id' =>$post['ID'], 'post_type' =>$post['post_type'])))
		return 1;
	else
		return 0;
}
//------------------------------------------------------------------
function FavAddFrom($post_id=0,$str='')
{
	return '<form method="post" style="display: initial;cursor:pointer" onClick="this.submit()"><input type="hidden" name="addtofav" value="'.$post_id.'">'.$str.'</form>';
}
//------------------------------------------------------------------
function FavRemoveFrom($post_id=0,$str='')
{
	return '<form method="post" style="display: initial;cursor:pointer" onClick="this.submit()"><input type="hidden" name="rmvfromfav" value="'.$post_id.'">'.$str.'</form>';
}
//------------------------------------------------------------------
function wpa_styles($page){
	wp_enqueue_style('wp-analytify-style',plugins_url('css/style.css',__FILE__));
}
//------------------------------------------------------------------
function wpa_install() {
	include(dirname(__FILE__).'/includes/db/database.php');
}
//------------------------------------------------------------------
function wpa_uninstall() {
}
//------------------------------------------------------------------
function shop_basket($content='') {
	if(isset($_POST['addtofav']))
	{
		$post_id=intval($_POST['addtofav']);
	}
	return $content;
}
//------------------------------------------------------------------
}
$query=new MehrFavorite();
?>