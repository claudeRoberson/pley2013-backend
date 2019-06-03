<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Storage\Cdn\Impl\Aws;

use \Aws\Common\Aws;

use Pley\Storage\Cdn\CdnStorageInterface;

/**
 * The <kbd>S3CdnStorage</kbd> is the specific implementation of the <kbd>CdnStorageInterface</kbd>
 * interface that interacts with the Amazon AWS S3 CDN storage to add assets.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Storage.Cdn.Impl.Aws
 * @subpackage Storage
 */
class S3CdnStorage implements CdnStorageInterface
{
    /** @var string */
    protected $_defaultBucket;
    /** @var \Aws\S3\S3Client */
    protected $_s3client;

    public function __construct(Aws $aws)
    {
        $this->_s3client = $aws->get('s3');
    }
    
    /**
     * Sets the default Bucket name to use.
     * <p>This is required by some implementations like Amazon's S3.</p>
     * <p>This bucket will be used if none is supplied by the asset interating methods. It is also
     * a way of saving to add the bucket for every single transaction if all assets are going
     * over the same bucket.</p>
     * 
     * @param string $bucket
     */
    public function setDefaultBucket($bucket)
    {
        if (!is_string($bucket)) {
            throw new \Exception('Invalid bucket name, $bucket is not a string.');
        }
        
        $this->_defaultBucket = $bucket;
    }
    
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
    public function putAsset($assetPath, $assetContent, $headersMap = null, $bucket = null)
    {
        // Check for bucket which is required for Amazon AWS S3
        if (empty($this->_defaultBucket) && (empty($bucket) || !is_string($bucket))) {
            throw new \Exception('S3 CDN storage requires a bucket, none supplied');
        }
        
        // Getting the bucket to use, if parameter is sent, it overrides the default bucket.
        $transactionBucket = $this->_defaultBucket;
        if (!empty($bucket)) {
            $transactionBucket =  $bucket;
        }
        
        $s3Request = [
            'Bucket'        => $transactionBucket,
            'Key'           => $assetPath,
        ];

        switch (gettype($assetContent)) {
            case 'string':
                $s3Request['SourceFile'] = $assetContent;
                break;
            default:
                $s3Request['Body'] = $assetContent;
                break;
        }
        
        if (!empty($headersMap)) {
            array_merge($s3Request, $headersMap);
        }

        /* @var response \Guzzle\Service\Resource\Model */
        $response = $this->_s3client->putObject($s3Request);
        
        return !empty($response);
    }

    /**
     * Removes an asset from the CDN Storage
     * @param string $assetPath
     * @param null   $bucket
     */
    public function deleteAsset($assetPath, $bucket = null)
    {
        // Check for bucket which is required for Amazon AWS S3
        if (empty($this->_defaultBucket) && (empty($bucket) || !is_string($bucket))) {
            throw new \Exception('S3 CDN storage requires a bucket, none supplied');
        }

        return $this->_s3client->deleteObject(['Bucket' => $this->_defaultBucket, $assetPath]);
    }
}
