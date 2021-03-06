<?php
/*
Plugin Name: KBoard : 게시판
Plugin URI: http://www.cosmosfarm.com/products/kboard
Description: 워드프레스 KBoard 게시판 플러그인 입니다.
Version: 5.3.2
Author: 코스모스팜 - Cosmosfarm
Author URI: http://www.cosmosfarm.com/
*/

if(!defined('ABSPATH')) exit;
if(!session_id()) session_start();

define('KBOARD_VERSION', '5.3.2');
define('KBOARD_PAGE_TITLE', __('KBoard : 게시판', 'kboard'));
define('KBOARD_WORDPRESS_ROOT', substr(ABSPATH, 0, -1));
define('KBOARD_WORDPRESS_APP_ID', '083d136637c09572c3039778d8667b27');
define('KBOARD_DIR_PATH', dirname(__FILE__));
define('KBOARD_URL_PATH', plugins_url('', __FILE__));
define('KBOARD_DASHBOARD_PAGE', admin_url('admin.php?page=kboard_dashboard'));
define('KBOARD_LIST_PAGE', admin_url('admin.php?page=kboard_list'));
define('KBOARD_NEW_PAGE', admin_url('admin.php?page=kboard_new'));
define('KBOARD_SETTING_PAGE', admin_url('admin.php?page=kboard_list'));
define('KBOARD_LATESTVIEW_PAGE', admin_url('admin.php?page=kboard_latestview'));
define('KBOARD_LATESTVIEW_NEW_PAGE', admin_url('admin.php?page=kboard_latestview_new'));
define('KBOARD_BACKUP_PAGE', admin_url('admin.php?page=kboard_backup'));
define('KBOARD_UPGRADE_ACTION', admin_url('admin.php?page=kboard_upgrade'));
define('KBOARD_CONTENT_LIST_PAGE', admin_url('admin.php?page=kboard_content_list'));

include_once 'class/KBAdminController.class.php';
include_once 'class/KBoardBuilder.class.php';
include_once 'class/KBContent.class.php';
include_once 'class/KBContentList.class.php';
include_once 'class/KBContentMedia.class.php';
include_once 'class/KBContentOption.class.php';
include_once 'class/KBController.class.php';
include_once 'class/KBoard.class.php';
include_once 'class/KBoardList.class.php';
include_once 'class/KBoardMeta.class.php';
include_once 'class/KBoardSkin.class.php';
include_once 'class/KBSeo.class.php';
include_once 'class/KBStore.class.php';
include_once 'class/KBTemplate.class.php';
include_once 'class/KBUrl.class.php';
include_once 'class/KBUpgrader.class.php';
include_once 'class/KBRouter.class.php';
include_once 'class/KBLatestview.class.php';
include_once 'class/KBLatestviewList.class.php';
include_once 'class/KBFileHandler.class.php';
include_once 'helper/Pagination.helper.php';
include_once 'helper/Security.helper.php';
include_once 'helper/Functions.helper.php';

/*
 * 애드온 파일 로딩
 */
foreach(glob(KBOARD_DIR_PATH . '/addons/*.php') as $filename){
	include_once $filename;
}

/*
 * KBoard 게시판 시작
 */
