<?php
// Increase memory limit if needed
ini_set('memory_limit', '256M');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["tarfile"]) && $_FILES["tarfile"]["error"] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES["tarfile"]["tmp_name"];
        $originalName = $_FILES["tarfile"]["name"];

        // Validate file extension: must be .tar.gz or .tgz
        if (!preg_match('/\.(tar\.gz|tgz)$/i', $originalName)) {
            echo "<p class='error'>❌ Error: The file must be a .tar.gz or .tgz archive.</p>";
            exit;
        }

        // Create a unique temporary directory for extraction
        $baseName = preg_replace('/\.(tar\.gz|tgz)$/i', '', $originalName);
        $extractedDir = "extracted_" . uniqid() . "_" . preg_replace('/\W+/', '_', $baseName);
        if (!mkdir($extractedDir, 0755, true)) {
            echo "<p class='error'>❌ Error: Could not create temporary directory.</p>";
            exit;
        }

        // Move the uploaded file into the temporary directory
        $uploadedFilePath = $extractedDir . "/" . $originalName;
        if (!move_uploaded_file($tmpName, $uploadedFilePath)) {
            echo "<p class='error'>❌ Error: Failed to move uploaded file.</p>";
            rrmdir($extractedDir);
            exit;
        }

        // Use PharData to extract the tarball
        try {
            $phar = new PharData($uploadedFilePath);
            if (preg_match('/\.(tar\.gz|tgz)$/i', $originalName)) {
                // Decompress to get a .tar file (does not remove the original)
                $tarPath = str_replace(array(".tar.gz", ".tgz"), ".tar", $uploadedFilePath);
                if (!file_exists($tarPath)) {
                    $phar->decompress();
                }
            } else {
                $tarPath = $uploadedFilePath;
            }

            // Open the decompressed tar archive and extract its contents
            $tarPhar = new PharData($tarPath);
            if (!$tarPhar->extractTo($extractedDir, null, true)) {
                echo "<p class='error'>❌ Error: Extraction failed.</p>";
                rrmdir($extractedDir);
                exit;
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error extracting tarball: " . htmlspecialchars($e->getMessage()) . "</p>";
            rrmdir($extractedDir);
            exit;
        }

        // Search for IODeviceTree.txt (case-insensitive)
        $iodeviceTreePath = findFileInDir($extractedDir, "IODeviceTree.txt");
        if (!$iodeviceTreePath) {
            echo "<p class='error'>❌ Error: 'IODeviceTree.txt' not found in the extracted files.</p>";
            rrmdir($extractedDir);
            exit;
        }

        // Read and parse IODeviceTree.txt
        $content = file_get_contents($iodeviceTreePath);
        $model = "N/A";
        $serialNumber = "N/A";
        $firstName = "N/A";
        $lastName = "N/A";
        $appleId = "N/A";

        // Extract model using regex
        if (preg_match('/model"\s*=\s*<"([^"]+)"/', $content, $matches)) {
            $model = $matches[1];
        }
        // Extract serial number
        if (preg_match('/IOPlatformSerialNumber"\s*=\s*"([^"]+)"/', $content, $matches)) {
            $serialNumber = $matches[1];
        }
        // Extract hex-encoded token for 'fmm-mobileme-token-FMM'
        if (preg_match('/"fmm-mobileme-token-FMM"\s+=\s+<([0-9a-fA-F]+)>/', $content, $matches)) {
            $hexData = $matches[1];
            $binaryData = hex2bin($hexData);
            if ($binaryData !== false) {
                if (strpos($binaryData, '<?xml') === 0) {
                    // XML plist - parse via SimpleXML
                    libxml_use_internal_errors(true);
                    $plist = simplexml_load_string($binaryData);
                    if ($plist !== false) {
                        $arr = xmlToArray($plist);
                        if (isset($arr['userInfo'])) {
                            $firstName = isset($arr['userInfo']['InUseOwnerFirstName']) ? $arr['userInfo']['InUseOwnerFirstName'] : "N/A";
                            $lastName  = isset($arr['userInfo']['InUseOwnerLastName']) ? $arr['userInfo']['InUseOwnerLastName'] : "N/A";
                        }
                        if (isset($arr['username'])) {
                            $appleId = $arr['username'];
                        }
                    }
                } elseif (strpos($binaryData, 'bplist') === 0) {
                    // Parse binary plist using our minimal parser
                    try {
                        $arr = parseBinaryPlist($binaryData);
                        if (isset($arr['userInfo'])) {
                            $firstName = isset($arr['userInfo']['InUseOwnerFirstName']) ? $arr['userInfo']['InUseOwnerFirstName'] : "N/A";
                            $lastName  = isset($arr['userInfo']['InUseOwnerLastName']) ? $arr['userInfo']['InUseOwnerLastName'] : "N/A";
                        }
                        if (isset($arr['username'])) {
                            $appleId = $arr['username'];
                        }
                    } catch(Exception $e) {
                        $firstName = "N/A (binary plist parsing failed)";
                        $lastName  = "N/A (binary plist parsing failed)";
                        $appleId   = "N/A (binary plist parsing failed)";
                    }
                } else {
                    $firstName = "N/A (binary plist parsing not supported)";
                    $lastName  = "N/A (binary plist parsing not supported)";
                    $appleId   = "N/A (binary plist parsing not supported)";
                }
            }
        }

        // Output the extracted information in a styled format
        echo "<div class='result'>";
        echo "<h2>🔍 Extracted Information:</h2>";
        echo "<p><strong>📌 Model:</strong> " . htmlspecialchars($model) . "</p>";
        echo "<p><strong>🔢 Serial Number:</strong> " . htmlspecialchars($serialNumber) . "</p>";
        echo "<p><strong>👤 First Name:</strong> " . htmlspecialchars($firstName) . "</p>";
        echo "<p><strong>👤 Last Name:</strong> " . htmlspecialchars($lastName) . "</p>";
        echo "<p><strong>📧 Apple ID:</strong> " . htmlspecialchars($appleId) . "</p>";
        echo "</div>";

        // Cleanup extracted files
        rrmdir($extractedDir);
    } else {
        echo "<p class='error'>No file uploaded or file upload error.</p>";
    }
}

