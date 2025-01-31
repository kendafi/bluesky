<?php

/*
 * Post to your Bluesky profile
 *
 * Load this file in your browser. Be careful - it posts on every reload.
 *
 * Documentation
 * https://docs.bsky.app/docs/api/com-atproto-repo-upload-blob
 * https://docs.bsky.app/docs/api/com-atproto-repo-create-record
 *
 */

$text = 'This is a test post sent via Bluesky AT Protocol XRPC API. This message will soon be deleted. Cheers @kenda.fi for the code ;)';

// Which languages the post contains. Max 3 (for more Bluesky gives and error)
//$langs = array( 'fi' );
//$langs = array( 'sv', 'sv-FI', 'sv-SE' );
$langs = array( 'en-GB', 'en-US' );

include 'config.php';
include 'common-functions.php';

$bluesky_session = bluesky_open_session();

// Remove unwanted characters that may mess up the sent data.

$unwanted = array( '{', '}', '[', ']', '"' );

$text = str_replace( $unwanted, '', $text );

// Parse $text to see if we find links and user handles that should be mentioned separately.
// Not perhaps the best way, but $text should be quite short so this should be quick

$facets = array();
$embed = '';

$explode = explode( ' ', $text );

foreach( $explode as $str ) {

	$str = trim( $str );

	// Remove some characters from the end if for example a sentence ends with a user handle and a dot after it.

	$remove_these = array(
		'...',
		'.',
		':',
		';',
		',',
		'?',
		'!',
	);

	foreach( $remove_these as $remove_this ) {

		$strlen = strlen( $remove_this );

		$last = mb_substr( $str, -$strlen );

		if( in_array( $last, $remove_these ) ) {

			$str = trim( mb_substr( $str, 0, -$strlen ) );

		}

	}

	if( filter_var( $str, FILTER_VALIDATE_URL ) ) {

		// URL
    // $str is a valid URL! Adding to data...

		$byteStart = strpos( $text, $str );
		$byteEnd = $byteStart + strlen( $str );

		$facets[] = '{
      "index": {
        "byteStart": '.$byteStart.',
        "byteEnd": '.$byteEnd.'
      },
      "features": [
        {
          "$type": "app.bsky.richtext.facet#link",
          "uri": "'.$str.'"
        }
      ]
    }';

		// That should have taken care of the clickable link.
		// Now we will make it an embed.
		// Lets do that only if there is not yet a link embed.

		if( $embed == '' ) {

			// Adding link also as embed...
			// ...if we can fetch all required data.

			$title = '';
			$description = '';
			$thumb_link = '';
			$thumb_mime = '';
			$thumb_size = '';

			$html = new DOMDocument();
			@$html->loadHTML( file_get_contents( $str ) );

			$xpath = new DOMXPath( $html );

			// We could make a backup further down where we search for
			// twitter:image twitter:title twitter:description
			// in case og: versions are not found.

			$query = '//*/meta[starts-with(@property, \'og:\')]';
			$metas = $xpath->query($query);

			foreach( $metas as $meta ) {

				$property = $meta->getAttribute('property');
				$content = $meta->getAttribute('content');

				if( $property == 'og:title' ) {

					$title = $content;

				}
				elseif( $property == 'og:description' ) {

					$description = $content;

				}
				elseif( $property == 'og:image' ) {

					$headers = get_headers( str_replace( ' ', '%20', $content ) );

					foreach( $headers as $header ) {

						$header = strtolower( trim( $header ) );

						if( strstr( $header, 'content-type: image/' ) ) {

							$thumb_mime = str_replace('content-type: ', '', $header );

							// Fetch image content and upload to Bluesky

							$img_data = file_get_contents( str_replace( ' ', '%20', $content ) );

							if( $img_data != '' ) {

								// Uncomment following line to save a local version to see if it really contains image data (debug)
								// file_put_contents( time() . '.jpg', $img_data );
								// That works! (view the file manually)

								$api_endpoint = BLUESKY_API_ENDPOINT_AUTH . 'com.atproto.repo.uploadBlob';

								$response_array = bluesky_fetch_data( $api_endpoint, 'POST', $img_data, $bluesky_session );

								if( is_array( $response_array ) && array_key_exists( 'blob', $response_array ) ) {

									if( array_key_exists( 'ref', $response_array['blob'] ) ) {

										if( array_key_exists( '$link', $response_array['blob']['ref'] ) ) {

											$thumb_link = $response_array['blob']['ref']['$link'];
											$thumb_mime = $response_array['blob']['mimeType'];
											$thumb_size = $response_array['blob']['size'];

											// We have image link returned from Bluesky: $thumb_link
											// We have verified image size: $thumb_mime
											// We have verified image size: $thumb_size

										}

									}

								}

							}
							else {

								// Image data is EMPTY!

							}

						}

					}

				}

			}

			if( $title != '' && $description != '' ) {

				// We HAVE data to add link as an embed.

				$embed = '
        "embed": {
          "$type": "app.bsky.embed.external",
          "external": {
            "uri": "' . $str . '",
            "title": "' . $title . '",
            "description": "' . $description . '"';

				if( $thumb_link != '' && $thumb_mime != '' && $thumb_size != '' ) {

					// We ALSO have data to add thumbnail in the embed.

					$embed .= ',
            "thumb": {
              "$type": "blob",
              "ref": {
                "$link": "' . $thumb_link . '"
              },
              "mimeType": "' . $thumb_mime . '",
              "size": ' . $thumb_size . '
            }';

				}
				else {

					// but we do NOT have data to add thumbnail in the embed.

				}

				$embed .= '
          }
        }';

				// Done.

			}
			else {

				// Fail. We do NOT have data to add link as an embed.

			}

		}

	}
	elseif( substr( $str, 0, 1 ) == '@' && preg_match( '/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', str_replace( '@', '', $str ) ) ) {

		// User handle found in content
		// https://atproto.com/specs/handle

		// Trying to fetch DID.

		$api_endpoint = BLUESKY_API_ENDPOINT_AUTH . 'app.bsky.actor.getProfile?actor=' . str_replace( '@', '', $str );

		$data = bluesky_fetch_data( $api_endpoint, 'GET', array(), $bluesky_session );

		if( array_key_exists( 'did', $data ) ) {

			// User DID FOUND: $data[ 'did' ]
			// User displayName: $data[ 'displayName' ]
			// User description: $data[ 'description' ]
			// Adding to $postdata...' );

			$byteStart = strpos( $text, $str );
			$byteEnd = $byteStart + strlen( $str );

			$facets[] = '{
        "index": {
          "byteStart": '.$byteStart.',
          "byteEnd": '.$byteEnd.'
        },
        "features": [
          {
            "$type": "app.bsky.richtext.facet#mention",
            "did": "'.$data[ 'did' ].'"
          }
        ]
      }';

		}
		else {

			// We have no DID. User handle NOT added to $postdata.

		}

	}

	// Facets only support links and mentions for now, but can be extended to support features like bold and italics in the future.

}

