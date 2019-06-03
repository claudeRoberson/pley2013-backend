<?php

namespace Pley\Console\Currency;

/** @copyright Pley (c) 2017, All Rights Reserved */

use Illuminate\Console\Command;
use Pley\Repository\Currency\CurrencyRateRepository;
use GuzzleHttp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>RefreshCurrencyRatesCommand</kbd>
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RefreshCurrencyRatesCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    const CURRENCY_API_URL = 'http://api.fixer.io/latest?base=USD';
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:currency:refreshRates';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to refresh currency rates';

    /** @var \Pley\Config\ConfigInterface */
    protected $_config;

    /**
     * @var \Pley\Repository\Currency\CurrencyRateRepository
     */
    protected $_currencyRatesRepo;

    /**
     * @var \Pley\Price\PriceManager
     */
    protected $_priceManager;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_config = \App::make('\Pley\Config\ConfigInterface');
        $this->_currencyRatesRepo = \App::make('\Pley\Repository\Currency\CurrencyRateRepository');
        $this->_priceManager = \App::make('\Pley\Price\PriceManager');

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        $this->info('Currency update started...');

        /**
         * Using http://fixer.io/ as a data source
         */
        $httpClient = new GuzzleHttp\Client();
        $response = $httpClient->request('GET', self::CURRENCY_API_URL);

        $latestRates = $this->_parseResponse($response->getBody());
        $this->_updateRates($latestRates);

        $this->info('Currency update complete...');
    }

    protected function _parseResponse($responseJson)
    {
        $rates = [];
        $currenciesArray = json_decode($responseJson, true);
        if (!isset($currenciesArray['rates'])) {
            throw new \Exception('Something was wrong with the http://fixer.io API. No currencies fetched.');
        }
        foreach ($currenciesArray['rates'] as $code => $rate) {
            if (in_array($code, $this->_priceManager->allowedCurrencyCodes)) {
                $rates[$code] = $rate;
            }
        }
        return $rates;
    }

    protected function _updateRates($rates)
    {
        $dbRates = $this->_currencyRatesRepo->all();
        foreach ($dbRates as $rate) {
            if (array_key_exists($rate->getCode(), $rates)) {
                $rate->setRate($rates[$rate->getCode()]);
                $this->line(sprintf('Set %s rate to: %s', $rate->getCode(), $rate->getRate()));
                $this->_currencyRatesRepo->save($rate);
            }
        }
    }

}