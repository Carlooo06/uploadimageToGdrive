<?php
// Load Google API Client
require 'vendor/autoload.php';
use Google\Client;
use Google\Service\Drive;

session_start(); // Start the session to store the access token

// Check if the OAuth code is in the URL
if (isset($_GET['code'])) {
    // Initialize the Google Client
    $client = new Client();
    $client->setAuthConfig('credentials.json'); // Your Google API credentials
    $client->addScope(Drive::DRIVE_FILE); // Permission for Google Drive access
    $client->setRedirectUri('http://localhost/callback.php'); // Make sure this matches your redirect URI

    // Exchange the authorization code for an access token
    try {
        $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($accessToken['error'])) {
            throw new Exception('Error fetching access token: ' . $accessToken['error']);
        }

        // Save the access token in the session for future requests
        $_SESSION['access_token'] = $accessToken;

        // You can also store the access token in a database if needed for future use
        // Example: saveAccessToken($accessToken);

        // Redirect to the main page or wherever you want the user to go after successful authentication
        header('Location: /'); // Change this to your desired page
        exit;

    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
} else {
    // No code in URL, something went wrong
    echo 'Authorization code not received. Please try again.';
}
?>
