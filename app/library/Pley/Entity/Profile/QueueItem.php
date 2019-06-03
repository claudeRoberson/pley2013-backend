<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Profile;

/**
 * The <kbd>QueueItem</kbd> represents an entry in the Profile Subscription item sequence queue.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Profile
 * @subpackage Entity
 */
class QueueItem
{
    const TYPE_PURCHASED = 'P';
    const TYPE_RESERVED  = 'R';
    
    private static $NODE_SEQUENCE_INDEX = 'seq_idx';
    private static $NODE_TYPE           = 'type';
    
    /** @var int */
    protected $_sequenceIndex;
    /** @var int */
    protected $_type;
    
    /**
     * Creates a new <kbd>QueueItem</kbd> item from the supplied data in array format.
     * @param array $data
     * @return \Pley\Entity\Profile\QueueItem
     * @see ::toArray()
     */
    public static function fromArray($data)
    {
        $queueItem = new static($data[self::$NODE_SEQUENCE_INDEX], $data[self::$NODE_TYPE]);
        return $queueItem;
    }
    
    public function __construct($sequenceIndex, $type)
    {
        $this->_checkType($type);
        
        $this->_sequenceIndex = $sequenceIndex;
        $this->_type          = $type;
    }

    /**
     * Get the Sequence Index that represents the item for this queue entry.
     * <p>The item (aka item id) is defined at the last minute when we are ready to ship which is
     * when we lock the `itemId`</p>
     * <p>Other than that, the item to be ship is always undefined with only the sequence being fixed
     * </p>
     * @return int
     */
    public function getSequenceIndex()
    {
        return $this->_sequenceIndex;
    }

    /**
     * Indicates whether the item that will be represented in this queue entry is already purchased
     * or in reserved state.
     * <p>Purchased items can be added to shipments when the active period allows it, while Reserved
     * require a successful payment before they can be considered purchased and thus added.</p>
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Allows to update a Reserved item to a Purchased state (after a successful payment)
     * @param string $type
     * @see ::TYPE_PURCHASED
     * @see ::TYPE_RESERVED
     */
    public function setType($type)
    {
        $this->_checkType($type);
        
        if ($this->_type == self::TYPE_PURCHASED && $type == self::TYPE_RESERVED) {
            throw new \Exception('Cannot change a purchased item into a reserved');
        }
        
        $this->_type = $type;
    }

    /**
     * Get the array representation of this queue item.
     * @return array
     */
    public function toArray()
    {
        $queueItem = [
            self::$NODE_SEQUENCE_INDEX => $this->_sequenceIndex,
            self::$NODE_TYPE           => $this->_type,
        ];
        
        return $queueItem;
    }
    
    /**
     * Helper function to check that the supplied type is within the allowed values.
     * @param string $type
     * @throws \Exception
     */
    private function _checkType($type)
    {
        $allowedTypes = [self::TYPE_PURCHASED, self::TYPE_RESERVED];
        if (!in_array($type, $allowedTypes)) {
            throw new \Exception('Invalid type for Queue Item');
        }
    }
}
