<?php

/*
 * This gets a Bluesky user profile
 *
 * Does not require auth, but contains relevant metadata with auth.
 *
 * Documentation
 * https://docs.bsky.app/docs/api/app-bsky-actor-get-profile
 *
 */

$bluesky_username = 'kenda.fi';

include 'config.php';
include 'common-functions.php';

$api_endpoint = BLUESKY_API_ENDPOINT_AUTH . 'app.bsky.actor.getProfile?actor=' . $bluesky_username;

$bluesky_session = bluesky_open_session();

$bluesky_array = bluesky_fetch_data( $api_endpoint, 'GET', false, $bluesky_session );

echo bluesky_author_and_datetime( $bluesky_array );

?>