$postdata = '{
  "repo": "'.$bluesky_session[ 'did' ].'",
  "collection": "app.bsky.feed.post",
  "record": {
    "$type": "app.bsky.feed.post",
    "text": "' . $text . '",
		"langs": [ "' . implode( '", "', $langs ) . '" ],
    "createdAt": "' . date( 'c' ) . '"';

	if( !empty( $facets ) ) {

		// We have linkes and/or mentions, lets add them

		$postdata .= ',
    "facets": [
    ' . implode( ',', $facets ) . '
    ]';

	}

	if( $embed != '' ) {

		// We have link embed, lets add it

		$postdata .= ',' . $embed;

	}

$postdata .= '
  }
}';

// This is the whole array that we will send:
// echo '<pre>' . $postdata . '</pre>';

// Posting to Bluesky!

$api_endpoint = BLUESKY_API_ENDPOINT_AUTH . 'com.atproto.repo.createRecord';

$response = bluesky_fetch_data( $api_endpoint, 'POST', json_decode( $postdata ), $bluesky_session );

if( is_array( $response ) ) {

	if( array_key_exists( 'uri', $response ) ) {

		echo '<span style="color:white;background:green;">
		Great success!
		</span>';

		echo '<pre>'; print_r( $response ); echo '</pre>';

	}
	elseif( array_key_exists( 'error', $response ) ) {

		echo '<span style="color:white;background:red;">
		We have an error: ' . $response['message'] . '
		</span>';

	}
	else {

		echo '<span style="color:white;background:red;">
		Not sure what happened...
		</span>';

		echo '<pre>'; print_r( $response ); echo '</pre>';

	}

}
else {

	echo '<span style="color:white;background:red;">
	We did not receive a JSON?
	</span>';

	echo '<p>Trying to output as is: ' . $response . '</p>';

	echo '<pre>print_r: '; print_r( $response ); echo '</pre>';

}

// A successful post returns a JSON that looks like this:
// {"uri":"at://did:plc:53p7ljvdslyob4yee4l7ukr2/app.bsky.feed.post/3kch4xqry342m","cid":"bafyreibcldcgupnwpgvfoi47zsjuli2jiidbnmbswvgpwtdmchkpfnb3te"}
// Failed post:
// {"error":"InvalidRequest","message":"Input must have the property \"repo\""}

?>