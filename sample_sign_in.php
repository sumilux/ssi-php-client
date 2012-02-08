<?php

include('SSI.php'); // our main PHP client library, please do not modify
session_start(); // required before using the SSI client

echo "<h2> Processing User Sign-In </h2>\n";
echo "<p><a href=\"index.php\">Back to Sample Page</a>\n";

if ( $_POST['stat'] != 'ok' ) {
    echo "User Sign-In Failed, error message: " . $_POST['errMsg'];
    die;
}

echo "<p>Processing incoming POST variables: <br>\n";
var_dump($_POST);

// Creating a new SSI service object
$widgetName = "githubsample";
$widgetSecret = "3fee29873770452f834919108262c300";
$ssi = new Services_Sumilux_SSI($widgetName, $widgetSecret);

// Fill the object with the credential we just received
$ssi->setToken($_POST['ssi_token']);

// Fetch the user attributes and display them
echo "<p>This user has the following attributes: <br>\n";
var_dump($ssi->getAttributes());

// TODO: display permanent UID:

echo "<p><h2> User Sign-In Processed Successfully!</h2>\n";

echo "<p>For more information this PHP library, please consult the
	<a href=\"http://www.sumilux.com/docs/ssi-php-client/\">
	reference documentation</a>";

?>



