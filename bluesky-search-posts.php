<?php

/*
 * Display list of posts based on search
 *
 * Documentation
 * https://docs.bsky.app/docs/api/app-bsky-feed-search-posts
 *
 */

$search_string = 'Finland'; // required

$search_mentions = '';
$search_author = '';
$search_tag = ''; // do not include the hash (#) prefix

$search_sort = 'latest'; // top or latest
$posts_amount = 12; // 1-100

include 'config.php';
include 'common-functions.php';

$api_endpoint = BLUESKY_API_ENDPOINT_PUBLIC . 'app.bsky.feed.searchPosts?q=' . $search_string . '&limit=' . $posts_amount . '&sort=' . $search_sort . '&mentions=' . $search_mentions . '&author=' . $search_author . '&tag=' . $search_tag;

$bluesky_html = bluesky_posts_html( $api_endpoint );

echo $bluesky_html;

?>