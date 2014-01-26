<?php
/*
Plugin Name: LM metadata Owners
Plugin URI: http://www.laylamandella.com
Description: A plugin to subscribe all owners to the marketing forum.
Version: 1.0
Author: Layla Mandella and Mike Schachter
Author URI: http://www.laylamandella.com
License: GPL2
*/




/*
	change the metadata for the owner role in order to subscribe to the marketing forum
*/
function getUsersWithRole($role) 
{
	$args = array('role' => $role);
    $users = get_users($args);
    return $users;
}

function get_user_subscriptions2($uid)
{
	//get the comma-separated list of user categories from the database
    $category_string = get_user_meta($uid, 'catsub_categories', true);
    if (strlen($category_string) == 0)
    {
    	return array();
    }
    
    //turn the comma separated list into an array of categories
    $user_categories = explode(',', $category_string);
    
	return $user_categories;
}	

function subscribe_users($category_name, $user_role)
{
	$the_users = getUsersWithRole($user_role);

	foreach ($the_users as $u)
	{
		$uid = $u->ID;
		//get the array of categories the user is subscribed to
		$user_subs = get_user_subscriptions2($uid);
		//check to see if the user is subscribed to the marketing forum
		$has_marketing_category = in_array($category_name, $user_subs);
		//if not, then add the marketing forum to the array and save it
		if (!$has_marketing_category)
		{
			$user_subs[] = $category_name;
			$category_string = implode($user_subs, ',');
			error_log("adding marketing forum to user $u->user_email: $category_string");
		
			//store the user category subscriptions
			update_user_meta($uid, 'catsub_categories', $category_string);
		}	
	}

}

subscribe_users('Marketing Forum', 'owners');
subscribe_users('Marketplace Forum', 'owners');


?>