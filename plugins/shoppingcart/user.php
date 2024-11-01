<?php
/*
WordPress Cart User
http://www.wordpresscart.org
Dave Merwin and Michael Calabrese
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

//Do USER Level stuff
//------------------------------------
function shopcart_choosetemplate() {
//------------------------------------
	global $posts;
	//not sure if I need these...they might be used in the templates
	//I will have to check
	global $user_table;
	global $userext_table;
	global $order_table;
	global $orderdetail_table;
	global $product_table;
	global $category_table;
	
	global $user_level;
	global $user_ID;
	global $wpdb;
	global $cart;
	global $wp_version;
	global $cc_cvv_ind,$cc_months;
	//global $shopcart_admin_level;
	$is_admin = false;
	$cart=new shoppingCart;
	list($wp_version_major,$wp_version_minor,$wp_version_rev) = split ('\.',$wp_version);
	
	//-----------------------
	//Goto the profile screen
	//I have included it here because the information is
	//really needed as part of the shopping cart
	if (isset($_GET['cart_profile']) || isset($_POST['cart_profile'])) $template_name = 'cart_userprofile';
	
	//------------------------
	//ok the actual shopping cart redirection
	if (isset($_GET['cart'])) $template_name = 'cart';
	if (isset($_GET['updatecart']) || isset($_POST['updatecart'])) $template_name = 'cart';
	
	//------------------------
	//if you are going to add something to the cart
	if (isset($_GET['addcart']) && count($posts) == 1 && $posts[0]->post_is_prod == 'Yes') {
		$template_name = 'cart';
	} //isset addcart
	if (isset($_GET['downloads'])) {
		$template_name = 'downloads';
	} //isset addcart
	
	//------------------------
	//This is for confirming an order
	if (isset($_GET['confcart']) || isset($_POST['confcart'])) {
		//These two var cartconf and rand must be accessable via POST and gET
		//POST when using the buy now version and GET when 
		//being accessed by a email URL
                if ($_SERVER['SERVER_PORT'] == 443 || get_option('shopcart_ssl')!='true') {
                   require_once(ABSPATH . 'wp-content/plugins/shoppingcart/admin.php');
                } else {
                   $url = $_SERVER['SERVER_NAME'];
                   $query = $_SERVER['QUERY_STRING'];
                   $path = $_SERVER['PHP_SELF'];
                   header("Location: https://$url$path?$query");
                   exit;
                }

		$cartid = isset($_GET['confcart']) ? $_GET['confcart'] : $_POST['confcart'];
		$rand = isset($_GET['rand']) ? $_GET['rand'] : $_POST['rand'];
		$cart->init_CartID($cartid);
		if ($cart->check_confirmation($rand)) $template_name = 'cart_conf';
		else $template_name = 'cart_conf_error';
	} //isset confcart
	
	//------------------------
	//Setup the stuff for acting as, so that it can be displayed on all pages.
	//  This acting as stuff must be the last thing before the template is displayed
	//  or you could have user permission problems.  This is used in conguction with
	//  display_acting_as()
	if (strlen($user_level) && $user_level>=9) {
		//Someone will also have to logged into the system as a admin to have a user_level >=9  THAT is not passed by the GET var. The actingas ID is passed through the DB the user should not have any access to that.
		$usertest = $wpdb->get_var("SELECT actingas FROM  {$userext_table} WHERE ID=$user_ID");
		if (isset($usertest)) {
			global $acting_as_name;
			$admin_user_ID=$user_ID;
			$user_ID=$usertest;
			$userdata = get_userdata($user_ID);
			$acting_as_name = $userdata->user_firstname . ' ' . $userdata->user_lastname;
		}
	}
	if ($cart->cart_id==null && strlen($user_ID)) {
		$cart->init_User ($user_ID); 
	}
	//setup stuff for our templates.
	if ($template_name=='cart' || $template_name=='cart_userprofile' || $template_name=='cart_conf') {
		if (!isset($user_ID) && $cart->cart_id==null) {
			$template_name = 'login_error';
		}
		if (isset($_GET['formaction'])) { 
			$formaction = $_GET['formaction']; 
			$VARS = $_GET;
			}
		if (isset($_POST['formaction'])) { 
			$formaction = $_POST['formaction']; 
			$VARS = $_POST;
			}
		switch ($template_name) {
		case 'cart_conf':
		case 'cart':
			if (isset($formaction)) {
				$cart->update_qtys ($VARS);
				$cart->update_contactinfo ($VARS);
				$cart->update_ccinfo ($VARS);
				if (isset($VARS['cart_notes'])) $cart->update_ordernote_byID ($VARS['cart_notes']);
				switch ($formaction) {
				case 'delete'   : $cart->delete_item($VARS['deleteitem']); break;
				case 'reset'    : $cart->delete_cart(); break;
				case 'buynow'   :
           if ($_SERVER['SERVER_PORT'] == 443 || get_option('shopcart_ssl')!='true') {
                require_once(ABSPATH . 'wp-content/plugins/shoppingcart/admin.php');
           } else {
                $url = $_SERVER['SERVER_NAME'];
                $query = $_SERVER['QUERY_STRING'];
                $path = $_SERVER['PHP_SELF'];
                header("Location: https://$url$path?$query");
                exit;
           }

					$template_name = 'cart_conf';
					$rand=$cart->rand_string();
					//the get vars here must match on the user side down below
					//$conf_url=get_bloginfo('url') ."?confcart={$this->cart_id}&rand=$rand";
					$ispurchasednow=1;
					$cart->update_confnum_byID($rand);
					//$cart->make_wait (); 
					break;
				case 'makequote': 
					$cart->make_quote (); 
					//reset any acting as
					if (isset($acting_as_name)) {
						$wpdb->get_var("UPDATE {$userext_table} SET actingas=NULL WHERE ID=$admin_user_ID");
						print '<meta http-equiv="refresh" content="0;URL='.get_bloginfo('url').'/wp-admin/admin.php?page=shoppingcart/admin.php">'; 
						exit;
					}
					include (TEMPLATEPATH . "/thank-you.php");
					exit;
					break;
				case 'buy'      : 
                if ($_SERVER['SERVER_PORT'] == 443 || get_option('shopcart_ssl')!='true') {
//                        require_once(ABSPATH . 'wp-content/plugins/shoppingcart/admin.php');
                } else {
                        $url = $_SERVER['SERVER_NAME'];
                        $query = $_SERVER['QUERY_STRING'];
                        $path = $_SERVER['PHP_SELF'];
                        header("Location: https://$url$path?$query");
                        exit;
                }

					$error=false;
					if (isset($_POST['buyingnow'])) { $ispurchasednow=1; }
					if (!$error) {
						//change the card
						do_action('wpc_pay_auth',array(&$cart)); 
						$orderres=$cart->get_orderinfo(true);
						//if no error message make it an order
						if (strlen($orderres->cc_message) == 0 ) {
							$cart->make_order ();  
							$cart->remove_confnum_byID();
							$template_name='thank-you';
						} else {
						$errormessage.="<h3 class=\"alert\">".$orderres->cc_message."</h3>";
						}
						} // if $error
				break;
				default:
					if (isset($acting_as_name)) {
						$changed=false;
						switch ($formaction) {
						case 'makequote':     $cart->make_quote ();        $changed=true; break;
						case 'makeconf':      $cart->make_confirmation (); $changed=true; break;
						case 'makeorder':     $cart->make_order ();        $changed=true; break;
						case 'makeprocessed': $cart->make_processed ();    $changed=true; break;
						} // switch 
						if ($changed) {
							$wpdb->get_var("UPDATE {$userext_table} SET actingas=NULL WHERE ID=$admin_user_ID");
							print '<meta http-equiv="refresh" content="0;URL='.get_bloginfo('url').'/wp-admin/admin.php?page=shoppingcart/admin.php">'; 
							exit;
						} // changed
					} //if actingas
				} // switch $_GET
			} elseif (isset($_GET['p'])) {
				$cart->add_item ($_GET['p']);
			} //if-elseif
			break;
		case 'cart_userprofile':
			if (isset($formaction)) {
				switch ($formaction) {
				case 'add-cart':
				case 'add':
					//I don't think that this add part is being seen by the users at
					//this point in time.  It is only being used by the admin.  NOTCE:
					//that this procedur allow you NOT to enter a email address.  This
					// is for call in orders.
					$didadd=false;
					//These are the list of field that are required when an admin creates a user
					//make sure and add the css contional where the html input box is
					$user_required = array ('user_firstname','user_lastname','user_login');
                                        $userext_required = array ();
					//$userext_required = array ('ship_address_1','ship_city','ship_state','ship_postal');
					//check to make sure all required fields are filled in.
					$error_message='Missing the following required fields: ';
					$haserror=false;
					foreach ($user_required as $req_field) {
						$postedname = "u_$req_field";
						if (strlen(trim($_POST[$postedname]))==0) {
							$error_message .= $req_field . ','; $haserror=true;
							eval ('$e_'.$req_field.' = true;'); //mark for css
							}
						}
					foreach ($userext_required as $req_field) {
						$postedname = "x_$req_field";
						if (strlen(trim($_POST[$postedname]))==0) {
							$error_message .= $req_field . ',';$haserror=true;
							eval ('$e_'.$req_field.' = true;'); //mark for css
							}
						}
					//test if logon is already used
					$sql = "SELECT count(user_login) FROM $user_table u WHERE u.user_login='{$_POST['u_user_login']}'";
					$count = $wpdb->get_var($sql);
					if ($count !=0) {
						$e_user_login=true;
						$haserror=true;
						$error_message .= '
			<h1>Oops.</h1>
			<h3>Someone already has that name.</h3>'; 
					}
					if ($haserror) break;
					//if every thing is OK then add the user the just continue through the update.
					$user_nicename = sanitize_title($_POST['u_user_login']);
					$user_nickname = $_POST['u_user_login'];
					$now = gmdate('Y-m-d H:i:s');
					$password = substr( md5( uniqid( microtime() ) ), 0, 7);
					
					if ($wp_version_major == '2') {
						require_once(ABSPATH . 'wp-includes/registration-functions.php');
						//$wp_userdata = get_userdata($up_ID);
						//setup the vars that the update expects to see
						$up_user_wp2['ID'] = '';
						$up_user_wp2['first_name'] = $_POST['u_user_firstname'];
						$up_user_wp2['last_name'] = $_POST['u_user_lastname'];
						$up_user_wp2['user_login'] = $_POST['u_user_login'];
						//Need the password so we dont over write with nothing
						$up_user_wp2['user_pass'] = $password;
						$_POST['u_ID'] = wp_insert_user($up_user_wp2);
					} else {
						$sql = "INSERT INTO  $user_table (user_login,user_level,user_idmode,user_status,user_nickname,user_nicename,user_registered,user_pass) VALUES ('{$_POST['u_user_login']}',0,'nickname',0,'$user_nickname','$user_nicename','$now', MD5('$password') )";
						$results = $wpdb->query($sql);
						if ($results == false) {
							die (sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register... please contact the <a href="mailto:%s">webmaster</a> !'), get_settings('admin_email')));
						}
						$_POST['u_ID']=$wpdb->insert_id;
						do_action('user_register', $wpdb->insert_id);
					}					
					if (strlen(trim($_POST['u_user_email']))!=0) {
						$message  = sprintf(__('Username: %s'), $_POST['u_user_login']) . "\r\n";
						$message .= sprintf(__('Password: %s'), $password) . "\r\n";
						$message .= get_settings('siteurl') . "/wp-login.php\r\n";
						
						wp_mail($_POST['u_user_email'], sprintf(__('[%s] Your username and password'), get_settings('blogname')), $message);
					}
					//now we just fall through to update
					$didadd=true;
				case 'update':
				case 'password':
					//ok what am I doing here? Well, I have setup the input name the same as the 
					//field names with either u_ (for normal wp_users) and x_ (for the extd fields)
					//I step through the POST var and build the UPDATE table SET var1=value1,
					// var2=value2, etc...  or the column list and values for an INSERT table
					$up_user_wp2 = array();
					foreach ($_POST as $variable => $value) {
						if ($variable == 'u_ID') {
							$up_ID=$value; //setup this way so an admin can use this screen
						} elseif ( badfield($variable) ) {
							//do nothing...remove all fieldnames we don't want to pass to avoid hack attemps
						} elseif (substr($variable,0,2)=='u_') {
							$up_user .= substr($variable,2) . " = '".  wp_specialchars($value) ."', " ;
							$up_user_wp2[substr($variable,2)] = wp_specialchars($value);
						} elseif (substr($variable,0,2)=='x_') {
							$up_ext  .= substr($variable,2) . " = '".  wp_specialchars($value) ."', ";
							$up_cols  .= substr($variable,2) . ", ";
							$up_vars  .= "'".  wp_specialchars($value) ."', ";
						}
					} // foreach $_POST
					//eat the last comma and space
					$up_ext  = substr($up_ext,0,strlen($up_ext)-2);
					$up_user = substr($up_user,0,strlen($up_user)-2);
					$up_cols = substr($up_cols,0,strlen($up_cols)-2);
					$up_vars = substr($up_vars,0,strlen($up_vars)-2);
			
					if ($wp_version_major == '2') {
						require_once(ABSPATH . 'wp-includes/registration-functions.php');
						$wp_userdata = get_userdata($up_ID);
						//setup the vars that the update expects to see
						$up_user_wp2['ID'] = $up_ID;
						$up_user_wp2['first_name']  = $_POST['u_user_firstname'];
						$up_user_wp2['last_name']   = $_POST['u_user_lastname'];
						$up_user_wp2['user_email']  = $_POST['u_user_email'];
						$up_user_wp2['user_url']    = $_POST['u_user_url'];
						$up_user_wp2['nickname']    = $_POST['u_user_nickname'];
						$up_user_wp2['description'] = $_POST['u_user_description'];
						$up_user_wp2['jabber']      = $_POST['u_user_jabber'];
						$up_user_wp2['aim']         = $_POST['u_user_aim'];
						$up_user_wp2['yim']         = $_POST['u_user_yim'];
						update_usermeta( $up_ID, 'msn', $_POST['u_user_msn'] );
						update_usermeta( $up_ID, 'icq', $_POST['u_user_icq'] );
						//Need the password so we dont over write with nothing
						unset($up_user_wp2['user_pass']);
						wp_update_user($up_user_wp2);
					} else {
						$wpdb->get_var("UPDATE $user_table SET $up_user WHERE ID=$up_ID");
					}
					//Do we need to insert to update the extended info
					$in_ext =  $wpdb->get_var("SELECT ID FROM  $userext_table WHERE ID=$up_ID");
					if (isset($in_ext))
						$wpdb->get_var("UPDATE $userext_table SET $up_ext WHERE ID=$up_ID");
					else
						$wpdb->get_var("INSERT INTO $userext_table (ID, $up_cols ) VALUES ($up_ID, $up_vars ) ");
			
					//ok if we did an add and we want to redir to a cart go here....
					if ($formaction=='add-cart') {
						cart_redir ('/wp-admin/admin.php?page=shopcart_callin&usercartid='. $up_ID);
					}
					break;
			
				case 'setpassword':
				$pass1 = $_POST["user_pass"];
				$pass2 = $_POST["user_pass2"];
				do_action('check_passwords', array($user_login, &$pass1, &$pass2));
			
					//check the password given
					if ($_POST['user_pass'] != $_POST['user_pass2']) {
						echo '
			<h1>Oops.</h1>
			<h3>Those passwords do not match.</h3>
			<h3 class="alert">Please try again.</h3>'; 
						$formaction='password'; //go back the the password setting page
					} elseif (strlen(trim($_POST['user_pass']))<6) {
						echo '
			<h1>Oops.</h1>
			<h3>Please choose a password that has at LEAST 6 characters.</h3>
			<h3 class="alert">Please try again.</h3>'; 
						$formaction='password'; //go back the the password setting page
					} elseif (strlen(trim($_POST['user_pass']))>63) {
						echo '
			<h1>Oops.</h1>
			<h3>That is a really big password. Please limit it to 63 characters.</h3>
			<h3 class="alert">Please try again.</h3>'; 
						$formaction='password'; //go back the the password setting pages
					} else {
						//ok we can save the new passwords
                                        if ($wp_version_major == '2') {
                                                require_once(ABSPATH . 'wp-includes/registration-functions.php');
                                                $wp_userdata = get_userdata($up_ID);
                                                //setup the vars that the update expects to see
                                                $up_user_wp2['ID'] = $user_ID;
                                                //Need the password so we dont over write with nothing
                                                $up_user_wp2['user_pass']=$_POST['user_pass'];
                                                wp_update_user($up_user_wp2);
                                        } else {
						$wpdb->get_var("UPDATE  $user_table SET user_pass=MD5('{$_POST['user_pass']}')  WHERE ID=$user_ID ");
                                        }

					} //if elseif 
					break;
				} //end switch
			
			} //if isset formaction
			
			if ( ($user_level>=9) && ($_GET['blank']=='1')) {
				//Load the vars so the admin does not have to reenter
				// when adding a new user.
				$load_user = array(); $load_ext = array();
				foreach ($_POST as $varname => $value) {
					if (substr($varname,0,2) == 'u_' && strlen($value) != 0 )
						$load_user[substr($varname,2)]=$value;
					if (substr($varname,0,2) == 'x_' && strlen($value) != 0 )
						$load_ext[substr($varname,2)]=$value;
				}
				$wp_userdata = array2object('newuserobj',$load_user);
				$wp_userext  = array2object('newextobj',$load_ext);
			} else {
				//get the info from the database
				$wp_userdata = get_userdata($user_ID);
				$wp_userext = $wpdb->get_row("SELECT * from $userext_table WHERE ID=$user_ID");
				//$wp_userext = $results[0];
			}
			break;
		} //if user_id
	
	} //if we need to setup stuff for our templates.
	//------------------------
	//ok display it -- this skips any wordpress filtering
	if (isset($template_name)) {
		if (file_exists(TEMPLATEPATH."/{$template_name}.php")) require_once (TEMPLATEPATH."/{$template_name}.php");
		exit;
	} //isset template name
	//Otherwise let WP do it's work...this will just go back to the template code
} //shopcart_choosetempalte
function shopcart_register($id){
	global $wpdb,$userext_table;
	//add a row into the ext table when someone registers
	$wpdb->get_var("INSERT INTO $userext_table (ID) VALUES ($id ) ");

}
add_action('template_redirect', 'shopcart_choosetemplate');
add_action('user_register', 'shopcart_register');
?>