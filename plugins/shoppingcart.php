<?php
/*
Plugin Name: WordPress Cart
Plugin URI: http://www.wordpresscart.org
Description: This plugin add a shopping cart system to wordpress. If you are looking for some help with the plugin please goto: <a href="http://www.wordpresscart.org/">wordpresscart.org</a>.  This is a collaborative project by <a href="http://www.davemerwin.com">DaveMerwin.com</a> and <a href="http://www.dunamisdesign.com">Dunamis Design</a>.
Author: Dave Merwin and Michael Calabrese
Author URI: http://www.wordpresscart.org
Version: 0.9.6
*/

/*  Copyright 2005, 2006  Dave Merwin and Michael Calabrese  (email : dave@madeblue.com m2calabr@dunamisdesign.com)

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
*/

//__________________________________________________________________________________
//Globals for this plugin variables used 
//__________________________________________________________________________________
//gives me a layer to deal with any changed to the name names
$user_table = $wpdb->users;
$userext_table = $table_prefix .'user_extd';
$usermeta_table = $wpdb->usermeta;
$order_table = $table_prefix . 'order_sys';
$orderdetail_table = $table_prefix . 'order_sys_detail';
$payment_table = $table_prefix . 'order_payments';
$product_table = $wpdb->posts;
$category_table = $wpdb->categories;
global $__shopcart_installed_payments;
$__shopcart_installed_payments=0;

$shopcart_admin_level=9;  //The admin level which allow certain types of edits.  (option var?)
//global $cart;

