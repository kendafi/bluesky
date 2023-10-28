<?php

/*
 * Post to your Bluesky profile
 *
 * Create your app password here and add it to $config below.
 * https://bsky.app/settings/app-passwords
 *
 * Load this file in your browser. Be careful - it posts on every reload.
 *
 */

// Comment out the following row to actually send text to Bluesky.
die( 'This file dies here to protect from accidentally posting anything.' );

$config = array(
	'bluesky-username' => 'USERNAME.bsky.social',
	'bluesky-password' => 'xxxx-xxxx-xxxx-xxxx'
);

$text = 'This is a test post via XRPC that will soon be deleted. Cheers @kenda.fi for the code ;) Here is a link: https://kenda.fi/';

// TODO: we should remove unwanted characters from $text

function debug( $str ) {

	echo htmlentities( $str ) . '<br>';

}

$curl = curl_init();

$postdata = array(
	'identifier' => $config[ 'bluesky-username' ],
	'password' => $config[ 'bluesky-password' ]
);

curl_setopt_array(
	$curl,
	array(
		CURLOPT_URL => 'https://bsky.social/xrpc/com.atproto.server.createSession',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => json_encode( $postdata ),
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		),
	)
);

if( $_SERVER['HTTP_HOST'] == 'localhost' ) {

	// skip SSL in localhost in case it doesn't support that
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );

}

$response = curl_exec( $curl );

curl_close( $curl );

$session = json_decode( $response, TRUE );

// Parse $text to see if we find links and user handles that should be mentioned separately.
// Not perhaps the best way, but $text should be quite short so this should be quick

$facets = array();
$embed = '';

$explode = explode( ' ', $text );

foreach( $explode as $str ) {

	//debug( 'We have string ' . $str );

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

			//debug( 'Removed: '.$last );
			//debug( 'New str: ' . $str );

		}

	}

	if( filter_var( $str, FILTER_VALIDATE_URL ) ) {

		// URL

    debug( $str . ' is a valid URL! Adding to data...' );

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

		debug( 'Done.' );

		// That should have taken care of the clickable link.
		// Now we will make it an embed.
		// Lets do that only if there is not yet a link embed.

		if( $embed == '' ) {

			debug( 'Adding link also as embed...' );

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

					debug( 'We have title: ' . $title );

				}
				elseif( $property == 'og:description' ) {

					$description = $content;

					debug( 'We have description: ' . $description );

				}
				elseif( $property == 'og:image' ) {

					debug( 'We have thumbnail: ' . $content );

					$headers = get_headers( str_replace( ' ', '%20', $content ) );

					foreach( $headers as $header ) {

						$header = strtolower( trim( $header ) );

						if( strstr( $header, 'content-type: image/' ) ) {

							$thumb_mime = str_replace('content-type: ', '', $header );

							debug( 'We have image mime type: ' . $thumb_mime );

							// Fetch image and upload to Bluesky

							debug( 'Trying to fetch image content...' );

							$img_data = file_get_contents( str_replace( ' ', '%20', $content ) );

							$curl = curl_init();

							if( $img_data != '' ) {

								// Save a local version to see if it really contains image data (debug)
								// file_put_contents( time() . '.jpg', $img_data );
								// It works! (view the file manually)

								curl_setopt_array(
									$curl,
									array(
										CURLOPT_URL => 'https://bsky.social/xrpc/com.atproto.repo.uploadBlob',
										CURLOPT_RETURNTRANSFER => true,
										CURLOPT_ENCODING => '',
										CURLOPT_FOLLOWLOCATION => true,
										CURLOPT_MAXREDIRS => 10,
										CURLOPT_TIMEOUT => 30,
										CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
										CURLOPT_CUSTOMREQUEST => 'POST',
										CURLOPT_POSTFIELDS => $img_data,
										CURLOPT_HTTPHEADER => array(
											'Content-Type: ' . $thumb_mime,
											'Authorization: Bearer ' . $session['accessJwt']
										),
									)
								);

								if( $_SERVER['HTTP_HOST'] == 'localhost' ) {

									// skip SSL in localhost in case it doesn't support that
									curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );

								}

								$response = curl_exec( $curl );

								curl_close( $curl );

								$response_array = json_decode( $response, true );

								echo '<pre>'; print_r( $response_array ); echo '</pre>';

								if( is_array( $response_array ) && array_key_exists( 'blob', $response_array ) ) {

									if( array_key_exists( 'ref', $response_array['blob'] ) ) {

										if( array_key_exists( '$link', $response_array['blob']['ref'] ) ) {

											$thumb_link = $response_array['blob']['ref']['$link'];
											$thumb_mime = $response_array['blob']['mimeType'];
											$thumb_size = $response_array['blob']['size'];

											debug( 'We have image link returned from Bluesky: ' . $thumb_link );
											debug( 'We have verified image size: ' . $thumb_mime );
											debug( 'We have verified image size: ' . $thumb_size );

										}

									}

								}

							}
							else {

								debug( 'Image data is EMPTY!' );

							}

						}
						/*
						elseif( strstr( $header, 'content-length: ' ) ) {

							$thumb_size = str_replace('content-length: ', '', $header );

							debug( 'We have image size: ' . $thumb_size );

						}
						*/

					}

				}

			}

			if( $title != '' && $description != '' ) {

				debug( 'Great success! We HAVE data to add link as an embed...' );

				$embed = '
        "embed": {
          "$type": "app.bsky.embed.external",
          "external": {
            "uri": "' . $str . '",
            "title": "' . $title . '",
            "description": "' . $description . '"';

				if( $thumb_link != '' && $thumb_mime != '' && $thumb_size != '' ) {

					debug( 'We ALSO have data to add thumbnail in the embed...' );

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

					debug( '...but we do NOT have data to add thumbnail in the embed.' );

				}

				$embed .= '
          }
        }';

				debug( 'Done.' );

			}
			else {

				debug( 'Fail. We do NOT have data to add link as an embed.' );

			}

		}

		debug( '' ); // results in only <br>, trying to make output more readable

	}
	elseif( substr( $str, 0, 1 ) == '@' && preg_match( '/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', str_replace( '@', '', $str ) ) ) {

		// User handle
		// https://atproto.com/specs/handle

		debug( 'User handle FOUND! ' . $str );

		// We need to get user DID value.

		debug( 'Trying to fetch DID.' );

		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL => 'https://bsky.social/xrpc/app.bsky.actor.getProfile?actor=' . str_replace( '@', '', $str ),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json',
					'Authorization: Bearer ' . $session['accessJwt']
				),
			)
		);

		if( $_SERVER['HTTP_HOST'] == 'localhost' ) {

			// skip SSL in localhost in case it doesn't support that
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );

		}

		$response = curl_exec( $curl );

		$data = json_decode( $response, TRUE );

		echo '<pre>'; print_r( $data ); echo '</pre>';

		if( array_key_exists( 'did', $data ) ) {

			debug( 'User DID FOUND: ' . $data[ 'did' ] );
			debug( 'User displayName: ' . $data[ 'displayName' ] );
			debug( 'User description: ' . $data[ 'description' ] );
			debug( 'Adding to $postdata...' );

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

			debug( 'Done.' );

		}
		else {

			debug( 'We have no DID. User handle NOT added to $postdata.' );

		}

		debug( '' ); // results in only <br>, trying to make output more readable

	}

	// Facets only support links and mentions for now, but can be extended to support features like bold and italics in the future.

}

