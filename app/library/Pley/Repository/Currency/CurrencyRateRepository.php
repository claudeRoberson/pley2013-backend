<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Currency;

use Pley\DataMap\Repository;
use Pley\DataMap\Dao;
use Pley\Entity\Currency\CurrencyRate;

/**
 * Repository class for currency rates related operations.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class CurrencyRateRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(CurrencyRate::class);
    }

    /**
     * Find <kbd>CurrencyRate</kbd> entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Currency\CurrencyRate
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find <kbd>CurrencyRate</kbd> entry by country code
     *
     * @param string $country
     * @return \Pley\Entity\Currency\CurrencyRate | null
     */
    public function findByCountryCode($country)
    {
        $result = $this->_dao->where('country LIKE ?', [
            '%' . $country . '%'
        ]);
        if (count($result)) {
            return $result[0];
        }
    }

    /**
     * Find <kbd>CurrencyRate</kbd> entries by currency code
     *
     * @param string $currencyCode
     * @return \Pley\Entity\Currency\CurrencyRate[] | null
     */
    public function findByCurrencyCode($currencyCode)
    {
        $result = $this->_dao->where('code = ?', [
            $currencyCode
        ]);
        return (count($result)) ? $result : null;
    }

    /**
     * Get all <kbd>CurrencyRate</kbd> entries
     *
     * @return \Pley\Entity\Currency\CurrencyRate[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>CurrencyRate</kbd> Entity.
     *
     * @param \Pley\Entity\Currency\CurrencyRate $rate
     * @return \Pley\Entity\Currency\CurrencyRate
     */
    public function save(CurrencyRate $rate)
    {
        return $this->_dao->save($rate);
    }
}

