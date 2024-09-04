<?php
session_start();
include 'vendor/autoload.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to zip a folder
function zipFolder($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } else if (is_file($file) === true) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($source) === true) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}

// Google Drive API setup with Service Account
$client = new Google_Client();
$client->setAuthConfig('REPLACE YOUR FILENAME'); //set your file name
$client->addScope(Google_Service_Drive::DRIVE);
$service = new Google_Service_Drive($client);

// Define folder path and zip file name
$folderPath = 'YOUR PATH HERE'; //example : /home/domain.com/public_html/test/assets
$dateNow = date('d-m-Y'); //datenow
$zipName = 'backup_assets_' . $dateNow; //output file name
$zipFilename = $zipName . '.zip'; //file type
$zipPath = sys_get_temp_dir() . '/' . $zipFilename;

// Create ZIP file
if (zipFolder($folderPath, $zipPath)) {
    // Check if ZIP file was created successfully
    if (file_exists($zipPath)) {
        echo 'ZIP file created successfully: ' . $zipFilename . '<br>';

        // Attempt to upload the file to Google Drive
        $file = new Google_Service_Drive_DriveFile();
        $file->setName($zipFilename);

        try {
            $result = $service->files->create($file, array(
                'data' => file_get_contents($zipPath),
                'mimeType' => 'application/zip',
                'uploadType' => 'multipart'
            ));

            if ($result) {
                // Get the file ID and create a shareable link
                $fileId = $result->id;

                // Set the file permissions to public
                $permission = new Google_Service_Drive_Permission();
                $permission->setType('anyone');
                $permission->setRole('reader');
                $service->permissions->create($fileId, $permission);

                // Add permission for specific email
                $emailPermission = new Google_Service_Drive_Permission();
                $emailPermission->setType('user');
                $emailPermission->setRole('writer');  // Change to 'writer' or 'editor'
                $emailPermission->setEmailAddress('YOUR EMAIL HERE'); //set your email
                $service->permissions->create($fileId, $emailPermission);

                // Create a shareable link
                $link = "https://drive.google.com/file/d/" . $fileId . "/view";

                echo 'Download Link: <a href="' . $link . '" target="_blank">' . $link . '</a><br>';
            } else {
                echo 'Failed to upload the file.<br>';
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . '<br>';
        }

        // Clean up the temporary zip file
        unlink($zipPath);
    } else {
        echo 'ZIP file was not created.<br>';
    }
} else {
    echo 'Failed to create zip file.<br>';
}