//In older versions of WordPress you need to guard against this from being
//run twice so that classes and function are not defined twice.
//__________________________________________________________________________________
if (!isset($__shoppingcart)) {
	//__________________________________________________________________________________
	//Variable input checking functions
	//__________________________________________________________________________________
	//These are more limiting than the php functions and all form input is string and 
	//is_int and is_numberic will not work.
	
	function shopcart_is_number($n) {  return !ereg('[^0-9.]', $n); }
	function shopcart_is_int($n)    {  return !ereg('[^0-9]', $n);  }


	class shoppingcart {
	var $cart_id=null; //The DB id of this cart
	var $user_id=null; //WP user id
	var $status;  //Status of this order
	
	//easy reference for the tables we are going to use
	var $user_table;
	var $userext_table;
	var $product_table;
	var $order_table;
	var $orderdetail_table;
	
	//This is for the cart iterator
	var $items = null;
	var $items_index = 0;
	//This is for the download iterator
	var $downloads = null;
	var $downloads_index = 0;
	
	var $user_info = null;
	var $order_res = null;
	
	var $payments = null;
	var $payments_index = 0;

	
	var $wpdb;  //wordpress database object
	//___________________________________________________
	function shoppingcart() {
	//___________________________________________________
		global $wpdb, $userext_table, $order_table, $orderdetail_table,$payment_table;
		$this->wpdb = &$wpdb;
		$this->user_table = $wpdb->users;
		$this->userext_table = $userext_table;
		$this->order_table = $order_table;
		$this->payment_table = $payment_table;
		$this->orderdetail_table = $orderdetail_table;
		$this->product_table = $wpdb->posts;
	}
	//___________________________________________________
	function is_available() {
	//___________________________________________________
		return isset($this->cart_id);
	}
	//___________________________________________________
	function get_userdata() {
	//___________________________________________________
	//grab the users info for ourselfs so we can protect 
	//against spoofing.
		if (isset($_COOKIE['wordpressuser_' . COOKIEHASH]))
			$user_login = $_COOKIE['wordpressuser_' . COOKIEHASH];
		$userdata = get_userdatabylogin($user_login);
		return $userdata;
	}
	//___________________________________________________
	function init_user($u_id) {
	//___________________________________________________
		// Using another person's user id should only be 
		// allowed an admin to 
		//check the user's information
		global $shopcart_adminlevel;
		$userdata=$this->get_userdata();
		if (shopcart_is_int($u_id) && ($userdata->user_level >= $shopcart_adminlevel) ) {
			$this->user_id = $u_id;
		} else {  //makes sure the user can only get their cart
			$this->user_id = $userdata->ID;
		}
		$this->cart_id = $this->wpdb->get_var("SELECT cart_id FROM {$this->order_table} WHERE status='cart' AND user_id='$u_id'");
	}
	//___________________________________________________
	function init_CartID($id) {
	//___________________________________________________
		//This function sets the cart object to a 
		//a given cart.  This is done under two cases
		// 1) An admin is modifing the cart
		// 2) A user is confirming the cart
		// !!!!! Need to comeup with a better way later...this is secure
		// as it is called once in the current code and the values
		// are checked.  It would be better if we could wrap all of the
		//security to this object.
	
		//test to make sure that this cart exsits
		$this->cart_id = $this->wpdb->get_var("SELECT cart_id FROM {$this->order_table} WHERE cart_id='$id'");
		$this->user_id = $this->wpdb->get_var("SELECT user_id FROM {$this->order_table} WHERE cart_id='$id'");
		$userdata=$this->get_userdata();
	}
	//___________________________________________________

        //___________________________________________________
        function has_payment_plugin() {
        //___________________________________________________
		global $__shopcart_installed_payments;
                do_action('wpc_installed');
                return ($__shopcart_installed_payments>0);
        }

	//___________________________________________________
	// ORDER ITTERATORS
	//___________________________________________________
	function reset_cart() {
		$this->items = null;
		$this->items_index = 0;
		if (isset($this->cart_id)) {
			$sql = "SELECT od.ID as cart_itemid, ".
							"od.post_id,od.quantity,od.map_price,od.price,od.notes,p.post_is_exclude,p.post_title ".
						"FROM ({$this->order_table} o INNER JOIN {$this->orderdetail_table} od USING (cart_id) )" .
													"INNER JOIN {$this->product_table} p ON od.post_id=p.id  " .
						"WHERE o.cart_id={$this->cart_id} ";
			$this->items = $this->wpdb->get_results($sql);
		}
	}
	function cart_item() {
		if (!$this->items) {$this->reset_cart();}
		if (isset($this->items[$this->items_index])) {
			return $this->items[$this->items_index++];
		} else {
			return null;
		}
	}
    //___________________________________________________
    // DOWNLOAD ITTERATOR
    //___________________________________________________
    function reset_downloads($user_id) {
        $this->downloads = null;
        $this->downloads_index = 0;
        $base = get_option('shopcart_download_location_base');
        print $base;
        $sql = "SELECT od.ID as cart_itemid, ".
                    "od.post_id,od.quantity,od.map_price,od.price,CONCAT('$base', p.post_download_name) as post_download_name,p.post_title ".
                 "FROM ({$this->order_table} o INNER JOIN {$this->orderdetail_table} od USING (cart_id) )" .
                                    "INNER JOIN {$this->product_table} p ON od.post_id=p.id  " .
                "WHERE  p.post_download_name <> '' " .
                "  AND o.status IN ('order','processed')" .
                '';
        $days_to_expire = get_option('shopcart_download_expire_days');
        if ($days_to_expire != 0) {
            $sql .= " AND ( TO_DAYS('".date('Y-m-d')."') - TO_DAYS(o.order_date)  <= " . $days_to_expire . ') ';
        }
        $this->downloads = $this->wpdb->get_results($sql);
    }
    function download_item() {
        if (!$this->downloads) {return null;}
        if (isset($this->downloads[$this->downloads_index])) {
            return $this->downloads[$this->downloads_index++];
        } else {
            return null;
        }
    }
	//___________________________________________________
	function get_orderinfo($reset=true) {
		if (isset($this->cart_id) && ($this->order_res == null || $reset)) {
			$sql = "SELECT * FROM {$this->order_table} o WHERE o.cart_id={$this->cart_id}";
			$this->order_res = $this->wpdb->get_row($sql);
		}
		return ($this->order_res);
	}
	function get_ordertotal() {
		$total=0;
		if (isset($this->cart_id)) {
			$sql = "SELECT sum(od.quantity*od.map_price) as total FROM {$this->orderdetail_table} od WHERE od.cart_id={$this->cart_id}";
			$total = $this->wpdb->get_var($sql);
		}
		return ($total);
	}
	function get_orderfinaltotal() {
		$total=0;
		if (isset($this->cart_id)) {
			$sql = "SELECT sum(od.quantity*od.price) as total FROM {$this->orderdetail_table} od WHERE od.cart_id={$this->cart_id}";
			$total = $this->wpdb->get_var($sql);
		}
		return ($total);
	}
	function get_ordernote() {
		if (isset($this->cart_id) && $this->order_res == null) {
			$sql = "SELECT * FROM {$this->order_table} o WHERE o.cart_id={$this->cart_id}";
			$this->order_res = $this->wpdb->get_row($sql);
		}
		return ($this->order_res->notes);
	}
	
	function get_userinfo() {
		$userres=null;
		if (isset($this->cart_id)) {
			$sql = "SELECT * FROM {$this->user_table} u LEFT JOIN {$this->userext_table} x ON u.ID=x.ID WHERE u.ID={$this->user_id}";
			$userres = $this->wpdb->get_row($sql);		
		}
		return ($userres);
	}
	function update_order($array_payinfo) {
		$valuelist="cart_id='{$this->cart_id}' ";
		foreach ($array_payinfo as $field => $value) {
			if ($field=='amount') {
				$valuelist .= ", {$field}={$value}";
			} else {
				$valuelist .= ",{$field}='{$value}'";
			}
		}
		// insert into table (fields) values (values)
		$sql = "UPDATE {$this->order_table} SET {$valuelist} WHERE cart_id='{$this->cart_id}';";
		$this->wpdb->query($sql);
	}
	//___________________________________________________
	//___________________________________________________
	// Payment Functions
	//___________________________________________________
	function add_payment($array_payinfo) {
		$valuelist = "'{$this->cart_id}'";
		$fieldlist='cart_id';
		foreach ($array_payinfo as $field => $value) {
			$fieldlist .= ",{$field}";
			if ($field=='amount') {
				$valuelist .= ",{$value}";
			} else {
				$valuelist .= ",'{$value}'";
			}
		}
		// insert into table (fields) values (values)
		$sql = "INSERT INTO {$this->payment_table} ({$fieldlist}) VALUES ({$valuelist});";
		$this->wpdb->query($sql);
	}
	function reset_payment() {
		$this->payments = null;
		$this->payments_index = 0;
		if (isset($this->cart_id)) {
			$sql = "SELECT * ".
						"FROM {$this->payment_table} " .
						"WHERE cart_id={$this->cart_id} ";
			$this->payments = $this->wpdb->get_results($sql);
		}
	}
	function has_payment() {
		if (isset($this->cart_id)) {
			$sql = "SELECT count(*) ".
						"FROM {$this->payment_table} " .
						"WHERE cart_id={$this->cart_id} ";
			$count = $this->wpdb->get_var($sql);
			if ($count>0) return true;
		}
		return false;
	}
	function cart_payment() {
		if (!$this->payments) {$this->reset_payment();}
		if (isset($this->payments[$this->payments_index])) {
			return $this->payments[$this->payments_index++];
		} else {
			return null;
		}
	}
	function get_paymenttotal() {
		$total=0;
		if (isset($this->cart_id)) {
			$sql = "SELECT sum(amount) as total FROM {$this->payment_table} WHERE cart_id={$this->cart_id}";
			$total = $this->wpdb->get_var($sql);
		}
		return ($total);
	}
	//___________________________________________________
	// Line Item Functions
	//___________________________________________________
	
	//___________________________________________________
	function add_item($item_id) {
	//___________________________________________________
		if (shopcart_is_int($item_id)) {
			$item_price = $this->wpdb->get_var("SELECT post_map FROM {$this->product_table} WHERE id=$item_id");
		}
		if (!isset($item_price)) {
			return;
		} //item was not found .. do nothing
		if ($this->is_available()) {
			//We have a cart to work with
			//Is this part alread in the cart?
			$detail_id = $this->wpdb->get_var("SELECT min(id) FROM {$this->orderdetail_table} WHERE cart_id='{$this->cart_id}' AND post_id='$item_id' ");
			if (isset($detail_id)) {
				//update the count on the current item in the cart
				$this->wpdb->get_var("UPDATE {$this->orderdetail_table} SET quantity=quantity+1 WHERE id='$detail_id'");
			} else {
				//add this item to the cart
				$this->wpdb->get_var("INSERT INTO {$this->orderdetail_table} (cart_id,post_id,quantity,map_price,price) VALUES ('{$this->cart_id}','$item_id',1,$item_price,$item_price)");
			}
		} else {
			//we need to create a new cart
			//insert into orders
			$user_info = $this->wpdb->get_row("SELECT * FROM {$this->user_table} u LEFT JOIN {$this->userext_table} x ON u.id=x.id WHERE u.id={$this->user_id}");
			//print "<pre>"; print_r ($user_info); print "</pre>";
                        if (strlen($user_info->ship_address_1)!=0 && strlen($user_info->bill_address_1)==0) {
			   foreach($user_info as $info=>$value) {
			   	   if (substr($info, 0, 4) == 'ship') {
					$field_list.= ",$info";
					$value_list.= ",'$value'";
				   } elseif (substr($info, 0, 4) == 'bill') {
					$field_list.= ",$info";
					$property= str_replace('bill','ship',$info);
					$value_list.= ",'".$user_info->$property."'";
				   }
			   } //foreach
                        } elseif (strlen($user_info->ship_address_1)==0 && strlen($user_info->bill_address_1)!=0) {
			   foreach($user_info as $info=>$value) {
			   	   if (substr($info, 0, 4) == 'bill') {
					$field_list.= ",$info";
					$value_list.= ",'$value'";
				   } elseif (substr($info, 0, 4) == 'ship') {
					$field_list.= ",$info";
					$property= str_replace('ship','bill',$info);
					$value_list.= ",'".$user_info->$property."'";
				   }
			   } //foreach
                        } else {
                           //this tries and copies the shipping and billing directly.
			   foreach($user_info as $info=>$value) {
			   	   if ((substr($info, 0, 4) == 'ship') || (substr($info, 0, 4) == 'bill')) {
					   $field_list.= ",$info";
					$value_list.= ",'$value'";
				   }
			   } //foreach
                        }
			$phone = $user_info->phone_office;
			if (strlen($phone) == 0) $phone = $user_info->phone_home;
			$this->wpdb->get_var("INSERT INTO {$this->order_table} (status,user_id,phone,email,order_date $field_list) VALUES ('cart','{$this->user_id}','$phone','{$user_info->user_email}',CURRENT_DATE $value_list)");
			//get the min(id)
			$this->cart_id = $this->wpdb->get_var("SELECT min(id) FROM {$this->order_table} WHERE status='cart' AND user_id='{$this->user_id}'");
			//update to set the cart id
			$this->wpdb->get_var("UPDATE {$this->order_table} SET cart_id={$this->cart_id} WHERE status='cart' AND user_id='{$this->user_id}'");
			//add this item to the cart
			if (is_numeric($item_id) && is_numeric($item_price)) {
				$this->wpdb->get_var("INSERT INTO {$this->orderdetail_table} (cart_id,post_id,quantity,map_price,price) VALUES ('{$this->cart_id}','$item_id',1,$item_price,$item_price)");
			}
		}
	} //add-item
	//___________________________________________________
	function delete_item($detail_id) {
	//___________________________________________________
		if (shopcart_is_int($detail_id))  {
			$this->wpdb->get_var("DELETE FROM {$this->orderdetail_table} WHERE id=$detail_id");
		}
	}
	//___________________________________________________
	//___________________________________________________
	//detail update functions
	//___________________________________________________
	function update_qty_byID($qty, $id) {
	//___________________________________________________
		if (shopcart_is_int($id) && shopcart_is_number($qty) )  {
			$this->wpdb->get_var("UPDATE {$this->orderdetail_table} SET quantity=$qty WHERE id=$id");
		}
	}
	//___________________________________________________
	function update_price_byID($amt, $id) {
	//___________________________________________________
		//This fuction is for setting prices and should only be done by an admin.
		global $shopcart_adminlevel;
		$userdata=$this->get_userdata();
		if ( ($userdata->user_level >= $shopcart_adminlevel) && shopcart_is_int($id) && is_numeric($amt) )  {
			$this->wpdb->get_var("UPDATE {$this->orderdetail_table} SET price=$amt WHERE id=$id");
		}
	}
	//___________________________________________________
	function update_detailnote_byID($note, $id) {
	//___________________________________________________
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// Need to check notes.
		if (shopcart_is_int($id) )  {
			$this->wpdb->get_var("UPDATE {$this->orderdetail_table} SET notes='$note' WHERE id=$id");
			}
	}
	//___________________________________________________
	//___________________________________________________
	//order update functions
	//___________________________________________________
	function remove_confnum_byID() {
	//___________________________________________________
		$this->wpdb->get_var("UPDATE {$this->order_table} SET confirmation_number=null WHERE cart_id='{$this->cart_id}'");
	}
	//___________________________________________________
	function update_confnum_byID($s) {
	//___________________________________________________
		//The confirmation id should only be letters (up/low) and numbers
		if (ereg('^[A-Za-z0-9]*$', $s)) {
			$this->wpdb->get_var("UPDATE {$this->order_table} SET confirmation_number='$s' WHERE cart_id='{$this->cart_id}'");
		}
	}
	//___________________________________________________
	function update_ordernote_byID($s) {
	//___________________________________________________
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// Need to check notes.
		$this->wpdb->get_var("UPDATE {$this->order_table} SET notes='$s' WHERE cart_id='{$this->cart_id}'");
	}
	//___________________________________________________
	function update_orderid_byID($s) {
	//___________________________________________________
		if (shopcart_is_int ($s)) {
			$this->wpdb->get_var("UPDATE {$this->order_table} SET order_id='$s' WHERE cart_id='{$this->cart_id}'");
		}
	}
	//___________________________________________________
	function update_contactinfo_byID($newvalue, $field) {
	//___________________________________________________
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// These are all string fields!!!  Need to check for magic quotes
		//only update the contact fields...this should stop hacking attempts
		//to update a field that is not a contact field
		if ((substr($field, 0, 4) == 'ship') || (substr($field, 0, 4) == 'bill') || $field == 'phone' || $field == 'email') {
			$this->wpdb->get_var("UPDATE {$this->order_table} SET $field='$newvalue' WHERE cart_id='{$this->cart_id}'");
		}
	}
	
	/*
	//___________________________________________________
	function update_ccinfo_byID($newvalue, $field) {
	//___________________________________________________
		//only update the contact fields...this should stop hacking attempts
		//to update a file that is not a contact field
		if ((substr($field, 0, 3) == 'cc_')) {
			$this->wpdb->get_var("UPDATE {$this->order_table} SET $field='$newvalue' WHERE cart_id='{$this->cart_id}'");
		}
	}
	*/
	//___________________________________________________
	//___________________________________________________
	function mass_update($vararray, $prefix, $functionname) {
	//___________________________________________________
		foreach($vararray as $varname=>$newvalue) if (substr($varname, 0, strlen($prefix)) == $prefix) $this->$functionname($newvalue, substr($varname, strlen($prefix)));
	}
	//___________________________________________________
	function update_qtys($vararray, $prefix = 'qty_') {
	//___________________________________________________
		$this->mass_update($vararray, $prefix, 'update_qty_byID');
	}
	//___________________________________________________
	function update_actPrice($vararray, $prefix = 'apr_') {
	//___________________________________________________
		$this->mass_update($vararray, $prefix, 'update_price_byID');
	}
	//___________________________________________________
	function update_detailnote($vararray, $prefix = 'dnote_') {
	//___________________________________________________
		$this->mass_update($vararray, $prefix, 'update_detailnote_byID');
	}
	//___________________________________________________
	function update_contactinfo($vararray, $prefix = 'x_') {
	//___________________________________________________
		$this->mass_update($vararray, $prefix, 'update_contactinfo_byID');
	}
	//___________________________________________________
	function update_ccinfo($vararray, $prefix = 'cc_') {
	//___________________________________________________
		//$this->mass_update($vararray, $prefix, 'update_ccinfo_byID');
		do_action('wpc_save_ccinfo',$this,$vararray);
	}
	//___________________________________________________
	function delete_cart() {
	//___________________________________________________
		if (isset($this->cart_id) && strlen($this->cart_id) != 0) {
			$this->wpdb->get_var("DELETE FROM {$this->orderdetail_table} WHERE cart_id={$this->cart_id}");
			$this->wpdb->get_var("DELETE FROM {$this->order_table} WHERE cart_id={$this->cart_id}");
		}
	}
	//___________________________________________________
	function current_status() {
	//___________________________________________________
		$this->status = $this->wpdb->get_var("SELECT status FROM {$this->order_table} WHERE cart_id={$this->cart_id}");
		return $this->status;
	}
	//___________________________________________________
	function change_status($newstatus, $sendmail = true) {
	//___________________________________________________
		switch ($newstatus) {
			case 'quote':
			case 'waitconf':
			case 'order':
			case 'canceled':
			case 'processed':
				if (isset($this->cart_id) && strlen($this->cart_id) != 0) {
					$this->wpdb->get_var("UPDATE {$this->order_table} SET status='$newstatus' WHERE cart_id={$this->cart_id}");
					$this->status = $newstatus;
					if ($sendmail) {
						$this->mail_confirmation();
					}
				}
				break;
			default:
		} //end switch
	}
	//___________________________________________________
	function make_quote() {
		$this->change_status('quote');
	}
	function make_confirmation() {
		$this->change_status('waitconf');
	}
	function make_order() {
	   //Change the order date to today.
		$this->wpdb->get_var("UPDATE {$this->order_table} SET order_date=now() WHERE cart_id={$this->cart_id}");
		$this->change_status('order');
	}
	function cancel_order() {
		$this->change_status('canceled');
	}
	function make_processed() {
		$this->change_status('processed');
		$cc_number = $this->wpdb->get_var("SELECT cc_number FROM {$this->order_table} WHERE cart_id={$this->cart_id}");
		//clear out the CC informaiton
		$cc_number = substr($cc_number, 0, 4) .'XXXX'.substr($cc_number, strlen($cc_number) -4);
		$this->wpdb->get_var("UPDATE {$this->order_table} SET cc_cvv=null,cc_number='$cc_number' WHERE cart_id={$this->cart_id}");
	}
	//___________________________________________________
	//___________________________________________________
	function mail_confirmation() {
	//___________________________________________________
		//Set defaults for this function
		$dtl_format_name ['Qty'] = 'Qty';
		$dtl_format_hdr  ['Qty'] = '%-5s ';
		$dtl_format      ['Qty'] = '[%3s] ';
		$dtl_format_name ['ItemName'] = 'Item Name';
		$dtl_format_hdr  ['ItemName'] = '%-30s ';
		$dtl_format      ['ItemName'] = '%-30s ';
		$dtl_format_name ['GenName'] = 'General Description';
		$dtl_format_hdr  ['GenName'] = '%-30s ';
		$dtl_format      ['GenName'] = '%-30s ';
		$dtl_format_name ['Price_Cust'] = 'Your Price';
		$dtl_format_hdr  ['Price_Cust'] = '%-10s ';
		$dtl_format      ['Price_Cust'] = '$%6.2f ';
		$dtl_format_name ['PriceExt_Cust'] = 'Your Price Ext';
		$dtl_format_hdr  ['PriceExt_Cust'] = '%-11s ';
		$dtl_format      ['PriceExt_Cust'] = '$%7.2f ';
		$dtl_format_name ['Price_Maps'] = 'Map Price';
		$dtl_format_hdr  ['Price_Maps'] = '%-10s ';
		$dtl_format      ['Price_Maps'] = '$%6.2f ';
		$dtl_format_name ['PriceExt_Maps'] = 'MAP Price Ext';
		$dtl_format_hdr  ['PriceExt_Maps'] = '%-11s ';
		$dtl_format      ['PriceExt_Maps'] = '$%7.2f ';
		$dtl_format_name ['Notes'] = 'Notes';
		$dtl_format_hdr  ['Notes'] = '%-40s ';
		$dtl_format      ['Notes'] = '%-40s ';
		
		//Build the string to insert into the email of the cart details - This names the product
		$order_total=0;
		$cart_detail = $this->wpdb->get_results("SELECT * FROM {$this->orderdetail_table} od INNER JOIN {$this->product_table} p on od.post_id=p.ID WHERE od.cart_id='{$this->cart_id}'");


		//pick the body to use the bodies should be put in the db options...later..;)
		//$user_info = $this->wpdb->get_row("SELECT * FROM {$this->user_table} WHERE ID='{$this->user_id}'");
		$user_info = get_userdata ($this->user_id);

		$order_info = $this->wpdb->get_row("SELECT * FROM {$this->order_table} o WHERE o.cart_id='{$this->cart_id}'");

		$custname= $user_info->user_firstname . ' ' . $user_info->user_lastname;
		$conf_url='';

		//--------------------------------------------------------------
		//Select which mail body and Subject to use
		//--------------------------------------------------------------
		//Different email and detail information can be set for each status
		$current_status = $this->current_status();
		$dtl_format_ftr = "%-31s %10s $%12.2f \n";
		switch ($current_status) {
			case 'quote': 
				$status_var='quoted';
				$detail_format_ftr = "";
				break;
			case 'waitconf':
				$rand=$this->rand_string();
				//the get vars here must match on the user side down below
				$conf_url = get_bloginfo('url') ."?confcart={$this->cart_id}&rand=$rand";
                                $conf_url = str_replace('http', 'https',$conf_url);
				$this->update_confnum_byID($rand);
				$status_var='waitconf';
			break;
			case 'order':
				$status_var='ordered';
				break;
			case 'processed':
				$status_var='processed';
				break;
			case 'canceled':
				$status_var='canceled';
				break;
			default: return;
		}
		$headers['Subject'] = get_option("shopcart_mail_{$status_var}_subject");
		$body = get_option("shopcart_mail_{$status_var}");
		$detail_layout = split (',',get_option("shopcart_mail_{$status_var}_detail"));
	
		//Build the order detail information
		$dtl_hdr_list = array();
		$dtl_hdr = $dtl = '';
		foreach ($detail_layout as $field) {
			$dtl_hdr .= $dtl_format_hdr [$field];
			$dtl     .= $dtl_format     [$field];
			array_push ($dtl_hdr_list,$dtl_format_name[$field]);
		}
		$dtl_hdr .= "\n";
		$dtl     .= "\n";
		$dtl_sep = "------------------------------------------------------------------------------\n";

		//Build the order detail into $text_detail
		$text_detail = vsprintf($dtl_hdr,$dtl_hdr_list);
		$text_detail .= $dtl_sep;
		$dtl_list = array();
		foreach ($cart_detail as $row) {
			foreach ($detail_layout as $field) {
				switch ($field) {
				case 'Qty':           array_push ($dtl_list,$row->quantity); break;
				case 'ItemName':      array_push ($dtl_list,$row->post_title); break;
				case 'GenName':       array_push ($dtl_list,$row->post_general_name); break;
				case 'Price_Cust':    array_push ($dtl_list,$row->price); break;
				case 'PriceExt_Cust': array_push ($dtl_list,$row->price*$row->quantity); break;
				case 'Price_Maps':    array_push ($dtl_list,$row->post_map); break;
				case 'PriceExt_Maps': array_push ($dtl_list,$row->post_map*$row->quantity); break;
				case 'Notes':         array_push ($dtl_list,$row->Notes); break;
				default:
				}//switch
			} // foreach $header_layout,
			$text_detail .= vsprintf ($dtl,$dtl_list);
			//clear the array
			foreach ($dtl_list as $i => $value) {
				unset($dtl_list[$i]);
			}
			//while (array_pop($dtl_list));
			$order_total += $row->price*$row->quantity;
		} //foreach row
		$text_detail .= $dtl_sep;
		$text_detail .= sprintf($dtl_format_ftr," ","Total",$order_total);
	
		//--------------------------------------------------------------
		//fill in the variables
		if (trim($order_info->notes) != '') {
			$body = str_replace("{OrderNotes}", 
									"Here are the notes for your order:\n{$order_info->notes}", 
									$body);
		} else {
			$body = str_replace('{OrderNotes}', '', $body);
		}
		$body = str_replace('{OrderDetail}', $text_detail, $body);
		$body = str_replace('{CustName}', $custname, $body);
		$body = str_replace('{BlogUrl}', get_bloginfo('url'), $body);
		$body = str_replace('{ConfUrl}', $conf_url, $body);
		$body = str_replace('{CartID}', $order_info->cart_id, $body);
		$body = str_replace('{QuickbooksID}', $order_info->order_id, $body);
		$body = str_replace('{ID}', $order_info->ID, $body);
	
		//die("got here : $body");
	
		//send the email
		$mail_method = get_option('shopcart_mail_method'); 
		$from = get_option('shopcart_mail_fromaddr'); 
		$plain_from = get_option('shopcart_mail_fromtxt');
		$recipients = "{$user_info->user_email}";
		//die ( "method: $mail_method<br />rec: $recipients<br /> userid: {$this->user_id}<br />cartid: {$this->cart_id}");
	
		switch ($mail_method) {
		case 'pearmail':      
			//require_once('/usr/share/pear/Mail.php');
			//~~~~~~~~~~~~~~~~~~~~
			// In order to use pear mail you need to make sure that PEAR is loaded
			// and that you have access to it.
			// If you are on a plesk server the system admin may have to add a vhost.conf
			// file, like the following:
			// <Directory /home/httpd/vhosts/<hostnamehere>/httpdocs>
			//      php_admin_value open_basedir /home/httpd/vhosts/<hostnamehere>/httpdocs:/tmp:/usr/share/pear
			// </Directory>
			// Then the admin will have to run:
			// /usr/local/psa/admin/sbin/websrvmng --reconfigure-vhost --vhost-name=<domain_name>
			// service httpd reload
			$params['host'] = get_option('shopcart_pearmail_host');
			$params['port'] = get_option('shopcart_pearmail_port');
			$params['auth'] = (get_option('shopcart_pearmail_auth')=='true')?true:false;
			$params['username'] = get_option('shopcart_pearmail_username');
			$params['password'] = get_option('shopcart_pearmail_password');

			$headers['To']      = $recipients;
			$headers['From']    = $plain_from ."<".$from .">";

			require_once('Mail.php');
			$mail_object =& Mail::factory('smtp',$params);
			$mail_object->send($recipients, $headers, $body);
		break;
		case 'phpmail':
			// additional headers  for From, Cc and Bcc
			$x_headers = "Content-type: text/plain; charset=us-ascii\n";
			$x_headers .= "From: $plain_from <".$from.">\n";
			//using the basic mail function in PHP
			mail ($recipients,$headers['Subject'],$body,$x_headers,'-f'.$from);
		break;
		} //end switch mail method
	
	
	} //mail confirmation
	//___________________________________________________
	function rand_string($length = 40) {
		//___________________________________________________
		//Modified from php.net documentation manual notes that someone
		//left on the site
		// RANDOM KEY PARAMETERS
		if (!is_int($length)) return;

		$keychars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		// RANDOM KEY GENERATOR
		$randkey = "";
		$max = strlen($keychars) -1;
		for ($i = 0;$i<$length;$i++) $randkey.= substr($keychars, rand(0, $max), 1);
		return $randkey;
	} //rand_string
	//___________________________________________________
	function check_confirmation($in_confno) {
	//___________________________________________________
	//This can be run with the order is waiting for a confrmation OR
	//if the user has hit buy now as a cart.
		if ($this->current_status() == 'waitconf' || $this->current_status() == 'cart') {
			$db_confno = $this->wpdb->get_var("SELECT confirmation_number FROM {$this->order_table} WHERE cart_id='{$this->cart_id}'");
			if ($db_confno == $in_confno) return true;
		} //if current status
		return false;
	} //check_confirmation
		
	} //class shopping cart
	
	//__________________________________________________________________________________
	//shopping cart utility functions
	//__________________________________________________________________________________
	function display_acting_as() {
		global $acting_as_name;
		if (isset($acting_as_name)) {
			print "<div class=\"cart-actingas\"><h2>Acting AS: $acting_as_name </h2><a href=\"./wp-admin/admin.php?page=shoppingcart/admin.php\">Back to Admin</a></div>";
		} 
	}
	function status_to_action ($status) {
		switch ($status) {   
			case 'cart'    : return 'makequote';
			case 'quote'   : return 'makeconf';
			case 'waitconf': return 'makeorder';
			case 'order'   : return 'makeprocessed';
			default        : return '';
			}
	}
	function status_to_actionName ($status) {
		switch ($status) {
			case 'cart'    : return 'quote';
			case 'quote'   : return 'confirmation';
			case 'waitconf': return 'order';
			case 'order'   : return 'processed';
			default        : return '';
			}
	}
	
function badfield($field) {
//fields that can not be updated by any user/admin
   switch (substr($field,2)) {
   case 'ID':
   case 'user_pass':
   case 'user_status':
   case 'user_domain':
   case 'user_activation_key':
   case 'idmode':
   case 'user_ip':
   case 'user_level':
   case 'user_registered':
   case 'user_browser':
   case 'user_login':
        return true;
   default: return false;
   }
}
function array2object ($obj_name,$val_array) {
  $code = "class $obj_name {\n";
  foreach ($val_array as $name => $value) {
     $code .= "var $$name = '$value';\n";
     }
  $code .= "};\n";
  eval ($code);
  return new $obj_name;
}

//This can be used before you are in the template
function cart_redir ($location) {
	print '<meta http-equiv="refresh" content="0;URL='.get_bloginfo('url'). $location . '">'; 
	exit;
}
//This can be used in a template
function cart_redir_java ($location) {
	print ('<SCRIPT LANGUAGE="JavaScript">');
	print ('window.location=\''. get_settings('siteurl') . $location . '\';');
	print ('</script>');
	exit;
}
function pr($var) {
	print '<pre>'.print_r($var).'</pre>';
}

/*
==============================================================================
==============================================================================
==============================================================================
This routine checks the credit card number. The following checks are made:

1. A number has been provided
2. The number is a right length for the card
3. The number has an appropriate prefix for the card
4. The number has a valid modulus 10 number check digit if required

If the validation fails an error is reported.

The structure of credit card formats was gleaned from
    http://www.blackmarket-press.net/info/plastic/check_digit.htm 
where the details of other cards may also be found.
Downloaded from :http://www.braemoor.co.uk/software/creditcard.php

Input parameters:
            cardnumber           number on the card
            cardname             name of card as defined in the card list below

Author:     John Gardner
Date:       4th January 2005
Updated:    26th February 2005  additional credit cards added

Modified by:     Michael Calabrese
Updated:    2th June 2005  ripped out need to know card type
                           setup to test length and prefix at the same time
                           changed the order of the checks

   
if (isset($_GET['submitted'])) {
  if (checkCreditCard ($_GET['CardNumber'] $ccerrortext)) {
    $ccerrortext = 'This card has a valid format';
  }
}

==============================================================================
*/
  $cards = array ( array ('name' => 'Visa', 
                          'length' => '13,16', 
                          'prefixes' => '4',
                          'checkdigit' => true
                         ),
                   array ('name' => 'MasterCard', 
                          'length' => '16', 
                          'prefixes' => '51,52,53,54,55',
                          'checkdigit' => true
                         ),
                   array ('name' => 'Diners Club', 
                          'length' => '14',
                          'prefixes' => '300,301,302,303,304,305,36,38',
                          'checkdigit' => true
                         ),
                   array ('name' => 'Carte Blanche', 
                          'length' => '14', 
                          'prefixes' => '300,301,302,303,304,305,36,38',
                          'checkdigit' => true
                         ),
                   array ('name' => 'American Express', 
                          'length' => '15', 
                          'prefixes' => '34,37',
                          'checkdigit' => true
                         ),
                   array ('name' => 'Discover', 
                          'length' => '16', 
                          'prefixes' => '6011',
                          'checkdigit' => true
                         ),
                   array ('name' => 'JCB', 
                          'length' => '15,16', 
                          'prefixes' => '3,1800,2131',
                          'checkdigit' => true
                         ),
                   array ('name' => 'Discover', 
                          'length' => '16', 
                          'prefixes' => '6011',
                          'checkdigit' => true
                         ),
                   array ('name' => 'Enroute', 
                          'length' => '15', 
                          'prefixes' => '2014,2149',
                          'checkdigit' => true
                         )
                 );
function creditCardType($cardNo) {
  global $cards;
  foreach ($cards as $accepted) {
    foreach (split(',',$accepted['length']) as $cardlength) {
       if ($cardlength==strlen($cardNo)) {
          foreach (split(',',$accepted['prefixes']) as $cardprefix) {
             if (substr($cardNo,0,strlen($cardprefix))==$cardprefix) {
             	return $accepted['name'];
             	}
             }//foreach prefix
          } //if accepted=cardno
       }//foreach length
    }//foreach cards
  return 'Unknown';
}
function checkCreditCard ($cardnumber, &$errortext) {
	global $cards;
  // Define the cards we support. You may add additional card types.
  //  Name:      As in the selection box of the form - must be same as user's
  //  Length:    List of possible valid lengths of the card number for the card
  //  prefixes:  List of possible prefixes for the card
  //  checkdigit Boolean to say whether there is a check digit NOT USED
  // Don't forget - all but the last array definition needs a comma separator!

  $ccErrorNo = 0;

  $ccErrors [0] = "<h3 class=\"alert\">We're not sure what type that card is. Please try again.</h3>";
  $ccErrors [1] = "<h3 class=\"alert\">I think you missed your card number. Please try again.</h3>";
  $ccErrors [2] = "<h3 class=\"alert\">Your card number is not quite right. Please try again.</h3>";
  $ccErrors [3] = "<h3 class=\"alert\">That card number did not work. Please try again.</h3>";
  //  $ccErrors [4] = "Credit card number is wrong length";
  // Establish card type
  $cardType = -1;

  // Ensure that the user has provided a credit card number
  if (strlen($cardnumber) == 0)  {
     $errortext = $ccErrors [1]; return false; 
     } //if cardnumber

  // Now remove any spaces from the credit card number
  $cardNo = ereg_replace('[^0-9]', '', $cardnumber);

  // Now check the modulus 10 check digit - if required
  $Checksum = 0;                                  // running checksum total
  $NumberLength = strlen($cardNo);
  //Add even digits in even length strings or odd digits in odd length strings.
  for ($Location = 1 - ($NumberLength % 2); $Location < $NumberLength; $Location += 2) {
      $Checksum += substr($cardNo, $Location, 1);
      }

   // Analyze odd digits in even length strings or even digits in odd length strings.
  for ($Location = ($NumberLength % 2); $Location < $NumberLength; $Location += 2) {
      $Digit = substr($cardNo, $Location, 1) * 2;
      if ($Digit < 10) $Checksum += $Digit;
      else $Checksum += $Digit - 9;
      }
  // All done - if checksum is divisible by 10, it is a valid modulus 10.
  if ($Checksum % 10 != 0) {
     $errortext = $ccErrors [3]; return false; 
     } //if checksum

  // The following are the card-specific checks we undertake.
  $PrefixValid = false; 
  foreach ($cards as $accepted) {
    foreach (split(',',$accepted['length']) as $cardlength) {
       if ($cardlength==strlen($cardNo)) {
          foreach (split(',',$accepted['prefixes']) as $cardprefix) {
             if (substr($cardNo,0,strlen($cardprefix))==$cardprefix) $PrefixValid = true;
             }//foreach prefix
          } //if accepted=cardno
       }//foreach length
    }//foreach cards
  if (!$PrefixValid) {
     $errortext = $ccErrors [3]; return false; 
     }//if prefixvalid

  return true;
}

	$cc_cvv_ind = array ('Present','Unreadable','Not Present');
	$cc_months = array('','01 Jan','02 Feb','03 Mar','04 Apr','05 May','06 Jun','07 Jul','08 Aug','09 Sep','10 Oct','11 Nov','12 Dec');


	//__________________________________________________________________________________
	//__________________________________________________________________________________
	//See if we are logged in as admin
    if (strstr($_SERVER['PHP_SELF'], 'wp-admin/')) {
        if ($_SERVER['SERVER_PORT'] == 443 || get_option('shopcart_ssl')!='true') {
            require_once(ABSPATH . 'wp-content/plugins/shoppingcart/admin.php');
        } else {
            $url = $_SERVER['SERVER_NAME'];
            $query = $_SERVER['QUERY_STRING'];
            $path = $_SERVER['PHP_SELF'];
            header("Location: https://$url$path?$query");
            exit;
        }
    } else {
        require_once(ABSPATH . 'wp-content/plugins/shoppingcart/user.php');
    }


$__shoppingcart = 1;
} // if __shoppingcart
