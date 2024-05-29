<?php

namespace App\Service;

use Google_Client;

class GoogleDrive
{
    function __construct()
    {
        $this->appName    = 'Upload File To Google Drive';
        $this->credPath   = storage_path('/app/drive-php-upload.json');
        $this->secretPath =  storage_path('/app/client_secret.json');
        $this->scopes     = implode(' ', array(\Google_Service_Drive::DRIVE));
    }
    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    private function getClient() {
        $client = new Google_Client();
        $client->setApplicationName($this->appName);
        $client->setScopes($this->scopes);
        $client->setAuthConfig($this->secretPath);
        $client->setAccessType('offline');
        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory($this->credPath);
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);

        } else {

            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            // Store the credentials to disk.
            if(!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }

        $client->setAccessToken($accessToken);
        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }
    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    private function expandHomeDirectory($path) {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }
    public function upload($path, $fileName){
        $client = $this->getClient();
        $service = new \Google_Service_Drive($client);
        $fileMetadata = new \Google_Service_Drive_DriveFile(array('name' => 'backup_'.$fileName));
        $content = file_get_contents($path. $fileName);
        $file = $service->files->create($fileMetadata, array(
                'data'       => $content,
                'mimeType'   => mime_content_type($path. $fileName), //'image/jpeg',
                'uploadType' => 'multipart',
                'fields'     => 'id')
        );
        return $file;
    }

    function getFiles()
    {
        $client = $this->getClient();
        $service = new \Google_Service_Drive($client);

        $optParams = array(
            'pageSize' => 10,
            'fields' => 'nextPageToken, files(id, name)'
        );
        $results = $service->files->listFiles($optParams);

        dd($results->getFiles());
    }

    /**
     * Update an existing file's metadata and content.
     *
     * @param Google_Service_Drive $service Drive API service instance.
     * @param string $fileId ID of the file to update.
     * @param string $newTitle New title for the file.
     * @param string $newDescription New description for the file.
     * @param string $newMimeType New MIME type for the file.
     * @param string $newFilename Filename of the new content to upload.
     * @param bool $newRevision Whether or not to create a new revision for this file.
     * @return Google_Servie_Drive_DriveFile The updated file. NULL is returned if
     *     an API error occurred.
     */
    function updateFile($fileId, $path, $fileName, $newRevision = false) {
        try {
            $client = $this->getClient();
            $service = new \Google_Service_Drive($client);

            // First retrieve the file from the API.
            $file =  new \Google_Service_Drive_DriveFile();//$service->files->get($fileId);
            $mime = mime_content_type($path. $fileName);
            // File's new metadata.
           // $file->setTitle($newTitle);
           // $file->setDescription('TRANSBAZA_BACKUP ' . now()->format('Y-m-d'));
          //  $file->setMimeType($mime);

            // File's new content.
            $data = file_get_contents($path.$fileName);

            $additionalParams = array(
              //  'newRevision' => $newRevision,
                'data' => $data,
                'mimeType' => $mime
            );

            // Send the request to the API.
            $updatedFile = $service->files->update($fileId, $file, $additionalParams);
            return $updatedFile;
        } catch (Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
    }
}