/* ===== Helper Functions ===== */

/**
 * Recursively search for a file (case-insensitive) within a directory.
 */
function findFileInDir($dir, $fileName)
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && strcasecmp($file->getFilename(), $fileName) === 0) {
            return $file->getPathname();
        }
    }
    return false;
}

/**
 * Recursively remove a directory and its contents.
 */
function rrmdir($dir)
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($ri as $file) {
        if ($file->isDir()){
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

/**
 * Convert a SimpleXMLElement to an associative array.
 */
function xmlToArray($xml)
{
    $json = json_encode($xml);
    return json_decode($json, true);
}

/**
 * Recursively list all files in a directory (for debugging purposes).
 * (This function is no longer used in output.)
 */
function listExtractedFiles($dir)
{
    $files = [];
    $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($it);
    foreach ($ri as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

/* ===== Minimal Binary Plist Parser ===== */

/**
 * Parse a binary plist string and return it as an associative array.
 * Supports a limited subset of types: null, bool, integer, ASCII string (0x5),
 * Unicode string (0x6), array (0xA), and dictionary (0xD).
 */
function parseBinaryPlist($data) {
    if (substr($data, 0, 8) !== "bplist00") {
        throw new Exception("Not a valid binary plist");
    }
    // Read trailer (last 32 bytes)
    $trailer = substr($data, -32);
    $offsetIntSize = ord($trailer[6]);
    $objectRefSize = ord($trailer[7]);
    $numObjects = readUInt64(substr($trailer, 8, 8));
    $topObject = readUInt64(substr($trailer, 16, 8));
    $offsetTableOffset = readUInt64(substr($trailer, 24, 8));

    // Read offset table
    $offsets = array();
    $offsetTableData = substr($data, $offsetTableOffset, $numObjects * $offsetIntSize);
    for ($i = 0; $i < $numObjects; $i++) {
        $start = $i * $offsetIntSize;
        $offsetBytes = substr($offsetTableData, $start, $offsetIntSize);
        $offsets[$i] = readIntFromBytes($offsetBytes);
    }
    // Recursively parse the top object
    return parseObject($topObject, $data, $offsets, $objectRefSize);
}

/**
 * Parse an object given its index in the offset table.
 */
function parseObject($objIndex, $data, $offsets, $objectRefSize) {
    $offset = $offsets[$objIndex];
    $marker = ord($data[$offset]);
    $type = $marker >> 4;
    $info = $marker & 0x0F;
    $offset++; // Advance past marker

    $getCount = function($info, &$offset, $data) {
        if ($info == 15) {
            $newMarker = ord($data[$offset]);
            $offset++;
            $newType = $newMarker >> 4;
            $newInfo = $newMarker & 0x0F;
            if ($newType != 1) {
                throw new Exception("Extended count expected to be integer");
            }
            $length = 1 << $newInfo;
            $intBytes = substr($data, $offset, $length);
            $offset += $length;
            return readIntFromBytes($intBytes);
        } else {
            return $info;
        }
    };

    switch ($type) {
        case 0x0:
            if ($info == 0) return null;
            else if ($info == 8) return false;
            else if ($info == 9) return true;
            return null;
        case 0x1:
            $length = 1 << $info;
            $intBytes = substr($data, $offset, $length);
            $offset += $length;
            return readIntFromBytes($intBytes);
        case 0x5:
            $count = $getCount($info, $offset, $data);
            $str = substr($data, $offset, $count);
            $offset += $count;
            return $str;
        case 0x6:
            $count = $getCount($info, $offset, $data);
            $byteCount = $count * 2;
            $uStr = substr($data, $offset, $byteCount);
            $offset += $byteCount;
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($uStr, 'UTF-8', 'UTF-16BE');
            } else {
                return iconv('UTF-16BE', 'UTF-8//IGNORE', $uStr);
            }
        case 0xA:
            $count = $getCount($info, $offset, $data);
            $array = array();
            for ($i = 0; $i < $count; $i++) {
                $refBytes = substr($data, $offset, $objectRefSize);
                $offset += $objectRefSize;
                $objRef = readIntFromBytes($refBytes);
                $array[] = parseObject($objRef, $data, $offsets, $objectRefSize);
            }
            return $array;
        case 0xD:
            $count = $getCount($info, $offset, $data);
            $keys = array();
            $values = array();
            for ($i = 0; $i < $count; $i++) {
                $refBytes = substr($data, $offset, $objectRefSize);
                $offset += $objectRefSize;
                $keys[] = readIntFromBytes($refBytes);
            }
            for ($i = 0; $i < $count; $i++) {
                $refBytes = substr($data, $offset, $objectRefSize);
                $offset += $objectRefSize;
                $values[] = readIntFromBytes($refBytes);
            }
            $dict = array();
            for ($i = 0; $i < $count; $i++) {
                $k = parseObject($keys[$i], $data, $offsets, $objectRefSize);
                $v = parseObject($values[$i], $data, $offsets, $objectRefSize);
                $dict[$k] = $v;
            }
            return $dict;
        default:
            return null;
    }
}

function readIntFromBytes($bytes) {
    $len = strlen($bytes);
    $val = 0;
    for ($i = 0; $i < $len; $i++) {
        $val = ($val << 8) | ord($bytes[$i]);
    }
    return $val;
}

function readUInt64($bytes) {
    if (strlen($bytes) != 8) {
        throw new Exception("readUInt64 requires 8 bytes");
    }
    $parts = unpack("Nhi/Nlo", $bytes);
    return ($parts['hi'] * 4294967296) + $parts['lo'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tarball Extractor and Parser</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Apple-inspired clean design */
        body {
            background-color: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            font-weight: 500;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        input[type="file"] {
            padding: 8px 0;
            font-size: 16px;
        }
        input[type="submit"] {
            background-color: #0071e3;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        input[type="submit"]:hover {
            background-color: #005bb5;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-top: 2px solid #e5e5ea;
        }
        p {
            font-size: 16px;
            line-height: 1.5;
        }
        .error {
            color: #d93025;
            font-weight: 500;
        }
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 15px 20px;
            }
            input[type="submit"] {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Tarball</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="tarfile" id="tarfile" accept=".tar.gz,.tgz" onchange="fileSelected()" required>
            <br><br>
            <input type="submit" value="Upload and Extract">
        </form>
    </div>
    <script>
        function fileSelected() {
            var fileInput = document.getElementById("tarfile");
            if (fileInput.files.length > 0) {
                alert("File selected: " + fileInput.files[0].name);
            }
        }
    </script>
</body>
</html>