add_action('init', 'kboard_init', 0);
function kboard_init(){

	// 게시판 페이지 이동
	$router = new KBRouter();
	$router->process();

	// 컨트롤러 시작
	$controller = new KBController();

	// 템플릿 시작
	$template = new KBTemplate();
	$template->route();

	// ajax 등록
	add_action('wp_ajax_kboard_ajax_builder', 'kboard_ajax_builder');
	add_action('wp_ajax_nopriv_kboard_ajax_builder', 'kboard_ajax_builder');

	$kboard_list_sort = isset($_GET['kboard_list_sort'])?$_GET['kboard_list_sort']:'';
	$kboard_list_sort_remember = isset($_GET['kboard_list_sort_remember'])?intval($_GET['kboard_list_sort_remember']):'';
	if($kboard_list_sort && $kboard_list_sort_remember){
		$_COOKIE["kboard_list_sort_{$kboard_list_sort_remember}"] = $kboard_list_sort;
		setcookie("kboard_list_sort_{$kboard_list_sort_remember}", $kboard_list_sort, strtotime('+1 year'), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
	}
}

/*
 * 테마 헤더에 정보 출력
 */
add_action('get_header', 'kboard_get_header');
function kboard_get_header(){
	
	// SEO 시작
	$seo = new KBSeo();
}

/*
 * KBoard 관리자 기능 실행
 */
add_action('admin_init', 'kboard_admin_init');
function kboard_admin_init(){

	// 관리자 컨트롤러 시작
	$admin_controller = new KBAdminController();
}

/*
 * 글쓰기 에디터에 미디어 추가하기 버튼을 추가한다.
 */
function kboard_editor_button($context){
	$context .= ' <button type="button" class="button" onclick="kboard_editor_open_media()">'.__('KBoard 미디어 추가', 'kboard').'</button> ';
	return $context;
}

/*
 * 글쓰기 에디터에 미디어 버튼을 등록한다.
 */
function kboard_register_media_button($buttons){
	array_push($buttons, 'kboard_media');
	return $buttons;
}

/*
 * 글쓰기 에디터에 미디어 버튼을 추가한다.
 */
function kboard_add_media_button($plugin_array){
	$plugin_array['kboard_media_button_script'] = plugins_url('/template/js/editor_media_button.js', __FILE__);
	return $plugin_array;
}

/*
 * 플러그인 페이지 링크
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'kboard_settings_link');
function kboard_settings_link($links){
	return array_merge($links, array('settings'=>'<a href="'.admin_url('admin.php?page=kboard_new').'">'.__('게시판 생성', 'kboard').'</a>'));
}

/*
 * 워드프레스 관리자 웰컴 패널에 KBoard 패널을 추가한다.
 */
add_action('welcome_panel', 'kboard_welcome_panel');
function kboard_welcome_panel(){
	echo '<script>jQuery(document).ready(function($){jQuery("div.welcome-panel-content").eq(0).hide();});</script>';
	$upgrader = KBUpgrader::getInstance();
	include_once 'pages/welcome.php';
}

/*
 * 관리자메뉴에 추가
 */
add_action('admin_menu', 'kboard_settings_menu');
function kboard_settings_menu(){
	global $wpdb, $_wp_last_object_menu;

	// KBoard 메뉴 등록
	$_wp_last_object_menu++;
	add_menu_page(KBOARD_PAGE_TITLE, 'KBoard', 'administrator', 'kboard_dashboard', 'kboard_dashboard', plugins_url('kboard/images/icon.png'), $_wp_last_object_menu);
	add_submenu_page('kboard_dashboard', KBOARD_PAGE_TITLE, __('대시보드', 'kboard'), 'administrator', 'kboard_dashboard');
	add_submenu_page('kboard_dashboard', KBOARD_PAGE_TITLE, __('게시판 목록', 'kboard'), 'administrator', 'kboard_list', 'kboard_list');
	add_submenu_page('kboard_dashboard', KBOARD_PAGE_TITLE, __('게시판 생성', 'kboard'), 'administrator', 'kboard_new', 'kboard_new');
	add_submenu_page('kboard_dashboard', KBOARD_PAGE_TITLE, __('최신글 뷰 목록', 'kboard'), 'administrator', 'kboard_latestview', 'kboard_latestview');
	add_submenu_page('kboard_dashboard', KBOARD_PAGE_TITLE, __('최신글 뷰 생성', 'kboard'), 'administrator', 'kboard_latestview_new', 'kboard_latestview_new');
	add_submenu_page('kboard_dashboard', KBOARD_PAGE_TITLE, __('백업 및 복구', 'kboard'), 'administrator', 'kboard_backup', 'kboard_backup');
	add_submenu_page('kboard_dashboard', KBOARD_PAGE_TITLE, __('전체 게시글', 'kboard'), 'administrator', 'kboard_content_list', 'kboard_content_list');

	// 표시되지 않는 페이지
	add_submenu_page('kboard_new', KBOARD_PAGE_TITLE, __('게시판 업데이트', 'kboard'), 'administrator', 'kboard_upgrade', 'kboard_upgrade');

	// 스토어 메뉴 등록
	$_wp_last_object_menu++;
	add_menu_page(__('스토어', 'kboard'), __('스토어', 'kboard'), 'administrator', 'kboard_store', 'kboard_store', plugins_url('kboard/images/icon.png'), $_wp_last_object_menu);
	add_submenu_page('kboard_store', __('스토어', 'kboard'), __('스토어', 'kboard'), 'administrator', 'kboard_store');

	// 댓글 플러그인 활성화면 댓글 리스트 페이지를 보여준다.
	if(defined('KBOARD_COMMNETS_VERSION') && KBOARD_COMMNETS_VERSION >= '1.3' && KBOARD_COMMNETS_VERSION < '3.3') add_submenu_page('kboard_dashboard', KBOARD_COMMENTS_PAGE_TITLE, __('전체 댓글', 'kboard'), 'administrator', 'kboard_comments_list', 'kboard_comments_list');
	else if(defined('KBOARD_COMMNETS_VERSION') && KBOARD_COMMNETS_VERSION >= '3.3') kboard_comments_settings_menu();

	$result = $wpdb->get_results("SELECT `meta`.`board_id`, `setting`.`board_name` FROM `{$wpdb->prefix}kboard_board_meta` AS `meta` LEFT JOIN `{$wpdb->prefix}kboard_board_setting` AS `setting` ON `meta`.`board_id`=`setting`.`uid` WHERE `meta`.`key`='add_menu_page'");
	foreach($result as $row){
		add_submenu_page('kboard_dashboard', KBOARD_PAGE_TITLE, $row->board_name, 'administrator', "kboard_admin_view_{$row->board_id}", 'kboard_admin_view');
	}

	// 메뉴 액션 실행
	do_action('kboard_admin_menu');
}

/*
 * 스토어 상품 리스트 페이지
 */
function kboard_store(){
	KBStore::productsList();
}

/*
 * 게시판 대시보드 페이지
 */
function kboard_dashboard(){
	$upgrader = KBUpgrader::getInstance();
	include_once 'pages/kboard_dashboard.php';
}

/*
 * 게시판 목록 페이지
 */
function kboard_list(){
	if(isset($_GET['board_id']) && $_GET['board_id']){
		kboard_setting();
	}
	else{
		include_once 'class/KBoardListTable.class.php';
		$table = new KBoardListTable();
		if(isset($_POST['board_id']) && $table->current_action() == 'delete'){
			foreach($_POST['board_id'] as $key=>$value){
				$table->board->delete($value);
			}
		}
		$table->prepare_items();
		include_once 'pages/kboard_list.php';
	}
}

/*
 * 새로운 게시판 생성
 */
function kboard_new(){
	$board = new KBoard();
	$meta = new KBoardMeta();
	$skin = KBoardSkin::getInstance();
	if(defined('KBOARD_COMMNETS_VERSION')){
		include_once WP_CONTENT_DIR.'/plugins/kboard-comments/class/KBCommentSkin.class.php';
		$comment_skin = KBCommentSkin::getInstance();
	}
	include_once 'pages/kboard_setting.php';
}

/*
 * 게시판 목록 페이지
 */
function kboard_setting(){
	$board_id = isset($_GET['board_id'])?$_GET['board_id']:'';
	$board = new KBoard($board_id);
	$meta = new KBoardMeta($board->uid);
	$skin = KBoardSkin::getInstance();
	if(defined('KBOARD_COMMNETS_VERSION')){
		include_once WP_CONTENT_DIR.'/plugins/kboard-comments/class/KBCommentSkin.class.php';
		$comment_skin = KBCommentSkin::getInstance();
	}
	include_once 'pages/kboard_setting.php';
}

/*
 * 최신글 뷰
 */
function kboard_latestview(){
	if(isset($_GET['latestview_uid']) && $_GET['latestview_uid']){
		$skin = KBoardSkin::getInstance();
		$latestview = new KBLatestview();
		$latestview->initWithUID($_GET['latestview_uid']);
		$linked_board = $latestview->getLinkedBoard();
		$board_list = new KBoardList();
		include_once 'pages/kboard_latestview_setting.php';
	}
	else{
		include_once 'class/KBLatestviewListTable.class.php';
		$table = new KBLatestviewListTable();
		if(isset($_POST['latestview_uid']) && $table->current_action() == 'delete'){
			$latestview = new KBLatestview();
			foreach($_POST['latestview_uid'] as $key=>$latestview_uid){
				$latestview->initWithUID($latestview_uid);
				$latestview->delete();
			}
		}
		$table->prepare_items();
		include_once 'pages/kboard_latestview.php';
	}
}

/*
 * 최신글 뷰 생성
 */
function kboard_latestview_new(){
	$skin = KBoardSkin::getInstance();
	$latestview = new KBLatestview();
	$linked_board = $latestview->getLinkedBoard();
	$board_list = new KBoardList();
	include_once 'pages/kboard_latestview_setting.php';
}

/*
 * 게시판 백업 및 복구 페이지
 */
function kboard_backup(){
	include_once 'pages/kboard_backup.php';
}

/*
 * 게시판 업그레이드
 */
function kboard_upgrade(){
	if(!current_user_can('activate_plugins')) wp_die(__('You do not have permission.', 'kboard'));

	$action = isset($_GET['action'])?kboard_htmlclear($_GET['action']):'';
	$download_url = isset($_GET['download_url'])?kboard_htmlclear($_GET['download_url']):'';
	$download_version = isset($_GET['download_version'])?kboard_htmlclear($_GET['download_version']):'';
	$form_url = wp_nonce_url(admin_url("admin.php?page=kboard_upgrade&action=$action" . ($download_url?"&download_url=$download_url":'') . ($download_version?"&download_version=$download_version":'')), 'kboard_upgrade');
	$upgrader = KBUpgrader::getInstance();

	if($action == 'kboard'){
		if(version_compare(KBOARD_VERSION, $upgrader->getLatestVersion()->kboard, '>=')){
			die('<script>alert("최신버전 입니다.");location.href="' . admin_url('admin.php?page=kboard_dashboard') . '"</script>');
		}
		if(!$upgrader->credentials($form_url, WP_CONTENT_DIR . KBUpgrader::$TYPE_PLUGINS)) exit;
		$download_file = $upgrader->download(KBUpgrader::$CONNECT_KBOARD, $upgrader->getLatestVersion()->kboard, KBStore::getAccessToken());
		$install_result = $upgrader->install($download_file, KBUpgrader::$TYPE_PLUGINS);
		die('<script>alert("KBoard 게시판 업그레이드가 완료 되었습니다.");window.location.href="' . admin_url('admin.php?page=kboard_dashboard') . '"</script>');
	}
	else if($action == 'comments'){
		if(defined('KBOARD_COMMNETS_VERSION')){
			if(version_compare(KBOARD_COMMNETS_VERSION, $upgrader->getLatestVersion()->comments, '>=')){
				die('<script>alert("최신버전 입니다.");window.location.href="' . admin_url('admin.php?page=kboard_dashboard') . '"</script>');
			}
		}
		if(!$upgrader->credentials($form_url, WP_CONTENT_DIR . KBUpgrader::$TYPE_PLUGINS)) exit;
		$download_file = $upgrader->download(KBUpgrader::$CONNECT_COMMENTS, $upgrader->getLatestVersion()->comments, KBStore::getAccessToken());
		$install_result = $upgrader->install($download_file, KBUpgrader::$TYPE_PLUGINS);
		die('<script>alert("KBoard 댓글 업그레이드가 완료 되었습니다.");window.location.href="' . admin_url('admin.php?page=kboard_dashboard') . '"</script>');
	}
	else if($action == 'plugin'){
		if(!$upgrader->credentials($form_url, WP_CONTENT_DIR . KBUpgrader::$TYPE_PLUGINS)) exit;
		$download_file = $upgrader->download($download_url, $download_version, KBStore::getAccessToken());
		$install_result = $upgrader->install($download_file, KBUpgrader::$TYPE_PLUGINS);
		die('<script>alert("플러그인 설치가 완료 되었습니다. 플러그인을 활성화해주세요.");window.location.href="' . admin_url('plugins.php') . '"</script>');
	}
	else if($action == 'theme'){
		if(!$upgrader->credentials($form_url, WP_CONTENT_DIR . KBUpgrader::$TYPE_THEMES)) exit;
		$download_file = $upgrader->download($download_url, $download_version, KBStore::getAccessToken());
		$install_result = $upgrader->install($download_file, KBUpgrader::$TYPE_THEMES);
		die('<script>alert("테마 설치가 완료 되었습니다. 테마를 선택해주세요.");window.location.href="' . admin_url('themes.php') . '"</script>');
	}
	else if($action == 'kboard-skin'){
		if(!$upgrader->credentials($form_url, WP_CONTENT_DIR . KBUpgrader::$TYPE_KBOARD_SKIN)) exit;
		$download_file = $upgrader->download($download_url, $download_version, KBStore::getAccessToken());
		$install_result = $upgrader->install($download_file, KBUpgrader::$TYPE_KBOARD_SKIN);
		die('<script>alert("스킨 설치가 완료 되었습니다. 게시판 목록->관리 페이지에서 스킨을 선택해주세요.");window.location.href="' . admin_url('admin.php?page=kboard_store') . '"</script>');
	}
	else if($action == 'comments-skin'){
		if(!$upgrader->credentials($form_url, WP_CONTENT_DIR . KBUpgrader::$TYPE_COMMENTS_SKIN)) exit;
		$download_file = $upgrader->download($download_url, $download_version, KBStore::getAccessToken());
		$install_result = $upgrader->install($download_file, KBUpgrader::$TYPE_COMMENTS_SKIN);
		die('<script>alert("스킨 설치가 완료 되었습니다. 게시판 목록->관리 페이지에서 스킨을 선택해주세요.");window.location.href="' . admin_url('admin.php?page=kboard_store') . '"</script>');
	}
	else{
		die('<script>alert("설치에 실패 했습니다.");window.location.href="' . admin_url('admin.php?page=kboard_dashboard') . '"</script>');
	}
}

/*
 * 전체 게시글 리스트
 */
function kboard_content_list(){
	include_once 'class/KBContentListTable.class.php';
	$table = new KBContentListTable();
	if(isset($_POST['uid'])){
		$action = $table->current_action();
		$content = new KBContent();
		if($action == 'delete'){
			foreach($_POST['uid'] as $key=>$value){
				$content->initWithUID($value);
				$content->remove();
			}
		}
		else if($action == 'trash'){
			foreach($_POST['uid'] as $key=>$value){
				$content->initWithUID($value);
				$content->status = 'trash';
				$content->updateContent();
			}
		}
		else if($action == 'published'){
			foreach($_POST['uid'] as $key=>$value){
				$content->initWithUID($value);
				$content->status = '';
				$content->updateContent();
			}
		}
	}
	$table->prepare_items();
	wp_enqueue_style('jquery-flick-style', KBOARD_URL_PATH.'/template/css/jquery-ui.css', array(), '1.12.1');
	wp_enqueue_style('jquery-timepicker', KBOARD_URL_PATH.'/template/css/jquery.timepicker.css', array(), KBOARD_VERSION);
	include_once 'pages/kboard_content_list.php';
}

/*
 *
 */
function kboard_admin_view(){
	$screen = get_current_screen();
	$board_id = intval(str_replace('kboard_page_kboard_admin_view_', '', $screen->id));
	$board = new KBoard($board_id);
	include_once 'pages/kboard_admin_view.php';
}

/*
 * 게시판 생성 숏코드
 */
add_shortcode('kboard', 'kboard_builder');
function kboard_builder($args){
	if(!$args['id']) return 'KBoard 알림 :: id=null, 아이디값은 필수입니다.';

	$board = new KBoard();
	$board->setID($args['id']);

	if($board->uid){
		$board_builder = new KBoardBuilder($board->uid);
		$board_builder->setSkin($board->skin);
		$board_builder->setRpp($board->page_rpp);
		$board_builder->board = $board;

		if(isset($args['category1']) && $args['category1']){
			$board_builder->category1 = $args['category1'];
		}
		if(isset($args['category2']) && $args['category2']){
			$board_builder->category2 = $args['category2'];
		}

		$kboard = $board_builder->create();
		return $kboard;
	}
	else{
		return 'KBoard 알림 :: id='.$args['id'].', 생성되지 않은 게시판입니다.';
	}
}

/*
 * 선택된 페이지에 자동으로 게시판 생성
 */
add_filter('the_content', 'kboard_auto_builder');
function kboard_auto_builder($content){
	global $post, $wpdb;
	if(isset($post->ID) && is_page($post->ID)){
		$board_id = $wpdb->get_var("SELECT `board_id` FROM `{$wpdb->prefix}kboard_board_meta` WHERE `key`='auto_page' AND `value`='$post->ID'");
		if($board_id) return $content . kboard_builder(array('id'=>$board_id));
	}
	return $content;
}

/*
 * 최신글 생성 숏코드
 */
add_shortcode('kboard_latest', 'kboard_latest_shortcode');
function kboard_latest_shortcode($args){
	if(!$args['id']) return 'KBoard 알림 :: id=null, 아이디값은 필수입니다.';
	else if(!$args['url']) return 'KBoard 알림 :: url=null, 페이지 주소는 필수입니다.';
	if(!$args['rpp']) $args['rpp'] = 5;

	$board = new KBoard();
	$board->setID($args['id']);

	if($board->uid){
		$board_builder = new KBoardBuilder($board->uid, true);
		$board_builder->setSkin($board->skin);
		$board_builder->setRpp($args['rpp']);
		$board_builder->setURL($args['url']);
		$board_builder->board = $board;

		if(isset($args['category1']) && $args['category1']){
			$board_builder->category1 = $args['category1'];
		}
		if(isset($args['category2']) && $args['category2']){
			$board_builder->category2 = $args['category2'];
		}

		$kboard_latest = $board_builder->createLatest();
		return $kboard_latest;
	}
	else{
		return 'KBoard 알림 :: id='.$args['id'].', 생성되지 않은 게시판입니다.';
	}
}

/*
 * 최신글 뷰 생성 숏코드
 */
add_shortcode('kboard_latestview', 'kboard_latestview_shortcode');
function kboard_latestview_shortcode($args){
	if(!$args['id']) return 'KBoard 알림 :: id=null, 아이디값은 필수입니다.';

	$latestview = new KBLatestview($args['id']);
	if($latestview->uid){
		$board_builder = new KBoardBuilder($latestview->getLinkedBoard(), true);
		$board_builder->setSkin($latestview->skin);
		$board_builder->setRpp($latestview->rpp);
		$board_builder->setSorting($latestview->sort);
		$kboard_latest = $board_builder->createLatest();
		return $kboard_latest;
	}
	else{
		return 'KBoard 알림 :: id='.$args['id'].', 생성되지 않은 최신글 뷰 입니다.';
	}
}

/*
 * 언어 파일 추가
 */
add_action('plugins_loaded', 'kboard_languages');
function kboard_languages(){
	$domain = 'kboard';
	$locale = apply_filters('plugin_locale', get_locale(), $domain);
	load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)).'/languages/');
}

