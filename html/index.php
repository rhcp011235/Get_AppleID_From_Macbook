<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apple ID Extractor</title>
</head>
<body>
    <h1>Apple ID Extractor from NVRAM</h1>
    <form action="extract_nvram.php" method="post" enctype="multipart/form-data">
        <label for="file">Upload IODeviceTree.txt:</label>
        <input type="file" name="file" id="file" required>
        <br><br>
        <input type="submit" value="Extract Apple ID">
    </form>
    <br>
    <div id="output">
        <?php
        // Check if a file was uploaded
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
            // Move the uploaded file to a temporary location
            $upload_dir = sys_get_temp_dir();
            $uploaded_file = $upload_dir . '/' . basename($_FILES['file']['name']);
            move_uploaded_file($_FILES['file']['tmp_name'], $uploaded_file);

            // Include the PHP extraction logic here
            include 'extract_nvram.php';

            // Clean up by deleting the uploaded file
            unlink($uploaded_file);
        }
        ?>
    </div>
</body>
</html>

