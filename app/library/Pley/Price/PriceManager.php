<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Price;

use Pley\Repository\Currency\CurrencyRateRepository;
use Pley\Entity\Currency\CurrencyRate;
use Pley\Entity\User\User;

/**
 * Class description goes here
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class PriceManager
{
    const BASE_CURRENCY_CODE = 'USD';

    const BASE_COUNTRY_CODE = 'US';
    /**
     * @var CurrencyRate[]
     */
    protected $_rates = [];
    /**
     * @var array
     */
    public $allowedCurrencyCodes = [
        'USD',
        'CAD',
        'GBP',
        'AUD',
        'NZD',
        'ILS',
        'EUR',
        'SGD'
    ];

    /**
     * @var array
     */
    protected $_currencySignsMap = [
        'USD' => '$',
        'CAD' => '$',
        'GBP' => '£',
        'AUD' => '$',
        'NZD' => '$',
        'ILS' => '₪',
        'EUR' => '€',
        'SGD' => '$',
    ];

    /**
     * @var CurrencyRateRepository
     */
    protected $_currencyRateRepository;

    /**
     * PriceManager constructor.
     * @param CurrencyRateRepository $currencyRateRepository
     */
    public function __construct(
        CurrencyRateRepository $currencyRateRepository
    )
    {
        $this->_currencyRateRepository = $currencyRateRepository;
        $this->_initRates();
    }

    /**
     * Init rates from db once to avoid multiple DB queries
     */
    protected function _initRates()
    {
        $rates = $this->_currencyRateRepository->all();
        foreach ($rates as $rate) {
            $this->_rates[$rate->getCountry()] = $rate;
        }
    }

    /**
     * @param $amount
     * @param $country
     * @return array
     */
    public function toCountryCurrency($amount, $country = null)
    {
        if ($country === null) {
            $country = self::BASE_COUNTRY_CODE;
        }
        if (!isset($this->_rates[$country])) {
            return $this->_convert($amount, self::BASE_CURRENCY_CODE);
        }
        $countryRate = $this->_rates[$country];
        return $this->_convert($amount, $countryRate->getCode(), $countryRate->getRate());
    }

    /**
     * @param $amount
     * @param User $user
     * @return array
     */
    public function toUserCurrency($amount, User $user)
    {
        return $this->toCountryCurrency($amount, $user->getCountry());
    }

    /**
     * @param $amount
     * @return array
     */
    public function toBaseCurrency($amount)
    {
        return $this->_convert($amount, self::BASE_CURRENCY_CODE);
    }

    public function getCountryCurrencyCode($country = null)
    {
        if (!$country) {
            return self::BASE_CURRENCY_CODE;
        }
        $countryRate = $this->_rates[$country];
        return $countryRate->getCode();
    }

    public function getCountryCurrencySign($country = null)
    {
        if (!$country) {
            return $currencySign = $this->_currencySignsMap[self::BASE_CURRENCY_CODE];
        }
        $countryRate = $this->_rates[$country];
        return $this->_currencySignsMap[$countryRate->getCode()];
    }

    /**
     * @param $amount
     * @param $toCurrencyCode
     * @param int $rate
     * @return array
     */
    protected function _convert($amount, $toCurrencyCode, $rate = 1)
    {
        return round($amount * $rate, 2);
    }

    /**
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return self::BASE_CURRENCY_CODE;
    }

    /**
     * @return mixed
     */
    public function getBaseCurrencySign()
    {
        return $this->_currencySignsMap[self::BASE_CURRENCY_CODE];
    }
}
