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

$config = array(
	'bluesky-username' => 'USERNAME.bsky.social',
	'bluesky-password' => 'xxxx-xxxx-xxxx-xxxx'
);

$text = 'This is a test post via XRPC. Cheers @kenda.fi for the code ;)';

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

$curl = curl_init();

curl_setopt_array(
	$curl,
	array(
		CURLOPT_URL => 'https://bsky.social/xrpc/com.atproto.repo.createRecord',
		CURLOPT_SSL_VERIFYPEER => false, // skip SSL because I don't have that in localhost
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => '{
			"repo":"' . $session['did'] . '",
			"collection":"app.bsky.feed.post",
			"record":{
				"$type":"app.bsky.feed.post",
				"createdAt":"' . date('c'). '",
				"text":"' . $text . '"
			}
		}',
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $session['accessJwt']
		),
	)
);

$response = curl_exec( $curl );

curl_close( $curl );

?>