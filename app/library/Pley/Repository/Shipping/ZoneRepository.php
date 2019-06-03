<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Shipping;

use Pley\DataMap\Repository;
use Pley\DataMap\Dao;
use Pley\Entity\Shipping\Zone;

/**
 * Repository class for shipping Zones related operations.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ZoneRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(Zone::class);
    }

    /**
     * Find <kbd>Zone</kbd> entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Shipping\Zone
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find <kbd>Zone</kbd> entries by country code
     *
     * @param string $country
     * @return \Pley\Entity\Shipping\Zone[] | null
     */
    public function findByCountry($country)
    {
        $result = $this->_dao->where('country LIKE ?', [
            '%' . $country . '%'
        ]);
        return count($result) ? $result : null;
    }
}

