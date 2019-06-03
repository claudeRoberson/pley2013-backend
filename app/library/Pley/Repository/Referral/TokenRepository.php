<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Referral;

use Pley\DataMap\Repository;
use Pley\DataMap\Dao;
use Pley\Entity\Referral\Token;

/**
 * Repository class for <kbd>Token</kbd> entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class TokenRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(Token::class);
    }

    /**
     * Find <kbd>Token</kbd> entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Referral\Token
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find <kbd>Token</kbd> entries by userId
     *
     * @param int $userId
     * @return \Pley\Entity\Referral\Token[]
     */
    public function findByUserId($userId)
    {
        return $this->_dao->where('user_id = ?', [$userId]);
    }

    /**
     * Find <kbd>Token</kbd> entries by userId and typeId
     *
     * @param int $userId
     * @param int $typeId
     * @return \Pley\Entity\Referral\Token[]
     */
    public function findByUserIdAndType($userId, $typeId)
    {
        return $this->_dao->where(
            'user_id = ? AND token_type_id = ?',
            [$userId, $typeId]);
    }

    /**
     * Find <kbd>Token</kbd> entries by referral email and typeId
     *
     * @param string $referralEmail
     * @param int $typeId
     * @return \Pley\Entity\Referral\Token[]
     */
    public function findByReferralEmailAndType($referralEmail, $typeId)
    {
        return $this->_dao->where(
            'referral_user_email = ? AND token_type_id = ?',
            [$referralEmail, $typeId]);
    }

    /**
     * Find <kbd>Token</kbd> entry by token
     *
     * @param string $token
     * @return \Pley\Entity\Referral\Token | null
     */
    public function findByToken($token)
    {
        $result = $this->_dao->where('token = ?', [$token]);
        return count($result) ? $result[0] : null;
    }

    /**
     * Find <kbd>Token</kbd> entry by referral email
     *
     * @param int $referralEmail
     * @return \Pley\Entity\Referral\Token[]
     */
    public function findByReferralUserEmail($referralEmail)
    {
        return $this->_dao->where('referral_user_email = ?', [$referralEmail]);
    }

    /**
     * Update tokens with user id based on referral email
     *
     * @param \Pley\Entity\User\User $user
     * @return void
     */
    public function updateEntriesWithUser(\Pley\Entity\User\User $user)
    {
        /**
         * @var $tokens Token[]
         */
        $tokens = $this->_dao->where('referral_user_email = ?', [$user->getEmail()]);
        foreach ($tokens as $token){
            $token->setUserId($user->getId());
            $this->save($token);
        }
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Referral\Token[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>Token</kbd> Entity.
     *
     * @param \Pley\Entity\Referral\Token $token
     * @return \Pley\Entity\Referral\Token
     */
    public function save(Token $token)
    {
        return $this->_dao->save($token);
    }
}