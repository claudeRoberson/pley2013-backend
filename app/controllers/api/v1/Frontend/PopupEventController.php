<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace api\v1\Frontend;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Mail\AbstractMail as Mail;

/**
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class PopupEventController extends \api\v1\BaseController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var \Pley\Dao\Frontend\Popup\PopupEventDao */
    protected $_popupEventDao;
    /** @var \Pley\Dao\Frontend\Popup\PopupEmailCaptureDao */
    protected $_popupEmailCaptureDao;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;
    
    public function __construct(Config $config, Mail $mail,
            \Pley\Dao\Frontend\Popup\PopupEventDao $popupEventDao,
            \Pley\Dao\Frontend\Popup\PopupEmailCaptureDao $popupEmailCaptureDao,
            \Pley\Coupon\CouponManager $couponManager)
    {
        $this->_config               = $config;
        $this->_mail                 = $mail;
        $this->_popupEventDao        = $popupEventDao;
        $this->_popupEmailCaptureDao = $popupEmailCaptureDao;
        $this->_couponManager        = $couponManager;
    }
    
    // GET popup/event-list
    public function getPopupEventList()
    {
        \RequestHelper::checkGetRequest();
        
        $responseMap = ['isShow' => false, 'eventList' => []];
        
        $activeList = $this->_popupEventDao->getActiveList();
        
        if (empty($activeList)) {
            return \Response::json($responseMap);
        }
        
        $responseMap['isShow'] = true;
        
        foreach ($activeList as $popupEvent) {
            $responseMap['eventList'][] = $this->_parsePopupEvent($popupEvent);
        }
        
        return \Response::json($responseMap);
    }
    
    // POST popup/event
    public function registerPopupEvent()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        \ValidationHelper::validate($json, [
            'popupEventId' => 'required|integer',
            'email'        => 'required|email',
        ]);
        
        /* @var $popupEvent \Pley\Entity\Frontend\Popup\PopupEvent */
        $popupEvent = $this->_popupEventDao->find($json['popupEventId']);
        $email      = $json['email'];

        switch ($popupEvent->getEventType()) {
            case \Pley\Enum\PopupEventType::EMAIL_SHARE:
                $this->_handleEmailShare($email);
                break;
            case \Pley\Enum\PopupEventType::SOCIAL_SHARE:
                $this->_handleSocialShare($popupEvent, $email);
                break;
        }
        
        return \Response::json(['success' => true]);
    }
    
    private function _parsePopupEvent(\Pley\Entity\Frontend\Popup\PopupEvent $popupEvent)
    {
        $popupEventMap = [
            'id'       => $popupEvent->getId(),
            'index'    => $popupEvent->getIndex(),
            'type'     => $popupEvent->getEventType(),
            'secDelay' => $popupEvent->getSecDelay(),
            'title'    => $popupEvent->getTitle(),
            'body'     => $popupEvent->getBody(),
            'coupon'   => null,
        ];
        
        if (!empty($popupEvent->getCouponId())) {
            $coupon = $this->_couponManager->getCoupon($popupEvent->getCouponId());
            $popupEventMap['coupon'] = [
                'code'   => $coupon->getCode(),
                'type'   => $coupon->getType(),
                'amount' => $coupon->getDiscountAmount(),
            ];
        }
        
        return $popupEventMap;
    }
    
    /**
     * @param string $email
     * @return \Pley\Entity\Frontend\Popup\PopupEmailCapture
     */
    private function _handleEmailShare($email)
    {
        $popupEmailCapture = $this->_popupEmailCaptureDao->findByEmail($email);
        
        // If the email has been captured, there is nothing left to do.
        if (!empty($popupEmailCapture)) {
            return $popupEmailCapture;
        }
        
        $popupEmailCapture = new \Pley\Entity\Frontend\Popup\PopupEmailCapture();
        $popupEmailCapture->setEmail($email);
        
        $this->_popupEmailCaptureDao->save($popupEmailCapture);
        return $popupEmailCapture;
    }
    
    /**
     * 
     * @param \Pley\Entity\Frontend\Popup\PopupEvent $popupEvent
     * @param string $email
     */
    private function _handleSocialShare(\Pley\Entity\Frontend\Popup\PopupEvent $popupEvent, $email)
    {
        $popupEmailCapture = $this->_handleEmailShare($email);
        
        $socialShareAt = $popupEmailCapture->getSocialShareAt();
        
        // if the share has happened, no need to do anything.
        if (!empty($socialShareAt)) {
            return;
        }
        
        // Otherwise we need to register the time and send the email
        $coupon = $this->_couponManager->getCoupon($popupEvent->getCouponId());
        
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($coupon);
        $mailTagCollection->setCustom('email', $email);
        
        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::POPUP_SOCIAL_SHARE;

        $mailUserTo = new \Pley\Mail\MailUser($email, $email);

        // Sending a welcome email is important, but if the third party mail provider fails for some
        // reason, until we can add some sort of queue to retry, we don't want to just crash as
        // it would leave the frontend believing the Request failed, when it actually completed but
        // just the email didn't go out, so for now, we just siliently log the exception and continue
        // to return success.
        try {
            $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo);
        } catch (Exception $ex) {
            \Log::error((string)$ex);
        }
        
        $popupEmailCapture->setSocialShareAt(time());
        $this->_popupEmailCaptureDao->save($popupEmailCapture);
    }
}
