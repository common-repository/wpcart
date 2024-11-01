<?php 
global $post,$wpdb;
get_header();
$e_class=' class="error" ';
?>
<div id="content"  class="narrowcolumn"><!-- START CONTENT -->

<?php
/*
==============================================================================
==============================================================================
==============================================================================
*/
//$months = array('','01 Jan','02 Feb','03 Mar','04 Apr','05 May','06 Jun','07 Jul','08 Aug','09 Sep','10 Oct','11 Nov','12 Dec');
//$cvv_ind = array ('Present','Unreadable','Not Present');


//cart should be initialize from the shopping cart plugin
//table name should be initialized by shopping cart plugin

//Get information to display
$orderres = $cart->get_orderinfo();

$cart_total=0;
//check to see if we have anything in the cart
echo '<tbody class="cart-body">';
while ($row = $cart->cart_item()) {
	$tbody .= '<tr>';
	$tbody .= '<td class="td-delete"><a href="javascript:void(actiondelete(\''. $row->cart_itemid . '\'))">-</a></td>';
	$tbody .= '<td class="td-qnty"><input type="text" size="3" name="qty_'. $row->cart_itemid .'" value="'.$row->quantity.'"></td>';
	$tbody .= '<td class="td-item" class=\"name\"><a href="'.get_bloginfo('url').'/?p='.$row->post_id.'">' . $row->post_title . '</a></td>';
	$tbody .= '<td class="td-price">$'. $row->price .'</td>';
	$tbody .= '<td class="td-price">$'.$row->price*$row->quantity .'</td>';
   $tbody .= '<td class="td-notes">'.$row->notes .'</td>';
	$tbody .= '</tr>';
	$cart_total +=($row->price*$row->quantity);
	echo $tbody;
}
echo '</tbody>';
?>
<script type="text/javascript">
<!--
function setaction(faction){
document.cartform.formaction.value=faction;
document.cartform.submit();
}
function actiondelete(cartid){
document.cartform.formaction.value='delete';
document.cartform.deleteitem.value=cartid;
document.cartform.submit();
}
-->
</script>

<div id="cart-block"> <!-- START CART BLOCK -->
<?php echo $errormessage; ?>
<form name="cartform" method="post" id="cartform">
<h2>Take a look at these prices!</h2>
<small>These are great prices on the items you requested. Please look it over, and if you have any questions call us or email us. We will be happy to modify this quote and send you a new one.</small>

<table id="cart">
<thead class="cart-head"><tr>
   <td colspan="2">quantity</td>
   <td>item</td>
   <td>your price</td>
   <td>ext price</td>
   <td>notes</td>
</tr></thead>

<?php echo $tbody; ?>

<tbody><tr><td colspan="6"  class="td-total">total: $<?php echo $cart_total ?></td></tr></tbody></table>
<input type="hidden" name="formaction" id="formaction" value="" />
<input type="hidden" name="updatecart" id="updatecart" value="1" />
<input type="hidden" name="deleteitem" id="deleteitem" value="" />
<?php 
  echo '<input type="hidden" name="cartid" id="cartid" value="'.$cart->cart_id.'" />'; 
?>


<h4>Notes:</h4>
<textarea id="cart_notes" name="cart_notes" rows="3" cols="20">
<?php echo $cart->get_ordernote(); ?>
</textarea>

<small>Wanna change your amounts, or remove an item? Go ahead and do it above then click update. No need to create a new cart.</small>

<ul class="cart-buttons">
<li id="update"><a href="javascript:void(setaction('update'))">update</a></li>
</ul>

<h2>Like It? Love It? Buy It!</h2>
<small>Fill in your payment info and any address stuff not displayed, scroll to the bottom and click buy now. We will process you order and send it on it's way.</small>

<h2>Credit Card Info</h2>
<div class="accent-block">
<div class="left">
<small>name on card</small><input type="text" name="cc_cc_name" id="cc_cc_name" value="<?php echo $orderres->cc_name; ?>" <?php echo $e_cc_name ? $e_class :''; ?> />
<small>credit card number</small><input type="text" name="cc_cc_number" id="cc_cc_number" value="<?php echo $orderres->cc_number; ?>" <?php echo $e_cc_number ? $e_class :''; ?> />
<small>cvv</small><input type="text" name="cc_cc_cvv" id="cc_cc_cvv" value="<?php echo $orderres->cc_cvv; ?>" <?php echo $e_cc_cvv ? $e_class :''; ?> />
</div>

