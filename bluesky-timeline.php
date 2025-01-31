<?php

/*
 * Display a Bluesky user's home timeline
 *
 * Documentation
 * https://docs.bsky.app/docs/api/app-bsky-feed-get-timeline
 *
 */

$posts_amount = 12; // 1-100

include 'config.php';
include 'common-functions.php';

$api_endpoint = BLUESKY_API_ENDPOINT_AUTH . 'app.bsky.feed.getTimeline?limit=' . $posts_amount;

$bluesky_session = bluesky_open_session();

$bluesky_html = bluesky_posts_html( $api_endpoint, 'GET', false, $bluesky_session );

echo $bluesky_html;

?>