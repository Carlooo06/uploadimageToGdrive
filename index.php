<?php
require 'vendor/autoload.php'; // Include the Google API Client
use Google\Client;
use Google\Service\Drive;

session_start();

// Fetch images from the database
$conn = new mysqli("localhost", "root", "", "gdriveupload");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM images";
$result = $conn->query($sql);
$databaseImages = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $databaseImages[] = $row;  // Store image details from database
    }
}
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload & Display Images</title>
</head>
<body>

<!-- Upload Image Form -->
<form action="upload.php" method="post" enctype="multipart/form-data">
    <label for="imageUpload">Select image:</label>
    <input type="file" id="imageUpload" name="image" accept="image/*" onchange="previewImage(event)" required>
    <br>
    <img id="imagePreview" src="" alt="Preview" style="max-width: 300px; display: none;">
    <p id="fileSize" style="display: none;"></p>
    <br>
    <button type="submit" name="upload">Upload Image</button>
</form>

<!-- Display Images from Database -->
<h2>Images from Database</h2>
<div id="databaseImages">
    <?php
    if (count($databaseImages) == 0) {
        echo "<p>No images found in the database.</p>";
    } else {
        foreach ($databaseImages as $image) {
            // Construct the URL for the database image (it should be stored with the file_id from Google Drive)
            echo "<div style='display:inline-block; margin-right: 10px;'>
                    <img src='" . $image['file_url'] . "' alt='" . $image['image_name'] . "' style='max-width: 200px; height: auto;'>
                    <p>" . $image['file_id'] . "</p>
                  </div>";
        }
    }
    ?>
    </div>
</div>

<script>
    function previewImage(event) {
        const file = event.target.files[0];
        const reader = new FileReader();
        
        // Show image preview
        reader.onload = function() {
            const preview = document.getElementById('imagePreview');
            preview.src = reader.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);

        // Show file size
        const fileSizeElement = document.getElementById('fileSize');
        const fileSizeInKB = (file.size / 1024).toFixed(2);
        fileSizeElement.textContent = `File size: ${fileSizeInKB} KB`;
        fileSizeElement.style.display = 'block';
    }
</script>

</body>
</html>
