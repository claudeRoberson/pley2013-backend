<?php
	
	// function g_authenticateUser($sessionId, $userId) {
	// 	$user = User::where('id','=',intval($userId))->where('remember_token','=',$sessionId, 'AND')->get();		
	// 	if (sizeof($user) != 1) {
	// 		return false;
	// 	}
	// 	if ($user[0]->remember_token !== $sessionId) {
	// 		return false;
	// 	}
	// 	return $user[0];
	// }


if ( ! function_exists('array_get_value'))
{
    /**
     * Return value for the given array key .
     * It differs from function "array_get", scanning only one dimentional array and 
     * returning value even if it's NULL , empty or 0. 
     *
     * @param  array $arr
     * @param  mixed $key
     * @param  mixed $default
     * @return mixed
     */
    function array_get_value($arr, $key, $default = '')
    {
         if (is_array($arr)) {
             if (key_exists($key, $arr)) {
                 return $arr[$key];
             }
             return $default; 
         }
         return $arr;
    }
}