/*
 * 비동기 게시판 빌더
 */
function kboard_ajax_builder(){
	if(!isset($_SESSION['kboard_board_id']) || !$_SESSION['kboard_board_id']) die('KBoard 알림 :: id=null, 아이디값은 필수입니다.');

	$board = new KBoard();
	$board->setID($_SESSION['kboard_board_id']);

	if($board->uid){
		$board_builder = new KBoardBuilder($board->uid);
		$board_builder->setSkin($board->skin);
		$board_builder->setRpp($board->page_rpp);
		$board_builder->board = $board;
		die($board_builder->getJsonList());
	}
	else{
		die('KBoard 알림 :: id='.$_SESSION['kboard_board_id'].', 생성되지 않은 게시판입니다.');
	}
}

/*
 * 관리자 알림 출력
 */
add_action('admin_notices', 'kboard_admin_notices');
function kboard_admin_notices(){
	if(current_user_can('activate_plugins')){
		if(!is_writable(WP_CONTENT_DIR.'/uploads')){
			echo '<div class="notice notice-error"><p>KBoard 게시판 : 디렉토리 '.WP_CONTENT_DIR.'/uploads'.'에 파일을 쓸 수 없습니다. 디렉토리가 존재하지 않거나 쓰기 권한이 있는지 확인해주세요. - <a href="http://www.cosmosfarm.com/threads" onclick="window.open(this.href);return false;">이 알림에 대해서 질문하기</a></p></div>';
		}
		$upgrader = KBUpgrader::getInstance();
		if(version_compare(KBOARD_VERSION, $upgrader->getLatestVersion()->kboard, '<')){
			echo '<div class="notice notice-info is-dismissible"><p>KBoard 게시판 : '.$upgrader->getLatestVersion()->kboard.' 버전으로 업그레이드가 가능합니다. - <a href="'.admin_url('/admin.php?page=kboard_dashboard').'">대시보드로 이동</a> 또는 <a href="http://www.cosmosfarm.com/products/kboard" onclick="window.open(this.href);return false;">홈페이지 열기</a></p></div>';
		}
	}
}

