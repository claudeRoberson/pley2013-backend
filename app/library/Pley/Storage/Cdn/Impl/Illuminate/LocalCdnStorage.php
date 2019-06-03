<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Storage\Cdn\Impl\Illuminate;

use Pley\Storage\Cdn\CdnStorageInterface;

/**
 * The <kbd>LocalCdnStorage</kbd> is the specific implementation of the <kbd>CdnStorageInterface</kbd>
 * interface that interacts with the Local directories using the Laravel libraries and project
 * structure.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Storage.Cdn.Impl.Illuminate
 * @subpackage Storage
 */
class LocalCdnStorage implements CdnStorageInterface
{
    private static $_localCdnPath = null;
    
    public function __construct()
    {
        // The `app_path` method is supplied by Laravel
        $appPath = app_path();
        
        self::$_localCdnPath = realpath($appPath . '/../public/local_cdn');
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
        // Local storage does not need buckets, it uses local directories for development.
    }

    /**
     * Adds/Updates an asset to the CDN Storage.
     * 
     * @param string $assetPath    The path to store the asset on the CDN Storage
     * @param mixed  $assetContent The body of the asset to upload (usually a string).
     * @param array  $headersMap   [Optional]<br/>Map of Headers to add to the request.
     * @param string $bucket       [Optional]<br/>The bucket to use for storing the asset.
     * @return boolean <kbd>true</kbd> if asset was uploaded successfully, <kbd>false</kbd> otherwise.
     */
    public function putAsset($assetPath, $assetContent, $headersMap = null, $bucket = null)
    {
        // Bucket is not needed for local development usage
        
        $finalPath = self::$_localCdnPath . '/' . $assetPath;
        
        // Sanitizing path to remove any double // added by either configuration of supplied path
        $qualifiedPath = str_replace('//', '/', $finalPath);
        
        $fileName  = basename($qualifiedPath);
        $directory = substr($qualifiedPath, 0, strlen($qualifiedPath) - strlen($fileName) - 1);
        
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, $this->_umaskPerm(0777), true)) {
                throw new \RuntimeException(sprintf('Unable to create the "%s" directory', $directory));
            }
        } elseif (!is_writable($directory)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory', $directory));
        }
        
        // If the $assetContent is not a String (representing a path to a file that needs to be
        // uploaded), then it is a Guzzle EntityBody object representing the contents to be uploaded
        if (!is_string($assetContent)) {
            /* @var $assetContent \Guzzle\Http\EntityBody */
            $assetContent = $assetContent->getStream();
        } else {
            $assetContent = file_get_contents($assetContent);
        }
        $bytes = file_put_contents($qualifiedPath, $assetContent);
        return $bytes !== false;
    }

    /**
     * Removes an asset from the CDN Storage
     * @param string $assetPath
     * @param null   $bucket
     */
    public function deleteAsset($assetPath, $bucket = null)
    {
        unlink($assetPath);
    }
    
    /**
     * Helper method to apply the system's <kbd>umask</kbd> to the required permission.
     * <p><kbd>umask()</kbd> is the systems default mask to be applied to files or directories
     * created.</p>
     * <p>In general, by default, its value is 0022 (meaning, remove writing permissions)</p>
     * <p>So apply the the mask to the permission supplied to abide by such system requirements
     * and prevent additional permissions that may expose the system's security.</p>
     * 
     * @param int $permission Octal representation of the file permission (i.e. 0666)
     * @return int The updated permission with the applied umask.
     */
    protected function _umaskPerm($permission)
    {
        // We have to use the `~` modifier to correctly apply the mask to the permission, otherwise
        // the value of the mask will be always returned as it is an AND operation.
        return $permission & ~umask();
    }
}
