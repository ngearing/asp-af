<?php
/**
* ASP Attachments Fix
*
* @package           asp-af
* @author            Nathan
* @copyright         2019 Nathan
* @license           GPL-2.0-or-later
*
* @asp-af
* Plugin Name:       ASP Attachments Fix
* Plugin URI:        https://github.com/ngearing/asp-af
* Description:       Fixes the urls of attachments in asp search results.
* Version:           1.0.0
* Requires at least: 5.2
* Requires PHP:      7.2
* Author:            Nathan
* Author URI:        https://greengraphics.com.au
* Text Domain:       asp-af
* License:           GPL v2 or later
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'ABSPATH' ) ) {
    die;
}


// $ra['attachment_results'] = apply_filters('asp_attachment_results', $ra['attachment_results'], $args["_id"], $this);

add_filter('asp_results', 'filter_woo_uploads' );
function filter_woo_uploads( $results ) {
	$altered = false;
	foreach ( $results as $key => $result ) {
		if ( isset( $result->link ) && strpos( $result->link, 'woocommerce_uploads' ) !== false ) {
			$post = get_post( $result->id );
			$parent = get_post( $post->post_parent );
			if ( ! $parent ) {
				continue;
			}

			$new_result = array_replace(
				(array) $result,
				[
					'id' => $parent->ID,
					'title' => htmlspecialchars( $parent->post_title ),
					'content' => get_the_excerpt( $parent->ID ),
					'excerpt' => $parent->post_excerpt,
					'link' => get_the_permalink( $parent->ID ),
					'image' => wp_get_attachment_url( get_post_thumbnail_id( $parent->ID ) ),
				],
				(array) $parent
			);
			$results[ $key ] = (object) $new_result;
			$altered = true;
		}
	}

	// If results have been altered check for duplicates.
	if ( $altered ) {
		$ids = [];
		foreach ( $results as $key => $result ) {
			if ( isset( $ids[ $result->id ] ) ) {
				unset( $results[ $key ] );
				continue;
			}

			$ids[ $result->id ] = true;
		}
	}

	return $results;
}

function fix_attachment_urls( $permalink, $post ) {

	if ( strpos( $permalink, 'attachment_id' ) !== false ) {
		$post = get_post( $post );
		if ( $post->guid !== $permalink ) {
			return $post->guid;
		}
	}

	return $permalink;
}
add_filter( 'the_permalink', 'fix_attachment_urls', 100, 2 );

function link_attachment_to_pdf( $permalink, $post ) {

	if ( ! is_search() ) {
		return $permalink;
	}

	$post = get_post( $post );
	if ( $post->post_mime_type == 'application/pdf' && $post->guid !== $post->asp_guid ) {
		return $post->guid;
	}

	return $permalink;
}
add_filter( 'the_permalink', 'link_attachment_to_pdf', 100, 2 );