/*
 * 스크립트와 스타일 파일 등록
 */
add_action('wp_enqueue_scripts', 'kboard_style', 999);
function kboard_style(){
	wp_enqueue_script('jquery');
	
	// KBoard 미디어 추가 스타일 속성 등록
	wp_enqueue_style('kboard-editor-media', KBOARD_URL_PATH . '/template/css/editor_media.css', array(), KBOARD_VERSION);
	
	// font-awesome 출력
	if(!get_option('kboard_fontawesome')){
		global $wp_styles;
		wp_enqueue_style('font-awesome', KBOARD_URL_PATH . '/font-awesome/css/font-awesome.min.css', array(), KBOARD_VERSION);
		wp_enqueue_style('font-awesome-ie7', KBOARD_URL_PATH . '/font-awesome/css/font-awesome-ie7.min.css', array(), KBOARD_VERSION);
		$wp_styles->add_data('font-awesome-ie7', 'conditional', 'lte IE 7');
	}

	// 활성화된 스킨의 style.css 등록
	$skin = KBoardSkin::getInstance();
	foreach($skin->getActiveList() as $skin_name){
		wp_enqueue_style("kboard-skin-{$skin_name}", $skin->url($skin_name, 'style.css'), array(), KBOARD_VERSION);
	}
}

