<?php 
//Get the order information
$userres = $cart->get_userinfo();
$orderres = $cart->get_orderinfo();
?>

<SCRIPT language="javascript">
function setaction(faction){
document.cartform.formaction.value=faction;
document.cartform.submit();
}
function actiondelete(cartid){
document.cartform.formaction.value='delete';
document.cartform.deleteitem.value=cartid;
document.cartform.submit();
}
</SCRIPT>
<div id="cart-block"> <!-- START CART BLOCK -->

<form name="cartform" method="GET" id="cartform">

<p>Quickbooks orderid:
<?php 
if ($cart->current_status() == 'processed') echo $orderres->order_id;
else echo '<input type="text" size="8" name="order_id" value="'. $orderres->order_id . '"></p>';
?>

<table cellspacing="0">
<caption><h2>Your Cart</h2></caption>
<thead><tr>
   <th colspan="2" scope="col">quantity</th>
   <th scope="col" class="name">item</th>
   <th scope="col">price</th>
   <th scope="col">ext price</th>
   <th scope="col">act. price</th>
   <th scope="col">ext act. price</th>
   <th scope="col">notes</th>
</tr></thead>
<?php
$cart_total=0;
//check to see if we have anything in the cart
while ($row = $cart->cart_item()) {
	$tbody .= '<tbody class="cart-body">'.
		  '<tr>'.
           '<td class="td-delete"><a href="javascript:void(actiondelete(\''. $row->cart_itemid . '\'))">-</a></td>'.
           '<td class="td-qnty"><input type="text" size="3" name="qty_'. $row->cart_itemid .'" value="'.$row->quantity.'"></td>'.
           '<td class="td-item" class=\"name\"><a href="'.get_bloginfo('url').'/?p='.$row->post_id.'">'.$row->post_title.'</a></td>';
        $tbody .= '<td class="td-price">$'. $row->map_price .'</td>';
        $tbody .= '<td class="td-price">$'.$row->map_price*$row->quantity .'</td>';
        $tbody .= '<td><input type="text" size="8" name="apr_'.$row->cart_itemid.'" value ="'.$row->price.'"></td>';
        $tbody .= '<td class="td-price">$'.$row->price*$row->quantity .'</td>';
        $tbody .= '<td><input type="text" size="20" name="dnote_'.$row->cart_itemid.'" value ="'.$row->notes .'"></td>';
        $tbody .= '</tr></tbody>';
        $cart_total +=($row->price*$row->quantity);
}
        echo $tbody;
?>

<tbody><tr><td colspan="6">Invoice Total:</td><td align="right" colspan="1" class="td-total">$<?php echo $cart_total; ?></td></tr></tbody>
<tbody>
<?php
$amount_remaining=$cart_total;
//show payments
while ($row = $cart->cart_payment()) {
	print '<tr>';
	print '<td align="right" colspan="6">';
	//add this switch to the query some how.....
	switch ($row->transaction_type) {
		case 'creditcard';
			print "Card: {$row->cc_number}<br />";
			print "Approval Code: {$row->approval_code}";
			break;
		default:
			print "Payment";
	}
	print '</td>';
	print '<td align="right" >';
	print number_format($row->amount,2);
	$amount_remaining -= $row->amount;
	print '</td>';
	print '</tr>';
}
?>
</tbody>
<tfoot><tr><td colspan="6">Total Due:</td><td align="right" colspan="1" class="td-total">$<?php echo $amount_remaining; ?></td></tr></tfoot>
</table>

<input type="hidden" name="formaction" id="formaction" value="">
<input type="hidden" name="updatecart" id="updatecart" value="1">
<input type="hidden" name="deleteitem" id="deleteitem" value="">
<input type="hidden" name="cartid" id="cartid" value="<?php echo $cart->cart_id; ?>">
<input type="hidden" name="page" id="page" value="shoppingcart/admin.php">


<h4>Notes:</h4>
<textarea id="cart_notes" name="cart_notes" rows="3" cols="20">
<?php echo $orderres->notes; ?>
</textarea>

<h4 class="alert">Credit Card Info</h4>
<div class="accent-block">
<?php do_action ('wpc_display_auth',$cart); ?>
<div class="clearfix"></div>
</div>


<ul class="cart-buttons">
<!-- only display if the cc has not been run for the current total -->
<li id="payment"><a href="javascript:void(setaction('payment'))">charge cc</a></li>
<li id="update"><a href="javascript:void(setaction('update'))">update</a></li>
<?php if ($cart->has_payment()) { ?>
<li id="clear-cart"><a href="javascript:void(setaction('cancel'))">cancel order</a></li>
<?php } else { ?>
<li id="clear-cart"><a href="javascript:void(setaction('reset'))">clear my stuff</a></li>
<?php }  ?>
<?php 
   if ($cart->is_available() && $cart->current_status() != 'processed') 
      echo '<li id="request-quote"><a href="javascript:void(setaction(\''.status_to_action ($cart->current_status()).'\'))">request '.status_to_actionName ($cart->current_status()).'</a></li>';
?>
</ul>

<div class="clearfix"></div>

<div id="profile-info">

<small>phone</small><input type="text" name="x_phone" id="x_phone" value="<?php echo $orderres->phone; ?>">
<?php if($is_admin) { ?>
<small>email</small><p><a href="mailto:<?php echo $orderres->email; ?>"><?php echo $orderres->email; ?></a></p>
<?php } else { ?>
<small>email</small><input type="text" name="x_email" id="x_email" value="<?php echo $orderres->email; ?>">
<?php } ?>

<h4>shipping address</h4>
	<div class="accent-block">
		<div class="left">
		<h4>shipping</h4>
		<small>address</small><input type="text" name="x_ship_address_1" id="x_ship_address_1" value="<?php echo $orderres->ship_address_1; ?>">
		<input type="text" name="x_ship_address_2" id="x_ship_address_2" value="<?php echo $orderres->ship_address_2; ?>">
		<small>city</small><input type="text" name="x_ship_city" id="x_ship_city" value="<?php echo $orderres->ship_city; ?>">
		<small>state</small><input type="text" name="x_ship_state" id="x_ship_state" value="<?php echo $orderres->ship_state; ?>">
		<small>country</small><input type="text" name="x_ship_country" id="x_ship_country" value="<?php echo $orderres->ship_country; ?>">
		<small>postal</small><input type="text" name="x_ship_postal" id="x_ship_postal" value="<?php echo $orderres->ship_postal; ?>">
		</div>
	
		<div class="right">
		<h4>billing address</h4>
		<small>address</small><input type="text" name="x_bill_address_1" id="x_bill_address_1" value="<?php echo $orderres->bill_address_1; ?>">
		<input type="text" name="x_bill_address_2" id="x_bill_address_2" value="<?php echo $orderres->bill_address_2; ?>">
		<small>city</small><input type="text" name="x_bill_city" id="x_bill_city" value="<?php echo $orderres->bill_city; ?>">
		<small>state</small><input type="text" name="x_bill_state" id="x_bill_state" value="<?php echo $orderres->bill_state; ?>">
		<small>country</small><input type="text" name="x_bill_country" id="x_bill_country" value="<?php echo $orderres->bill_country; ?>">
		<small>postal</small><input type="text" name="x_bill_postal" id="x_bill_postal" value="<?php echo $orderres->bill_postal; ?>">
		</div>
		<div class="clearfix"></div>
	</div>

</div>
</form>

</div> <!-- END CART BLOCK -->
