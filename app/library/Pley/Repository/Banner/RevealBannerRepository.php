<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Banner;

use Pley\DataMap\Repository;
use Pley\DataMap\Dao;
use Pley\Entity\Banner\RevealBanner;

/**
 * Repository class for reveal banner related operations.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RevealBannerRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(RevealBanner::class);
    }

    /**
     * Find <kbd>RevealBanner</kbd> entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Banner\RevealBanner
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find <kbd>RevealBanner</kbd> entries within show dates
     *
     * @param \DateTime $dateTime
     * @return \Pley\Entity\Banner\RevealBanner[] | null
     */
    public function findActiveByDate(\DateTime $dateTime)
    {
        $result = $this->_dao->where("date_start_show <= ? AND date_end_show >= ?", [
            $dateTime->format('Y-m-d H:i:s'),
            $dateTime->format('Y-m-d H:i:s')
        ]);
        return (count($result)) ? $result : [];
    }

    /**
     * Get all <kbd>RevealBanner</kbd> entries
     *
     * @return \Pley\Entity\Banner\RevealBanner[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>RevealBanner</kbd> Entity.
     *
     * @param \Pley\Entity\Banner\RevealBanner $banner
     * @return \Pley\Entity\Banner\RevealBanner
     */
    public function save(RevealBanner $banner)
    {
        return $this->_dao->save($banner);
    }
}

