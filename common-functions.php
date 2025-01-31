<?php

/*
 * Common functions used by the other PHP files.
 *
 **/

/*
 * Wrapper function for cURL.
 *
 **/

function bluesky_fetch_data( $api_endpoint, $api_method = 'GET', $postdata = array(), $session = array() ) {

	if ( ! function_exists( 'curl_init' ) ) {

		// cURL PHP module not loaded on server
		return false;

	}

	$curl = curl_init();

	$args = array(
		CURLOPT_URL => $api_endpoint,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => $api_method,
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		),
	);

	if ( $_SERVER[ 'HTTP_HOST' ] == 'localhost' ) {

		// skip SSL in localhost because we may not have that when testing locally
		$args[ CURLOPT_SSL_VERIFYPEER ] = false;

	}

	if ( ! empty( $postdata ) ) {

		$args[ CURLOPT_POSTFIELDS ] = json_encode( $postdata );

	}

	if ( ! empty( $session ) ) {

		$args[ CURLOPT_HTTPHEADER ] = array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $session[ 'accessJwt' ]
		);

	}

	curl_setopt_array(
		$curl,
		$args,
	);

	$response = curl_exec( $curl );

	curl_close( $curl );

	if ( strlen( $response ) < 1 ) {

		return false;

	}

	$array = json_decode( $response, TRUE );

	if ( ! is_array( $array ) ) {

		return false;

	}

	if ( array_key_exists( 'error', $array ) ) {

		return false;

	}

	return $array;

}

/*
 * Open a session for those requests that require auth.
 *
 **/

function bluesky_open_session() {

	// BLUESKY_USERNAME and BLUESKY_PASSWORD are defined in config.php
	// See README.md for more information.

	if ( ! defined( 'BLUESKY_USERNAME' ) ) {

		return false;

	}

	if ( ! defined( 'BLUESKY_PASSWORD' ) ) {

		return false;

	}

	$api_endpoint = BLUESKY_API_ENDPOINT_AUTH . 'com.atproto.server.createSession';

	$api_method = 'POST';

	$postdata = array(
		'identifier' => BLUESKY_USERNAME,
		'password' => BLUESKY_PASSWORD
	);

	if ( ! $session = bluesky_fetch_data( $api_endpoint, $api_method, $postdata ) ) {

		return false;

	}

	if ( ! array_key_exists( 'accessJwt', $session ) ) {

		return false;

	}

	return $session;

}

/*
 * Facets are URLs, hashtags, and usernames in content that we make clickable links.
 *
 **/

function bluesky_facets( $bluesky_post_text = '', $bluesky_facets = array() ) {

	$replace = array();

	foreach( $bluesky_facets as $facet ) {

		if ( array_key_exists( 'features', $facet ) && is_array( $facet[ 'features' ] ) && !empty( $facet[ 'features' ] ) ) {

			if ( array_key_exists( 'uri', $facet[ 'features' ][0] ) ) {

				// Link

				$uri = $facet[ 'features' ][0][ 'uri' ];

				$length = $facet[ 'index' ][ 'byteEnd' ] - $facet[ 'index' ][ 'byteStart' ];

				$replace_this = substr( $bluesky_post_text, $facet[ 'index' ][ 'byteStart' ], $length );

				$replace[ $replace_this ] = '<a href="' . $uri . '" target="_blank">' . $replace_this . '</a>';

			}
			elseif ( array_key_exists( 'tag', $facet[ 'features' ][0] ) ) {

				// Hashtag

				$tag = $facet[ 'features' ][0][ 'tag' ];

				$length = $facet[ 'index' ][ 'byteEnd' ] - $facet[ 'index' ][ 'byteStart' ];

				$replace_this = substr( $bluesky_post_text, $facet[ 'index' ][ 'byteStart' ], $length );

				$replace[ $replace_this ] = '<a href="' . BLUESKY_HOST . 'hashtag/' . $tag . '" target="_blank">' . $replace_this . '</a>';

			}
			elseif ( $facet[ 'features' ][0][ '$type' ] == 'app.bsky.richtext.facet#mention' ) {

				// Username

				$length = $facet[ 'index' ][ 'byteEnd' ] - $facet[ 'index' ][ 'byteStart' ];

				$replace_this = substr( $bluesky_post_text, $facet[ 'index' ][ 'byteStart' ], $length );

				$replace[ $replace_this ] = '<a href="' . BLUESKY_HOST . 'profile/' . str_replace( '@', '', $replace_this ) . '" target="_blank">' . $replace_this . '</a>';

			}

		}

	}

	return nl2br( str_replace( array_keys( $replace ), $replace, $bluesky_post_text ), false );

}

/*
 * Author and datetime.
 *
 **/

