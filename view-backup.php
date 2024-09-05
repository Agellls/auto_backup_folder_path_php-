<?php
session_start();
include 'vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig("REPLACE YOUR FILENAME");
$client->addScope(Google_Service_Drive::DRIVE);

$service = new Google_Service_Drive($client);

// Authenticate the client
if (isset($_SESSION['upload_token'])) {
    $client->setAccessToken($_SESSION['upload_token']);
} else {
    $authUrl = $client->createAuthUrl();
    header("Location: $authUrl");
    exit;
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $fileId = $_GET['delete'];
    try {
        // Permanently delete the file (bypass trash)
        $service->files->delete($fileId, array('supportsAllDrives' => true));
        echo "<div class='alert alert-success'>File deleted permanently.</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>An error occurred: " . $e->getMessage() . "</div>";
    }
}

// Handle delete all files
if (isset($_POST['delete_all'])) {
    $filesToDelete = $service->files->listFiles(array(
        'pageSize' => 10, // Adjust the page size as needed
        'fields' => 'files(id)'
    ))->getFiles();

    foreach ($filesToDelete as $file) {
        try {
            // Permanently delete each file
            $service->files->delete($file->getId(), array('supportsAllDrives' => true));
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>An error occurred while deleting file " . $file->getId() . ": " . $e->getMessage() . "</div>";
        }
    }

    // Optional: Empty the trash after deleting all files
    try {
        $service->files->emptyTrash();
        echo "<div class='alert alert-success'>All files and trash emptied successfully.</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>An error occurred while emptying the trash: " . $e->getMessage() . "</div>";
    }
}

// List files
$results = $service->files->listFiles(array(
    'pageSize' => 10, // Adjust the page size as needed
    'fields' => 'nextPageToken, files(id, name, webViewLink)'
));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Files</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Uploaded Files</h2>
            <!-- Delete All Form -->
            <form method="post" action="">
                <button type="submit" name="delete_all" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete all files?');">Delete All Files</button>
            </form>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($results->getFiles()) == 0) {
                    echo "<tr><td colspan='2'>No files found.</td></tr>";
                } else {
                    foreach ($results->getFiles() as $file) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($file->getName()) . "</td>";
                        echo "<td>";
                        echo "<a href='" . $file->getWebViewLink() . "' class='btn btn-primary btn-sm' target='_blank'>View</a> ";
                        echo "<a href='?delete=" . $file->getId() . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this file?\");'>Delete</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS (optional, for certain Bootstrap features) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>