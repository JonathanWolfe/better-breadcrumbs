<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
global $post;

/**
 * Plugin Name: Better Breadcrumbs
 * Plugin URI: http://github.com/JonathanWolfe/better-breadcrumbs
 * Description: Produces a better breadcrumb list for all your pages, categories, and taxonomies.
 * Version: 1.0.0
 * Author: Jon Wolfe
 * Author URI: http://github.com/JonathanWolfe
 * License: MIT
 */

class Better_Breadcrumbs {

	protected $crumbs = array();
	protected $term_slug;
	protected $taxonomy_slug;

	protected function build_term_crumb($term) {
		$url = get_term_link($term);

		return "<li><a href='{$url}' title='{$term->name}'>{$term->name}</a></li>";
	}

	protected function add_posts_to_crumbs() {
		$product_terms = wp_get_object_terms(
			$post->ID, 
			array('category', 'product_category'), 
			array('orderby' => 'count', 'order' => 'DESC')
		);

		if ( ! empty($product_terms) && ! is_wp_error($product_terms) ) {

			$this->term_slug = $product_terms[0]->slug;
			$this->taxonomy_slug = $product_terms[0]->taxonomy;

			$this->crumbs[] = "<li>{$post->post_title}</li>";

		}
	}

	protected function add_page_to_crumbs() {
		$this->crumbs[] = "<li>{$post->post_title}</li>";
	}

	protected function add_search_to_crumbs() {
		$this->crumbs[] = '<li>Search: "'.get_search_query().'"</li>';
	}

	protected function add_taxonomies_to_crumbs() {
		$term = get_term_by( "slug", $this->term_slug, $this->taxonomy_slug ); //print_r($term);
		
		if ( ! empty($this->crumbs) ) {
			$this->crumbs[] = build_term_crumb($term);
		} else {
			$this->crumbs[] = "<li>{$term->name}</li>";

		}
		
		while ( (bool) $term->parent ) {
			$term = get_term_by( "term_id", (int) $term->parent, $this->taxonomy_slug ); //print_r($term);
			$this->crumbs[] = build_term_crumb($term);
		}
	}

	protected function category_to_taxonomy() {
		$cat = get_the_category(get_query_var("cat"))[0];
		$this->term_slug = $cat->slug;
		$this->taxonomy_slug = $cat->taxonomy;
	}

	public function build() {

		if ( get_query_var('cat') ) {
			$this->category_to_taxonomy();
		}

		if ( is_single() ) {
			$this->add_posts_to_crumbs(); // also covers custom post types

		} elseif ( is_page() ) {
			$this->add_page_to_crumbs();

		} elseif ( is_search() ) {
			$this->add_search_to_crumbs();
		}

		if ( ! empty($this->term_slug) ) {
			$this->add_taxonomies_to_crumbs();
		}

		if( ! empty($this->crumbs) ) {
			$this->crumbs = array_reverse($this->crumbs); //print_r($this->crumbs);
			
			$last_crumb = array_pop($this->crumbs);
			$last_crumb = str_replace("<li", "<li class='active'", $last_crumb);
			$this->crumbs[] = $last_crumb;
			
			$this->crumbs = implode("\r\n", $this->crumbs);
		}
		else {
			$this->crumbs = '';
		}

		return "<ol class='breadcrumb'><li><a href='".get_home_url()."'>Home</a></li>{$this->crumbs}</ol>";
	}

}

function better_breadcrumbs($echo = true) {

	$better_breadcrumbs = new Better_Breadcrumbs();
	$breadcrumbs = $better_breadcrumbs->build();
	
	if ($echo) {
		echo $breadcrumbs;
	}
	
	return $breadcrumbs;

}