function bluesky_author_and_datetime( $bluesky_author_and_datetime, $postlink = '', $datetime = '' ) {

	$return = '';

	$return .= '<div class="bsky-profile">';

	// Banner

	if ( array_key_exists( 'banner', $bluesky_author_and_datetime ) ) {

		$return .= '<div class="bsky-profile-banner"><img src="' . $bluesky_author_and_datetime['banner'] . '" alt="" width="100%"></div>';

	}

	$return .= '<p class="bsky-profile-avatar-and-name">';

	// Avatar

	if ( array_key_exists( 'avatar', $bluesky_author_and_datetime ) ) {

		$avatar = $bluesky_author_and_datetime[ 'avatar' ];

		$return .= '<img src="' . $avatar . '" alt="" width="40" hspace="10" align="left" class="bsky-profile-avatar">';

	}

	// Author name incl. link to profile

	$profilelink = BLUESKY_HOST . 'profile/' . $bluesky_author_and_datetime[ 'handle' ];

	$authorname = htmlentities( $bluesky_author_and_datetime[ 'displayName' ], ENT_QUOTES );

	$return .= '<a href="' . $profilelink . '" target="_blank" class="bsky-profile-link">' . $authorname . '</a><br>';

	if ( $postlink != '' && $datetime != '' ) {

		// Post date and time incl. link to post.

		$return .= '<small class="bsky-profile-datetime-link"><a href="' . $postlink . '" target="_blank">' . $datetime . '</a></small>';

	}

	$return .= '</p>';

	if ( array_key_exists( 'description', $bluesky_author_and_datetime ) ) {

		// Description (we also look for 'banner' in the if clause above to avoid this being output in list mentions.
		$return .= '<p class="bsky-profile-description">' . nl2br( htmlentities( $bluesky_author_and_datetime['description'] ) ) . '</p>';

		if ( array_key_exists( 'createdAt', $bluesky_author_and_datetime ) ) {

			// Profile statistics

			$return .= '<p class="bsky-profile-stats"><small>';
			$return .= '<span class="bsky-profile-created">Account created: ' . date( BLUESKY_DATETIME_FORMAT, strtotime( $bluesky_author_and_datetime['createdAt'] ) ) . '<br></span>';

			if ( array_key_exists( 'postsCount', $bluesky_author_and_datetime ) ) {

				$return .= '<span class="bsky-profile-posts">Posts: ' . $bluesky_author_and_datetime['postsCount'] . '<br></span>';

			}

			if ( array_key_exists( 'followsCount', $bluesky_author_and_datetime ) ) {

				$return .= '<span class="bsky-profile-following">Following: ' . $bluesky_author_and_datetime['followsCount'] . '<br></span>';

			}

			if ( array_key_exists( 'followersCount', $bluesky_author_and_datetime ) ) {

				$return .= '<span class="bsky-profile-followers">Followers: ' . $bluesky_author_and_datetime['followersCount'] . '<br></span>';

			}

			if ( array_key_exists( 'associated', $bluesky_author_and_datetime ) && array_key_exists( 'feedgens', $bluesky_author_and_datetime[ 'associated' ] ) ) {

				$return .= '<span class="bsky-profile-feeds">Feeds: ' . $bluesky_author_and_datetime['associated']['feedgens'] . '<br></span>';

			}

			if ( array_key_exists( 'associated', $bluesky_author_and_datetime ) && array_key_exists( 'lists', $bluesky_author_and_datetime[ 'associated' ] ) ) {

				$return .= '<span class="bsky-profile-lists">Lists: ' . $bluesky_author_and_datetime['associated']['lists'] . '<br></span>';

			}

			$return .= '</small></p>';

		}

	}

	$return .= '</div> <!-- bsky-profile -->';

	return $return;

}

/*
 * Open a session for those requests that require auth.
 *
 **/

