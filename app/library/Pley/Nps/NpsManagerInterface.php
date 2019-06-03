<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Nps;

use \Pley\Entity\User\User;

/**
 * The <kbd>NPS Manager</kbd>
 *
 * @author Igor Shvartsev (igor.shvartsev@gmail.com)
 * @version 1.0
 * @package /Pley/Nps
 * @subpackage Nps
 */
interface NpsManagerInterface
{
    /**
    *  Creates/updates user data in the NPS service account
    *  and allows to send survey depending oo $allowSending and $delay parameters
    * 
    *  @param \Pley\Entity\User\User $user
    *  @param boolean $allowSending,
    *  @param int $delay  -  seconds
    *  @param array $properties - array of additional properties for the created/updated account 
    */
    public function addUserToSchedule(User $user, $allowSending = true, $delay = 0, $properties = []);
}