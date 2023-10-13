<?php
/**
 * Plugin Name: SEO settings for pages
*/

if (!class_exists('justbenice_seo_settings')) {
	class justbenice_seo_settings
	{
		private function assets($filename  = null)
		{
			return trailingslashit(plugin_dir_url(__FILE__)).'assets/'. ($filename ? $filename : '') ;
		}
		private function version()
		{
			return WP_DEBUG ? rand(0, 100000) : false;
		}
		public function __construct()
		{
			add_action('plugins_loaded', array($this, 'initialize'));
		}
		public function enqueue()
		{
			if(is_user_logged_in()) {
			
				wp_enqueue_script('jquery');
			
				wp_register_script("jquery.cookie", plugin_dir_url(__FILE__) . "jquery.cookie.js", array('jquery'), $this->version(), true);
				
				wp_enqueue_script("jquery.cookie");
			}
		}
		public function initialize()
		{
			add_action('init', array($this, 'register'), 0);
			add_action('enqueue_block_editor_assets', array($this , 'blocks'));
			

			add_action('wp_footer', array( $this, 'yandex' ));
			add_filter('wp_title', array( $this, 'wp_title' ));
			add_filter('robots_txt', array( $this, 'robots_txt'), 10, 2);
			
			add_action('publish_post', array( $this, 'publish_post'));
			add_action('wp_head', array( $this, 'the_wp_opengraph'));
			
			add_action('wp_enqueue_scripts', array($this , 'enqueue'), 99);
			add_action('wp_footer', array( $this, 'preview_opengraph'));
		}
		public function register()
		{
			$seo = array(
				'show_in_rest' => array(
					'schema' => array(
						'type'	   => 'object',
						'properties' => array(
							'opengraph_title'  => array(
								'type' => 'string',
							),
							'opengraph_description' => array(
								'type' => 'string',
							),
							'opengraph_media_id'  => array(
								'type' => 'number',
							),
							'opengraph_media_url' => array(
								'type' => 'string',
							),
							'meta_keywords'  => array(
								'type' => 'string',
							),
							'meta_description' => array(
								'type' => 'string',
							),
						),
					),
				),
				'single'	   => true,
				'type'		 => 'object',
				'auth_callback' => function () {
					return current_user_can('edit_posts');
				}
			);
			
			$types = array('work','page','post');
			
			foreach($types as $type){
				register_post_meta(
					$type,
					'seo',
					$seo
				);
				
			}
			
		}
		public function blocks()
		{
			if (! function_exists('register_block_type')) {
				return;
			}
			wp_enqueue_script(
				'seo',
				$this->assets('seo.min.js'),
				array('wp-element', 'wp-blocks', 'wp-i18n', 'wp-components', 'wp-editor'),
				$this->version(),
				true
			);
		}
		public static function get_keywords()
		{
			
			
			
			$id = get_queried_object_id();
			
			$result = [];
			
			/* Just Be Nice website projects start */
			
			$clients = get_the_terms($id, 'client');
			$clients = is_array($clients) ? array_map(function($n){ return $n->name; }, $clients) : [];
			
			$result = array_merge($result, $clients);
			
			$area = get_the_terms($id, 'area');
			$area = is_array($area) ? array_map(function($n){ return $n->name; }, $area) : [];
			
			$result = array_merge($result, $area);
			
			$service = get_the_terms($id, 'service');
			$service = is_array($service) ? array_map(function($n){ return $n->name; }, $service) : [];
				
			$result = array_merge($result, $service);
			
			/* Just Be Nice website projects end */
			
			$product = wc_get_product(get_queried_object_id());
			
			if($product){
				
				$attributes = $product->get_attributes();
				
				$prod_attrs = [];
				
				foreach ($attributes as &$attribute) {
					$terms = $attribute->get_terms();
					if ($terms){
					foreach ($attribute->get_terms() as $term) {
						if($term){
							array_push($prod_attrs, $term->name);
						}
						}
					}
				}
				
			
				if($prod_attrs){
					$result = array_merge($result, $prod_attrs);
				}
			}
			
			
			
			
			$tags = get_the_tags($id);
			$tags = $tags ? array_map(function($n){ return $n->name; }, $tags) : [];
			
			$result = array_merge($result, $tags);
			
			return $result;
		}
		public function the_wp_opengraph()
		{
				echo $this->get_wp_opengraph();
		}
		public function preview_opengraph()
		{
			
			global $_COOKIE;

			$cookie = (object) $_COOKIE;
			$open = null;
			
			if (property_exists($cookie, "opengraph_details")) {
					$open = $cookie->opengraph_details == "undefined" ? "open=\"open\"" : null ;
			}
			
			echo is_user_logged_in() ? "<section class=\"py-2 px-4\"><details id=\"opengraph_details\" $open><summary style=\"user-select:none\">Opengraph</summary><pre>".htmlspecialchars($this->get_wp_opengraph())."</pre></details></section>" : null;
			
			echo is_user_logged_in() ? '<script>(function($) {$(document).ready(function(){$("#opengraph_details summary").click(function(){$.cookie("opengraph_details", $(this).parent().attr("open"), {expires: 365, path: "/"})})})})(jQuery);</script>' : null;
			
			
			
			}
		public function get_wp_opengraph()
		{
			if (!get_queried_object_id()) {
				return;
			}
			$return = '';
			
			$id = get_queried_object_id();
			
			$opengraph = (object) get_post_meta($id, 'seo', true);
			
			// og:title meta tag
			$og_title = is_front_page() ? get_bloginfo('name') : (is_tax() ? single_cat_title(null, false) : get_the_title($id)) ." — " . get_bloginfo('name');
			if( property_exists($opengraph, 'opengraph_title')){
				if(!empty($opengraph->opengraph_title)){
					$og_title = $opengraph->opengraph_title . " — " . get_bloginfo('name');  
				}
			}
			$return .= "<meta property=\"og:title\" content=\"".wp_strip_all_tags($og_title)."\">\n";;
			
			
			// og:description meta tag
			
			$og_description = has_excerpt($id) ? get_the_excerpt($id) : (is_tax() ? category_description() : get_bloginfo('description'));
			
			if( property_exists($opengraph, 'opengraph_description')){
				if(!empty($opengraph->opengraph_description)){
					$og_description = $opengraph->opengraph_description;
				}else{
					if( property_exists($opengraph, 'meta_description')){
						if(!empty($opengraph->meta_description)){
							$og_description = $opengraph->meta_description;
						}
					}
				}
			}
			$return .= "<meta property=\"og:description\" content=\"".wp_strip_all_tags($og_description)."\">\n";
			
			
			// description meta tag
			$meta_description = has_excerpt($id) ? get_the_excerpt($id) : (is_tax() ? category_description() : get_bloginfo('description'));
			
			if( property_exists($opengraph, 'meta_description')){
				if(!empty($opengraph->meta_description)){
					$meta_description = $opengraph->meta_description;
				}else{
					if( property_exists($opengraph, 'opengraph_description')){
						if(!empty($opengraph->opengraph_description)){
							$meta_description = $opengraph->opengraph_description;
						}
					}
				}
			}
			$return .= "<meta name=\"description\" content=\"".wp_strip_all_tags($meta_description)."\">\n";
			
			// keywords meta tag
			
			$meta_keywods = implode(', ' , $this->get_keywords());
			
			if( property_exists($opengraph, 'meta_keywords')){
				if(!empty($opengraph->meta_keywords)){
					$meta_keywods = $opengraph->meta_keywords;
				}
			}
			
			$return .= "<meta name=\"keywords\" content=\"$meta_keywods\">\n";
			
			
			
			$og_image = get_the_post_thumbnail_url( $id, 'facebook' );
			
			$placeholder = $og_image ? wp_get_attachment_image_src(get_option('page_on_front'), 'facebook') : null;
			$placeholder = $placeholder ? $placeholder[0] : null;
			
			$og_image = $og_image ? $og_image : $placeholder;
			
			if( property_exists($opengraph, 'opengraph_media_id')){
			
				if(!empty($opengraph->opengraph_media_id)){
			
					$og_image = wp_get_attachment_image_src($opengraph->opengraph_media_id, 'facebook');
			
					$og_image = $og_image ? $og_image[0] : $og_image_empty;
			
				}
			
			}
			
			$return .= "<meta property=\"og:image\" content=\"".esc_url( is_single() || is_page() || is_product() ? $og_image : $placeholder)."\" />\n";
			
		
		
			$return .= "<meta property=\"og:url\" content=\"".esc_url(is_front_page() ? home_url() : get_permalink())."\" />\n";
			$return .= "<meta property=\"og:type\" content=\"".(is_single() ? 'article' : 'website')."\" />\n";
			$return .= "<meta property=\"og:site_name\" content=\"".get_bloginfo('name')."\" />\n";
			
			return $return;
		}
		public static function wp_title($title)
		{
			return $title.esc_attr(get_bloginfo('name'));
		}
		public static function yandex()
		{
			echo !WP_DEBUG ? '<!-- Yandex.Metrika counter --> <script type="text/javascript" > (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)}; m[i].l=1*new Date(); for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }} k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)}) (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym"); ym(33829759, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true, trackHash:true, ecommerce:"dataLayer" }); </script> <noscript><div><img src="https://mc.yandex.ru/watch/33829759" style="position:absolute; left:-9999px;" alt="" /></div></noscript> <!-- /Yandex.Metrika counter -->' : null;
			
			echo !WP_DEBUG ? '<!-- Google tag (gtag.js) --><script async src="https://www.googletagmanager.com/gtag/js?id=UA-440331-3"></script><script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag(\'js\', new Date()); gtag(\'config\', \'UA-440331-3\'); </script>' : null;	
				
				
		}
		public static function robots_txt($output, $public)
		{
			 $output .= 'Sitemap: https://www.jutbenice.ru/sitemap.xml';
			
				return $output;
			}
		public static function publish_post()
		{
		
			$sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
		
			$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		
			$sitemap .= '<url>';
		
				$sitemap .= '<loc>'.get_bloginfo("siteurl").'</loc>';
				$sitemap .= '<priority>1.0</priority>';
		
			$sitemap .= '</url>';
		
			$sitemap_categories = get_categories(array(
				'orderby' => 'name',
				'order' => 'ASC',
			));
		
			foreach ($sitemap_categories as $category) {
				$sitemap .= '<url>';
		
				$sitemap .= '<loc>'.str_replace('./', '', get_category_link($category->term_id)).'</loc>';
				$sitemap .= '<priority>0.9</priority>';
		
				$sitemap .= '</url>';
			}
		
			$sitemap_pages = get_posts(array(
				'numberposts' => -1,
				'orderby' => 'modified',
				'post_type' => array('page'),
				'order' => 'DESC',
			));
		
			foreach ($sitemap_pages as $post) {
				setup_postdata($post);
		
				$postdate = explode(' ', $post->post_modified);
		
				$sitemap .= '<url>';
		
				$sitemap .= '<loc>'.get_permalink($id).'</loc>';
				$sitemap .= '<priority>0.8</priority>';
		
				$sitemap .= '</url>';
			}
		
			//wp_reset_postdata();
		
			$sitemap_posts = get_posts(array(
				'numberposts' => -1,
				'orderby' => 'modified',
				'post_type' => array('post'),
				'order' => 'DESC',
			));
		
			foreach ($sitemap_posts as $post) {
				setup_postdata($post);
		
				$postdate = explode(' ', $post->post_modified);
		
				$sitemap .= '<url>';
		
				$sitemap .= '<loc>'.get_permalink($id).'</loc>';
				$sitemap .= '<priority>0.8</priority>';
		
				$sitemap .= '</url>';
			}
		
			//wp_reset_postdata();
		
			$sitemap .= '</urlset>';
		
			$sitemap_xml = fopen(ABSPATH.'sitemap.xml', 'w');
		
			fwrite($sitemap_xml, $sitemap);
		
			fclose($sitemap_xml);
		
		}
	}
	new justbenice_seo_settings();
}
