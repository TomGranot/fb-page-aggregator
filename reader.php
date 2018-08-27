<?php
$file = file_get_contents("posts.json");
// The true part sends back associative arrays, easy to work and play with.
$posts = json_decode($file, true);
// Handle reverse chronological order requests
if(isset($_GET["order"]) && ($_GET["order"] == "reversed")){
		$posts = array_reverse($posts);
}

// Get indices of posts - this is used for post identification and URL creation
$keys = array_keys($posts);
$postKey = 0;

// Get the last update, last row in fetch.log. A bit dangerous since I'm playing with the shell, but this is a clean fix, and
// secured by escaping the actual path. Also, no user input, so there's not really a risk here
$path = "fetch.log";
$file = escapeshellarg($path);
$updated = `tail -n1 $file`;
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Boottrap's things -->
 <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
	<title>Facebook Page Reader</title>
</head>
<body>
	<div class="container-fluid">
		<div class="row">
			<div class="col">
				<h1>Confession Reader</h1>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<h4> Showing the latest posts in <code>posts.json</code></h4>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<h6> <?=$updated;?></h6>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<a class="btn btn-primary" href="reader.php" role="button">Sort Chronologically (Newest To Oldest)</a>
				<a class="btn btn-primary" href="reader.php?order=reversed" role="button">Sort Chronologically (Oldest To Newest)</a>
			</div>
		</div>
		<?php foreach ($posts as $post){
			$time = date('Y-m-d h:i:s',$post['timestamp']);
			echo   "
			<div class=\"row\">
				<!-- Post message -->
				<div class=\"col\">
					<div class=\"card\">
						<div class=\"card-body\">
							<b>Message: </b>" . $post["message"] . " </br>
							<b>Posted On: </b> " . $time . " </br>
							<b>Post ID: </b> " . $keys[$postKey] . " </br>
							<a class=\"btn btn-primary\" href=https://www.facebook.com/" . $keys[$postKey] . " role=\"button\">Go To Post</a>  
						</div>
					</div>
				</div>
			</div>"
			;
			$postKey++;
		}
		?>
	</div>
</body>
</html>
