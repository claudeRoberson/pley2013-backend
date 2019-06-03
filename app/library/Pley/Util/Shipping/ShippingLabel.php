<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Util\Shipping;

class ShippingLabel
{
    /**
     * @param string $labelUrl
     * @return string
     */
    public static function convert($labelUrl)
    {
        $zplFile = file_get_contents($labelUrl);
        
        // The ZPL command ^PO is used for rotating a label
        // if `I` is specified, the label is invereted (aka upside down)
        // So we replace it with `N` to show it as normal
        $zplFile = str_replace('^POI', '^PON', $zplFile);

        $pngFile    = self::_labelaryConvert($zplFile);
        $b64PngFile = base64_encode($pngFile);

        return $b64PngFile;
    }

    /**
     * @param $zplFile
     *
     * @return mixed
     */
    private static function _labelaryConvert($zplFile)
    {
        $url = 'http://api.labelary.com/v1/printers/8dpmm/labels/4x6/0/';

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $zplFile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //execute post
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);

        return $result;
    }

}