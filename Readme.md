# fb-page-aggregator

## Intro
![badge 1](https://img.shields.io/badge/FB%20Graph%20API-V3.0-orange.svg)
![badge 2](https://img.shields.io/badge/FB%20PHP%20SDK-V5.0-green.svg)
![badge 2](https://img.shields.io/badge/Repo%20Status-Maintained-blue.svg)

This small script does exactly one thing: It takes the posts of a certain Facebook page, and stores the ID (and therefore the post link), message and publishing time of each post in a JSON file. If you set up the relevant `cron` job on your server, it will also update itself with new posts as they come along (see the Update Posts section below for more info on that). Just for show, I've added a small `reader.php` file that shows the data available using this script. If you want some background to why I wrote this, check out [Why](https://github.com/tomgs/fb-page-aggregator#why) below.

**This Is An Actively Maintained Repo - Just Open An Issue If There's A Problem**

## Page Access Is Required For This Script To Work

This script is built around the premise that **you're allowed** to use the [Graph API](https://developers.facebook.com/docs/graph-api/) to access the posts of the page in question. This is not true for everyone anymore - see [Authentication Concerns](https://github.com/tomgs/fb-page-aggregator#authentication-concerns) for more on that topic. If you can't access the page in question, **don't** use this script.

## Requirements

1. A [Facebook App](https://developers.facebook.com/docs/apps/) - either with or without the [manage_pages](https://developers.facebook.com/docs/facebook-login/permissions/#reference-manage_pages) permission ([see below]((https://github.com/tomgs/fb-page-aggregator#authentication-concerns))). I will not cover the creation of an app here, nor the proper configuration for Facebook Login, but note that the app can be in any of the [development cycle stages](https://developers.facebook.com/docs/apps/managing-development-cycle/) and should work the same (I've tested this in apps in Development and in Test mode). 
2. A valid Facebook App ID and Facebook App Secret - obtained from the app you created above. You will need to input these later in some spots in the script.
3. A [Facebook Page Access Token](https://developers.facebook.com/docs/facebook-login/access-tokens/#pagetokens) - I've written a small script (see [Usage](https://github.com/tomgs/fb-page-aggregator#usage) below) that helps you get it without too much fuss (borrowing substantially [from the docs](https://developers.facebook.com/docs/php/howto/example_facebook_login)). Note, again, that you need an access token with the proper permissions, so if you don't have the permissions this won't work.
4. An up-and-running *remote* server (I tested this on an AWS Ubuntu t2.micro instance), with PHP installed. I've tried running this from my local [MAMP](https://www.mamp.info/en/) installation, but I failed to properly configure them in my App's dashboard.
5. The [Facebook PHP SDK](https://developers.facebook.com/docs/reference/php/), installed via [Composer](https://getcomposer.org/) ([see instructions here](https://github.com/facebook/php-graph-sdk)).

## Structure
* `login.php` - Login script, used to get long-lived access token from the 
* `fb-callback.php` - Catches the response back from Facebook, shows the long-lived access token required for the proper operation of this script.
* `fetch.php` - Checks if there are new posts to fetch from your page's feed,  and stores them in `posts.json`. If it is the first time you run the script, it creates `posts.json` and stores the posts there. If it's not the first run, checks for new posts and updates them in the file.
* `fetch.log` - A log of all the pulls you performed from the API. Just `cat` it from the terminal whenever you want to see the latest pulls.
* `posts.json` - Will be created in the first run of the script. Hosts all the FB page posts you've pulled. You can see the file's structure [below](https://github.com/tomgs/fb-page-aggregator#postsjson).
* `reader.php` - Displays the posts in the `posts.json` file.

## Usage

### Getting The Access Token
As I mentioned, `login.php` should take care of getting a [long-lived access token](https://developers.facebook.com/docs/facebook-login/access-tokens/expiration-and-extension/) (good for 60 days):

1. Open `login.php` and `fb-callback.php` in a text editor.
2. In both files, edit the `Facebook/Facebook` object with your App ID and your App Secret.
3. In `login.php`, edit the `getLoginUrl` parameter with the same callback URL you specified in your App's settings. Make sure to include the **full** path to `fb-callback.php` on your server.
4. In `fb-callback.php`, edit the `validateAppId` with your App ID.
4. Access `login.php` in a browser, then click the login link and follow the instructions. 
5. In the last page, you will see the original access token you received from Facebook, and another long-lived access token below. Take the latter and copy it, we'll need it for later. - Note that it also shows exactly how long your token will last.

### Setting Up `fetch.php`

Now we need to tell the script which page we want to pull the posts from.

1. Open `fetch.php` in a text editor.
2. Edit the `Facebook/Facebook` object with your App ID and your App Secret.
3. Edit `$accessToken` with the access token acquired in the previous stage.
4. Edit `$fb->get` with the *name* of your FB page (if your page's address is `https://www.facebook.com/Squidward/`, then your page's name is Squidward).
5. Access `fetch.php` from a web browser, and let it do its thing. Note: it can take 10-15 seconds on a relatively fast connection for the script to run while it fetched, arranges and stores the posts in `posts.json`. 

### Seeing The Posts

If you've ran `fetch.php` at least once without errors, then `posts.json` should include all the posts of the page. Access `reader.php` to see all the posts, their publishing time and a like to each one.

### Setting Up Automatic Updates Using A Job Daemon
In practice, this script can and should be run on a constant basis to retrieve and store new posts as they are posted. As to not offend the gods over at Facebook ([see below](https://github.com/tomgs/fb-page-aggregator#facebook-api-throttling)), for most pages it's more than enough to run `fetch.php` every 3 minutes or so using `cron` or your OS's favourite job daemon.

**Note:** The access token needs to be changes every 60 days. There isn't currently an automated way to do this. Contributions welcome:)

## `posts.json`

The JSON file we're generating is build as a collection of JSON objects, each object representing a single post, with the following structure:

```json
    "post_id": {
        "timestamp": post_unix_timestamp,
        "message": "post_message"
    }
```

## Authentication Concerns

Since the [Facebook-Cambridge Analytica thing exploded](https://en.wikipedia.org/wiki/Facebook%E2%80%93Cambridge_Analytica_data_scandal), Facebook has imposed some limitations on accessing public page data. More specifically, we can divide the access of pages into 2 cases based on the association you have with the page in question:

1. Pages in which you have a [page role](https://www.facebook.com/help/289207354498410?helpref=about_content) - Can be accessed using the method described above.
2. Pages in which you do not have a page role - can be accessed only if your app has the [manage_pages](https://developers.facebook.com/docs/facebook-login/permissions/#reference-manage_pages), which can be granted to it during the [App Review process](https://developers.facebook.com/docs/apps/review/#app-review) performed by Facebook. Note: This is not an easy process to go through, and make take weeks (or months) at a time to get your app approved.

## Facebook API Throttling
[By design](https://developers.facebook.com/docs/graph-api/advanced/rate-limiting/), Facebook throttles the requests you make to ensure you wouldn't bomb their servers. If you get an API Error #4, that's likely the cause. 

**Note:** You can see the status of your API calls in your app's dashboard. I recommend looking at it while working with `fetch.php` to ensure you're not crossing the threshold.

## Troubleshooting

1. **File Permissions:** Make sure `fetch.php` can access `posts.json` and `fetch.log` - i.e. make sure the file has the correct permissions (on linux machines).
2. **`reader.php` crashing:** At least twice during my testing I messed up a `posts.json` file by appending things that I shouldn't have to it. If you get errors while reading the posts in `reader.php`, it's almost always due to a `posts.json` problem. Delete the current file and start anew.
3. **Facebook API Errors:** If at any point you've crossed the FB API query limit, you're gonna get a [Graph API Error #4](https://stackoverflow.com/questions/28554422/facebook-graph-api-4-error-application-request-limit-reached). When that happens - wait for 10-20 minutes and try again. 

## Why

This came about when I was building a page aggregator for the ever-entertaining [IDC Confessions](https://www.facebook.com/IDCHerzliyaConfessions/) page. This type of pages started popping up around Israeli universities & colleges around 2017-2018, and feature an anonymous way to share personal issues, thoughts and (most often) running gags by Israeli students in these fine establishments. If you don't read Hebrew, trust me - they're addictive.

I wanted to have a way to access all the posts and play with them for a bit (sort them by date, by amount of likes/comments, etc...). In order to avoid hitting the [API limits](https://developers.facebook.com/docs/graph-api/advanced/rate-limiting/) (and make the app significantly faster), this meant I'd have to save a list of all the posts in a file somewhere. Hence - this repo.