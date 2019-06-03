<?php /** @copyright Pley (c) 2014, All Rights Reserved */

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends \Illuminate\Database\Eloquent\Model implements UserInterface, RemindableInterface
{
    use UserTrait, RemindableTrait;
    
    // ---------------------------------------------------------------------------------------------
    // This section declares the known DB fields that are accessible through attribute reflection
    // (laravel-way: $this->column_name;)
    // For the purpose of using Laravel's auth, we only really need to know
    // @var int    $id
    // @var string $email
    // @var string $password
    // ---------------------------------------------------------------------------------------------

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('password', 'remember_token', 'updated_at');

}
