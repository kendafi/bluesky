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

$postdata = array(
	'identifier' => $config[ 'bluesky-username' ],
	'password' => $config[ 'bluesky-password' ]
);

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
		CURLOPT_POSTFIELDS => json_encode( $postdata ),
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
	$fetch_amount = 50;

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
				'Authorization: Bearer ' . $session[ 'accessJwt' ]
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

		echo '<div class="bsky-wrapper">';

		foreach( $data['feed'] as $bsky_post ) {

			// Original post does not have $bsky_post['reply'].
			// We want to display only original posts, so we skip if it's a reply.

			// If you ever want to include replies, note that in a reply
			// the original post is in 'reply', and the reply is in 'post'.

			if( !array_key_exists( 'reply', $bsky_post ) ) {

				// By comparing username whose feed we fetched and the post username,
				// we can exclude all re-posts of someone elses post.

				if( $fetch_username == $bsky_post['post']['author']['handle'] ) {

					$display_loop++;

					echo '<div class="bsky-item">';

						echo '<div class="bsky-user-and-created"><p>';

							// Avatar
							echo '<img src="' . $bsky_post['post']['author']['avatar'] . '" alt="" width="60" hspace="10"align="left">';

							// Username
							echo '<a href="https://bsky.app/profile/'.$bsky_post['post']['author']['handle'].'" target="_blank">' . htmlentities( $bsky_post['post']['author']['displayName'] ) . '</a><br>';

							// We need post ID for the link... This is very ugly.
							$link_parts = explode( 'app.bsky.feed.post/', $bsky_post['post']['uri'] );

							// Timestamp incl. link to post.
							echo '<small><a href="https://bsky.app/profile/' . $bsky_post['post']['author']['handle'] . '/post/' . $link_parts[ 1 ] . '" target="_blank">' . date( $date_time_format, strtotime( $bsky_post['post']['record']['createdAt'] ) ) . '</a></small>';

						echo '</p></div> <!--bsky-user-and-created -->';

						echo '<div class="bsky-item-text">';

						echo '<p>';

						// The content

						if( array_key_exists( 'record', $bsky_post['post'] ) && array_key_exists( 'facets', $bsky_post['post']['record'] ) ) {

							// We seem to have links. Let's make them clickable in the content.

							$replace = array();

							foreach( $bsky_post['post']['record']['facets'] as $link ) {

								if( array_key_exists( 'features', $link ) && is_array( $link['features'] ) && !empty( $link['features'] ) ) {

									if( array_key_exists( 'uri', $link['features'][0] ) ) {

										$uri = $link['features'][0]['uri'];

										$length = $link['index']['byteEnd'] - $link['index']['byteStart'];

										$replace_this = substr( $bsky_post['post']['record']['text'], $link['index']['byteStart'], $length );

										$replace[ $replace_this ] = '<a href="' . $uri . '" target="_blank">' . $replace_this . '</a>';

									}

								}

							}

							echo nl2br( str_replace( array_keys( $replace ), $replace, $bsky_post['post']['record']['text'] ), false );

						}
						else {

							// We have no rich content. Output as plain text.
							echo nl2br( $bsky_post['post']['record']['text'], false );

						}

						echo '</p>';

						// Embeds

						if( array_key_exists( 'embed', $bsky_post['post'] ) ) {

							// Quoted post

							if( array_key_exists( '$type', $bsky_post['post']['embed'] ) && $bsky_post['post']['embed']['$type'] == 'app.bsky.embed.recordWithMedia#view' ) {

								// We have both images and quoted post




							}
							elseif( array_key_exists( 'record', $bsky_post['post']['embed'] ) ) {

								// Quoted post only

								if( $bsky_post['post']['embed']['record']['$type'] == 'app.bsky.embed.record#viewRecord' ) {

									echo '<div class="bsky-embeds-record"><blockquote>';

									// Avatar
									echo '<img src="' . $bsky_post['post']['embed']['record']['author']['avatar'] . '" alt="" width="60" hspace="10" align="left">';

									// Username
									echo '<a href="https://bsky.app/profile/'.$bsky_post['post']['embed']['record']['author']['handle'].'" target="_blank">' . htmlentities( $bsky_post['post']['embed']['record']['author']['displayName'] ) . '</a><br>';

									// We need post ID for the link... This is very ugly.
									$link_parts = explode( 'app.bsky.feed.post/', $bsky_post['post']['embed']['record']['uri'] );

									// Timestamp incl. link to post.
									echo '<small><a href="https://bsky.app/profile/' . $bsky_post['post']['embed']['record']['author']['handle'] . '/post/' . $link_parts[ 1 ] . '" target="_blank">' . date( $date_time_format, strtotime( $bsky_post['post']['embed']['record']['value']['createdAt'] ) ) . '</a></small>';

									echo '<div class="bsky-item-embed-text"><p>';

										// The content

										if( array_key_exists( 'record', $bsky_post['post'] ) && array_key_exists( 'facets', $bsky_post['post']['record'] ) ) {

											// We seem to have links. Let's make them clickable in the content.

											$replace = array();

											foreach( $bsky_post['post']['record']['facets'] as $link ) {

												if( array_key_exists( 'features', $link ) && is_array( $link['features'] ) && !empty( $link['features'] ) ) {

													if( $link['features'][0]['$type'] == 'app.bsky.richtext.facet#tag' ) {

														// Hashtag - TODO when this is officially supported

													}
													elseif( $link['features'][0]['$type'] == 'app.bsky.richtext.facet#link' ) {

															// Link

														$uri = $link['features'][0]['uri'];

														$length = $link['index']['byteEnd'] - $link['index']['byteStart'];

														$replace_this = substr( $bsky_post['post']['record']['text'], $link['index']['byteStart'], $length );

														$replace[ $replace_this ] = '<a href="' . $uri . '" target="_blank">' . $replace_this . '</a>';

													}

												}

											}

											echo nl2br( str_replace( array_keys( $replace ), $replace, $bsky_post['post']['record']['text'] ), false );

										}
										else {

											// We have no rich content. Output as plain text.
											echo nl2br( $bsky_post['post']['record']['text'], false );

										}

									echo '</div> <!--bsky-item-embed-text -->';

									echo '</blockquote></div> <!-- bsky-embeds-record -->';

								}

							}

							// External link

							if( array_key_exists( 'external', $bsky_post['post']['embed'] ) ) {

								if( $bsky_post['post']['embed']['$type'] == 'app.bsky.embed.external#view' ) {

									echo '<div class="bsky-embeds-external"><blockquote>';

									// Thumbnail
									echo '<img src="' . $bsky_post['post']['embed']['external']['thumb'] . '" alt="" width="40" hspace="10" align="left">';

									echo '<a href="'.$bsky_post['post']['embed']['external']['uri'].'" target="_blank">';
									echo '<strong>' . htmlentities( $bsky_post['post']['embed']['external']['title'] ) . '</strong><br>';
									echo htmlentities( $bsky_post['post']['embed']['external']['description'] );
									echo '</a><br>';

									echo '</blockquote></div> <!-- bsky-embeds-external -->';

								}

							}

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

			}

			if( $display_loop == $display_limit ) {

				// Stop the loop since we are displaying enough.
				break;

			}

		}

		echo '</div> <!-- bsky-wrapper -->';

	}

}

?>