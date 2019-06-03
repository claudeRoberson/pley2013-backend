<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Util;

/**
 * The <kbd>Token</kbd> Util class provides a funciton to create random unique strings that can
 * be used for tokens to be redeemed.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Util
 * @subpackage Util
 */
abstract class Token
{
    const TYPE_UUID   = 1;
    const TYPE_BASE36 = 36;
    const TYPE_BASE10 = 10;
    
    /**
     * Creates a Unique string sequence (Not a UUID)
     * <p>Note: The algorithm used is based on the one used to create sessions by Laravel</p>
     * 
     * @return string
     */
    public static function create()
    {
        return sha1(uniqid('', true) . self::_random(20) . microtime(true));
    }
    
    public static function idToToken($userId)
    {
        $inviteCode     = md5(rand());
        $inviteUserCode = sprintf('%07d', $userId);
        $inviteCode     = substr_replace($inviteCode, $inviteUserCode, 4, 7);

        return $inviteCode;
    }
    
    public static function tokenToId($token)
    {
        return intval(substr($token, 4,7));
    }
    
    /**
     * Generates a valid RFC 4211 COMPLIANT Universally Unique IDentifiers (UUID), version 4.
     * <p>By default, the UUID return will not contain the hyphens, to get one that contains the
     * hyphens, supply <kbd>true</kbd> to the optional parameter.</p>
     * 
     * @param boolean $withHyphens [Optional]<br/>Default <kbd>false</kbd>.
     * @return string The version 4 UUID.
     */
    public static function uuid($withHyphens = false)
    {
        $uuidFormat = '%04x%04x%04x%04x%04x%04x%04x%04x';
        if ($withHyphens) {
            $uuidFormat = '%04x%04x-%04x-%04x-%04x-%04x%04x%04x';
        }
        
        return sprintf($uuidFormat,
                // 32 bits for "time_low"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0x3fff) | 0x8000,
                // 48 bits for "node"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function int2Alpha($value, $minPadding = false)
    {
        $toNumber = false;
        return static::_alphaID($value, $toNumber, $minPadding);
    }
    
    public static function alpha2Int($value, $sourcePadding = false)
    {
        $toNumber = true;
        return static::_alphaID($value, $toNumber, $sourcePadding);
    }

    /**
     * Creates a 12 character number (without counting dashes) highly unique string using Base36 for entropy.
     * <p>The process to create this number is similar to that of the UUID version 4, but uses
     * Base 36 (0-9a-z) instead of Base 16 (Hex), and reduces the number of quartet character groups
     * from 8 to 3, creating a much shorter string (12 characters compared to 32) but keeping a
     * similar high entropy to reduce the chances of collision.</p>
     * <p><b>Note:</b> Base36 considers the use of case-insensitive English alphabet letters, that
     * is 26 letters (a-z) plus 10 digits (0-9).<p>
     * <p>This an example number returned using hyphens: <kbd>on81-1ni6-0vby</kbd></p>
     * <p>This gives us 4.73838134e18 possible permutations (aka a 1 Quintillion).<p>
     * 
     * @param boolean $withHyphens [Optional]<br/>Default <kbd>false</kbd>.
     * @return string
     */
    public static function base36($withHyphens = false)
    {
        $b32highest = base_convert('zzzz', 36, 10);
        $b32v1value = base_convert('0zzz', 36, 10); // represents the version 1 max value node
        $b32v1mask  = base_convert('1000', 36, 10); // represents the version 1 mask

        $format = "%'04s%'04s%'04s";
        if ($withHyphens) {
            $format = "%'04s-%'04s-%'04s";
        }
        
        return sprintf($format, 
            // 24 bits for First Quartet
            base_convert(mt_rand(0, $b32highest), 10, 36),
            // 24 bits for Version Quartet
            // six most significant bits holds version number 1
            base_convert(mt_rand(0, $b32v1value) | $b32v1mask, 10, 36),
            // 24 bits for First Quartet
            base_convert(mt_rand(0, $b32highest), 10, 36)
        );
    }
    
    /**
     * Creates a 16 digit number (without counting dashes) using 4 blocks of 4 digits (zero-padded)
     * with a relatively low entropy.
     * <p>The process to create this number is similar to that of the UUID version 4, but uses
     * Base 10 (0-9) instead of Base 16 (Hex), and reduces the number of quartet character groups
     * from 8 to 4, creating a much shorter string (16 characters compared to 32).</p>
     * <p>This an example number returned using hyphens: <kbd>0007-8546-0456-7107</kbd></p>
     * <p>This gives us 10e16 possible permutations (aka a 10 Quadrillion).<p>
     * 
     * @param boolean $withHyphens [Optional]<br/>Default <kbd>false</kbd>.
     * @return string
     */
    public static function base10($withHyphens = false)
    {
        $format = "%'04d%'04d%'04d%'04d";
        if ($withHyphens) {
            $format = "%'04d-%'04d-%'04d-%'04d";
        }
        
        // since we are using 4 digits on base10, we are still using 16 bits, but we are not using
        // the maximum value that it could be used (which in hex would be 0xffff, instead 9999 in
        // hex is 0x270f)
        return sprintf($format, 
            // 16 bits for First Quartet
            mt_rand(0, 9999),
            // 16 bits for Second Quartet
            mt_rand(0, 9999),
            // 16 bits for Third Quartet
            mt_rand(0, 9999),
            // 16 bits for Fourth Quartet
            mt_rand(0, 9999)
        );
    }
    
    /**
     * Helper method to Hyphenize a token that is in pure form (no dashes).
     * @param string $token
     * @param int    $tokenType Type of Token that will be hyphenized. See class constants.
     * @return string The hyphenated token
     * @see ::TYPE_UUID
     * @see ::TYPE_BASE10
     * @see ::TYPE_BASE36
     */
    public static function hyphenize($token, $tokenType)
    {
        if ($tokenType == self::TYPE_BASE36) {
            $quartet1 = substr($token, 0, 4);
            $quartet2 = substr($token, 4, 4);
            $quartet3 = substr($token, 8, 4);

            return sprintf('%s-%s-%s', $quartet1, $quartet2, $quartet3);
            
        } else if ($tokenType == self::TYPE_BASE10) {
            $quartet1 = substr($token, 0, 4);
            $quartet2 = substr($token, 4, 4);
            $quartet3 = substr($token, 8, 4);
            $quartet4 = substr($token, 12, 4);

            return sprintf('%s-%s-%s-%s', $quartet1, $quartet2, $quartet3, $quartet4);
            
        } else { // if ($tokenType == self::TYPE_UUID) {
            $timeLow   = substr($token, 0, 8);
            $timeMid   = substr($token, 8, 4);
            $timeHi    = substr($token, 12, 4);
            $clkSeqLow = substr($token, 16, 4);
            $node      = substr($token, 20, 12);

            return sprintf('%s-%s-%s-%s-%s', $timeLow, $timeMid, $timeHi, $clkSeqLow, $node);
        }
    }
    
    /**
     * Translates a number to a short alhanumeric version
     *
     * <p>Translated any number up to 9007199254740992 to a shorter version in letters e.g.:
     * <code>9007199254740989 --> PpQXn7COf</code>
     * </p>
     * <p>specifiying the second argument true, it will translate back e.g.:
     * <code>PpQXn7COf --> 9007199254740989</code>
     * </p>
     * <p>This function is based on <kbd>any2dec</kbd> && <kbd>dec2any</kbd> by <kbd>fragmer@mail.ru</kbd><br/>
     * see: http://nl3.php.net/manual/en/function.base-convert.php#52450
     * </p>
     * <p>If you want the alphaID to be at least 3 letter long, use the
     * <code>$pad_up = 3</code> argument
     * </p>
     * <p>In most cases this is better than totally random ID generators because this can easily avoid
     * duplicate ID's.<br/>
     * For example if you correlate the alpha ID to an auto incrementing ID in your database, you're
     * done.
     * </p>
     * <p>The reverse is done because it makes it slightly more cryptic, but it also makes it easier to
     * spread lots of IDs in different directories on your filesystem.<br/>
     * Example:<br/>
     * <code>
     * &nbsp;   $part1 = substr($alpha_id,0,1);<br/>
     * &nbsp;   $part2 = substr($alpha_id,1,1);<br/>
     * &nbsp;   $part3 = substr($alpha_id,2,strlen($alpha_id));<br/>
     * &nbsp;   $destindir = "/".$part1."/".$part2."/".$part3;<br/>
     * &nbsp;   // by reversing, directories are more evenly spread out.<br/>
     * &nbsp;   // The first 26 directories already occupy 26 main levels<br/>
     * </code>
     * </p>
     * <p>More info on limitation:<br/>
     * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
     * </p>
     * <p>If you really need this for bigger numbers you probably have to look at things like:<br/>
     *   http://theserverpages.com/php/manual/en/ref.bc.php<br/>
     * or:<br/>
     *   http://theserverpages.com/php/manual/en/ref.gmp.php<br/>
     * but I haven't really dugg into this. If you have more info on those matters feel free to
     * leave a comment.
     * </p>
     * <p>The following code block can be utilized by PEAR's Testing_DocTest<br/>
     * <code>
     * // Input //<br/>
     * $number_in = 2188847690240;<br/>
     * $alpha_in  = "SpQXn7Cb";<br/>
     * <br/>
     * // Execute //<br/>
     * $alpha_out  = alphaID($number_in, false, 8);<br/>
     * $number_out = alphaID($alpha_in, true, 8);<br/>
     * <br/>
     * if ($number_in != $number_out) {<br/>
     * &nbsp;   echo "Conversion failure, ".$alpha_in." returns ".$number_out." instead of the ";<br/>
     * &nbsp;   echo "desired: ".$number_in."\n";<br/>
     * }<br/>
     * if ($alpha_in != $alpha_out) {<br/>
     * &nbsp;   echo "Conversion failure, ".$number_in." returns ".$alpha_out." instead of the ";<br/>
     * &nbsp;   echo "desired: ".$alpha_in."\n";<br/>
     * }<br/>
     * <br/>
     * // Show //<br/>
     * echo $number_out." => ".$alpha_out."\n";<br/>
     * echo $alpha_in." => ".$number_out."\n";<br/>
     * echo alphaID(238328, false)." => ".alphaID(alphaID(238328, false), true)."\n";<br/>
     * <br/>
     * // expects:<br/>
     * // 2188847690240 => SpQXn7Cb<br/>
     * // SpQXn7Cb => 2188847690240<br/>
     * // aaab => 238328<br/>
     * </code>
     * </p>
     * 
     * @author Kevin van Zonneveld <kevin@vanzonneveld.net>
     * @author Simon Franz
     * @author Deadfish
     * @author SK83RJOSH
     * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
     * @license http://www.opensource.org/licenses/bsd-license.php New BSD Licence
     * @version SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
     * @link http://kevin.vanzonneveld.net/
     *
     * @param mixed   $in      String or long input to translate
     * @param boolean $toNum   Reverses translation when true
     * @param mixed   $padUp   Number or boolean padds the result up to a specified length
     * @param string  $passKey Supplying a password makes it harder to calculate the original ID
     * @return mixed string or long
     * @see http://kvz.io/blog/2009/06/10/create-short-ids-with-php-like-youtube-or-tinyurl/
     */
    private static function _alphaID($in, $toNum = false, $padUp = false, $passKey = null)
    {
        $out   = '';
        
        // Base index as obtained from the source is linear in the sequence making it quite easy
        // to figure out that it is a straight mapping.
        //$index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //
        // We shuffle it to make it less obvious and a bit more arbitrary looking
        $index = 'MeIDL0jsgCnhpxtvFV3PbUuE9rWNlO64ZJwk8aQHydKcfiYBAG17XmRSq5Tz2o';
        
        $base  = strlen($index);

        if ($passKey !== null) {
            // Although this function's purpose is to just make the ID short - and not so much secure,
            // with this patch by Simon Franz (http://blog.snaky.org/)
            // you can optionally supply a password to make it harder to calculate the corresponding
            // numeric ID

            for ($n = 0; $n < strlen($index); $n++) {
                $i[] = substr($index, $n, 1);
            }

            $passHash = hash('sha256', $passKey);
            $passHash = (strlen($passHash) < strlen($index) ? hash('sha512', $passKey) : $passHash);

            for ($n = 0; $n < strlen($index); $n++) {
                $p[] = substr($passHash, $n, 1);
            }

            array_multisort($p, SORT_DESC, $i);
            $index = implode($i);
        }

        if ($toNum) {
            // Digital number  <<--  alphabet letter code
            $len = strlen($in) - 1;

            for ($t = $len; $t >= 0; $t--) {
                $bcp = bcpow($base, $len - $t);
                $out = $out + strpos($index, substr($in, $t, 1)) * $bcp;
            }

            if (is_numeric($padUp)) {
                $padUp--;

                if ($padUp > 0) {
                    $out -= pow($base, $padUp);
                }
            }
        } else {
            // Digital number  -->>  alphabet letter code
            if (is_numeric($padUp)) {
                $padUp--;

                if ($padUp > 0) {
                    $in += pow($base, $padUp);
                }
            }

            for ($t = ($in != 0 ? floor(log($in, $base)) : 0); $t >= 0; $t--) {
                $bcp = bcpow($base, $t);
                $a   = floor($in / $bcp) % $base;
                $out = $out . substr($index, $a, 1);
                $in  = $in - ($a * $bcp);
            }
        }

        return $out;
    }
    
    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int $length [Optional]<br/>Default 16
     * @return string
     * @throws \RuntimeException
     */
    private static function _random($length = 16)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length * 2);

            if ($bytes === false) {
                throw new \RuntimeException('Unable to generate random string.');
            }

            return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
        }

        // If the OpenSSL function is not available, then just create one quick with base charset
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

}