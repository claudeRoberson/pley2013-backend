<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace api\v1\Notification;

use \Pley\Config\ConfigInterface as Config;
use Pley\Enum\NotificationSubscriberTypeEnum;
use \Pley\Mail\AbstractMail as Mail;
use \Pley\Entity\Notification\NotificationSubscriber;

/**
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class NotificationSubscriberController extends \api\v1\BaseController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Repository\Notification\NotificationSubscriberRepository */
    protected $_notificationSubscriberRepository;

    public function __construct(
        Config $config, Mail $mail,
        \Pley\Db\AbstractDatabaseManager $dbManager,
        \Pley\Repository\Notification\NotificationSubscriberRepository $notificationSubscriberRepository)
    {
        $this->_config = $config;
        $this->_mail = $mail;

        $this->_dbManager = $dbManager;
        $this->_notificationSubscriberRepository = $notificationSubscriberRepository;
    }

    // POST /notification/subscribe/reveal

    public function subscribeRevealNotifications()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        $rules = ['email' => 'required|email'];

        \ValidationHelper::validate($json, $rules);
        $subscriber = new NotificationSubscriber();
        $subscriber->setType(NotificationSubscriberTypeEnum::REVEAL)
            ->setEmail($json['email']);
        $this->_notificationSubscriberRepository->save($subscriber);

        return \Response::json([
            'success' => true
        ]);
    }
}
