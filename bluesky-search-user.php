<?php

/*
 * Display list of users based on search
 *
 * Documentation
 * https://docs.bsky.app/docs/api/app-bsky-actor-search-actors
 *
 */

$search_string = 'finland';

$posts_amount = 12; // 1-100

include 'config.php';
include 'common-functions.php';

$api_endpoint = BLUESKY_API_ENDPOINT_PUBLIC . 'app.bsky.actor.searchActors?q=' . $search_string . '&limit=' . $posts_amount . '&filter=posts_no_replies';

$bluesky_array = bluesky_fetch_data( $api_endpoint );

if ( array_key_exists( 'actors', $bluesky_array ) ) {

	foreach ( $bluesky_array[ 'actors' ] as $profile_array ) {

		echo bluesky_author_and_datetime( $profile_array );

	}

}

?>