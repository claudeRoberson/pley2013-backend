<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Nps\Impl\Delighted;

use \Pley\Entity\User\User;
use \Pley\Nps\NpsManagerInterface;
use \Pley\Entity\User\UserNps;
use \Pley\Repository\User\UserNpsRepository;
use \Delighted\Client;
use \Delighted\Person;
use \Delighted\SurveyRequest;
use \Delighted\SurveyResponse;
use \Delighted\Metrics;
use \Delighted\Unsubscribe;
use \Delighted\Bounce;

/**
 * The <kbd>NPS Manager</kbd> class based on Delighted servise API
 * for more see https://github.com/delighted/delighted-php
 *
 * @author Igor Shvartsev (igor.shvartsev@gmail.com)
 * @version 1.0
 * @package Pley.Nps.Impl.Delighted
 * @subpackage Nps
 */
class NpsManager implements NpsManagerInterface
{
    /**
     * Survey throttling,
     * Time period , default 3 months in seconds
     *
     * @var int
     */
    protected $_timePeriod = 8121600;

    /** @var \Pley\Repository\User\UserNpsRepository */
    protected $_userNpsRepository;

    /** @var [] */
    protected $_npsConfig;

    public function __construct(UserNpsRepository $userNpsRepository)
    {
        $npsConfig = \Config::get('constants.nps');
        Client::setApiKey($npsConfig['delighted']['apiKey']);
        $this->_userNpsRepository = $userNpsRepository;
        $this->_npsConfig = $npsConfig['delighted'];
    }

    /**
     *  Creates/updates user data in the NPS service account, adds him to schedule
     *  and allows to send survey depending oo $allowSending and $delay parameters
     *
     * @param \Pley\Entity\User\User $user
     * @param boolean $allowSending
     * @param int $delay -  seconds
     * @param array $properties - array of additional properties for the created/updated account ,
     *                             also here may be used "question_product_name" to be used in the survey question
     *                             and "locale"  to use one of "ar", bg", "de", "da" ...
     *                             (see https://delighted.com/docs/api/sending-to-people)
     * @return []|null
     */
    public function addUserToSchedule(User $user, $allowSending = true, $delay = 0, $properties = [])
    {
        $userNpsList = $this->_userNpsRepository->findByUserId($user->getId());
        $userNps = null;
        $npsCount = count($userNpsList);

        if ($npsCount > 0) {
            return;
        }

        $data = [
            'email' => $user->getEmail(),
            'name' => $user->getFirstName() . ' ' . $user->getLastName(),
            'send' => $allowSending,
            'delay' => intVal($delay),
            'properties' => [
                'locale' => 'en',
                'userId' => $user->getId()
            ]
        ];

        if (count($properties) > 0) {
            $data['properties'] = array_merge($data['properties'], $properties);
        }

        // if sandbox is active replace user email by sandbox email
        // to avoid sending survey to real user
        if ($this->_npsConfig['sandbox']) {
            $data['email'] = $this->_npsConfig['sandboxEmail'];
            $data['last_sent_at'] = $userNps ? ($userNps->getCreatedAt() - $this->_timePeriod) : (time() - $this->_timePeriod);
        }

        $response = Person::create($data);

        if (isset($response->id)) {
            $userNps = new UserNps();
            $userNps->setUserId($user->getId());
            $userNps->setSurveyScheduledAt($response->survey_scheduled_at);
            $this->_userNpsRepository->save($userNps);

            \Log::info('Delighted NPS service request to ' . $data['email'] . ', scheduled at ' . date('Y-m-d H:i:s', $response->survey_scheduled_at));
            return [
                'id' => $response->id,
                'survey_scheduled_at' => $response->survey_scheduled_at
            ];
        }
    }
}