/*
 * 스크립트와 스타일 파일 등록
 */
add_action('wp_enqueue_scripts', 'kboard_scripts', 999);
function kboard_scripts(){
	wp_enqueue_script('jquery');
	wp_enqueue_script('kboard-script', KBOARD_URL_PATH . '/template/js/script.js', array(), KBOARD_VERSION, true);
	
	if(kboard_use_recaptcha()){
		wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js');
	}
	
	// 설정 등록
	$localize = array(
			'home_url' => home_url('/', 'relative'),
			'site_url' => site_url('/', 'relative'),
			'post_url' => admin_url('admin-post.php'),
			'alax_url' => admin_url('admin-ajax.php'),
			'plugin_url' => KBOARD_URL_PATH,
			'media_group' => uniqid(),
	);
	wp_localize_script('kboard-script', 'kboard_settings', $localize);

	// 번역 등록
	$localize = array(
			'kboard_add_media' => __('KBoard Add Media', 'kboard'),
			'next' => __('Next', 'kboard'),
			'prev' => __('Prev', 'kboard'),
			'please_enter_the_title' => __('Please enter the title.', 'kboard'),
			'please_enter_the_author' => __('Please enter the author.', 'kboard'),
			'please_enter_the_password' => __('Please enter the password.', 'kboard'),
			'please_enter_the_CAPTCHA' => __('Please enter the CAPTCHA.', 'kboard'),
			'please_enter_the_name' => __('Please enter the name.', 'kboard'),
			'please_enter_the_email' => __('Please enter the email.', 'kboard'),
			'you_have_already_voted' => __('You have already voted.', 'kboard'),
			'please_wait' => __('Please wait.', 'kboard'),
			'newest' => __('Newest', 'kboard'),
			'best' => __('Best', 'kboard'),
			'updated' => __('Updated', 'kboard'),
			'viewed' => __('Viewed', 'kboard'),
			'yes' => __('Yes', 'kboard'),
			'no' => __('No', 'kboard'),
			'did_it_help' => __('Did it help?', 'kboard'),
	);
	wp_localize_script('kboard-script', 'kboard_localize_strings', $localize);
}

/*
 * 관리자 페이지 스타일 파일을 출력한다.
 */
add_action('admin_enqueue_scripts', 'kboard_admin_style', 999);
function kboard_admin_style(){
	wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_script('kboard-cosmosfarm-apis', KBOARD_URL_PATH . '/pages/cosmosfarm-apis.js', array(), KBOARD_VERSION);
	wp_enqueue_script('jquery-timepicker', KBOARD_URL_PATH . '/template/js/jquery.timepicker.js', array(), KBOARD_VERSION);
	wp_enqueue_style('kboard-admin', KBOARD_URL_PATH . '/pages/kboard-admin.css', array(), KBOARD_VERSION);
}

/*
 * 스킨의 functions.php 파일을 실행한다.
 */
add_action('init', 'kboard_skin_functions');
function kboard_skin_functions(){
	$skin = KBoardSkin::getInstance();
	foreach($skin->getActiveList() as $skin_name){
		$skin->loadFunctions($skin_name);
	}
}

/*
 * 툴바에 게시판 설정페이지 링크를 추가한다.
 */
add_action('admin_bar_menu', 'kboard_add_toolbar_link', 999);
function kboard_add_toolbar_link($wp_admin_bar){
	global $post, $wpdb;
	if(!is_admin() && current_user_can('activate_plugins') && isset($post->ID) && $post->ID){
		$board_id = $wpdb->get_var("SELECT `board_id` FROM `{$wpdb->prefix}kboard_board_meta` WHERE `key`='auto_page' AND `value`='{$post->ID}'");
		if($board_id){
			$args = array(
					'id'    => 'kboard-setting-page',
					'title' => 'KBoard 게시판 설정',
					'href'  => admin_url("admin.php?page=kboard_list&board_id={$board_id}"),
					'meta'  => array('class' => 'kboard-setting-page')
			);
			$wp_admin_bar->add_node($args);
		}
	}
}

