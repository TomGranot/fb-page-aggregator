<?php

// Checks for session cookie. If exists - retrieve session ID, if not - create new one.
session_start();

// Remeber we installed FB PHP SDK using composer. This loads that SDK.
require_once( 'vendor/autoload.php' );
 
// Note that this creates a new Facebook object, which is part of the Facebook library. 
// Insert your App ID & Secret here.
$fb = new Facebook\Facebook([
  'app_id'                => 'YOUR_APP_ID',
  'app_secret'            => 'YOUR_APP_SECRET',
  'default_graph_version' => 'v3.0',
]);

// Try to get an access token & deal with exceptions
$helper = $fb->getRedirectLoginHelper(); 
try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When The Graph itself returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues occur - the SDK returns an error
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

// If at the process, you're still not left with an access token, and no FacebookSDKException was thrown, throw a detailed report as possible of what failed.
if (! isset($accessToken)) {
  if ($helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Bad request';
  }
  exit;
}

// Get the access token metadata from /debug_token. What this actually does, is create a new token metadata object and push the ORIGINAL acces token metadata into it, then echo it all out.
$oAuth2Client = $fb->getOAuth2Client(); 
$tokenMetadata = $oAuth2Client->debugToken($accessToken);

// Display the ORIGINAL, not long-lived, access token
echo '<h3>$accesToken</h3>';
echo "<pre>"; print_r($accessToken); echo "</pre>";

// Make sure everything's OK with the token - insert your app ID here.
$tokenMetadata->validateAppId('YOUR_APP_ID');
$tokenMetadata->validateExpiration();
 
// If you failed to get a long-lived token, or this is the first time you run this script
if (! $accessToken->isLongLived()) {
  // Exchanges a short-lived access token for a long-lived one
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
    exit;
  }

// Display the new, long-lived access token
  echo '<h3>Long-lived</h3>';
  var_dump($accessToken->getValue());
}
 
// Add the current access token to the session.
$_SESSION['fb_access_token'] = (string) $accessToken;