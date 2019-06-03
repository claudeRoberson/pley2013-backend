<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Laravel\Providers;

/**
 * The <kbd>PleyNpsServiceProvider</kbd>
 *
 * @author Igor Shvartsev (igor.shvartsev@gmail.com)
 * @version 1.0
 * @package Pley\Laravel
 * @subpackage ServiceProvider
 */
class PleyNpsServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const NPS_PATH  = '\\Pley\\Nps\\';
    const IMPL_NPS  = 'Delighted';
    
    public function register()
    {
        $implToUse = self::IMPL_NPS;
        
        $bindPath = self::NPS_PATH . 'NpsManagerInterface';
        $implPath = self::NPS_PATH . 'Impl\\'. $implToUse . '\\NpsManager'; 
        
        $this->app->bind($bindPath, $implPath, true);
    }
}
