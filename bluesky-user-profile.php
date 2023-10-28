<?php

/*
 * This gets a Bluesky user profile
 *
 * Create your app password here and add it to $config below.
 * https://bsky.app/settings/app-passwords
 *
 */

$config = array(
	'bluesky-username' => 'USERNAME.bsky.social',
	'bluesky-password' => 'xxxx-xxxx-xxxx-xxxx'
);

// The user whose profile you want to display.
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

if ( !is_array( $session ) ) { die( 'Missing data.' ); }

if ( empty( $session ) ) { die( 'Missing data.' ); }

if ( !array_key_exists( 'accessJwt', $session ) ) { die( 'Missing data.' ); }

$curl = curl_init();

curl_setopt_array(
	$curl,
	array(
		CURLOPT_URL => 'https://bsky.social/xrpc/app.bsky.actor.getProfile?actor='.$fetch_username,
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

//echo '<pre>'; print_r( $data ); echo '</pre>';

if( is_array( $data ) && !empty( $data ) && array_key_exists( 'handle', $data ) ) {

	echo '<div class="bsky-account"><p>';

	// Avatar
	echo '<img src="' . $data['avatar'] . '" alt="" width="60" align="right">';

	// Username
	echo '<strong>' . htmlentities( $data['displayName'] ) . '</strong><br>';
	echo '<a href="https://bsky.app/profile/'.$data['handle'].'" target="_blank">@'.$data['handle'].'</a><br><br>';

	// Description
	echo htmlentities( $data['description'] ) . '<br><br>';

	$date_time_format = 'j.n.Y @ H:i';

	echo '<small>';
	echo 'Account created: ' . date( $date_time_format, strtotime( $data['indexedAt'] ) ) . '<br>';
	echo 'Posts: ' . $data['postsCount'] . '<br>';
	echo 'Following: ' . $data['followsCount'] . '<br>';
	echo 'Followers: ' . $data['followersCount'] . '<br>';
	echo '</small>';

	echo '</p></div> <!-- bsky-account -->';

}

?>