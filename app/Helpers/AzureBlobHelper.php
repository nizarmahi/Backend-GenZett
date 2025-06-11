<?php

namespace App\Helpers;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Common\Internal\Authentication\SharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\Utilities;

class AzureBlobHelper
{
    protected $accountName;
    protected $accountKey;
    protected $container;

    public function __construct()
    {
        $this->accountName = env('AZURE_STORAGE_NAME');
        $this->accountKey = env('AZURE_STORAGE_KEY');
        $this->container = env('AZURE_STORAGE_CONTAINER');
    }

    /**
     * Generate SAS URL for a blob valid for $minutes minutes
     */
    public function generateSasUrl(string $blobName, int $minutes = 60): string
    {
        $sasHelper = new SharedAccessSignatureHelper(
            $this->accountName,
            $this->accountKey
        );

        $expiry = gmdate('Y-m-d\TH:i:s\Z', strtotime("+{$minutes} minutes"));

        $sasToken = $sasHelper->generateBlobServiceSharedAccessSignatureToken(
            'r', // permissions: read
            $this->container,
            $blobName,
            $expiry
        );

        $url = sprintf(
            'https://%s.blob.core.windows.net/%s/%s?%s',
            $this->accountName,
            $this->container,
            $blobName,
            $sasToken
        );

        return $url;
    }
}
