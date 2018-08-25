<?php

// Checks for session cookie. If exists - retrieve session ID, if not - create new one.
session_start();

// Remeber we installed FB PHP SDK using composer. This loads it.
require_once( 'vendor/autoload.php' );
 
// Note that this creates a new Facebook object, which is part of the Facebook library. 
$fb = new Facebook\Facebook([
  'app_id'                => 'YOUR_APP_ID',
  'app_secret'            => 'YOUR_APP_SECRET',
  'default_graph_version' => 'v3.0',
]);

// Builds our query for the FB login, then attempts to login with the proper permissions. Asked for more permissions than neccessary, just in case.
$helper = $fb->getRedirectLoginHelper();
$permissions = array(
	'email',
	'manage_pages',
	'public_profile'
);
$loginUrl = $helper->getLoginUrl('https://FULL_PATH/fb-callback.php', $permissions);

// Finally, a login link appears. This should send you to a login page, or throw you to fb-callback.php if you're already logged in
echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
?>