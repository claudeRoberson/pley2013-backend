<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Referral;

use Pley\DataMap\Repository;
use Pley\DataMap\Dao;
use Pley\Entity\Referral\Program;

/**
 * Repository class for <kbd>Program</kbd> entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ProgramRepository extends Repository
{

    const DEFAULT_REFERRAL_PROGRAM_ID = 1;

    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(Program::class);
    }

    /**
     * Find <kbd>Program</kbd> entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Referral\Program
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Referral\Program[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>Program</kbd> Entity.
     *
     * @param \Pley\Entity\Referral\Program $Program
     * @return \Pley\Entity\Referral\Program
     */
    public function save(Program $Program)
    {
        return $this->_dao->save($Program);
    }

    /**
     * Just gets the first record in a referral_program table
     *
     * @return \Pley\Entity\Referral\Program
     */
    public function getDefaultReferralProgram(){
        return $this->find(self::DEFAULT_REFERRAL_PROGRAM_ID);
    }
}