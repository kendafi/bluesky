<?php

/*
 * Display a Bluesky users original posts (replies and re-posts are skipped)
 *
 * Create your app password here and add it to $config below.
 * https://bsky.app/settings/app-passwords
 *
 */

$config = array(
	'bluesky-username' => 'USERNAME.bsky.social',
	'bluesky-password' => 'xxxx-xxxx-xxxx-xxxx'
);

// The user whose posts you want to display.
$fetch_username = 'kenda.fi';

$curl = curl_init();

curl_setopt_array(
	$curl,
	array(
		CURLOPT_URL => 'https://bsky.social/xrpc/com.atproto.server.createSession',
		CURLOPT_SSL_VERIFYPEER => false, // skip SSL because I don't have that in localhost
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => '{
			"identifier":"' . $config['bluesky-username'] . '",
			"password":"' . $config['bluesky-password'] . '"
		}',
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		),
	)
);

$response = curl_exec( $curl );

curl_close( $curl );

$session = json_decode( $response, TRUE );

if ( is_array( $session ) && !empty( $session ) && array_key_exists( 'accessJwt', $session ) ) {

	// In code further down we skip replies and re-posts. So displayed posts
	// may be less than specified here. If the user replies or re-posts a lot,
	// you may need to increase this value to have enough to display.
	// Default is 50, but we want to specify this ourself. Allowed: 1-100.
	$fetch_amount = 20;

	$curl = curl_init();

	curl_setopt_array(
		$curl,
		array(
			CURLOPT_URL => 'https://bsky.social/xrpc/app.bsky.feed.getAuthorFeed?actor=' . $fetch_username . '&limit=' . $fetch_amount,
			CURLOPT_SSL_VERIFYPEER => false, // skip SSL because I don't have that in localhost
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $session['accessJwt']
			),
		)
	);

	$response = curl_exec( $curl );

	$data = json_decode( $response, TRUE );

	curl_close( $curl );

	// If you set $fetch_amount to something big to make sure you get enough
	// original posts even if some are skipped, then you can limit the amount
	// of displayed posts here.

	$display_limit = 10;

	// Do not change this. It is used to stop the loop when we hit $display_limit.
	$display_loop = 0;

	$date_time_format = 'j.n.Y @ H:i';

	if( is_array( $data ) && !empty( $data ) && array_key_exists( 'feed', $data ) ) {

		foreach( $data['feed'] as $bsky_post ) {

			// Original post does not have $bsky_post['reply'].
			// We want to display only original posts, so we skip if it's a reply.

			// If you ever want to include replies, note that in a reply
			// the original post is in 'reply', and the reply is in 'post'.

			if( !array_key_exists( 'reply', $bsky_post ) ) {

				echo '<div class="bsky-wrapper">';

				// By comparing username whose feed we fetched and the post username,
				// we can exclude all re-posts of someone elses post.

				if( $fetch_username == $bsky_post['post']['author']['handle'] ) {

					$display_loop++;

					echo '<div class="bsky-item">';

						echo '<div class="bsky-user-and-created"><p>';

							// Avatar
							echo '<img src="' . $bsky_post['post']['author']['avatar'] . '" alt="" width="40" align="left">';

							// Username
							echo '<a href="https://bsky.app/profile/'.$bsky_post['post']['author']['handle'].'" target="_blank">' . htmlentities( $bsky_post['post']['author']['displayName'] ) . '</a><br>';

							// We need post ID for the link... This is very ugly.
							$link_parts = explode( 'app.bsky.feed.post/', $bsky_post['post']['uri'] );

							// Timestamp incl. link to post.
							echo '<a href="https://bsky.app/profile/' . $bsky_post['post']['author']['handle'] . '/post/' . $link_parts[ 1 ] . '" target="_blank">' . date( $date_time_format, strtotime( $bsky_post['post']['record']['createdAt'] ) ) . '</a>';

						echo '</p></div> <!--bsky-user-and-created -->';

						echo '<div class="bsky-item-text"><p>';

							// The content
							echo nl2br( $bsky_post['post']['record']['text'], false );

							/*

							TODO:

							if post includes links we could list them somehow
							$bsky_post['post']['record']['facets'] array
							$bsky_post['post']['record']['facets'][x][features] array
							$bsky_post['post']['record']['facets'][x][features][x]['$type'] == 'app.bsky.richtext.facet#link'
							$bsky_post['post']['record']['facets'][x][features][x][uri]

							*/

						echo '</p>';

						// Embeds

						if( array_key_exists( 'embed', $bsky_post['post'] ) ) {

							// Images

							if( array_key_exists( 'images', $bsky_post['post']['embed'] ) ) {

								echo '<div class="bsky-embeds-images">';

								foreach( $bsky_post['post']['embed']['images'] as $bsky_image ) {

									echo '<p><a href="' . $bsky_image['fullsize'] . '" target="_blank"><img src="' . $bsky_image['thumb'] . '" alt="' . $bsky_image['alt'] . '" width="100%"></a></p>';

								}

								echo '</div> <!-- bsky-embeds-images -->';

							}

							// At the moment Bluesky does not support videos.
							// But some day it may do that and we can do something like this...
							// elseif( array_key_exists( 'videos', $bsky_post['post']['embed'] ) ) {
							// }

						}

						echo '</div> <!--bsky-item-content -->';

						echo '<div class="bsky-item-stats"><p><small>';

							// Stats
							echo '<span class="bsky-stats-likes">Likes <span class="bsky-stats-value">' . $bsky_post['post']['likeCount'] . '</span></span> ';
							echo '<span class="bsky-stats-reposts">Reposts <span class="bsky-stats-value">' . $bsky_post['post']['repostCount'] . '</span></span> ';
							echo '<span class="bsky-stats-replies">Replies <span class="bsky-stats-value">' . $bsky_post['post']['replyCount'] . '</span></span>';

						echo '</small></p></div> <!-- bsky-item-stats -->';

					echo '</div> <!-- bsky-item -->';

				}

				echo '</div> <!-- bsky-wrapper -->';

			}

			if( $display_loop == $display_limit ) {

				// Stop the loop since we are displaying enough.
				break;

			}

		}

	}

}

?>