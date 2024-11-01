<?php get_header(); ?>
<div id="content" class="narrowcolumn">
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

<table cellspacing="0">
<caption><h2>Your Cart</h2></caption>
<thead><tr>
   <th colspan="2" scope="col">quantity</th>
   <th scope="col" class="name">item</th>
   <th scope="col">price</th>
   <th scope="col">ext price</th>
   <?php if ($is_admin) {echo "<th scope=\"col\">act. price</th><th scope=\"col\">ext act. price</th scope=\"col\"><th scope=\"col\">notes</th>"; } ?>

</tr></thead>
<?php
$cart_total=0;
//check to see if we have anything in the cart
echo '<tbody class="cart-body">';
while ($row = $cart->cart_item()) {
	$tbody .= '<tr>';
	$tbody .= '<td class="td-delete"><a href="javascript:void(actiondelete(\''. $row->cart_itemid . '\'))">-</a></td>';
	$tbody .= '<td class="td-qnty"><input type="text" size="3" name="qty_'. $row->cart_itemid .'" value="'.$row->quantity.'"></td>';
	$tbody .= '<td class="td-item" class=\"name\"><a href="'.get_bloginfo('url').'/?p='.$row->post_id.'">' . $row->post_title . '</a></td>';
	$tbody .= '<td class="td-price">$'. $row->map_price .'</td>';
	$tbody .= '<td class="td-price">$'.$row->map_price*$row->quantity .'</td>';
	$tbody .= '</tr>';
	$cart_total +=($row->map_price*$row->quantity);
	echo $tbody;
}
	echo '</tbody>';
?>

<tfoot><tr><td colspan="5"  class="td-total">total: $<?php echo $cart_total ?></td></tr></foot></table>

<input type="hidden" name="formaction" id="formaction" value="">
<input type="hidden" name="updatecart" id="updatecart" value="1">
<input type="hidden" name="deleteitem" id="deleteitem" value="">

<h4>Notes:</h4>
<p class="post">What else can we do to serve you?</p>
<textarea id="cart_notes" name="cart_notes" rows="3" cols="20">
<?php echo $cart->get_ordernote(); ?>
</textarea>

<ul class="cart-buttons">
<li id="update"><a href="javascript:void(setaction('update'))">update</a></li>
<li id="clear-cart"><a href="javascript:void(setaction('reset'))">clear my stuff</a></li>
<?php 
echo '<li id="continue-shopping"><a href="'. get_settings('siteurl') .'/">continue shopping</a></li>';
echo '<li id="edit"><a href="'.get_settings('siteurl') . '/?cart_profile=1&fromcart=1">edit profile</a></li>';
//put this in so that a user can not hack processing their order  the
//other actions can only be done via the admin screens.
if ($cart->is_available()) {
	if (isset($acting_as_name)) {
	echo '<li id="request-quote"><a href="javascript:void(setaction(\'makequote\'))">make quote</a></li>';
	echo '<li id="request-quote"><a href="javascript:void(setaction(\'makeconf\'))">make confirmation</a></li>';
	echo '<li id="request-quote"><a href="javascript:void(setaction(\'makeorder\'))">make order</a></li>';
	echo '<li id="request-quote"><a href="javascript:void(setaction(\'makeprocessed\'))">make processed</a></li>';

	} else {
	echo '<li id="request-quote"><a href="javascript:void(setaction(\'makequote\'))">request quote</a></li>';
	}
}
?>
</ul>

<div class="clearfix"></div>

</form>

</div> <!-- END CART BLOCK -->

<?php if(!$is_admin) : ?>
	</div><!-- END CONTENT -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
<?php endif; ?>
