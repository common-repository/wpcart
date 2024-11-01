<?php 
global $post,$user_login, $userdata, $user_level, $user_ID, $user_nickname, $user_email, $user_url, $user_pass_md5, $user_identity,$wpdb;
get_currentuserinfo();
get_header();


?>
<div id="content" class="narrowcolumn"><!-- START CONTENT -->


<div id="profile-container"><!-- Profile Container -->

<?php

//if we are not showing the password screen...so the profile information
if ($_POST['formaction']!='password') :

//THIS IS THE ACTING AS INDICATOR
if ($_GET['blank']=='1' || $_POST['formaction']=='add') {
  print "<div class=\"cart-actingas\"><h2>Adding New User</h2><a href=\"./wp-admin/admin.php?page=shoppingcart.php\">Back to Admin (will not save)</a>
</div>";
} else {
  print '<h3 class="storytitle">Your Profile '.$wp_userdata->user_firstname.'</h3>';
}

function ferror($id) {
$e_id = "e_$id";
echo $$e_id ? ' class="error" ' : '' ;
}

if ($haserror) {
  print $error_message;
}
?>
<form method="POST" name="userform" id="userform" >
<!-- normal wp user fields -->

<?php if ((isset($_GET['blank']) || ($_POST['formaction']=='add')) && $user_level>=9  && $didadd==false) : ?>
	<small>login</small><input type="text" name="u_user_login" id="u_user_login" value="<?php echo $wp_userdata->user_login; ?>" <?php echo $e_user_login ? $e_class :'';?>>
<?php else : ?>
	<h2>Login: <span class="alert"><?php echo $wp_userdata->user_login; ?></span></h2>
<?php endif; ?>


<!--<h2>profile info</h2>-->
<h2>The Basics</h2>
<div class="accent-block">
<div class="left">
<small>first name</small><input type="text" name="u_user_firstname" id="u_user_firstname" value="<?php echo $wp_userdata->user_firstname; ?>" <?php echo $e_user_firstname ? $e_class :''; ?>>
<small>last name</small><input type="text" name="u_user_lastname" id="u_user_lastname" value="<?php echo $wp_userdata->user_lastname; ?>" <?php echo $e_user_lastname ? $e_class :''; ?>>
<!--<small>nickname</small><input type="text" name="u_user_nickname" id="u_user_nickname" value="<?php echo $wp_userdata->user_nickname; ?>">-->
<small>company</small><input type="text" name="x_company" id="x_company" value="<?php echo $wp_userext->company; ?>" >
</div>

<div class="right">
<small>home phone</small><input type="text" name="x_phone_home" id="x_phone_home" value="<?php echo $wp_userext->phone_home; ?>">
<small>cell phone</small><input type="text" name="x_phone_cell" id="x_phone_cell" value="<?php echo $wp_userext->phone_cell; ?>">
<small>office phone</small><input type="text" name="x_phone_office" id="x_phone_office" value="<?php echo $wp_userext->phone_office; ?>">
</div>
<div class="clearfix"></div>
</div>

<div class="accent"><h4>Email</h4><input type="text" name="u_user_email" id="u_user_email" value="<?php echo $wp_userdata->user_email; ?>" <?php echo $e_user_email ? $e_class :''; ?>></div>

<h2>Primary Addresses</h2>
<div class="accent-block">
<div class="left">
<h4>shipping</h4>
<small>address</small><input type="text" name="x_ship_address_1" id="x_ship_address_1" value="<?php echo $wp_userext->ship_address_1; ?>" <?php echo $e_ship_address_1 ? $e_class :''; ?>>
<input type="text" name="x_ship_address_2" id="x_ship_address_2" value="<?php echo $wp_userext->ship_address_2; ?>" >
<small>city</small><input type="text" name="x_ship_city" id="x_ship_city" value="<?php echo $wp_userext->ship_city; ?>" <?php echo $e_ship_city ? $e_class :''; ?>>
<small>state</small><input type="text" name="x_ship_state" id="x_ship_state" value="<?php echo $wp_userext->ship_state; ?>" <?php echo $e_ship_state ? $e_class :''; ?>>
<small>country</small><input type="text" name="x_ship_country" id="x_ship_country" value="<?php echo $wp_userext->ship_country; ?>">
<small>postal</small><input type="text" name="x_ship_postal" id="x_ship_postal" value="<?php echo $wp_userext->ship_postal; ?>" <?php echo $e_ship_postal ? $e_class:''; ?>>
</div>

<div class="right">
<h4>billing address</h4>
<small>address</small><input type="text" name="x_bill_address_1" id="x_bill_address_1" value="<?php echo $wp_userext->bill_address_1; ?>">
<input type="text" name="x_bill_address_2" id="x_bill_address_2" value="<?php echo $wp_userext->bill_address_2; ?>">
<small>city</small><input type="text" name="x_bill_city" id="x_bill_city" value="<?php echo $wp_userext->bill_city; ?>">
<small>state</small><input type="text" name="x_bill_state" id="x_bill_state" value="<?php echo $wp_userext->bill_state; ?>">
<small>country</small><input type="text" name="x_bill_country" id="x_bill_country" value="<?php echo $wp_userext->bill_country; ?>">
<small>postal</small><input type="text" name="x_bill_postal" id="x_bill_postal" value="<?php echo $wp_userext->bill_postal; ?>">
</div>
<div class="clearfix"></div>
</div>

