<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace api\v1\User;

use Pley\Entity\User\User;
use Pley\Enum\InviteEnum;
use Pley\Repository\User\InviteRepository;
use Pley\Repository\User\UserRepository;
use Pley\User\InviteManager;

/**
 * Controller class for managing user friend invites tokens
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package api.v1
 */
class UserInviteController extends \api\v1\BaseController
{
    /** @var \Pley\Repository\User\UserRepository */
    protected $_userRepository;
    /** @var \Pley\Repository\User\InviteRepository */
    protected $_inviteRepository;
    /** @var \Pley\User\InviteManager */
    protected $_inviteManager;

    public function __construct(
        UserRepository $userRepository,
        InviteRepository $inviteRepository,
        InviteManager $inviteManager
    )
    {
        $this->_userRepository = $userRepository;
        $this->_inviteRepository = $inviteRepository;
        $this->_inviteManager = $inviteManager;
    }

    // POST /user/invite/friend/email
    public function inviteFriendEmail()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $user = $this->_checkAuthenticated();

        $json = \Input::json()->all();
        $rules = [
            'contactList' => 'required',
        ];
        \ValidationHelper::validate($json, $rules);

        $inviteList = $this->_parseContactList($json['contactList']);

        $this->_inviteManager->processInvites($inviteList, $user);

        $responseArray = ['success' => true];
        return \Response::json($responseArray);
    }

    // POST /invite/friend/email
    public function nonUserInviteEmail()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();
        $rules = [
            'referralEmail' => 'required',
            'contactList' => 'required',
        ];

        \ValidationHelper::validate($json, $rules);

        $user = \Pley\Entity\User\User::dummy();
        $user->setEmail($json['referralEmail']);

        $inviteList = $this->_parseContactList($json['contactList']);

        $this->_inviteManager->processNonUserInvites($inviteList, $user);

        $responseArray = ['success' => true];
        return \Response::json($responseArray);
    }

    // GET /user/invite
    public function getUserInvites()
    {
        \RequestHelper::checkGetRequest();

        $user = $this->_checkAuthenticated();
        $response = [
            'success' => true,
            'invites' => []
        ];
        $inviteList = $this->_inviteRepository->findByUserId($user->getId());

        foreach ($inviteList as $invite) {
            $response['invites'][] = [
                'id' => $invite->getId(),
                'inviteName' => $invite->getInviteName(),
                'inviteEmail' => $invite->getInviteEmail(),
                'status' => InviteEnum::asString($invite->getStatus())
            ];
        }
        return \Response::json($response);
    }

    /**
     * Parse a String containing a list of contacts into a list of arrays containing the data as a map.
     * <p>Return structure:<br/>
     * <pre>array(
     * &nbsp;   0 => ['name' => string, 'email' => string],
     * &nbsp;   ...,
     * )</pre></p>
     * @param string $contactListStr
     * @return array
     */
    private function _parseContactList($contactListStr)
    {
        $inviteList = explode(',', $contactListStr);
        $parsedList = [];

        $inviteListLength = count($inviteList);
        for ($i = 0; $i < $inviteListLength; $i++) {
            $contactInfoStr = trim($inviteList[$i]);

            $inviteName = null;
            $inviteEmail = null;

            // Get the Email address from the supplied contact info, if there is no valid address
            // then ignore and go to the next entry
            $isEmailMatch = preg_match(\Pley\Util\Util::REGEX_EMAIL, $contactInfoStr, $matches);
            if (!$isEmailMatch) {
                continue;
            }
            $inviteEmail = $matches[0];

            // Subtracting the email address from the contact info so that we can now search for a
            // possible name
            $contactLessEmail = str_replace($inviteEmail, '', $contactInfoStr);
            $isNameMatch = preg_match('#[a-zA-Z][\w. -]*#', $contactLessEmail, $matches);
            if ($isNameMatch) {
                $inviteName = trim($matches[0]);
            }

            // In-place replacement of contact with array notation
            $parsedList[] = [
                'name' => $inviteName,
                'email' => $inviteEmail,
            ];
        }

        return $parsedList;
    }
}
