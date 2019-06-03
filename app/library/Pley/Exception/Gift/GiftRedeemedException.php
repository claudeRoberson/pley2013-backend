<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Exception\Gift;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>GiftRedeemedException</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class GiftRedeemedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(\Pley\Entity\User\User $user, \Pley\Entity\Gift\Gift $gift, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'giftId' => $gift->getId()]);
        parent::__construct($message, ExceptionCode::GIFT_REDEEMED, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