<div class="right">
<small>exp month</small>
<select name="cc_cc_expmonth" id="cc_cc_expmonth" <?php echo $e_cc_expmonth ? $e_class :''; ?>>
<?php
 foreach ($months as $value => $display) {
   $selected = ($orderres->cc_expmonth == $value) ? 'selected' : '';
   echo "<option $selected value=\"$value\">$display</option>\n";
   }
?>
</select>

<small>exp year</small>
<select name="cc_cc_expyear" id="cc_cc_expyear" <?php echo $e_cc_expyear ? $e_class :''; ?>>
<option value=""></option>
<?php
 for ($year=date('Y');$year < (10 +date('Y'));$year++) {
   $selected = ($orderres->cc_expyear == $year) ? 'selected' : '';
   echo "<option $selected value=\"$year\">$year</option>\n";
   }
?>
</select>

<select name="cc_cc_cvvindicator" id="cc_cc_cvvindicator" <?php echo $e_cc_cvvindicator ? $e_class :''; ?>>
<?php
 foreach ($cvv_ind as $value) {
   $selected = ($orderres->cc_cvvindicator == $value) ? 'selected' : '';
   echo "<option $selected value=\"$value\">$value</option>\n";
   }
?>
</select>
</div>
<div class="clearfix"></div>
</div>

<p><span  class="alert">Need a different way to pay?</span><br />
We want to help you. Please contact us dirrectly if you need to pay us a different way.</p>

<small>Does the address info below look correct? If not, please feel free to correct it and we will keep it with just this order. You can also make changes to your <a href="./?cart_profile=1">profile</a> that we will store for you.</small>

<div class="clearfix"></div>

<h2>Address</h2>
<div id="profile-info">
	<div class="accent-block">
		<div class="left">
		<h4>shipping</h4>
		<small>address</small><input type="text" name="x_ship_address_1" id="x_ship_address_1" value="<?php echo $orderres->ship_address_1; ?>" />
		<input type="text" name="x_ship_address_2" id="x_ship_address_2" value="<?php echo $orderres->ship_address_2; ?>" />
		<small>city</small><input type="text" name="x_ship_city" id="x_ship_city" value="<?php echo $orderres->ship_city; ?>" />
		<small>state</small><input type="text" name="x_ship_state" id="x_ship_state" value="<?php echo $orderres->ship_state; ?>" />
		<small>country</small><input type="text" name="x_ship_country" id="x_ship_country" value="<?php echo $orderres->ship_country; ?>" />
		<small>postal</small><input type="text" name="x_ship_postal" id="x_ship_postal" value="<?php echo $orderres->ship_postal; ?>" />
		</div>
	
		<div class="right">
		<h4>billing address</h4>
		<small>address</small><input type="text" name="x_bill_address_1" id="x_bill_address_1" value="<?php echo $orderres->bill_address_1; ?>" />
		<input type="text" name="x_bill_address_2" id="x_bill_address_2" value="<?php echo $orderres->bill_address_2; ?>" />
		<small>city</small><input type="text" name="x_bill_city" id="x_bill_city" value="<?php echo $orderres->bill_city; ?>" />
		<small>state</small><input type="text" name="x_bill_state" id="x_bill_state" value="<?php echo $orderres->bill_state; ?>" />
		<small>country</small><input type="text" name="x_bill_country" id="x_bill_country" value="<?php echo $orderres->bill_country; ?>" />
		<small>postal</small><input type="text" name="x_bill_postal" id="x_bill_postal" value="<?php echo $orderres->bill_postal; ?>" />
		</div>
		<div class="clearfix"></div>
	</div>

<div class="accent">
<small>phone</small><input type="text" name="x_phone" id="x_phone" value="<?php echo $orderres->phone; ?>" />
<small>email</small><a href="mailto:<?php echo $orderres->email; ?>"><?php echo $orderres->email; ?></a>
</div>

<a href="javascript:void(setaction('buy'))">BUY NOW</a>

</div>

</form>

</div> <!-- END CART BLOCK -->

</div><!-- END CONTENT -->
<div class="clearfix"></div>
<!-- Start Footer --><?php get_footer(); ?><!-- End Footer-->
</div> <!-- end IE5Center -->

</body>
</html>