function bluesky_posts_html( $api_endpoint, $api_method = 'GET', $postdata = array(), $session = array() ) {

	$html = '';

	$bluesky_posts = bluesky_fetch_data( $api_endpoint, $api_method, $postdata, $session );

	if ( ! is_array( $bluesky_posts ) ) {

		return '';

	}

	if ( empty( $bluesky_posts ) ) {

		return '';

	}

	if ( ! array_key_exists( 'feed', $bluesky_posts ) && ! array_key_exists( 'posts', $bluesky_posts ) ) {

		return '';

	}

	if ( ! defined( 'BLUESKY_HOST' ) ) {

		define( 'BLUESKY_HOST', 'https://bsky.app/' );

	}

	if ( ! defined( 'BLUESKY_DATETIME_FORMAT' ) ) {

		define( 'BLUESKY_DATETIME_FORMAT', 'j.n.Y @ H:i' );

	}

	$html .= '<div class="bsky-wrapper">';

	// getAuthorFeed and searchPosts returns data in different structure

	if ( array_key_exists( 'feed', $bluesky_posts ) ) {

		// getAuthorFeed
		$data_array = $bluesky_posts[ 'feed' ];

	}
	else {

		// searchPosts
		$data_array = $bluesky_posts[ 'posts' ];

	}

	foreach( $data_array as $bluesky_post ) {

		// getAuthorFeed and searchPosts returns data in different structure

		if ( array_key_exists( 'post', $bluesky_post ) ) {

			// getAuthorFeed, getActorLikes, getTimeline
			$item_array = $bluesky_post[ 'post' ];

		}
		else {

			// searchPosts
			$item_array = $bluesky_post;

		}

		$html .= '<div class="bsky-item">';

		// User avatar + name, and post creation time

		$html .= '<div class="bsky-user-and-created">';

		// Post link and datetime

		// We need post ID for the link. This is very ugly way of getting it.

		$link_parts = explode( 'app.bsky.feed.post/', $item_array[ 'uri' ] );
		$postlink = BLUESKY_HOST . 'profile/' . $item_array[ 'author' ][ 'handle' ] . '/post/' . $link_parts[ 1 ];

		$datetime = date( BLUESKY_DATETIME_FORMAT, strtotime( $item_array[ 'record' ][ 'createdAt' ] ) );

		$html .= bluesky_author_and_datetime( $item_array[ 'author' ], $postlink, $datetime );

		$html .= '</div> <!--bsky-user-and-created -->';

		// The content

		$html .= '<div class="bsky-item-content">';

		$html .= '<p class="bsky-item-text">';

		if ( array_key_exists( 'record', $item_array ) && array_key_exists( 'facets', $item_array[ 'record' ] ) ) {

			// We seem to have links. Let's make them clickable in the content.
			$html .= bluesky_facets( $item_array[ 'record' ][ 'text' ], $item_array[ 'record' ][ 'facets' ] );

		}
		else {

			// We have no rich content. Output as plain text.
			$html .= nl2br( $item_array[ 'record' ][ 'text' ], false );

		}

		$html .= '</p> <!--bsky-item-text -->';

		// Embeds (link preview, quoted post, image, video)

		if ( array_key_exists( 'embed', $item_array ) ) {

			if ( $item_array[ 'embed' ][ '$type' ] == 'app.bsky.embed.external#view' ) {

				// External link

				if ( array_key_exists( 'title', $item_array[ 'embed' ][ 'external' ] ) && array_key_exists( 'description', $item_array[ 'embed' ][ 'external' ] ) ) {

					$html .= '<div class="bsky-embeds-external"><blockquote>';

					$html .= '<a href="' . $item_array[ 'embed' ][ 'external' ][ 'uri' ] . '" target="_blank">';

					if ( array_key_exists( 'thumb', $item_array[ 'embed' ][ 'external' ] ) ) {

						// Thumbnail
						$html .= '<img src="' . $item_array[ 'embed' ][ 'external' ][ 'thumb' ] . '" alt="" width="100%" vspace="10">';

					}

					$html .= '<strong>' . htmlentities( $item_array[ 'embed' ][ 'external' ][ 'title' ] ) . '</strong><br>';

					$html .= htmlentities( $item_array[ 'embed' ][ 'external' ][ 'description' ] );

					$html .= '</a><br>';

					$html .= '</blockquote></div> <!-- bsky-embeds-external -->';

				}

			}
			elseif ( $item_array[ 'embed' ][ '$type' ] == 'app.bsky.embed.record#view' ) {

				// Quoted post and list mentions (app.bsky.embed.record#view may also include something else)

				$html .= '<div class="bsky-embeds-record"><blockquote>';

				// User avatar + name, and post creation time

				// We need post ID for the link. This is very ugly way of getting it.

				if ( strstr( $item_array[ 'embed' ][ 'record' ][ 'uri' ], 'app.bsky.feed.generator' ) ) {

					// List mention

					$replace = array(
						'at://' => '',
						'app.bsky.feed.generator' => 'feed',
					);

					$list_did = str_replace( array_keys( $replace ), $replace, $item_array[ 'embed' ][ 'record' ][ 'uri' ] );

					$embed_postlink = BLUESKY_HOST . 'profile/' . $list_did;

					$embed_datetime = date( BLUESKY_DATETIME_FORMAT, strtotime( $item_array[ 'embed' ][ 'record' ][ 'creator' ][ 'createdAt' ] ) );

					$embed_author = $item_array[ 'embed' ][ 'record' ][ 'creator' ];

				}
				else {

					// Normal embed

					$link_parts = explode( 'app.bsky.feed.post/', $item_array[ 'embed' ][ 'record' ][ 'uri' ] );

					$embed_postlink = BLUESKY_HOST . 'profile/' . $item_array[ 'author' ][ 'handle' ] . '/post/' . $link_parts[ 1 ];

					$embed_datetime = date( BLUESKY_DATETIME_FORMAT, strtotime( $item_array[ 'embed' ][ 'record' ][ 'value' ][ 'createdAt' ] ) );

					$embed_author = $item_array[ 'embed' ][ 'record' ][ 'author' ];

				}

				$html .= bluesky_author_and_datetime( $embed_author, $embed_postlink, $embed_datetime );

				// The content

				$html .= '<div class="bsky-item-embed-text"><p>';

				if ( array_key_exists( 'record', $item_array[ 'embed' ] ) && array_key_exists( 'value', $item_array[ 'embed' ][ 'record' ] ) && array_key_exists( 'facets', $item_array[ 'embed' ][ 'record' ][ 'value' ] ) ) {

					// We seem to have links. Let's make them clickable in the content.
					$html .= bluesky_facets( $item_array[ 'embed' ][ 'record' ][ 'value' ][ 'text' ], $item_array[ 'embed' ][ 'record' ][ 'value' ][ 'facets' ] );

				}
				else {

					// We have no rich content. Output as plain text.

					if ( array_key_exists( 'displayName', $item_array[ 'embed' ][ 'record' ] ) && array_key_exists( 'description', $item_array[ 'embed' ][ 'record' ] ) ) {

					// List mention

						$html .= nl2br( '<a href="' . $embed_postlink . '">' . $item_array[ 'embed' ][ 'record' ][ 'displayName' ] . "</a>\r\n" . $item_array[ 'embed' ][ 'record' ][ 'description' ], false );

					}
					elseif ( array_key_exists( 'value', $item_array[ 'embed' ][ 'record' ] ) ) {

						// Normal embed

						$html .= nl2br( $item_array[ 'embed' ][ 'record' ][ 'value' ][ 'text' ], false );

					}

				}

				$html .= '</div> <!--bsky-item-embed-text -->';

				$html .= '</blockquote></div> <!-- bsky-embeds-record -->';

			}
			elseif ( $item_array[ 'embed' ][ '$type' ] == 'app.bsky.embed.images#view' ) {

				// Images

				$html .= '<div class="bsky-embeds-images"><p>';

				$embed_amount = count( $item_array[ 'embed' ][ 'images' ] );

				foreach( $item_array[ 'embed' ][ 'images' ] as $bsky_image ) {

					$html .= '<a href="' . $bsky_image[ 'fullsize' ] . '" target="_blank"><img src="' . $bsky_image[ 'thumb' ] . '" alt="' . $bsky_image[ 'alt' ] . '" ' . ( $embed_amount > 1 ? 'width="48%" hspace="1%" vspace="5"' : 'width="100%"' ) . '></a>';

				}

				$html .= '</p></div> <!-- bsky-embeds-images -->';

			}
			elseif ( $item_array[ 'embed' ][ '$type' ] == 'app.bsky.embed.video#view' ) {

				// Video
				// We only display preview image of video until Bluesky implements videos in API data.

				if ( array_key_exists( 'thumbnail', $item_array[ 'embed' ] ) ) {

					$html .= '<div class="bsky-embeds-images"><p>';

					$thumbnail = $item_array[ 'embed' ][ 'thumbnail' ];
					$alt = ( array_key_exists( 'alt', $item_array[ 'embed' ] ) ? $item_array[ 'embed' ][ 'alt' ] : '' );

					$html .= '<a href="' . $postlink . '" target="_blank"><img src="' . $thumbnail . '" alt="' . $alt . '" title="Click to see video" width="100%"></a>';

					$html .= '</p></div> <!-- bsky-embeds-images -->';

				}

			}

		}

		$html .= '</div> <!--bsky-item-content -->';

		// Statistics

		$html .= '<div class="bsky-item-stats"><p><small>';

		$html .= '<span class="bsky-stats-likes">Likes <span class="bsky-stats-value">' . $item_array[ 'likeCount' ] . '</span></span> ';
		$html .= '<span class="bsky-stats-reposts">Reposts <span class="bsky-stats-value">' . $item_array[ 'repostCount' ] . '</span></span> ';
		$html .= '<span class="bsky-stats-replies">Replies <span class="bsky-stats-value">' . $item_array[ 'replyCount' ] . '</span></span>';

		$html .= '</small></p></div> <!-- bsky-item-stats -->';

		$html .= '</div> <!-- bsky-item -->';

	}

	$html .= '</div> <!-- bsky-wrapper -->';

	return $html;

}

?>