/*
 * head 태그 사이에 내용을 출력한다.
 */
add_action('wp_head', 'kboard_head', 999);
add_action('kboard_iframe_head', 'kboard_head');
function kboard_head(){
	wp_print_head_scripts();
	print_late_styles();
	
	$custom_css = get_option('kboard_custom_css');
	if($custom_css){
		echo "<style type=\"text/css\">{$custom_css}</style>";
	}
}

/*
 * 활성화
 */
register_activation_hook(__FILE__, 'kboard_activation');
function kboard_activation($networkwide){
	global $wpdb;

	if(function_exists('is_multisite') && is_multisite()){
		if($networkwide){
			$old_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col("SELECT `blog_id` FROM {$wpdb->blogs}");
			foreach($blogids as $blog_id){
				switch_to_blog($blog_id);
				kboard_activation_execute();
			}
			switch_to_blog($old_blog);
			return;
		}
	}
	kboard_activation_execute();
}

/*
 * 활성화 실행
 */
function kboard_activation_execute(){
	global $wpdb;

	/*
	 * KBoard 2.5
	 * table 이름에 prefix 추가
	 */
	$tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
	foreach($tables as $table){
		$table = $table[0];
		$prefix = substr($table, 0, 7);
		if($prefix == 'kboard_') $wpdb->query("RENAME TABLE `{$table}` TO `{$wpdb->prefix}{$table}`");
	}
	unset($tables, $table, $prefix);

	$charset_collate = $wpdb->get_charset_collate();

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_board_setting` (
	`uid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`board_name` varchar(127) NOT NULL,
	`skin` varchar(127) NOT NULL,
	`use_comment` varchar(5) NOT NULL,
	`use_editor` varchar(5) NOT NULL,
	`permission_read` varchar(127) NOT NULL,
	`permission_write` varchar(127) NOT NULL,
	`admin_user` text NOT NULL,
	`use_category` varchar(5) NOT NULL,
	`category1_list` text NOT NULL,
	`category2_list` text NOT NULL,
	`page_rpp` int(10) unsigned NOT NULL,
	`created` char(14) NOT NULL,
	PRIMARY KEY (`uid`)
	) {$charset_collate};");

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_board_attached` (
	`uid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`content_uid` bigint(20) unsigned NOT NULL,
	`file_key` varchar(127) NOT NULL,
	`date` char(14) NOT NULL,
	`file_path` varchar(127) NOT NULL,
	`file_name` varchar(127) NOT NULL,
	PRIMARY KEY (`uid`),
	KEY `content_uid` (`content_uid`)
	) {$charset_collate};");

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_board_content` (
	`uid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`board_id` bigint(20) unsigned NOT NULL,
	`parent_uid` bigint(20) unsigned DEFAULT NULL,
	`member_uid` bigint(20) unsigned DEFAULT NULL,
	`member_display` varchar(127) DEFAULT NULL,
	`title` varchar(127) NOT NULL,
	`content` longtext NOT NULL,
	`date` char(14) DEFAULT NULL,
	`update` char(14) DEFAULT NULL,
	`view` int(10) unsigned DEFAULT NULL,
	`comment` int(10) unsigned DEFAULT NULL,
	`like` int(10) unsigned DEFAULT NULL,
	`unlike` int(10) unsigned DEFAULT NULL,
	`vote` int(11) DEFAULT NULL,
	`thumbnail_file` varchar(127) DEFAULT NULL,
	`thumbnail_name` varchar(127) DEFAULT NULL,
	`category1` varchar(127) DEFAULT NULL,
	`category2` varchar(127) DEFAULT NULL,
	`secret` varchar(5) DEFAULT NULL,
	`notice` varchar(5) DEFAULT NULL,
	`search` char(1) DEFAULT NULL,
	`status` varchar(20) DEFAULT NULL,
	`password` varchar(127) DEFAULT NULL,
	PRIMARY KEY (`uid`),
	KEY `board_id` (`board_id`),
	KEY `parent_uid` (`parent_uid`),
	KEY `member_uid` (`member_uid`),
	KEY `date` (`date`),
	KEY `update` (`update`),
	KEY `view` (`view`),
	KEY `vote` (`vote`),
	KEY `category1` (`category1`),
	KEY `category2` (`category2`),
	KEY `notice` (`notice`),
	KEY `status` (`status`)
	) {$charset_collate};");

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_board_option` (
	`uid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`content_uid` bigint(20) unsigned NOT NULL,
	`option_key` varchar(127) NOT NULL,
	`option_value` text NOT NULL,
	PRIMARY KEY (`uid`),
	UNIQUE KEY `content_uid` (`content_uid`,`option_key`)
	) {$charset_collate};");

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_board_meta` (
	`board_id` bigint(20) unsigned NOT NULL,
	`key` varchar(127) NOT NULL,
	`value` text NOT NULL,
	UNIQUE KEY `meta_index` (`board_id`,`key`)
	) {$charset_collate};");

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_board_latestview` (
	`uid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(127) NOT NULL,
	`skin` varchar(127) NOT NULL,
	`rpp` int(10) unsigned NOT NULL,
	`sort` varchar(20) NOT NULL,
	`created` char(14) NOT NULL,
	PRIMARY KEY (`uid`)
	) {$charset_collate};");

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_board_latestview_link` (
	`latestview_uid` bigint(20) unsigned NOT NULL,
	`board_id` bigint(20) unsigned NOT NULL,
	UNIQUE KEY `latestview_uid` (`latestview_uid`,`board_id`)
	) {$charset_collate};");

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_meida` (
	`uid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`media_group` varchar(127) DEFAULT NULL,
	`date` char(14) DEFAULT NULL,
	`file_path` varchar(127) DEFAULT NULL,
	`file_name` varchar(127) DEFAULT NULL,
	PRIMARY KEY (`uid`),
	KEY `media_group` (`media_group`)
	) {$charset_collate};");

	$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}kboard_meida_relationships` (
	`content_uid` bigint(20) unsigned NOT NULL,
	`media_uid` bigint(20) unsigned NOT NULL,
	UNIQUE KEY `content_uid` (`content_uid`,`media_uid`),
	KEY `media_uid` (`media_uid`)
	) {$charset_collate};");

	/*
	 * KBoard 2.9
	 * kboard_board_meta 테이블의 value 컬럼 데이터형 text로 변경
	 */
	list($name, $type) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_meta` `value`", ARRAY_N);
	if(stristr($type, 'varchar')){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_meta` CHANGE `value` `value` TEXT NOT NULL");
	}
	unset($name, $type);

	/*
	 * KBoard 3.5
	 * kboard_board_content 테이블에 search 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `search`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD `search` char(1) DEFAULT NULL AFTER `notice`");
	}
	unset($name);

	/*
	 * KBoard 4.1
	 * kboard_board_content 테이블에 comment 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `comment`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD `comment` int(10) unsigned DEFAULT NULL AFTER `view`");
	}
	if(defined('KBOARD_COMMNETS_VERSION')){
		// comment 컬럼에 댓글 입력 숫자를 등록한다.
		$contents = $wpdb->get_results("SELECT `uid` FROM `{$wpdb->prefix}kboard_board_content` WHERE 1");
		foreach($contents as $content){
			$count = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}kboard_comments` WHERE `content_uid`='".intval($content->uid)."'");
			$wpdb->query("UPDATE `{$wpdb->prefix}kboard_board_content` SET `comment`='$count' WHERE `uid`='".intval($content->uid)."'");
		}
	}
	unset($name, $count);

	/*
	 * KBoard 4.2
	 * kboard_board_content 테이블에 parent_uid 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `parent_uid`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD `parent_uid` BIGINT UNSIGNED NOT NULL AFTER `board_id`");
	}
	unset($name);

	/*
	 * KBoard 4.5
	 * kboard_board_meta 테이블의 content 컬럼 데이터형 longtext로 변경
	 */
	list($name, $type) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `content`", ARRAY_N);
	if(stristr($type, 'text')){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` CHANGE `content` `content` LONGTEXT NOT NULL");
	}
	unset($name, $type);

	/*
	 * KBoard 4.5
	 * kboard_board_content 테이블에 like 컬럼 추가
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `like`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD `like` int(10) unsigned DEFAULT NULL AFTER `comment`");
	}
	unset($name);

	/*
	 * KBoard 5.1
	 * kboard_board_option 테이블에 content_uid 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_option` WHERE `Key_name`='content_uid'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_option` ADD UNIQUE (`content_uid`, `option_key`)");
	}
	unset($index);

	/*
	 * KBoard 5.1
	 * kboard_board_content 테이블에 unlike 컬럼 추가
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `unlike`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD `unlike` int(10) UNSIGNED NULL AFTER `like`");
	}
	unset($name);

	/*
	 * KBoard 5.1
	 * kboard_board_content 테이블에 vote 컬럼 추가
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `vote`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD `vote` int(11) NULL AFTER `unlike`");
	}
	unset($name);

	/*
	 * KBoard 5.1
	 * kboard_board_content 테이블에 parent_uid 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='parent_uid'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`parent_uid`)");
	}
	unset($index);

	/*
	 * KBoard 5.1
	 * kboard_board_attached 테이블에 content_uid 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_attached` WHERE `Key_name`='content_uid'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_attached` ADD INDEX (`content_uid`)");
	}
	unset($index);

	/*
	 * KBoard 5.2
	 * kboard_board_content 테이블에 update 컬럼 추가
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `update`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD `update` char(14) NULL AFTER `date`");
		$wpdb->query("UPDATE `{$wpdb->prefix}kboard_board_content` SET `update`=`date` WHERE 1");
	}
	unset($name);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 status 컬럼 추가
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `status`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD `status` varchar(20) DEFAULT NULL AFTER `search`");
	}
	unset($name);
	
	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 member_uid 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='member_uid'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`member_uid`)");
	}
	unset($index);
	
	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 date 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='date'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`date`)");
	}
	unset($index);
	
	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 update 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='update'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`update`)");
	}
	unset($index);
	
	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 view 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='view'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`view`)");
	}
	unset($index);
	
	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 vote 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='vote'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`vote`)");
	}
	unset($index);
	
	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 category1 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='category1'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`category1`)");
	}
	unset($index);
	
	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 category2 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='category2'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`category2`)");
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 notice 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='notice'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`notice`)");
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 status 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='status'");
	if(!count($index)){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_content` ADD INDEX (`status`)");
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_latestview 테이블에 sort 컬럼 추가
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_latestview` `sort`", ARRAY_N);
	if(!$name){
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}kboard_board_latestview` ADD `sort` varchar(20) NOT NULL AFTER `rpp`");
	}
	unset($name);
	
	/*
	 * KBoard 5.3
	 * kboard_board_meta 테이블에서 게시판의 total, list_total 데이터를 지움으로써 초기화한다.
	 */
	$wpdb->query("DELETE FROM `{$wpdb->prefix}kboard_board_meta` WHERE `key`='total' OR `key`='list_total'");
}

