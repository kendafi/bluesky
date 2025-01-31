<?php

/*
 * Display posts a Bluesky user has liked
 *
 * You can only get likes for the account under which the Bluesky app password is created.
 *
 * Documentation
 * https://docs.bsky.app/docs/api/app-bsky-feed-get-actor-likes
 *
 */

$posts_amount = 12; // 1-100

include 'config.php';
include 'common-functions.php';

$api_endpoint = BLUESKY_API_ENDPOINT_AUTH . 'app.bsky.feed.getActorLikes?actor=' . BLUESKY_USERNAME . '&limit=' . $posts_amount;

// Requires auth, actor must be the requesting account.
$bluesky_session = bluesky_open_session();

$bluesky_html = bluesky_posts_html( $api_endpoint, 'GET', false, $bluesky_session );

echo $bluesky_html;

?>