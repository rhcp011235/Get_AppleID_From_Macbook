<?php
// Ensure the file was uploaded successfully
if (isset($uploaded_file) && file_exists($uploaded_file)) {
    // Read the file content in binary mode
    $content = file_get_contents($uploaded_file);

    // Define the email pattern
    $email_pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

    // Search for the 'nvram-proxy-data' key and capture the binary data
    if (preg_match('/"nvram-proxy-data"\s*=\s*<([0-9a-fA-F]+)>/', $content, $matches)) {
        $binary_data = $matches[1];

        // Convert the hex string to binary
        $binary_bytes = hex2bin($binary_data);

        // Search for the email address in the binary data
        if (preg_match($email_pattern, $binary_bytes, $email_matches)) {
            $email_address = $email_matches[0];
            echo "<p><strong>Apple ID:</strong> " . htmlspecialchars($email_address) . "</p>";
        } else {
            echo "<p>No email address found in the binary data.</p>";
        }
    } else {
        echo "<p>nvram-proxy-data key not found.</p>";
    }
} else {
    echo "<p>Error: File not found or not uploaded correctly.</p>";
}
?>

