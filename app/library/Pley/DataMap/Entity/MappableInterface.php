<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap\Entity;
/**
 * Class description goes here
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
interface MappableInterface
{
    /**
     * Maps values to object properties from given DB data
     * @param $rowData
     * @return []
     */
    public function mapFromRow($rowData);

    /**
     * Maps array for db insertion/update from given object
     * @return []
     */
    public function mapToRow();

    /**
     * Returns an array with all column names, used in mapping
     * @return []
     */
    public static function columns();

    /**
     * Returns a table name mapped to entity
     * @return string
     */
    public static function tableName();

}