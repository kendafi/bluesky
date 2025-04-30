# Bluesky connection examples #

These are simple examples how to connect with Bluesky.
They may lack proper validation. See comments in the files
for more information.

Make a copy of `config.example.php` or just rename it to `config.php`
and add your details into it.

Create your Bluesky app password here:
[bsky.app/settings/app-passwords](https://bsky.app/settings/app-passwords)

API documentation
[docs.bsky.app](https://docs.bsky.app/)

Videos come as playlists. A JavaScript library that implements an HTTP Live Streaming client
is required for playlists to work in all modern browsers. You can use `hls.js`. Get it from here:
[www.jsdelivr.com/package/npm/hls.js](https://www.jsdelivr.com/package/npm/hls.js)

If you want to keep your webpage fast you may want to only display images
instead of the whole video embed that also requires that heavy JavaScript library mentioned above.
Look for `Image only that links to original post` in file `common-functions.php`.

Have fun!