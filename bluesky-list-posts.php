<?php

/*
 * Display a Bluesky users original posts (replies are skipped)
 *
 * Documentation
 * https://docs.bsky.app/docs/api/app-bsky-feed-get-author-feed
 *
 */

// The user whose posts you want to display.
$bluesky_username = 'kenda.fi';

$posts_amount = 12; // 1-100

include 'config.php';
include 'common-functions.php';

$api_endpoint = BLUESKY_API_ENDPOINT_PUBLIC . 'app.bsky.feed.getAuthorFeed?actor=' . $bluesky_username . '&limit=' . $posts_amount . '&filter=posts_no_replies';

$bluesky_html = bluesky_posts_html( $api_endpoint );

echo $bluesky_html;

?>