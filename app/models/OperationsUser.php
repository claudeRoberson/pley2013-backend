<?php /** @copyright Pley (c) 2014, All Rights Reserved */

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class OperationsUser extends Eloquent implements UserInterface, RemindableInterface
{
    use UserTrait;
    use RemindableTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'op_user';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('password', 'remember_token');

}
