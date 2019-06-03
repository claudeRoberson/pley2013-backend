<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Laravel\Providers;

/**
 * The <kbd>PleyMailServiceProvider</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @version 1.0
 * @package Pley\Laravel
 * @subpackage ServiceProvider
 */
class PleyMailServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const TYPE_ABSTRACT  = 1;
    const TYPE_INTERFACE = 2;
    
    const MAILER_PATH     = '\\Pley\\Mail\\';
    const IMPL_ILLUMINATE = 'Illuminate';
    
    public function register()
    {   
        $this->_bind('Mail', self::TYPE_ABSTRACT, self::IMPL_ILLUMINATE);
    }
    
    private function _bind($className, $type, $implementation)
    {
        $bindName = $className;
        if ($type == self::TYPE_ABSTRACT) {
            $bindName = 'Abstract' . $bindName;
        } else { //if ($type == self::TYPE_INTERFACE) {
            $bindName = $bindName . 'Interface';
        }
        
        $bindPath = self::MAILER_PATH . $bindName;
        $implPath = self::MAILER_PATH . 'Impl\\'. $implementation . '\\' . $className;
        $this->app->bind($bindPath, $implPath);
    }
}
