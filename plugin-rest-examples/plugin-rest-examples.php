<?php
/*
Plugin Name: Plugin REST Examples
Plugin URI: https://github.com/AlexandrSgadlev/plugin-rest-examples/
Description: Это пример использования WP REST API в плагине.
Author: Alex Sg
Author URI: https://github.com/AlexandrSgadlev
Version: 1.0.0
Text Domain: plugin-rest-examples
Domain Path: /languages/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/



// Для маршрута на основе Класса контроллера (ООП)
// https://wp-kama.ru/handbook/rest/extending/routes-endpoints
include_once(plugin_dir_path(__FILE__) . '/custom-class-rest-controller.php');



class Plugin_Rest_Examples{
	
	// Постоянные переменные
	public static $basename;
	public static $nombre;
	public static $url;
	public static $url_path;
	
	// Изменяймые переменные
	public string $url_hostname;		 // адрес сайта
	public string $url_api_namespace;    // пространства имен
	public string $url_rest_route;		 // маршрут
	public string $url_api;      		 // полный адресс запроса
	public string $user_login;  		 // логин
	public string $user_api_pass;		 // пароль приложения
	
	
	
	// Инициализация
	public function __construct() {

		// Параметры класса
		$this->asignar_variables_estaticas();

		/* CPT */
			// 'show_in_rest', 'rest_base', 'rest_controller_class', 'rest_namespace' 
			add_action( 'init', array( $this, 'custom_post_type' ) );


		/* Создание Маршрутов */
			// Простой пример маршрута
			add_action( 'rest_api_init', array( $this, 'custom_rest_route' ) );


		/* Проверка поддержки rest-api */
			if( $this->check_rest_api($this->url_api) === false ){
				return;
			}


		/* Отправляем запросы */
			// Для JS
			// Обратите внимание, что такой JS должен подключатся на страницах 
			// куда имеют доступ только админы иначе любой сможет открыть js файл и посмотреть логин и пароль.
			// add_action( 'wp_footer', array( $this, 'js_footer' ) );		
			
			// Для PHP
			// Запрос к API внутри WordPress
			add_action( 'wp_head', array( $this, 'rest_api_this_wp' ) );
			// Запрос к API внешнего WordPress
			//$this->rest_api_to_wp($this->url_api);


		/* Библиотека WP API для удобной работы с rest api через js */
			// https://wp-kama.ru/handbook/rest/basic/wp-api-js
			// Чтобы создать/изменить запись (пост), убедитесь, что вы авторизованы и у вас есть достаточные для этого права.
			// wp_enqueue_script( 'wp-api' );
			// Через зависимость от нашего скрипта, например, my_script:
			// wp_enqueue_script( 'my_script', 'path/to/my/script', array('wp-api') );

	}


	/**
	 * Отправляем запросы
	 */	
	public function custom_rest_route()
	{
		
		function endpoint_author_posts_get(WP_REST_Request $request){
			$posts = get_posts( array(
				'author' => (int) $request['id'],
			) );

			if ( empty( $posts ) )
				return new WP_Error( 'no_author_posts', 'Записей не найдено', [ 'status' => 404 ] );

			return wp_send_json($posts);				
		}
		
		function endpoint_author_posts_post( WP_REST_Request $request ){
			$response = array(
				'arg_str' => $request->get_param('arg_str'),
				'arg_int' => $request->get_param('arg_int')
			);

			return $response;
		}		
		
		register_rest_route( 'plugin-rest-examples/v1', '/author-posts/(?P<id>\d+)', array(
			array(
				'methods'  => 'GET',
				'callback' => 'endpoint_author_posts_get',
			),
			array(
				'methods'  => 'POST',
				'callback' => 'endpoint_author_posts_post',
				'args'     => array(
					'title' => array(
						'type'     => 'string', // значение параметра должно быть строкой
						'required' => true,     // параметр обязательный
					),
					'arg_int' => array(
						'type'    => 'integer', // значение параметра должно быть числом
						'default' => 10,        // значение по умолчанию = 10
					),
				),
				'permission_callback' => function( $request ){
					// только авторизованный юзер имеет доступ к эндпоинту
					return is_user_logged_in();
				},
			)
		) );
		
		
		
	}
	

	/**
	 * Отправляем запросы к WP
	 */	
	public function rest_api_to_wp(string $url)
	{
		$response = wp_remote_request( $url,
			[
				'method'    => 'GET',
				'headers'   => [
					'Authorization' => 'Basic ' . base64_encode( $this->user_login . ':' . $this->user_api_pass )
				]
			]
		);		
		//var_dump($response);
	}

	
	/**
	 * Отправляем запросы внутри WP
	 */	
	public function rest_api_this_wp(string $url)
	{
		$request = new WP_REST_Request( 'GET', ( '/' . $this->url_api_namespace . '/' . $this->url_rest_route ) );
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			$error = $response->as_error();
			$message = $error->get_error_message();
			$error_data = $error->get_error_data();
			$status = isset( $error_data['status'] ) ? $error_data['status'] : 500;
			return;
		}
		$data = $response->get_data();
		$headers = $response->get_headers();
		//var_dump($data);
	}
	
	
	/**
	 * Отправляем запросы
	 */	
	public function js_footer() 
	{
		/*
		 Запрос к сайту с текущим доменом.
		 Если пользователь аторизован.
		 IMPORTANT! wp_localize_script()  MUST be called after the script has been registered using wp_register_script()  or wp_enqueue_script().
		*/
		wp_register_script( 'wp-api-cpt-cookie', self::$url . 'assets/js/wp-api-cpt-cookie.js', array( 'jquery' ), '', false );
		wp_localize_script( 'wp-api-cpt-cookie', 'wpApiSettings', array(
			'root' => esc_url_raw( rest_url() ),
			'url' => $this->url_api,
			'nonce' => wp_create_nonce( 'wp_rest' )
		));			
		//wp_enqueue_script( 'wp-api-cpt-cookie' );


		/*
		 Запрос к сайту на wp по секретному паролю.
		 Только для https.
		 IMPORTANT! wp_localize_script()  MUST be called after the script has been registered using wp_register_script()  or wp_enqueue_script().
		*/
		wp_register_script( 'wp-api-cpt-app', self::$url . 'assets/js/wp-api-cpt-app.js', array( 'jquery' ), '', false );	
		wp_localize_script( 'wp-api-cpt-app', 'wpApiSettings', array(
			'user_name' => $this->user_login,
			'user_pass' => $this->user_api_pass,
			'url' => $this->url_api,
			'root' => esc_url_raw( rest_url() ),
		));	
		//wp_enqueue_script( 'wp-api-cpt-app' );

	}	


	/**
	 * Параметры
	 */	
	public function asignar_variables_estaticas()
	{
		self::$basename = plugin_basename(__FILE__);
		self::$nombre = dirname(self::$basename);
		self::$url = plugin_dir_url(__FILE__);
		self::$url_path = plugin_dir_path(__FILE__);
		
		$this->url_hostname = 'https://site/';
		$this->url_api_namespace = 'wp/v2';
		$this->url_rest_route = 'project';
		$this->url_api = $this->url_hostname . 'wp-json/' . $this->url_api_namespace . '/' . $this->url_rest_route . '/';
		$this->user_login = 'login';
		$this->user_api_pass = '1234 EFGH 1234 ijkl MNOP 6789';
	}

	
	/**
	 * Проверка поддержки rest-api
	 */	
	public function check_rest_api(string $url)
	{
		if( !isset($url) ){return false;}

		// Проверка на наличие сертификата
		$p_url = parse_url($url);
		// if ($p_url['scheme'] != 'https'){return false;};


		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_NOBODY, 1);
		$output = curl_exec($curl);

		if(stripos($output, 'api.w.org') === false){
			return false;
		}else{
			return true;
		}	
	}


	/**
	 * CPT
	 */	
	public function custom_post_type() 
	{

		// Рекомендуется сначала регистрировать таксономию, а потом тип записи с которым эта таксономия связана!

		/* Tag */
		$labels = array(
			'name'                       => _x( 'Project Type', 'Taxonomy General Name', THEME_NAME ),
			'singular_name'              => _x( 'Project Type', 'Taxonomy Singular Name', THEME_NAME ),
			'menu_name'                  => __( 'Project Type', THEME_NAME ),
			'all_items'                  => __( 'All Project Type', THEME_NAME ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'new_item_name'              => __( 'Name tag', THEME_NAME ),
			'add_new_item'               => __( 'Add tag', THEME_NAME ),
			'edit_item'                  => __( 'Edit', THEME_NAME ),
			'update_item'                => __( 'Update', THEME_NAME ),
			'view_item'                  => __( 'View', THEME_NAME ),
			'separate_items_with_commas' => __( 'Separate tag', THEME_NAME ),
			'add_or_remove_items'        => __( 'Add or remove tag', THEME_NAME ),
			'choose_from_most_used'      => __( 'Choose from the list tag', THEME_NAME ),
			'popular_items'              => __( 'Tags', THEME_NAME ),
			'search_items'               => __( 'Search', THEME_NAME ),
			'not_found'                  => __( 'Not found', THEME_NAME ),
			'no_terms'                   => __( 'No tags', THEME_NAME ),
			'items_list'                 => __( 'List tags', THEME_NAME ),
			'items_list_navigation'      => __( 'Tags navigation', THEME_NAME ),
		);
		$rewrite = array(
			'slug'                       => 'project-type',
			'with_front'                 => true,
			'hierarchical'               => false,
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'publicly_queryable'         => false,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_in_rest'		      	 => true, // По умолчанию: false
			'rest_base'   		         => 'project-type', // По умолчанию: $taxonomy
			'rest_controller_class'      => 'WP_REST_Terms_Controller', // По умолчанию: 'WP_REST_Terms_Controller'
			'rest_namespace'   		     => 'wp/v2', // По умолчанию: wp/v2			
			'show_tagcloud'              => true,
			'rewrite'                    => $rewrite,		
			'meta_box_cb'                => 'post_tags_meta_box',
			'sort'                       => true,
		);
		register_taxonomy( 'project-type', array( 'project' ), $args );		


		/* POST */	
		$labels = array(
			'name'                => _x( 'Projects', 'Post Type General Name', THEME_NAME ),
			'singular_name'       => _x( 'Projects', 'Post Type Singular Name', THEME_NAME ),
			'menu_name'           => __( 'Projects', THEME_NAME ),
			'parent_item_colon'   => __( 'Parent Project', THEME_NAME ),
			'all_items'           => __( 'All Projects', THEME_NAME ),
			'view_item'           => __( 'View Project', THEME_NAME ),
			'add_new_item'        => __( 'Add New Project', THEME_NAME ),
			'add_new'             => __( 'Add New', THEME_NAME ),
			'edit_item'           => __( 'Edit Project', THEME_NAME ),
			'update_item'         => __( 'Update Project', THEME_NAME ),
			'search_items'        => __( 'Search Project', THEME_NAME ),
			'not_found'           => __( 'Not Found', THEME_NAME ),
			'not_found_in_trash'  => __( 'Not found in Trash', THEME_NAME ),
		);
		$args = array(
			'label'            	      => __( 'Project', THEME_NAME ),
			'description'      	  	  => __( 'Website development project', THEME_NAME ),
			'labels'                  => $labels,
			'supports'                => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', 'hierarchical' ),
			'public'                  => true,
			'show_ui'                 => true,
			'show_in_menu'            => true,
			'show_in_nav_menus'       => true,
			'show_in_rest'		      => true, // По умолчанию: false
			'rest_base'   		      => 'project', // По умолчанию: $post_type
			'rest_controller_class'   => 'WP_REST_Posts_Controller', // По умолчанию: 'WP_REST_Posts_Controller'
			'rest_namespace'   		  => 'wp/v2', // По умолчанию: wp/v2
			'menu_position'           => 24,
			'can_export'              => true,
			'has_archive'             => true,
			'rewrite'                 => true,
			'exclude_from_search'     => true,
			'publicly_queryable'      => true,
			'capability_type'         => 'page',
			'has_archive'             => 'projects',
		);
		register_post_type( 'project', $args );
	}
	
}

new Plugin_Rest_Examples;


?>