<?php

/*
 * Display list of followers
 *
 * Documentation
 * https://docs.bsky.app/docs/api/app-bsky-graph-get-followers
 *
 */

$bluesky_username = 'kenda.fi';

$posts_amount = 12; // 1-100

include 'config.php';
include 'common-functions.php';

$api_endpoint = BLUESKY_API_ENDPOINT_PUBLIC . 'app.bsky.graph.getFollowers?actor=' . $bluesky_username . '&limit=' . $posts_amount;

$bluesky_array = bluesky_fetch_data( $api_endpoint );

if ( array_key_exists( 'followers', $bluesky_array ) ) {

	foreach ( $bluesky_array[ 'followers' ] as $profile_array ) {

		echo bluesky_author_and_datetime( $profile_array );

	}

}

?>