/*
 * 비활성화
 */
register_deactivation_hook(__FILE__, 'kboard_deactivation');
function kboard_deactivation($networkwide){

}

/*
 * 언인스톨
 */
register_uninstall_hook(__FILE__, 'kboard_uninstall');
function kboard_uninstall(){
	global $wpdb;
	if(function_exists('is_multisite') && is_multisite()){
		$old_blog = $wpdb->blogid;
		$blogids = $wpdb->get_col("SELECT `blog_id` FROM {$wpdb->blogs}");
		foreach($blogids as $blog_id){
			switch_to_blog($blog_id);
			kboard_uninstall_execute();
		}
		switch_to_blog($old_blog);
		return;
	}
	kboard_uninstall_execute();
}

/*
 * 언인스톨 실행
 */
function kboard_uninstall_execute(){
	global $wpdb;
	$wpdb->query("DROP TABLE
			`{$wpdb->prefix}kboard_board_attached`,
			`{$wpdb->prefix}kboard_board_content`,
			`{$wpdb->prefix}kboard_board_option`,
			`{$wpdb->prefix}kboard_board_setting`,
			`{$wpdb->prefix}kboard_board_meta`,
			`{$wpdb->prefix}kboard_board_latestview`,
			`{$wpdb->prefix}kboard_board_latestview_link`,
			`{$wpdb->prefix}kboard_meida`,
			`{$wpdb->prefix}kboard_meida_relationships`
			");
}

/*
 * 시스템 업데이트
 */
add_action('admin_init', 'kboard_system_update');
function kboard_system_update(){
	global $wpdb;

	// 시스템 업데이트를 이미 진행 했다면 중단한다.
	if(version_compare(KBOARD_VERSION, get_option('kboard_version'), '<=')) return;

	// 시스템 업데이트를 확인하기 위해서 버전 등록
	if(get_option('kboard_version') !== false){
		update_option('kboard_version', KBOARD_VERSION);

		// 관리자 알림
		add_action('admin_notices', create_function('', "echo '<div class=\"updated\"><p>KBoard 게시판 : '.KBOARD_VERSION.' 버전으로 업데이트 되었습니다. - <a href=\"http://www.cosmosfarm.com/products/kboard\" onclick=\"window.open(this.href);return false;\">홈페이지 열기</a></p></div>';"));
	}
	else{
		add_option('kboard_version', KBOARD_VERSION, null, 'no');
	}

	$networkwide = is_plugin_active_for_network(__FILE__);

	/*
	 * KBoard 2.0
	 * kboard_board_meta 테이블 추가 생성
	 */
	if(!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}kboard_board_meta'")){
		kboard_activation($networkwide);
		return;
	}

	/*
	 * KBoard 2.5
	 * table 이름에 prefix 추가
	 */
	$tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
	foreach($tables as $table){
		$table = $table[0];
		$prefix = substr($table, 0, 7);
		if($prefix == 'kboard_'){
			kboard_activation($networkwide);
			return;
		}
	}
	unset($tables, $table, $prefix);

	/*
	 * KBoard 2.9
	 * kboard_board_meta 테이블의 value 컬럼 데이터형 text로 변경
	 */
	list($name, $type) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_meta` `value`", ARRAY_N);
	if(stristr($type, 'varchar')){
		kboard_activation($networkwide);
		return;
	}
	unset($name, $type);

	/*
	 * KBoard 3.2
	 * captcha.php 파일 제거
	 */
	@unlink(KBOARD_DIR_PATH . '/execute/captcha.php');

	/*
	 * KBoard 3.5
	 * kboard_board_content 테이블에 search 컬럼 생성 확인
	 * kboard_board_latestview, kboard_board_latestview_link 테이블 추가 생성
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `search`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	if(!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}kboard_board_latestview'")){
		kboard_activation($networkwide);
		return;
	}
	unset($name);

	/*
	 * KBoard 3.5
	 * 파일 제거
	 */
	@unlink(KBOARD_DIR_PATH . '/BoardBuilder.class.php');
	@unlink(KBOARD_DIR_PATH . '/Content.class.php');
	@unlink(KBOARD_DIR_PATH . '/ContentList.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBBackup.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBCaptcha.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBFileHandler.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBMail.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBoard.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBoardMeta.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBoardSkin.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBSeo.class.php');
	@unlink(KBOARD_DIR_PATH . '/KBUpgrader.class.php');
	@unlink(KBOARD_DIR_PATH . '/Pagination.helper.php');
	@unlink(KBOARD_DIR_PATH . '/Security.helper.php');
	@unlink(KBOARD_DIR_PATH . '/Url.class.php');
	@unlink(KBOARD_DIR_PATH . '/XML2Array.class.php');

	/*
	 * KBoard 4.1
	 * kboard_board_content 테이블에 comment 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `comment`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	unset($name);

	/*
	 * KBoard 4.2
	 * kboard_board_content 테이블에 parent_uid 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `parent_uid`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	unset($name);

	/*
	 * KBoard 4.5
	 * kboard_board_meta 테이블의 content 컬럼 데이터형 longtext로 변경
	 */
	list($name, $type) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `content`", ARRAY_N);
	if(stristr($type, 'text')){
		kboard_activation($networkwide);
		return;
	}
	unset($name, $type);

	/*
	 * KBoard 4.5
	 * kboard_board_content 테이블에 like 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `like`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	unset($name);

	/*
	 * KBoard 5.0
	 * kboard_meida, kboard_meida_relationships 테이블 추가 생성
	 */
	if(!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}kboard_meida'")){
		kboard_activation($networkwide);
		return;
	}

	/*
	 * KBoard 5.1
	 * kboard_board_option 테이블에 content_uid 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_option` WHERE `Key_name`='content_uid'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.1
	 * kboard_board_content 테이블에 unlike 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `unlike`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	unset($name);

	/*
	 * KBoard 5.1
	 * kboard_board_content 테이블에 vote 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `vote`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	unset($name);

	/*
	 * KBoard 5.1
	 * kboard_board_content 테이블에 parent_uid 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='parent_uid'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.1
	 * kboard_board_attached 테이블에 content_uid 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_attached` WHERE `Key_name`='content_uid'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.2
	 * kboard_board_content 테이블에 update 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `update`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	unset($name);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 status 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_content` `status`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	unset($name);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 member_uid 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='member_uid'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 date 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='date'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 update 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='update'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 view 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='view'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 view 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='vote'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 category1 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='category1'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 category2 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='category2'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 notice 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='notice'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_content 테이블에 status 인덱스 추가
	 */
	$index = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}kboard_board_content` WHERE `Key_name`='status'");
	if(!count($index)){
		kboard_activation($networkwide);
		return;
	}
	unset($index);

	/*
	 * KBoard 5.3
	 * kboard_board_latestview 테이블에 sort 컬럼 생성 확인
	 */
	list($name) = $wpdb->get_row("DESCRIBE `{$wpdb->prefix}kboard_board_latestview` `sort`", ARRAY_N);
	if(!$name){
		kboard_activation($networkwide);
		return;
	}
	unset($name);
}
?>