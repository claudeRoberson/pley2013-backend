<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Storage\Cdn;

/**
 * The <kbd>CdnStorageInterface</kbd> provides with the contract methods to interact with a CDN
 * Storage.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Storage.Cdn
 * @subpackage Storage
 */
interface CdnStorageInterface
{
    /**
     * Sets the default Bucket name to use.
     * <p>This is required by some implementations like Amazon's S3.</p>
     * <p>This bucket will be used if none is supplied by the asset interating methods. It is also
     * a way of saving to add the bucket for every single transaction if all assets are going
     * over the same bucket.</p>
     * 
     * @param string $bucket
     */
    public function setDefaultBucket($bucket);
    
    /**
     * Adds/Updates an asset to the CDN Storage.
     * 
     * @param string $assetPath    The path to store the asset on the CDN Storage
     * @param mixed  $assetContent If a String, the path representing the file to upload, otherwise
     *      a <kbd>\Guzzle\Http\EntityBody</kbd> object representing a resource/stream that should
     *      be uploaded.
     * @param array  $headersMap   [Optional]<br/>Map of Headers to add to the request.
     * @param string $bucket       [Optional]<br/>The bucket to use for storing the asset.
     * @return boolean <kbd>true</kbd> if asset was uploaded successfully, <kbd>false</kbd> otherwise.
     */
    public function putAsset($assetPath, $assetContent, $headersMap = null, $bucket = null);

    /**
     * Removes an asset from the CDN Storage
     * @param string $assetPath
     * @param string $bucket
     */
    public function deleteAsset($assetPath, $bucket = null);
}
