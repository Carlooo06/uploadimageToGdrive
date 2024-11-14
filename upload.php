<?php
require 'vendor/autoload.php'; // Include the Google API Client
use Google\Client;
use Google\Service\Drive;
use mysqli;

session_start(); // Start the session to store the access token

// Database connection (replace with your actual database credentials)
$mysqli = new mysqli("localhost", "root", "", "gdriveupload");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the request method is POST and if the image is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    // Image upload handling
    $imageFile = $_FILES['image'];
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    $imageName = uniqid() . '.' . pathinfo($imageFile['name'], PATHINFO_EXTENSION);
    $webpFileName = pathinfo($imageName, PATHINFO_FILENAME) . '.webp'; // WebP file name
    $webpPath = $uploadDir . $webpFileName;

    // Create an Imagick object and convert the image to WebP format with compression
    $imagick = new Imagick($imageFile['tmp_name']);
    $imagick->setImageFormat('webp');
    $imagick->setCompression(Imagick::COMPRESSION_WEBP);
    $imagick->setCompressionQuality(80); // Adjust compression quality (1-100)
    
    // Write the WebP file (this replaces the temporary saving of the original image)
    $imagick->writeImage($webpPath);

    // Google OAuth 2.0 Authentication
    $client = new Client();
    $client->setAuthConfig('credentials.json'); // Your Google API credentials
    $client->addScope(Drive::DRIVE_FILE);
    $client->setRedirectUri('http://localhost/callback.php'); // Replace with your actual redirect URI

    // Handle token authentication (check if token is already set or expired)
    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        $client->setAccessToken($_SESSION['access_token']);
        
        // Check if the token is expired and refresh it if necessary
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->refreshToken($client->getRefreshToken());
                $_SESSION['access_token'] = $client->getAccessToken(); // Update session with new token
            } else {
                // If no refresh token, redirect to the OAuth authorization URL
                $authUrl = $client->createAuthUrl();
                header("Location: $authUrl");
                exit;
            }
        }
    } else {
        // If no token found, prompt for authorization
        $authUrl = $client->createAuthUrl();
        header("Location: $authUrl");
        exit;
    }

    // Save the token for future requests
    $_SESSION['access_token'] = $client->getAccessToken();

    // Now you can use the authenticated Google Drive service
    $driveService = new Drive($client);

    // Prepare the file metadata for Google Drive upload
    $fileMetadata = new Drive\DriveFile([
        'name' => $webpFileName,
        'parents' => ['1AXLlCwJdhqzmZJNapWY5fHFPPRM--Ogs'] // Specify your folder ID
    ]);
    $content = file_get_contents($webpPath);

    try {
        // Upload the file to Google Drive
        $file = $driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'image/webp',
            'uploadType' => 'multipart'
        ]);

        // Get the file ID and URL for database insertion
        $fileId = $file->id;
        $fileUrl = "https://drive.google.com/uc?id=$fileId";

        // Delete the image from the server's uploads folder after successful upload

        // Insert file details into the database
        $stmt = $mysqli->prepare("INSERT INTO images (image_name, file_id, file_url, uploaded_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $webpFileName, $fileId, $fileUrl);

        if ($stmt->execute()) {
            // Output success message and redirect with an alert
            echo "<script>alert('Image uploaded successfully and saved to database!'); window.location.href = '/';</script>";
        } else {
            echo "<script>alert('Error inserting image details into database.'); window.location.href = '/';</script>";
        }

        $stmt->close();
    } catch (Exception $e) {
        // Handle error and display the message with an alert
        $errorMessage = $e->getMessage();
        echo "<script>alert('Error uploading the image: $errorMessage'); window.location.href = '/';</script>";
    }
} else {
    echo "<script>alert('No image selected.'); window.location.href = '/';</script>";
}

$mysqli->close();
?>