$postdata = '{
  "repo": "'.$session[ 'did' ].'",
  "collection": "app.bsky.feed.post",
  "record": {
    "$type": "app.bsky.feed.post",
    "text": "' . $text . '",
		"langs": [ "sv-FI", "sv-SE" ],
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

debug( 'This is the whole array that we will send:' );

echo '<pre>' . $postdata . '</pre>';

debug( 'Posting to Bluesky...' );

$curl = curl_init();

curl_setopt_array(
	$curl,
	array(
		CURLOPT_URL => 'https://bsky.social/xrpc/com.atproto.repo.createRecord',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => $postdata,
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $session['accessJwt']
		),
	)
);

if( $_SERVER['HTTP_HOST'] == 'localhost' ) {

	// skip SSL in localhost in case it doesn't support that
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );

}

$response = curl_exec( $curl );

curl_close( $curl );

$response = json_decode( $response, TRUE );

if( is_array( $response ) ) {

	if( array_key_exists( 'uri', $response ) ) {

		echo '<span style="color:white;background:green;">';
		debug( 'Great success!' );
		echo '</span>';

		echo '<pre>'; print_r( $response ); echo '</pre>';

	}
	elseif( array_key_exists( 'error', $response ) ) {

		echo '<span style="color:white;background:red;">';
		debug( 'We have an error: ' . $response['message'] );
		echo '</span>';

	}
	else {

		echo '<span style="color:white;background:red;">';
		debug( 'Not sure what happened...' );
		echo '</span>';

		echo '<pre>'; print_r( $response ); echo '</pre>';

	}

}
else {

	echo '<span style="color:white;background:red;">';
	debug( 'We did not receive a JSON?' );
	echo '</span>';

	echo '<pre>'; print_r( $response ); echo '</pre>';

}

// A successful post returns a JSON that looks like this:
// {"uri":"at://did:plc:53p7ljvdslyob4yee4l7ukr2/app.bsky.feed.post/3kch4xqry342m","cid":"bafyreibcldcgupnwpgvfoi47zsjuli2jiidbnmbswvgpwtdmchkpfnb3te"}
// Failed post:
// {"error":"InvalidRequest","message":"Input must have the property \"repo\""}

debug( 'Done.' );

debug( '' ); // results in only <br>, trying to make output more readable

debug( 'End.' );

?>