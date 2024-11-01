<?php
/*
WordPress Cart Admin
http://www.wordpresscart.org
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

//Do ADMIN Stuff
function db_update_column($wpdb,$table,$field,$sql) {
	if($wpdb->get_var("SHOW COLUMNS FROM $table LIKE '$field'") != $field) {
		$wpdb->query($sql);
	}
}
function shopcart_update_option ($option,$default,$reset=false) {
	if ((strlen(get_option("shopcart_{$option}"))==0) || $reset) {
		update_option("shopcart_{$option}", $default);
	}
}
list($wp_version_major,$wp_version_minor,$wp_version_rev) = split ('\.',$wp_version);

//__________________________________________________________________________________
//__________________________________________________________________________________
$is_admin = true;

//___________________________________________________
function shopcart_orders() {
//___________________________________________________
	global $wpdb;
	global $user_table;
	global $userext_table,$payment_table;
	global $order_table, $orderdetail_table, $product_table;
	global $user_ID;
	global $cc_cvv_ind,$cc_months;
	$e_class=' class="error" ';


	get_currentuserinfo();
	//Remove any acting as
	$cart=new shoppingCart;
	$wpdb->get_var("UPDATE {$userext_table} SET actingas=NULL WHERE ID=$user_ID");
	if (isset($_GET['cartid']) || isset($_POST['cartid'])) {
		$cartid = isset($_GET['cartid']) ? $_GET['cartid'] : $_POST['cartid'];
		$is_admin = true;
		$cart->init_CartID ($cartid);
		$cart->update_qtys ($_GET);
		$cart->update_actPrice ($_GET);
		$cart->update_ccinfo ($_GET);
		$cart->update_detailnote ($_GET);
		$cart->update_contactinfo ($_GET);
		if (isset($_GET['order_id'])) $cart->update_orderid_byID ($_GET['order_id']);
		if (isset($_GET['cart_notes'])) $cart->update_ordernote_byID ($_GET['cart_notes']);
		
		if (isset($_GET['formaction'])) {
			//ok big ju-ju here.... I am setting up some dynamic execution
			//all of the admin screen have an option to take the order to the next level
			//but they all require differnt functions to be called
			//so I dynamically create the function to be part of the switch and then
			//to EVAL the correct function
			$backtoorderlist=false;
			$context_make=status_to_action ($cart->current_status());
			$context_func='$cart->make_'.status_to_actionName ($cart->current_status()) . ' ();';
			switch ($_GET['formaction']) {
			case 'payment'	 : 
				do_action('wpc_pay_auth',array(&$cart)); 
				$cart->get_orderinfo(true); 
				break;
			case 'delete'   : 
				$cart->delete_item($_GET['deleteitem']); 
				break;
			case 'reset'    : 
				$cart->delete_cart();  
				$backtoorderlist=true;
				break;
			case 'cancel'    : 
				$cart->cancel_order();  
				$backtoorderlist=true;
				break;
			case $context_make: 
				eval($context_func);  
				$backtoorderlist=true;
				break;
			} // switch $_GET
			if ($backtoorderlist) {
				print '<meta http-equiv="refresh" content="0;URL='.get_bloginfo('url').'/wp-admin/admin.php?page=shoppingcart/admin.php">'; 
				exit;
			}
		} //if formaction

		$template_name = "admin/admincart";
		if (file_exists(TEMPLATEPATH."/{$template_name}.php")) require_once (TEMPLATEPATH."/{$template_name}.php");
	} else {
		#do te actions assocated with this page then display this page.
		if (isset($_GET['delete'])) {
			$cart->init_CartID ($_GET['delete']);
			$cart->delete_cart();
		} elseif (isset($_GET['cancel'])) {
			$cart->init_CartID ($_GET['cancel']);
			$cart->cancel_order();
		} elseif (isset($_GET['resend'])) {
			$cart->init_CartID ($_GET['resend']);
			$cart->mail_confirmation();
		}
		$sql_list = array();
		//this was written this way because you can only send one SQL statement
		//at a time.  (So you don't get SQL insertion attacks)
		$sql_list[] = '$sql = "CREATE TEMPORARY TABLE ordersummary ( user_id INT,highlight INT, odate DATE, ototal float(8,2), cart_id INT, qty INT ) TYPE=HEAP ";';
		$sql_list[] = '$sql = "INSERT INTO ordersummary SELECT o.user_id '
				.       ',(o.order_date + INTERVAL $daytohighlight DAY) < CURDATE() as highlight '
				.       ',o.order_date as odate,sum(od.quantity*od.map_price) as ototal '
				.       ',o.cart_id, sum(od.quantity) as qty '
				.'FROM ($order_table o INNER JOIN $orderdetail_table od USING (cart_id) ) '
				.  'WHERE o.status=\'$status\' '
				.  'GROUP BY o.cart_id ";';
		$sql_end[] = '$sql="DROP TABLE ordersummary";';
		$sql_list[] = '$sql = "SELECT os.user_id,os.highlight, os.odate, os.ototal, os.cart_id, os.qty, sum(p.amount) as paidamt '
				.     'FROM (ordersummary os LEFT JOIN $payment_table as p USING (cart_id))  '
				.  'GROUP BY os.cart_id ";';
		//$sql_list[] = '$sql = "SELECT *, 0 as paidamt FROM ordersummary os ";';
		//Note the max(pay.paidamt)  you do not want some here.. remember that you 
		//will get a total paid for EACH order detail line.  So max makes sure
		//that I only get one total
		//Also I do not know what version of MYSQL (or greater) that I have just
		//limited this code to.
		//Do the work to display the page
		$orderviews = array(
							'quotes_waiting'=>array('status'=>'quote','desc'=>'quote','option'=>'quoted'),
							'confirmation_waiting'=>array('status'=>'waitconf','desc'=>'conf','option'=>'waitconf'),
							'orders_waiting'=>array('status'=>'order','desc'=>'order','option'=>'ordered')
							);
		
		foreach ($orderviews as $view => $info) {
			$daytohighlight = get_option('shopcart_'.$info['option'].'_highlight_days');
			$highlightcolor = get_option('shopcart_'.$info['option'].'_highlight_color');
			$status=$info['status'];
			foreach ($sql_list as $query) {
				eval($query);
				$results = $wpdb->get_results($sql);
			}
			if (isset($results)) {
				foreach($results as $row) {
					$userdata = get_userdata($row->user_id);
					$$view .='<tbody><tr';
					$$view .=  $row->highlight ? ' bgcolor="#'.$highlightcolor.'"' : '' ; 
					if (strlen($userdata->user_firstname . $userdata->user_lastname) == 0 ) {
						$user_identification =$userdata->user_login ;
					} else {
						$user_identification =$userdata->user_firstname .' '. $userdata->user_lastname;
					}
					$$view .='><td>' . $user_identification . '</td>'
						.  '<td>' . $row->qty . '</td>'
						.  '<td>' . $row->odate .'</td>'
						.  '<td align="right">$'. $row->ototal . '</td>'
						.  '<td align="right">$'. $row->paidamt . '</td>'
						.  '<td><a href="admin.php?page=shoppingcart/admin.php&cartid='. $row->cart_id .'">view '.$info['desc'] .'</a></td>'
						.  '<td><a href="admin.php?page=shoppingcart/admin.php&resend='. $row->cart_id .'">resend</a></td>';
						if (strlen($row->paidamt)==0) {
							$$view .=  '<td><a href="javascript:shopcart_delete(\''.$user_identification.'\','. $row->cart_id .')">delete</a></td>';
						} else {
							$$view .=  '<td><a href="javascript:shopcart_cancel(\''.$user_identification.'\','. $row->cart_id .')">cancel</a></td>';
						}
						$$view .=  '</tr></tbody>';	
				} //foreach row
			} //is set results
			foreach ($sql_end as $query) {
				eval($query);
				$results = $wpdb->get_results($sql);
			}
		} //foreach order view

		$sql = "SELECT o.user_id"
				.       ",o.order_date as odate,sum(od.quantity*od.price) as ototal"
				.       ",o.cart_id, sum(od.quantity) as qty,max(pay.paidamt) as paidamt "
				.   "FROM (($order_table o INNER JOIN $orderdetail_table od USING (cart_id) )"
				.       "LEFT JOIN (SELECT p.cart_id, sum(p.amount) as paidamt FROM $payment_table as p GROUP BY p.cart_id) as pay USING (cart_id) ) "
				.  "WHERE o.status='processed' "
				.  "GROUP BY o.cart_id";
		$status='processed';
		foreach ($sql_list as $query) {
			eval($query);
			$results = $wpdb->get_results($sql);
		}
		$results = $wpdb->get_results($sql);
		if (isset($results)) {
			foreach($results as $row) {
				$userdata = get_userdata($row->user_id);
				$processed .='<tbody><tr>';
				if (strlen($userdata->user_firstname . $userdata->user_lastname) == 0 ) {
					$user_identification =$userdata->user_login ;
				} else {
					$user_identification =$userdata->user_firstname .' '. $userdata->user_lastname;
				}
				$processed .='<td>' . $user_identification . '</td>'
								.            '<td>'.$row->qty . '</td>'
								.            '<td>' . $row->odate .'</td>'
								.            '<td align="right">$'.$row->ototal . '</td>'
								.            '<td align="right">$'.$row->paidamt . '</td>'
								.            '<td>'.$row->qb_id.'</td>'
								.            '<td><a href="admin.php?page=shoppingcart/admin.php&cartid='. $row->cart_id .'">view order</a></td>'
								. '</tr></tbody>';	
			}
		}
?>
<script language="javascript">
function shopcart_delete (name,cartid) {
  result = confirm ("Do you wish to delete the cart for: "+ name);
  if (result==true) {
    window.location="admin.php?page=shoppingcart/admin.php&delete=" + cartid;
  }
}
function shopcart_cancel (name,cartid) {
  result = confirm ("Do you wish to cancel the order for: "+ name);
  if (result==true) {
    window.location="admin.php?page=shoppingcart/admin.php&cancel=" + cartid;
  }
}
</script>

<div id="order-check" class="wrap">

<div id="order-gate">
<div id="quotes">
<h2>Quotes</h2>
<p>This is list of possible quotes that need to be given correct prices.  Once they have been processed and note will be sent to the customer to click on a link to confirm the order.</p>
<table>
<thead><tr><td>name</td><td># of items</td><td>date</td><td>quote total</td><td>amt paid</td><td>click to open</td></tr></thead>
<tfoot><tr><td colspan="5"></td></tr></tfoot>
<?php echo $quotes_waiting; ?>
</table>
</div>

<div id="waiting">
<h2>Waiting for Confirmation</h2>
<p>This is a list of quotes that have been sent to the customer to be confirmed.  They are listed here so they can be manually confirmed or removed.  The customer should respond via a page link that they in fact want their our at quoted price.</p>
<table>
<thead><tr><td>name</td><td># of items</td><td>date</td><td>conf total</td><td>amt paid</td><td>click to open</td></tr></thead>
<tfoot><tr><td colspan="5"></td></tr></tfoot>
<?php echo $confirmation_waiting; ?>
</table>
</div>

<div id="orders">
<h2>Orders</h2>
<p>These are orders once the customer has confirmed.  These are ready to be fulfilled.</p>
<p></p>
<table>
<thead><tr><td>name</td><td># of items</td><td>date</td><td>order total</td><td>amt paid</td><td>click to open</td></tr></thead>
<tfoot><tr><td colspan="4"></td></tr></tfoot>
<?php echo $orders_waiting; ?>
</table>
</div>

<div id="processed">
<h2>Processed</h2>
<p>These are orders that have been fulfilled and are done.</p>
<table>
<thead><tr><td>name</td><td># of items</td><td>date</td><td>processed total</td><td>amt paid</td><td>qb id</td><td>click to open</td></tr></thead>
<tfoot><tr><td colspan="5"></td></tr></tfoot>
<?php echo $processed; ?>
</table>
</div>
</div>

<div id="history-info">

</div>

</div>
<?php
	} //else isset cartid
}
//___________________________________________________
function shopcart_redir() {
//___________________________________________________
	global $userext_table;
	global $wpdb;
	global $user_level;
	get_currentuserinfo();
	if ($user_level == 0) {
		print ('<SCRIPT LANGUAGE="JavaScript">');
		print ('window.location=\''.get_settings('siteurl') .'\';');
		print ("</script>\n");
	} // end if
	$url = get_settings('siteurl');
   $url = $url . '/wp-content/themes/' . basename(TEMPLATEPATH) . '/admin-css.css';
   echo '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
?>

<script type="text/javascript" src="shoppingcart/js/prototype.lite.js"></script>
<script type="text/javascript" src="shoppingcart/js/moo.fx.js"></script>
<script type="text/javascript" src="shoppingcart/js/moo.fx.pack.js"></script>

<style type="text/css"><!--
/*#cart thead.cart-head {border-bottom:1px solid #333;}

for displaying the order detail 
#cart-block table caption {margin:20px 0 10px 0;}
#cart-block table {width:90%; border:1px solid #999; background:#fff;}
#cart-block table th, #cart-block table td {margin:0; padding:8px 20px; text-align:center; border-bottom:1px solid #999;}
#cart-block table thead {background:#dedede;}
#cart-block table .name {text-align:left;}
#cart-block table th {color:#999;}
#cart-block table tfoot td {text-align:right; color:#FF9B05; font-weight:bold; border-bottom:0px; background:#efefef;}

for displaying the other order information like CC phone/billing
#cart-block h2 {color:#000; margin:20px 0 5px 0;}

#cart-block .td-delete {text-align:center; width:15px; border-right:1px solid #f30;}
#cart-block .td-delete a {color:#000;  font-weight:bold; font-size:140%;}
#cart-block .td-delete:hover {background-color:#f30;} 
#cart-block .error {background:#de3333;}

#cart {}
#cart tr {height:20px; margin:0px;}
#cart td {margin:0px; padding:0px;}
#cart thead {color:#999;}
#cart thead td {padding:5px;}
#cart tbody td {padding:5px;}

#cart-block .left {float:left; width:175px !important; width:160px; margin:10px 5px 10px 10px;}
#cart-block .right {float:left; width:175px !important; width:160px; margin:10px 10px 10px 5px;}


#cart-block {margin:5%;}


#cartform {width:90%;}
#cartform:after {content: "."; display: block; height: 0; clear: both; visibility: hidden;}
#cartform textarea {display:block; width:100%; margin:10px 0; padding:10px; height:60px; border:1px solid #999;}

#quotes {background-color:#99FFFF; padding:20px; margin:10px 0;}
#waiting {background-color:#33FF99; padding:20px; margin:10px 0;}
#orders {background-color:#33FF66; padding:20px; margin:10px 0;}
#processed {background-color:#33FF33; padding:20px; margin:10px 0;}
*/
--></style>
	<?php
} // shopcart_redir
//___________________________________________________
function shopcart_callin() {
//___________________________________________________
	global $user_table;
	global $userext_table;
	global $usermeta_table;
	global $wpdb, $user_ID;
	global $wp_version_major;
	get_currentuserinfo();
	if (isset($_GET['usercartid'])) {
		$wpdb->get_var("UPDATE {$userext_table} SET actingas='{$_GET['usercartid']}' WHERE ID=$user_ID");
		print ('<SCRIPT LANGUAGE="JavaScript">');
		print ('window.location=\''.get_settings('siteurl') .'/?cart=1\'');
		print ('</script>');
		exit;
	}
?>



<div id="user-search" class="wrap"> 
<h2>Search for a Customer:</h2>
<form method="POST">
<table>
<tr> <td>
<small>First Name</small>
</td><td>
<input type="text" name="firstname" id="firstname" value="<?php echo $_POST['firstname']; ?>">
</td><td>
<small>Last Name</small>
</td><td>
<input type="text" name="lastname" id="lastname" value="<?php echo $_POST['lastname']; ?>">
</td></tr>

<tr> <td>
<small>Company</small>
</td><td>
<input type="text" name="company" id="company" value="<?php echo $_POST['company']; ?>">
</td><td>
<small>Login Name</small>
</td><td>
<input type="text" name="loginname" id="loginname" value="<?php echo $_POST['loginname']; ?>">
</td></tr>

<tr> <td>
<small>Email</small>
</td><td>
<input type="text" name="email" id="email" value="<?php echo $_POST['email']; ?>">
</td><td>
</td><td>
</td> </tr>

<tr> <td align="center" colspan="4">
   <input type="submit" name="search" id="search" value="Search for User">
</td> </tr>
</table>
</form>
</div>
<?php
	//see if we should display the search
	if ($_POST['firstname'] != '' || $_POST['lastname'] != '' || $_POST['loginname'] != '' || $_POST['email'] != '') {
		if ($_POST['loginname'] != '') $where.= " AND u.user_login LIKE '{$_POST['loginname']}%' ";
		if ($_POST['email'] != '') $where.= " AND u.user_email LIKE '{$_POST['email']}%' ";
		
		//Geting the username and password is dependent on which version we are using.
		if ($wp_version_major == '2') {
			if ($_POST['firstname'] != '') $where.= " AND mfn.meta_value LIKE '{$_POST['firstname']}%'";
			//ugly but it work...maybe something better later.
			$sql = "SELECT *, u.ID as uID,mfn.meta_value as user_firstname,mln.meta_value as user_lastname FROM (($user_table u LEFT JOIN $usermeta_table mfn ON  (u.ID=mfn.user_id AND mfn.meta_key='first_name')) LEFT JOIN $usermeta_table mln ON  (u.ID=mln.user_id AND mln.meta_key='last_name') )WHERE 1=1 $where";		
		} else {
			if ($_POST['firstname'] != '') $where.= " AND u.user_firstname LIKE '{$_POST['firstname']}%' ";
			if ($_POST['lastname'] != '') $where.= " AND u.user_lastname LIKE '{$_POST['lastname']}%' ";
//			$sql = "SELECT *, u.ID as uID FROM $user_table u LEFT JOIN $userext_table x USING (ID) WHERE 1=1 $where";
			$sql = "SELECT *, u.ID as uID FROM $user_table u WHERE 1=1 $where";
		} //major version
			
		$results = $wpdb->get_results($sql);
		echo ("<br><table border=\"1\" align=\"center\">");
		echo ("<thead><tr><td>ID</td><td>Login</td><td>First</td><td>Last</td><td>Email</td><td>Link to Display<br />or Create a New Cart</td></tr></thead>");
		if (isset($results)) {
			foreach($results as $row) {
				echo ("<tr>");
				echo ("<td>{$row->uID}</td>");
				echo ("<td>{$row->user_login}</td>");
				echo ("<td>{$row->user_firstname}</td>");
				echo ("<td>{$row->user_lastname}</td>");
				echo ("<td>{$row->user_email}</td>");
				echo ("<td><a href=\"admin.php?page=shopcart_callin&usercartid={$row->uID}\">Goto Cart</a></td>");
				echo ("</tr>\n");
			}	
		}
		echo ("<table>\n<br><br>");
		//----Add user---
		echo ("<a href=\"".get_settings('siteurl') ."?cart_profile=1&blank=1\">Add New User</a>\n<br><br>");
	}
}
//___________________________________________________
function shopcart_options() {
//___________________________________________________
    global $wpdb;
    global $product_table;
    
    //This is a silly hack... I am guessing the we need to turn off magic quotes.  Because without this block of code a ' becomes \' in the DB meaning that addslashes is happening twice.
    if (get_magic_quotes_gpc()) {
        // Yes? Strip the added slashes
        $_REQUEST = array_map('stripslashes', $_REQUEST);
        $_GET = array_map('stripslashes', $_GET);
        $_POST = array_map('stripslashes', $_POST);
        $_COOKIE = array_map('stripslashes', $_COOKIE);
    }
    //save any options sent
    $options = array('mail_method', 'mail_fromaddr', 'mail_fromtxt',
                    'pearmail_host', 'pearmail_port', 'pearmail_auth',
                    'pearmail_username', 'pearmail_password',
                    'quoted_highlight_color', 'quoted_highlight_days', 
                    'ordered_highlight_color', 'ordered_highlight_days', 
                    'waitconf_highlight_color', 'waitconf_highlight_days', 
                    'mail_processed', 'mail_processed_subject', 'mail_processed_detail', 
                    'mail_ordered', 'mail_ordered_subject', 'mail_ordered_detail', 
                    'mail_quoted', 'mail_quoted_subject', 'mail_quoted_detail', 
                    'mail_canceled', 'mail_canceled_subject', 'mail_canceled_detail', 
                    'mail_waitconf', 'mail_waitconf_subject', 'mail_waitconf_detail',
                    'download_location_base','download_expire_days');
    foreach ($options as $an_option) {
        if (isset($_POST[$an_option])) {
            update_option('shopcart_'.$an_option, $_POST[$an_option]);
        }
        ${$an_option} = get_option('shopcart_'.$an_option);
    }
        
    //Deal with checkboxes
    $options = array('ssl','demo_data');
    foreach ($options as $an_option) {
            //Make sure we are submitting...otherwise all checkboxes will be set to false
            if  (isset($_POST['submit'])) {
                if (isset($_POST[$an_option])) {
                    update_option('shopcart_'.$an_option, 'true');
                } else {
                    update_option('shopcart_'.$an_option, 'false');
                }
            }
            ${$an_option} = get_option('shopcart_'.$an_option);
    }
   
   //Load unload the demo data
   if ($demo_data=='false' && strlen(get_option('shopcart_demo_dataids')!=0)) {
        print "unloading data<br />\n";
        $posts = get_option('shopcart_demo_dataids');
        $post_array=explode(',',$posts);
        foreach ($post_array as $id) {
            wp_delete_post($id);
        }
        update_option('shopcart_demo_dataids','');
   } elseif (get_option('shopcart_demo_data')=='true') {
        print "loading data<br />\n";
        $demodata = array( 
                          array('post_status'=>'publish',
                                'post_content'=>'This is a test product',
                                'post_title'=>'Test Product 1',
                               ),
                          array('post_status'=>'publish',
                                'post_content'=>'This is a test product',
                                'post_title'=>'Test Product 2',
                                'cart_isprod'=>'Yes',
                                'cart_price'=>'100.00',
                               ),
                          array('post_status'=>'publish',
                                'post_content'=>'This is a test product',
                                'post_title'=>'Test Download 3',
                                'cart_isprod'=>'Yes',
                                'cart_price'=>'300.00',
                                'cart_download_name'=>'index.php',
                               ),
                          );
        
        $posts = array();
        foreach ($demodata as $data) {
            $id = wp_insert_post($data);
            shopcart_post_edit_post($id,$data);
            update_option('shopcart_demo_dataids',implode(',',$posts));
        }
   }
	//display the options
?>
<div class="wrap">
<h2>Shopping Cart Options</h2>
These are the setting for the shoping cart plugin.  Please review all settings before releasing a store.
<form method="post">
<fieldset class="options">
	<legend><b>General</b></legend>
        <table id="comp" cellspacing="2" cellpadding="5" class="editform">
             <tr><th valign="top">Activate SSL:</th>
                 <td><input id="ssl" name="ssl" type="checkbox" <?php checked($ssl, 'true'); ?> />
                      <br />This will protect certain user parts of the site and all of the admin part of the site by redirecting those parts to the SSL port. Turn this on if you use credit cards and setup your site for SSL.<br /> Default: unchecked
                 </td>
             </tr>
             <tr><th>Download Directory Base:</th>
                 <td><input type="text" name="download_location_base" id="download_location_base" value="<?php echo $download_location_base; ?>"></td></tr>
             <tr><th>Downloads Expire (in days):</th>
                 <td><input type="text" name="download_expire_days" id="download_expire_days" value="<?php echo $download_expire_days; ?>"><br />This is the number of days that download will be available for.  A 0 means unlimited.  Default:0 </td></tr>
             <tr><th valign="top">Demo Data:</th>
                 <td>
                 <?php
                 if (get_option('shopcart_demo_data')=='false') {
                     print 'Check to load demo data. '; 
                 } else {
                     print 'Check to remove demo data. '; 
                 }
                 ?>
                 <input id="demo_data" name="demo_data" type="checkbox" <?php checked($demo_data, 'true'); ?> /> 
                 <br />Press the button will load or unload the demo data.  The default setup is unloaded.
             </td></tr>
         </table>

</fieldset>
<fieldset class="options">
	<legend><b>Mail Settings</b></legend>
	<table cellspacing="2" cellpadding="5" class="editform">
	</TD><td valign="top">
	<tr><TD valign="top">
	<fieldset class="options">
		<legend><b>General Mail Settings</b></legend>
		These are the settings for this system to send 
                email out to notify the customer of the steps the order has taken.
		<table cellspacing="2" cellpadding="5" class="editform">
                 <tr><th>Mail Sending Method:</th>
                     <td><SELECT name="mail_method" id="mail_method">
                         <OPTION value="phpmail">Use PHP Mail Function</OPTION>
                         <OPTION value="pearmail">Use PEAR Mail function</OPTION>
                     </SELECT></td>
                 </tr>
                 <tr><th>Mail should appear from (Actual address):</th><TD><INPUT type="text" name="mail_fromaddr" id="mail_fromaddr" value="<?php echo $mail_fromaddr; ?>"></TD></tr>
                 <tr><th>Text Name Mail should appear from:</th><TD><INPUT type="text" name="mail_fromtxt" id="mail_fromtxt" value="<?php echo $mail_fromtxt; ?>"></TD></tr>

            </table>
   </fieldset>
	</TD><td valign="top">
	<fieldset class="options">
		<legend><b>Pear Mail Settings</b></legend>
              These settings are if you use the PEAR Mail function in the 
                     above General Mail Settings.  You should only this this if the PHP Mail Function
                     does not work.  This allows you to use a secure SMTP service to send your 
                     email through.
		<table cellspacing="2" cellpadding="5" class="editform">
                 <tr><th>SMTP Host:</th><TD><INPUT type="text" name="pearmail_host" id="pearmail_host" value="<?php echo $pearmail_host; ?>"></TD></tr>
                 <tr><th>SMTP Port:</th><TD><INPUT type="text" name="pearmail_port" id="pearmail_port" value="<?php echo $pearmail_port; ?>"></TD></tr>
                 <tr><th>SMTP Send Username/Password:</th><TD><INPUT type="text" name="pearmail_auth" id="pearmail_auth" value="<?php echo $pearmail_auth; ?>"></TD></tr>
                 <tr><th>SMTP Username:</th><TD><INPUT type="text" name="pearmail_username" id="pearmail_useranme" value="<?php echo $pearmail_username; ?>"></TD></tr>
                 <tr><th>SMTP Password:</th><TD><INPUT type="text" name="pearmail_password" id="pearmail_password" value="<?php echo $pearmail_password; ?>"></TD></tr>
            </table>
	</fieldset>	
	</td>
	
	</tr>
	<tr><TD colspan="2"><INPUT type="submit" name="submit" value="Update All Options"></TD></tr>
</table>
</fieldset>
<fieldset class="options">
	<legend><b>Mail Message Templates</b></legend>
	<table>
	<tr><TD>These are the templates for the automatic messages that are sent out
                at each stage of the order processing.  Use the following variables in braces "{}" 
                to display in your letter.
                <UL><LI><strong>CustName</strong>: Inserts the customers First and Last name.</LI>
                    <LI><strong>OrderDetail</strong>:  Inserts a text detail of the order with prices. The format of this is controled by the Order detail line for each template. (see below)</LI>
                    <LI><strong>OrderNotes</strong>:  Inserts the order notes if they are not blank.</LI>
                    <LI><strong>BlogUrl</strong>: The base URL of the log.</LI>
                    <LI><strong>ConfUrl</strong>: The URL needed to send someone to the confirmation page.</LI>
                </UL>
        </TD></tr>
	<tr><TD>In the Order Detail area, select what fields you would like to have displayed
                and the order you want them displayed in.  For example:
                Qty,ItemDescr,Price,PriceExt,Notes
                <UL><LI><strong>Qty</strong>: Inserts the customers First and Last name.</LI>
                    <LI><strong>ItemName</strong>:  The name (post title) for this item.</LI>
                    <LI><strong>GenName</strong>:  A General Name for this item.</LI>
                    <LI><strong>Price_Cust</strong>:  The price for the item the customer currently has.</LI>
                    <LI><strong>Price_Maps</strong>:  The advertised manufacture price.</LI>
                    <LI><strong>PriceExt_Cust</strong>:  Simply QTY * QUOTED_PRICE.</LI>
                    <LI><strong>PriceExt_Maps</strong>:  Simply QTY * MAPS_PRICE.</LI>
                    <LI><strong>Notes</strong>: Notes for the current order detail.</LI>
                    <LI><strong>CartID</strong>: The cart id assigned to the order (assigned by the system).</LI>
                    <LI><strong>QuickbooksID</strong>: The orderid that has been entered (assign from quickbooks).</LI>
                    <LI><strong>ID</strong>: This key for the order from the orders table (assigned by the system).</LI>
                </UL>
                <font color="red" size="+1"><b>CHECK to make sure these are entered correctly.  If they are 
                not, the email detail will not show up correctly.</b></font>
        </TD></tr>
	</table>
	<fieldset><legend><b>Recieved Quote Message</b></legend>
	<table border="0" id="quote" cellspacing="2" cellpadding="5" class="editform">
		<tr><th>Subject:</th><td><INPUT type="text" size="70" name="mail_quoted_subject" id="mail_quoted_subject" value="<?php echo $mail_quoted_subject; ?>"></td></tr>
		<tr><Th>Message Body</th><td><textarea rows="10" cols="70" name="mail_quoted"><?php echo $mail_quoted;?></textarea> </TD></tr>
		<tr><th>Order Detail:</th><td><INPUT type="text"  size="70" name="mail_quoted_detail" id="mail_quoted_detail" value="<?php echo $mail_quoted_detail; ?>"></TD></tr>
		<tr><th>Number of calendar days before it is highlighted:</th><td><INPUT type="text"  size="3" name="quoted_highlight_days" id="quoted_highlight_days" value="<?php echo $quoted_highlight_days; ?>"></TD></tr>
		<tr><th>Highlight Color (hex no #):</th><td><INPUT type="text"  size="10" name="quoted_highlight_color" id="quoted_highlight_color" value="<?php echo $quoted_highlight_color; ?>"></TD></tr>
		<tr><TD colspan="2"><INPUT type="submit" name="submit" value="Update All Options"></TD></tr>
	</table>
	</fieldset>
	
	<fieldset><legend><b>Get Confirmation Message</b></legend>
	<table id="conf" cellspacing="2" cellpadding="5" class="editform">
			<tr><th>Subject:</th><td><INPUT type="text" size="70" name="mail_waitconf_subject" id="mail_waitconf_subject" value="<?php echo $mail_waitconf_subject; ?>"></TD></tr>
		<tr><th>Message Body</th><td><textarea rows="10" cols="70" name="mail_waitconf"><?php echo $mail_waitconf;?></textarea> </TD></tr>
			<tr><th>Order Detail:</td><td><INPUT type="text"  size="70" name="mail_waitconf_detail" id="mail_waifconf_detail" value="<?php echo $mail_waitconf_detail; ?>"></TD></tr>
			<tr><th>Number of calendar day to wait before it is highlighted:</th><td><INPUT type="text"  size="3" name="waitconf_highlight_days" id="waitconf_highlight_days" value="<?php echo $waitconf_highlight_days; ?>"></TD></tr>
			<tr><th>Highlight Color (hex no #):</th><td><INPUT type="text"  size="10" name="waitconf_highlight_color" id="waitconf_highlight_color" value="<?php echo $waitconf_highlight_color; ?>"></TD></tr>
		<tr><TD colspan="2"><INPUT type="submit" name="submit" value="Update All Options"></TD></tr>
	</table>
	</fieldset>
	
	<fieldset><legend><b>Processing Order Message</b></legend>
	<table id="process" cellspacing="2" cellpadding="5" class="editform">
		<tr><th>Subject:</th><td><INPUT type="text" size="70" name="mail_ordered_subject" id="mail_ordered_subject" value="<?php echo $mail_ordered_subject; ?>"></TD></tr>
		<tr><th>Message Body</th><td><textarea rows="10" cols="70" name="mail_ordered"><?php echo $mail_ordered;?></textarea> </TD></tr>
		<tr><th>Order Detail:</th><td><INPUT type="text"  size="70" name="mail_ordered_detail" id="mail_ordered_detail" value="<?php echo $mail_ordered_detail; ?>"></TD></tr>
		<tr><th>Number of calendar days to wait before it is highlighted:</th><td><INPUT type="text"  size="3" name="ordered_highlight_days" id="ordered_highlight_days" value="<?php echo $ordered_highlight_days; ?>"></TD></tr>
		<tr><th>Highlight Color (hex no #):</th><td><INPUT type="text"  size="10" name="ordered_highlight_color" id="ordered_highlight_color" value="<?php echo $ordered_highlight_color; ?>"></TD></tr>
		<tr><TD colspan="2"><INPUT type="submit" name="submit" value="Update All Options"></TD></tr>
	</table>
	</fieldset>
	
	<fieldset><legend><b>Canceled Order Message</b></legend>
	<table id="process" cellspacing="2" cellpadding="5" class="editform">
		<tr><th>Subject:</th><td><INPUT type="text" size="70" name="mail_ordered_subject" id="mail_ordered_subject" value="<?php echo $mail_canceled_subject; ?>"></TD></tr>
		<tr><th>Message Body</th><td><textarea rows="10" cols="70" name="mail_ordered"><?php echo $mail_canceled;?></textarea> </TD></tr>
		<tr><th>Order Detail:</th><td><INPUT type="text"  size="70" name="mail_ordered_detail" id="mail_ordered_detail" value="<?php echo $mail_canceled_detail; ?>"></TD></tr>
		<tr><TD colspan="2"><INPUT type="submit" name="submit" value="Update All Options"></TD></tr>
	</table>
	</fieldset>
	
	<fieldset class="options"><legend><b>Completed/Sent Order Message</b></legend>
	<table id="comp" cellspacing="2" cellpadding="5" class="editform">
		<tr><th>Subject:</th><td><INPUT type="text" size="70" name="mail_processed_subject" id="mail_processed_subject" value="<?php echo $mail_processed_subject; ?>"></td></tr>
		<tr><th>Message Body</th><td><textarea rows="10" cols="70" name="mail_processed"><?php echo $mail_processed;?></textarea> </td></tr>
		<tr><th>Order Detail:</th><td><INPUT type="text"  size="70" name="mail_processed_detail" id="mail_processed_detail" value="<?php echo $mail_processed_detail; ?>"></td></tr>
		<tr><td colspan="2"><INPUT type="submit" name="submit" value="Update All Options"></td></tr>
	</table>
	</fieldset>

</fieldset>
</form>
</div>
<?
}
//___________________________________________________
function shopcart_testing() {
//___________________________________________________
	require_once(ABSPATH . 'wp-content/plugins/shoppingcart/carttest.php');
}
//___________________________________________________
function shopcart_admin() {
//___________________________________________________
	add_menu_page('Shopping Cart Management', 'Cart', 8, __FILE__, 'shopcart_orders');
	add_submenu_page(__FILE__, 'Display Quotes', 'Phone In', 8, 'shopcart_callin', 'shopcart_callin');
	add_submenu_page(__FILE__, 'Reports', 'Reports', 8, 'shopcart_reports', 'shopcart_reports');
	add_submenu_page(__FILE__, 'Display Options', 'Options', 8, 'shopcart_options', 'shopcart_options');
	add_submenu_page(__FILE__, 'Payment Options', 'Payment', 8, 'shopcart_payment', 'shopcart_payment');
	add_submenu_page(__FILE__, 'Shipping Options', 'Shipping', 8, 'shopcart_shipping', 'shopcart_shipping');
	add_submenu_page(__FILE__, 'Testing', 'Testing', 8, 'shopcart_testing', 'shopcart_testing');
}
//___________________________________________________
function shopcart_reports() {
//___________________________________________________
	global $wpdb,$payment_table;
	switch ($_GET['report']) {
	case 'creditcard':
		$startdate = isset($_POST['startdate']) ? $_POST['startdate'] :date('Y-m-d 16:30:01',time() - 60*60*24 );;
		//Yesterday
		$enddate=isset($_POST['enddate']) ? $_POST['enddate'] :date('Y-m-d 16:30:00',time());
		if (!isset($_POST['rpttype']) ) $_POST['rpttype']='detail';
		$sql="SELECT * from $payment_table WHERE payment_date BETWEEN '$startdate' AND '$enddate'";
		$payments = $wpdb->get_results($sql);
		if (!isset($payments)) $payments = array();

		$template_name = "cc_report";
		if (file_exists(TEMPLATEPATH."/admin/{$template_name}.php")) require_once (TEMPLATEPATH."/admin/{$template_name}.php");
		break;
	default:
		?>
		<h2>Reports</h2>
		These are reports to get various information about the shopping cart.
		<ul>
		<li><a href="admin.php?page=shopcart_reports&report=creditcard">Payment report</a></li>
		</ul>
		<?php	
	}
}
//___________________________________________________
function shopcart_payment() {
//___________________________________________________
?>
<h2>Payment Options</h2>
These are the setting for the shoping cart payments.  Please review all settings before releasing a store.
<form method="post">
<input type="hidden" name="Submit" value="" />
<?php do_action('wpc_pay_options'); ?>
</form>
<?php
}
//___________________________________________________
function shopcart_shipping() {
//___________________________________________________
echo "This is is a placeholder to setup options for the shipping plugins.";
}
//___________________________________________________
function shopcart_editpost() {
//___________________________________________________
	global $wpdb, $post_ID, $product_table;
	if (isset($post_ID)) {
		$isProd = $wpdb->get_var("SELECT post_is_prod FROM $product_table WHERE id=$post_ID");
		$isExclude = $wpdb->get_var("SELECT post_is_exclude FROM $product_table WHERE id=$post_ID");
		$price = $wpdb->get_var("SELECT post_map FROM $product_table WHERE id=$post_ID");
		$url = $wpdb->get_var("SELECT post_product_url FROM $product_table WHERE id=$post_ID");
		$genname = $wpdb->get_var("SELECT post_general_name FROM $product_table WHERE id=$post_ID");
		$downname = $wpdb->get_var("SELECT post_download_name FROM $product_table WHERE id=$post_ID");
	}
?>
<fieldset id="cartproduct">
   <legend><b>Cart Specific Setings</b></legend>
   <div>
     <table border=1 align="center"> 
     <tr>
        <td valign="top">
            <input id="cart_isprod" name="cart_isprod" type="checkbox" <?php checked($isProd, 'Yes'); ?> />
            <b>Post is a Product</b>
        </td>
         <td valign="top">
            <input id="cart_isexclude" name="cart_isexclude" type="checkbox" <?php checked($isExclude, 'Yes'); ?> />
            <b>Post is Excluded</b>
        </td>
        <td align="center">
            <b>Public Price (MAPS):</b><br />
            $<input id="cart_price" name="cart_price" type="text" size="7" value="<?=$price ?>"/>
        </td>
     </tr><tr>
        <td align="center" colspan="3">
            <b>Product URL:</b><br />
            <input id="cart_url" name="cart_url" type="text" size="45" value="<?=$url ?>"/>
        </td>
        </tr><tr>
        <td align="center" colspan="3">
            <b>Product General Name:</b><br />
            <input id="cart_genname" name="cart_genname" type="text" size="45" value="<?= $genname?>"/>
        </td>
     </tr><tr>
        <td align="center" colspan="3">
             <b>Product Download Name:</b><br />
             <input id="cart_download_name" name="cart_download_name" type="text" size="45" value="<?= $downname?>"/>
        </td>
     </tr>
     </table>
   </div>
</fieldset>
<?php
}
//___________________________________________________
function shopcart_post_edit_post($id,$data=false) {
//___________________________________________________
    global $wpdb, $product_table, $_POST;
    if ($data===false) {
       $data = $_POST;
    }
    $set=array();
    $set[] = 'post_is_prod='      . ((isset($data['cart_isprod']))        ? "'Yes'" : "'No'");
    $set[] = 'post_is_exclude='   . ((isset($data['cart_isexclude']))     ? "'Yes'" : "'No'");
    $set[] = 'post_map='          . ((isset($data['cart_price']))         ? "{$data['cart_price']}" : "0");
    $set[] = 'post_product_url='  . ((isset($data['cart_url']))           ? "'{$data['cart_url']}'" : "''");
    $set[] = 'post_general_name=' . ((isset($data['cart_genname']))       ? "'{$data['cart_genname']}'" : "''");
    $set[] = 'post_download_name='. ((isset($data['cart_download_name'])) ? "'{$data['cart_download_name']}'" : "''");
    $wpdb->get_var("UPDATE $product_table SET ". implode(',',$set) ." WHERE id=$id");
}
//___________________________________________________
function shopcart_install ($reset=false) {
//___________________________________________________
	global $table_prefix, $wpdb, $user_level;
	global $user_table;
	global $userext_table;
	global $order_table, $orderdetail_table, $product_table,$payment_table;

	get_currentuserinfo();
	if ($user_level < 8) { return; }

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	$sql = "CREATE TABLE $order_table (
  id mediumint(100) NOT NULL auto_increment,
  cart_id varchar(255) NOT NULL default '',
  order_id varchar(255) default NULL,
  order_date date NOT NULL default '0000-00-00',
  notes text NOT NULL,
  status enum('cart','quote','order','processed','waitconf','canceled') NOT NULL default 'cart',
  user_id varchar(255) NOT NULL default '',
  confirmation_number varchar(255) default NULL,
  ship_address_1 varchar(255) default NULL,
  ship_address_2 varchar(255) default NULL,
  ship_city varchar(255) default NULL,
  ship_state varchar(255) default NULL,
  ship_country varchar(255) default NULL,
  ship_postal varchar(255) default NULL,
  bill_address_1 varchar(255) default NULL,
  bill_address_2 varchar(255) default NULL,
  bill_city varchar(255) default NULL,
  bill_state varchar(255) default NULL,
  bill_country varchar(255) default NULL,
  bill_postal varchar(255) default NULL,
  phone varchar(255) default NULL,
  email varchar(255) default NULL,
  cc_name varchar(255) default NULL,
  cc_number varchar(30) default NULL,
  cc_expmonth char(2) default NULL,
  cc_expyear varchar(4) default NULL,
  cc_cvv varchar(10) default NULL,
  cc_cvvindicator varchar(50) default NULL,
  cc_message varchar (255) default NULL,
  UNIQUE KEY ID (ID),
  KEY user_id (user_id),
  KEY cart_id (cart_id),
  KEY order_id (order_id)
);";

//	if($wpdb->get_var("show tables like '$order_table'") != $order_table) {
		dbDelta($sql);
//		}

	$sql = "CREATE TABLE $orderdetail_table (
  ID mediumint(100) NOT NULL auto_increment,
  cart_id varchar(255) NOT NULL default '',
  post_id varchar(255) NOT NULL default '',
  quantity int(50) NOT NULL default '0',
  map_price float(8,2) default NULL,
  price float(8,2) default NULL,
  notes text,
  PRIMARY KEY  (ID),
  KEY cart_id (cart_id)
);";
	//if($wpdb->get_var("show tables like '$orderdetail_table'") != $orderdetail_table) {
		dbDelta($sql);
		//}
	
	$sql = "CREATE TABLE $payment_table (
  ID mediumint(100) NOT NULL auto_increment,
  cart_id varchar(255) NOT NULL default '',
  cc_name varchar(255) default NULL,
  cc_number varchar(30) default NULL,
  cc_expmonth char(2) default NULL,
  cc_expyear varchar(4) default NULL,
  cc_cvv varchar(10) default NULL,
  cc_cvvindicator varchar(50) default NULL,
  cc_type varchar(50) default NULL,
  amount float(8,2) default NULL,
  txn_id varchar (255) default NULL,
  approval_code varchar (255) default NULL,
  otherinfo varchar (255) default NULL,
  transaction_type enum('creditcard','check','paypal') NOT NULL default 'creditcard',
  payment_date DATETIME DEFAULT null,
  PRIMARY KEY  (ID),
  KEY cart_id (cart_id)
);";
	if($wpdb->get_var("show tables like '$payment_table'") != $payment_table) {
		dbDelta($sql);
		}
		
	$sql="CREATE TABLE $userext_table (
  ID mediumint(100) NOT NULL auto_increment,
  dj_users_id varchar(50) NOT NULL default '',
  company varchar(255) default NULL,
  ship_address_1 varchar(255) NOT NULL default '',
  ship_address_2 varchar(255) NOT NULL default '',
  ship_city varchar(255) NOT NULL default '',
  ship_state varchar(255) NOT NULL default '',
  ship_country varchar(255) NOT NULL default '',
  ship_postal varchar(255) NOT NULL default '',
  bill_address_1 varchar(255) NOT NULL default '',
  bill_address_2 varchar(255) NOT NULL default '',
  bill_city varchar(255) NOT NULL default '',
  bill_state varchar(255) NOT NULL default '',
  bill_country varchar(255) NOT NULL default '',
  bill_postal varchar(255) NOT NULL default '',
  alt_address_1 varchar(255) NOT NULL default '',
  alt_address_2 varchar(255) NOT NULL default '',
  alt_city varchar(255) NOT NULL default '',
  alt_state varchar(255) NOT NULL default '',
  alt_country varchar(255) NOT NULL default '',
  alt_postal varchar(255) NOT NULL default '',
  phone_home varchar(255) NOT NULL default '',
  phone_cell varchar(255) NOT NULL default '',
  phone_office varchar(255) NOT NULL default '',
  actingas varchar(255) default NULL,
  UNIQUE KEY ID (ID)
);";
		dbDelta($sql);
	
	// ALTERs for the post table ... these need to be moved to the postmeta table
	$field='post_map';
	$sql="ALTER TABLE $product_table ADD COLUMN $field float(8,2) NOT NULL default '0.00'";
	db_update_column($wpdb,$product_table,$field,$sql);

	$post_field='post_category_id';
	$sql="ALTER TABLE $product_table ADD COLUMN post_category_id int(5) unsigned NOT NULL default '0'";
	if($wpdb->get_var("SHOW COLUMNS FROM $product_table LIKE '$post_field'") != $post_field) {
		$wpdb->query($sql);
		}
	$post_field='post_is_exclude';
	$sql="ALTER TABLE $product_table ADD COLUMN post_is_exclude enum('Yes','No') NOT NULL default 'No'";
	if($wpdb->get_var("SHOW COLUMNS FROM $product_table LIKE '$post_field'") != $post_field) {
		$wpdb->query($sql);
        }

	$post_field='post_product_url';
	$sql="ALTER TABLE $product_table ADD COLUMN post_product_url varchar(100) NOT NULL default ''";
	if($wpdb->get_var("SHOW COLUMNS FROM $product_table LIKE '$post_field'") != $post_field) {
		$wpdb->query($sql);
		}
	$post_field='post_is_prod';
	$sql="ALTER TABLE $product_table ADD COLUMN post_is_prod enum('Yes','No') NOT NULL default 'No'";
	if($wpdb->get_var("SHOW COLUMNS FROM $product_table LIKE '$post_field'") != $post_field) {
		$wpdb->query($sql);
		}
	$post_field='post_general_name';
	$sql="ALTER TABLE $product_table ADD COLUMN post_general_name varchar(100) NOT NULL default ''";
	if($wpdb->get_var("SHOW COLUMNS FROM $product_table LIKE '$post_field'") != $post_field) {
		$wpdb->query($sql);
		}
	$field='post_download_name';
	$sql="ALTER TABLE $product_table ADD COLUMN $field varchar(200) NOT NULL default ''";
	db_update_column($wpdb,$product_table,$field,$sql);
				
	shopcart_update_option('mail_processed', "We have processed your order. It will arrive soon.");
	shopcart_update_option('mail_ordered', "Here is the order we are going to process.\n\n {OrderDetail}\n{OrderNotes}\n");
	shopcart_update_option('mail_waitconf', "Here is your quote. \n {OrderDetail} Please confirm your order by going to {ConfUrl}.");
	shopcart_update_option('mail_quoted', "Thank you {CustName} for your inquiry.  We will send a quote shortly.");
	shopcart_update_option('mail_canceled', "The following order was canceled.\n\n {OrderDetail}\n{OrderNotes}\n");
	shopcart_update_option('mail_method', "phpmail");
	shopcart_update_option('mail_processed_subject', "Order has been processed");
	shopcart_update_option('mail_ordered_subject', "Order has been placed");
	shopcart_update_option('mail_waitconf_subject', "Awaiting Confirmation");
	shopcart_update_option('mail_canceled_subject', "Order Canceled");
	shopcart_update_option('mail_quoted_subject', "Your Quote");
	shopcart_update_option('mail_canceled_detail', "Qty,ItemName,Price_Cust,PriceExt_Cust,Notes");
	shopcart_update_option('mail_processed_detail', "Qty,ItemName,Price_Cust,PriceExt_Cust,Notes");
	shopcart_update_option('mail_ordered_detail', "Qty,ItemName,Price_Cust,PriceExt_Cust,Notes");
	shopcart_update_option('mail_waitconf_detail', "Qty,ItemName,Price_Cust,PriceExt_Cust,Notes");
	shopcart_update_option('mail_quoted_detail', "Qty,GenName,Price_Cust,PriceExt_Cust,Notes");
	shopcart_update_option('mail_method', "phpmail");
	shopcart_update_option('mail_fromaddr', "sales@fromsomewhere");
	shopcart_update_option('mail_fromtxt', "ShoppingCart");
	shopcart_update_option('pearmail_host', "smpthost");
	shopcart_update_option('pearmail_port', "25");
	shopcart_update_option('pearmail_auth', "true");
	shopcart_update_option('pearmail_username', "username");
	shopcart_update_option('pearmail_password', "password");
	shopcart_update_option('quoted_highlight_days',3);
	shopcart_update_option('quoted_highlight_color','#FFDDDD');
	shopcart_update_option('ordered_highlight_days',3);
	shopcart_update_option('ordered_highlight_color','#FFDDDD');
	shopcart_update_option('waitconf_highlight_days',3);
	shopcart_update_option('waitconf_highlight_color','#FFDDDD');
	shopcart_update_option('ssl','false');
	shopcart_update_option('download_location_base','wp-content/uploads/');
	shopcart_update_option('download_expire_days',0);
	shopcart_update_option('demo_data','false');

}

if (isset($_GET['activate'])) {
	add_action('init', 'shopcart_install');
}

add_action('admin_menu', 'shopcart_admin');
add_action('admin_head', 'shopcart_redir');

//These actions are for adding fields to be changed as part of the
// add/edit/publish post area.
add_action('edit_form_advanced', 'shopcart_editpost');
add_action('simple_edit_form', 'shopcart_editpost');
add_filter('edit_post', 'shopcart_post_edit_post');
add_filter('publish_post', 'shopcart_post_edit_post');
add_filter('save_post', 'shopcart_post_edit_post');

?>