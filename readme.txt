WordPress Cart

Copyright 2005, 2006  Dave Merwin and Michael Calabrese  (email : dave@madeblue.com m2calabr@dunamisdesign.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


URI: http://www.wordpresscart.org
Description: This plugin add a shopping cart system to wordpress. There is an install script that adds some tables to the database that must be run first before using this plugin. If you are looking for some help with the plugin please goto: wordpresscart.org.  This is a collaborative project by Made Blue (madeblue.com) and Dunamis Design (dunamisdesign.com).
Authors: Dave Merwin (Made Blue) and Michael Calabrese (Dunamis Design)
Authors URI: http://madeblue.com  http://dunamisdesign.com
Version: 0.9.1

Introduction
============
This plugin is a shopping cart for the Word Press system. It was built as a quote system for a company that generates a quote, gets the customer approval, then they process the order.  It is setup that a post can be a item that can be sold, if it has marked as so.

Installation
============
You can extract the tar file into the wp-contents directory.  This will put the plugin into the plugins directory and the theme WPC into the themes directory.

Next login to the admin of your site and enable the shopping cart plugin.

Set your theme to the WPC theme.

Edit any options that you need to change in under the Cart | Options Page.

Notes
=====
This plugin requires extra templates to be available.  That is why we have included the WPC template.  If you have your own template, see how we setup the current version.  See Reuired Special Templates below.

Required Special Templates
==========================
cart.php
	This is the main display of the shopping cart.  When you are doing anything with the cart you are currently sent here.

admincart.php 
	The is the admin view of the whole order, very much like cart.php but more options.

cart_conf.php
	This is the confirmation page (which has been setup to be linked from an email).  Here the customer is expected to check to see if the order is correct and approves of the prices.  Here the customer can put in their email.
	
cart_conf_error.php
	If for some reason someone tries to go to the confirmaton page and has something thing wrong, this is the page displayed.

login_error.php
	Currently the system is setup that you must be a registered user to add something to the cart.  So this is the page that is brought up, if your are not logged into the system and try to add something to the cart.

thankyou.php
	This is displayed with a customer click on making what is in their cart into a quote.
	
Creating a Link to Add an Item the Cart
=======================================
The key is sending the post id and "addcart=1" as a link to the site itself and the shopping cart plugin will take over. For example:
<a href="<?php echo get_settings('siteurl') . '/?p='. $post->ID ?>&amp;addcart=1">add to cart</a>

Displaying the cart/Using the $cart object.
===========================================
The cart object ($cart) can be used in any template.  It is passed as a global so all main templates, like index, see it with out any special effort.  Any template that is called as a function from a main template (e.g: get_header(), get_sidebar(), get_footer() ), you will need to add a "global $cart;" at the top of the template.  See sidebar.php as an example.

Once you have access to the cart object, you can see if the user has a cart available to them use call: $cart->is_available().

You can set through the items in the cart by calling  $cart->cart_item().  This function returns an object with the row data in it.  The current attributes are:
+----+---------+---------+----------+-----------+-------+-------+
| ID | cart_id | post_id | quantity | map_price | price | notes |
+----+---------+---------+----------+-----------+-------+-------+

You can step through all of the items using code similar to the following:
$cart_total=0;$tbody='<table>';
$cart->reset_cart();
while ($row = $cart->cart_item()) {
	$tbody .= '<tr>';
   $tbody .= '<td>'.$row->quantity.'</td>';
   $tbody .= '<td><a href="'.get_bloginfo('url').'/?p='.$row->post_id.'">'.$row->post_title.'</a></td>';
   $tbody .= '<td class="td-price">$'. $row->map_price .'</td>';
   $tbody .= '<td class="td-price">$'.$row->map_price*$row->quantity .'</td>';
   $tbody .= '</tr></tbody>';
   $cart_total +=($row->map_price*$row->quantity);
}
$tbody .= '</table>';
echo $tbody;

Quick review of cart template functions
---------------------------------------
global $cart           -- gives access to the $cart object if it does not have it already
$cart->is_available()  -- return boolean to let you know if you can use the cart.
$cart->cart_item()     -- returns a row of the current item in the cart (this is an itterator)
$cart->reset_cart()    -- set the itterator back at the begining.
$cart->get_orderinfo() -- returns a row of the order information (only one row)

$cart->get_userinfo()  -- returns a row of the user information (only one row)
$cart->get_ordernote() -- returns the information in the order notes field only.

Attribrutes:
cart_item : 
| ID | cart_id | post_id | quantity | map_price | price | notes |

get_orderinfo :
| ID | cart_id | order_id | order_date | notes | status   | user_id | confirmation_number | ship_address_1 | ship_address_2 | ship_city | ship_state | ship_country | ship_postal | bill_address_1 | bill_address_2 | bill_city | bill_state | bill_country | bill_postal | phone | email | cc_name | cc_number | cc_expmonth | cc_expyear | cc_cvv | cc_cvvindicator |

get_userinfo :
| ID | user_login | user_pass | user_firstname | user_lastname | user_nickname | user_nicename | user_icq | user_email | user_url | user_ip | user_domain | user_browser | user_registered     | user_level | user_aim | user_msn | user_yim | user_idmode | user_activation_key | user_status | user_description | ID | dj_users_id | company | ship_address_1 | ship_address_2 | ship_city | ship_state | ship_country | ship_postal | bill_address_1 | bill_address_2 | bill_city | bill_state | bill_country | bill_postal | alt_address_1 | alt_address_2 | alt_city | alt_state | alt_country | alt_postal | phone_home | phone_cell | phone_office | actingas |


Acting AS
=========
This is a special feature of the cart. It was developed so you could have one system for web and phone-in orders.  The concept is you can ACT AS the person you want to create an order for.  You use this feature on the admin side, but selecting Cart | Phone In.  You then search for the person you want to create a order for.  If you find them you can click on GoTo Cart.  Otherwise you can add a person by select new user.

When you are templating your site, you can use the display_acting_as function to display a div tag that shows if you are acting as someone else.  In the WPC template you can see and example of this in the header.php.

Documentation
=============
Currently you are looking at it.

Todo:
=====
Better documentation
Move all major logic code into shoppingcart.php
Setup options for:
	Company Name
	Company Phone
	Company Email
	Variable based control of items like Thank you messages and such
	Other Magic numbers already in the code
	Admin Panel that controls feature set not just cart processing
Move extra post data to post meta.
Move extra user data into user meta (version 2 - only)
Setup module system (using WP modules system?)
Add CC processing modules
Add Shipping modules
Add Inventory Control

Change Log
==========
0.9.1 -- Initial public version
0.9.2 -- Rebuilt "Acting as" 
		-- Moved a lot of functionally from cart.php into the shopcart plugin
		-- Create functions to access cart data that can be used in templates
		-- Setup cart to be passed to all templates
		-- Improved the documentation
		-- Added template login_error.php
		-- Fixed CSS color bug for Notes textarea