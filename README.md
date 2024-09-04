# Auto backup folder path to php without login account
The first time is you have to download Google api php client and extract with the same folder with <b>upload.php</b>

<dl>
  <dt>Google api php client</dt><dd><a href="https://github.com/googleapis/google-api-php-client/releases">https://github.com/googleapis/google-api-php-client/releases</a>
</dl>

Then you have to Create a Service Account

<dl>
  <dt>Create a Service Account : </dt>
  <dd>1. Go to the <a href='https://console.cloud.google.com/'>Google Cloud Console</a> and scroll down until find 'IAM & Admin'
  <dd>2. Goto 'Service Accounts' then '+ Create a new service account'.
  <dd>3. Input Service Account Name, create and continue > add your email > add the role 'Editor' and 'Owner'
  <dd>3. Download the JSON key file associated with this service account and rename what do you want in this case i filled name <b>key.json</b>
</dl>

## Edit file upload.php

You need some configuration in [upload.php](https://github.com/Agellls/auto_backup_folder_path_php_without_login_account/blob/master/upload.php)

### Change the credentials name file

```php
// Google Drive API setup with Service Account
$client = new Google_Client();
$client->setAuthConfig('REPLACE YOUR FILENAME'); //set your file name
$client->addScope(Google_Service_Drive::DRIVE);
```

### Set folder path and name output file zip

```php
// Define folder path and zip file name
$folderPath = 'YOUR PATH HERE'; //example : /home/domain.com/public_html/test/assets
$dateNow = date('d-m-Y'); //datenow
$zipName = 'backup_assets_' . $dateNow; //output file name
$zipFilename = $zipName . '.zip'; //file type
```

### Set sharing file with defined email

```php
// Add permission for specific email
$emailPermission = new Google_Service_Drive_Permission();
$emailPermission->setType('user');
$emailPermission->setRole('writer');  // Change to 'writer' or 'editor'
$emailPermission->setEmailAddress('YOUR EMAIL HERE'); //set your email
$service->permissions->create($fileId, $emailPermission);
```

Finally, we can call the file from the corn job now, example i want call the [upload.php](https://github.com/Agellls/auto_backup_folder_path_php_without_login_account/blob/master/upload.php) from my hosting CyberPanel

```
curl https://rummmor.com/test/auto-login-upload.php > /dev/null 2>&1
```

then set the time of corn job, like 1 day once, or 1 week once.
