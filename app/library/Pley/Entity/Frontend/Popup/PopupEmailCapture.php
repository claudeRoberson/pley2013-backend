<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Frontend\Popup;

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>PopupEmailCapture</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Frontend.Popup
 * @subpackage Entity
 * @Meta\Table(name="popup_email_capture")
 */
class PopupEmailCapture extends Entity
{
    use Timestampable;
    
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(column="email")
     */
    protected $_email;
    /**
     * @var int
     * @Meta\Property(column="social_share_at")
     */
    protected $_socialShareAt;
    
    public function getId()
    {
        return $this->_id;
    }

    public function getEmail()
    {
        return $this->_email;
    }

    public function getSocialShareAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_socialShareAt);
    }

    public function setId($id)
    {
        $this->_checkImmutableChange('_id');
        $this->_id = $id;
        return $this;
    }

    public function setEmail($email)
    {
        $this->_email = $email;
        return $this;
    }

    public function setSocialShareAt($socialShareAt)
    {
        if (is_int($socialShareAt)) {
            $this->_socialShareAt = \Pley\Util\Time\DateTime::date($socialShareAt);
        } else {
            $this->_socialShareAt = $socialShareAt;
        }
    
        return $this;
    }

}
