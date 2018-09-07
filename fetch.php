<?php

// Get the credentials for accessing the FB API (see Readme for details)
require "config.inc.php";

// Checks for session cookie. If exists - retrieve session ID, if not - create new one.
session_start();

// Remeber we installed FB PHP SDK using composer. 
require_once('vendor/autoload.php' );
// Some helper functions I wrote for this script

// Note that this creates a new Facebook object, which is part of the Facebook library. 
$fb = new Facebook\Facebook([
  'app_id'                => $AppID,
  'app_secret'            => $AppSecret,
  'default_graph_version' => 'v3.0',
]);

// Rids us of having to enter the access token manually all the time ($accessToken is brought
// over from config.inc.php)
$fb->setDefaultAccessToken($accessToken);

// Store token in session for future use (since we won't have it if we haven't visited login.php in this session)
$_SESSION['fb_access_token'] = (string) $accessToken;

// Get first 100 pages of the page - in reverse order to ease the create of the array in order
$page_path = $PageName . '/feed?limit=100&order=reverse_chronological';
$res = $fb->get($page_path);
// An edge is a set of two points - you can think about it as the line connecting the two
$edge = $res->getGraphEdge();

// Check if the JSON file is empty or not
$JSONEmpty = isJSONFileEmpty("posts.json");
$posts = array();
$numPosts = 0;

// If the file is NOT empty, we want to check which posts exist in it and which do not.
// We act under the (not unreasonable) assumption that the new posts we pull are newer than the ones already in the file.
// This gets all the keys ()
if(!($JSONEmpty)){
  $file = file_get_contents("posts.json");
  $existing_posts = json_decode($file, true);
  $posts = $existing_posts;
  $existing_keys = array_keys($existing_posts);
}

// Loop through all the edges - each one of them containing 100 posts - and get the posts (like running through a singly-linked list)
while($edge){
  if($edge){
    foreach ($edge as $post){
      $id = $post->getField('id');
      // Check that this post is not already in the file, and later add it if it isn't
      if(!($JSONEmpty)){
        $break = False;
        
        // Run through the array keys and check for existing keys
        for($i = 0; $i < count($existing_keys); $i++){
          if($existing_keys[$i] == $id){
            $break = True;
            break;
          }
        }
        
        // If the key is exists in the file, there's no reason to continue running
        if($break){
          break;
        }   
      }
    // create an array element for the new post
    $timestamp_raw = $post->getField('created_time');
    $timestamp = $timestamp_raw->getTimeStamp();
    $posts[$id]['timestamp'] = $timestamp;
    $posts[$id]['message'] = $post->getField('message');
    $numPosts++;
    }
  }
  // Continue looping through all edges
  $edge = $fb->next($edge);
}

// The array is currently in a reverse state, reverse it back before storing
$posts = array_reverse($posts);

// Show message if there are no new posts to update
if($numPosts == 0){
  echo "No New Posts, Not Modifying posts.json";
  die();
}

// There are some posts to update, do so
try {
  // The JSON_PRETTY_PRINT is just for show, not really neccessary
  $newPosts = json_encode($posts, JSON_PRETTY_PRINT);
  file_put_contents("posts.json", $newPosts);
  // Log the operation to the log file
  $t = time();
  $string = "Updated on " . date("d-m-Y") . " at " . date("h:i:sa") . ", file contains " . count($posts) . " posts \n";   
  file_put_contents("fetch.log", $string, FILE_APPEND);
  // Display what you just did
  echo "Fetch completed sucessfully, fetched " . $numPosts . " new posts from your page.";
} catch (Exception $e){
  // Log the error to the log file
  echo "Caught exception:" . $e->getMessage() . "\n";
  $string = "Failed on ". date("d-m-Y") . " at " . date("h:i:sa") . ", Caught exception:" . $e->getMessage() . "\n";
  file_put_contents("fetch.log", $string, FILE_APPEND);
  echo $string;
}


/*
* Checks whether the JSON file containing the posts is empty or not.
* Returns True if the file is empty or does not exist (and creates the file, in the latter case)
* Returns False if the is not empty (contains posts)
*/
function isJSONFileEmpty($filename){
  // No posts file - create it
  if(!(file_exists($filename))){
  $file = fopen($filename, "w");
  echo "No posts.json file, created one. <br>";
  fclose($file);
  return True;
  } else {
    // File is empty - just add the posts as you normally would
    if(filesize($filename) == 0){
      echo "posts.json file is empty<br> ";
      return True;
    // File is not empty - check which posts exist and which don't, append only the ones that need appending
    } else {
      echo "posts.json file is not empty <br>";
      return False;
    }
  }
}

?>