<?php
/*
Plugin Name: Category Subscription LM
Plugin URI: https://github.com/layla37/subscribe-email-alert
Description: A plugin to email subscribers of particular categories when a post or comment has been made.
Version: 1.0
Author: Layla Mandella and Mike Schachter
Author URI: https://github.com/layla37/subscribe-email-alert
License: GPL2
*/

add_action( 'publish_post', 'catsub_publish' );

add_action( 'edit_user_profile', 'add_extra_profile_fields' );

add_action( 'edit_user_profile_update' , 'my_edit_user_profile_update' );

add_action( 'comment_post' , 'my_comment_post' );


function my_comment_post( $comment_id )
{
	$comment = get_comment( $comment_id );
	$post_id = $comment->comment_post_ID; 
	$the_post = get_post( $post_id, ARRAY_A );
	$post_categories = get_the_category( $post_id );
	
	// get a list of users that are subscribed to the categories and need to be emailed
	$users_to_email = get_subscribers_to_category( $post_categories );

	// email the subscribers of the post category
	foreach ( $users_to_email as $user )
	{
		$permalink = get_permalink( $post_id );
		$post_title = $the_post['post_title'];
		$catname = $post_categories[0]->name;
		$new_comment_email = "Someone has posted a new comment for the post, <a href=\"$permalink\">$post_title</a>, in the $catname!";

		wp_mail( $user->user_email,
				"New Comment in the $catname",
				$new_comment_email,
				array( 'content-type: text/html' ) );
	}	
}

function my_edit_user_profile_update( $user_id )
{
	// get the user ID
	$uid = $_POST['user_id'];
	
	// get the array of categories that were selected
	$user_categories = $_POST['catsub_catname'];
		
	// we only care of one or more categories was selected
	if ( count( $user_categories ) > 0 )
	{
		// concatenate the categories into one big string
		$category_string = implode( $user_categories, ',' );
		
		// store the user category subscriptions
		update_user_meta( $uid, 'catsub_categories', $category_string );
	} else {
		// make sure that the user is unsubscribed from all categories
		update_user_meta( $uid, 'catsub_categories', '' );
	}
		
}

/*
	Gets the categories that a user is subscribed to. Returns an array of category names.
	
	Arguments:
		$uid: the user id to get the categories of
*/
function get_user_subscriptions( $uid )
{
	// get the comma-separated list of user categories from the database
    $category_string = get_user_meta( $uid, 'catsub_categories', true );
    
    // turn the comma separated list into an array of categories
    $user_categories = explode( ',', $category_string );
    
	return $user_categories;
}

function add_extra_profile_fields( $user ) 
{
    echo '<h3>Category Subscriptions:</h3>';
    
    // get the user id
    $uid = $user->ID;
    
    $user_categories = get_user_subscriptions( $uid );
    
    
	$categories=get_categories( array( 'hide_empty'=>0 ) );

  	foreach( $categories as $category )
  	{ 
    	echo "\n";
    	$checked_html = '';
    	//check to see if the user is subscribed to the category we're going to display
    	if ( in_array( $category->name, $user_categories ) )
    	{
    		//since the user is already subscribed to this category, check the box
    		$checked_html = 'CHECKED';
    	}

    	echo "<input type='checkbox' name='catsub_catname[]' value='$category->name' $checked_html>$category->name<br />";
	} 
}

/*
	Checks to see if a user is subscribed to the categories that a post has. Returns true
	if the user should be emailed.
*/
function user_should_be_emailed( $user_categories, $post_categories )
{
	if ( count( $user_categories ) > 0 )
	{
		foreach ( $user_categories as $ucat )
		{
			foreach ( $post_categories as $pcat )
			{
				if ( $ucat == $pcat->name )
				{
					return true;
				}
			}	
		}
	}
	return false;
}


/*
Takes an array of category objects and returns an array of users that belong to at least
one of those categories.
*/
function get_subscribers_to_category( $post_categories )
{
	// get the subscribers to the post category
	$users_to_email = array();
    $blogusers = get_users();
    foreach ( $blogusers as $user ) {
    	//get the categories the user is subscribed to
        $user_categories = get_user_subscriptions( $user->ID );
        if ( user_should_be_emailed( $user_categories, $post_categories ) )
        {
        	// error_log(print_r($user, true));
        	// error_log("user $user->user_email should be emailed");
        	$users_to_email[] = $user;
        }
    }
    return $users_to_email;
}


function catsub_publish( $post_id )
{
	// get the category of the post
	$the_post = get_post( $post_id, ARRAY_A );
	
	// get an array of categories for the post
	$post_categories = get_the_category( $post_id );


	// get a list of users that are subscribed to the categories and need to be emailed
	$users_to_email = get_subscribers_to_category( $post_categories );

	// email the subscribers of the post category
	foreach ( $users_to_email as $user )
	{
		$permalink = get_permalink( $post_id );
		$post_title = $the_post['post_title'];
		$catname = $post_categories[0]->name;
		wp_mail( $user->user_email,
				"New Post in the $catname",
				"Check out the new post, <a href=\"$permalink\">$post_title</a>, in the $catname!",
				array( 'content-type: text/html' ) );
	}

	return $post_id;
}