<h2>Optional Stuff</h2>

<small>user description</small>
<textarea name="u_user_description" id="u_user_description" ><?php echo $wp_userdata->user_description; ?>
</textarea>

<div class="left">
<h4>web info</h4>
<small>icq</small><input type="text" name="u_user_icq" id="u_user_icq" value="<?php echo $wp_userdata->user_icq; ?>">
<small>url</small><input type="text" name="u_user_url" id="u_user_url" value="<?php echo $wp_userdata->user_url; ?>">
<small>aim</small><input type="text" name="u_user_aim" id="u_user_aim" value="<?php echo $wp_userdata->user_aim; ?>">
<small>msn</small><input type="text" name="u_user_msn" id="u_user_msn" value="<?php echo $wp_userdata->user_msn; ?>">
<small>yim</small><input type="text" name="u_user_yim" id="u_user_yim" value="<?php echo $wp_userdata->user_yim; ?>">
</div>

<div class="right">
<h4>alt. address</h4>
<small>address</small><input type="text" name="x_alt_address_1" id="x_alt_address_1" value="<?php //echo $wp_userext->alt_address_1; ?>">
<input type="text" name="x_alt_address_2" id="x_alt_address_2" value="<?php //echo $wp_userext->alt_address_2; ?>">
<small>city</small><input type="text" name="x_alt_city" id="x_alt_city" value="<?php //echo $wp_userext->alt_city; ?>">
<small>state</small><input type="text" name="x_alt_state" id="x_alt_state" value="<?php //echo $wp_userext->alt_state; ?>">
<small>country</small><input type="text" name="x_alt_country" id="x_alt_country" value="<?php //echo $wp_userext->alt_country; ?>">
<small>postal</small><input type="text" name="x_alt_postal" id="x_alt_postal" value="<?php //echo $wp_userext->alt_postal; ?>">
</div>

<div class="clearfix"></div>


<ul class="cart-buttons">
<?php if ((isset($_GET['blank']) || ($_POST['formaction']=='add')) && $user_level>=9 && !$didadd ) : ?>
  <li id="update"><a href="javascript:void(setaction('add'))">add new user</a></li>
  <li id="update"><a href="javascript:void(setaction('add-cart'))">save new user/start new cart</a></li>
<?php else : ?>
  <?php if ($didadd) :?> 
     <li id="update"><a href="<?php echo get_settings('siteurl') . '/wp-admin/admin.php?page=shoppingcart.php';?>">back to admin</a></li>
     <li id="update"><a href="<?php echo get_settings('siteurl') . '/wp-admin/admin.php?page=shopcart_callin&usercartid='. $wp_userdata->ID; ?>">act as (start a new cart for this user)</a></li>
  <?php else : ?>
     <li id="update"><a href="javascript:void(setaction('update'))">update</a></li>
  <?php endif; ?>
<?php endif; ?>

<li id="password"><a href="javascript:void(setaction('password'))">change password</a></li>
<?php if (isset($_POST['fromcart']) || isset($_GET['fromcart']) ) : ?>
<li id="continue-shopping"><a href="<?php echo get_settings('siteurl') . '/?cart=1'?>">back to cart</a></li>
<?php endif; ?>
</ul>

<input type="hidden" name="x_ID" id="x_ID" value="<?php echo $wp_userdata->ID; ?>" class="hidden">
<input type="hidden" name="u_ID" id="u_ID" value="<?php echo $wp_userdata->ID; ?>" class="hidden">
<input type="hidden" name="formaction" id="formaction" value="" class="hidden">
<?php if (isset($_POST['fromcart']) || isset($_GET['fromcart']) ) : ?>
<input type="hidden" name="fromcart" id="fromcart" value="1" class="hidden">
<?php endif; ?>

</form>

<?php else : ?>
<div id="password-block">
<form method="POST" name="userform" id="userform" >
<p><small><label>Password</label></small></p><input type="password" name="user_pass" id="user_pass" value="">
<p><small><label>Retype Password</label></small></p><input type="password" name="user_pass2" id="user_pass2" value="">
<ul class="cart-buttons">
<li id="update"><a href="javascript:void(setaction('setpassword'))">update password</a></li>
<li id="update"><a href="javascript:void(document.userform.submit())">back to profile</a></li>
</ul>

<input type="hidden" name="ID" id="ID" value="<?php echo $wp_userdata->ID; ?>" class="hidden">
<input type="hidden" name="formaction" id="formaction" value="" class="hidden">
<?php if (isset($_POST['fromcart']) || isset($_GET['fromcart']) ) : ?>
<input type="hidden" name="fromcart" id="fromcart" value="1" class="hidden">
<? endif; ?>

</form>
</div>

<?php endif;  ?>
<SCRIPT language="javascript">
function setaction(faction){
document.userform.formaction.value=faction;
document.userform.submit();
}
</SCRIPT>

</div><!-- End Profile Container -->

</div><!-- END CONTENT -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>