<?php

/*
 * This gets Bluesky user profiles
 *
 * Documentation
 * https://docs.bsky.app/docs/api/app-bsky-actor-get-profiles
 *
 */

$bluesky_usernames = array(
	'nyheter.bsky.social',
	'poliisintiedotteet.bsky.social',
	'polisen.bsky.social',
	'kkv-fi.bsky.social',
	'verkkotunnukset.bsky.social',
);

include 'config.php';
include 'common-functions.php';

$api_endpoint = BLUESKY_API_ENDPOINT_AUTH . 'app.bsky.actor.getProfiles?actors=' . implode( '&actors=', $bluesky_usernames );

$bluesky_session = bluesky_open_session();

$bluesky_array = bluesky_fetch_data( $api_endpoint, 'GET', false, $bluesky_session );

if ( array_key_exists( 'profiles', $bluesky_array ) ) {

	foreach ( $bluesky_array[ 'profiles' ] as $profile_array ) {

		echo bluesky_author_and_datetime( $profile_array );

	}

}

?>