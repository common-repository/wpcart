<?php get_header(); ?>
<div id="content" class="narrowcolumn">
<div id="cart-block"> <!-- START CART BLOCK -->

<form name="cartform" method="GET" id="cartform">

<table cellspacing="0">
<caption><h2>Your Downloads</h2></caption>
<thead><tr>
   <th scope="col" class="name">item</th>
   <th scope="col" class="name">Download</th>
</tr></thead>
<?php
//check to see if we have anything in the cart
echo '<tbody class="cart-body">';
    $cart->reset_downloads($user_ID);
    while ($row = $cart->download_item()) {
        $tbody .= '<tr>';
        $tbody .= '<td class="td-item" class=\"name\"><a href="'.get_bloginfo('url').'/?p='.$row->post_id.'">' . $row->post_title . '</a></td>';
        $tbody .= '<td><a href="'.get_bloginfo('url').'/'.$row->post_download_name .'">'.  ' Click Here To Download This Item</a></td>';
        $tbody .= '</tr>';
    }
    echo $tbody;
    echo '</tbody>';
?>
</table>

<div class="clearfix"></div>

</form>

</div> <!-- END CART BLOCK -->

<?php if(!$is_admin) : ?>
	</div><!-- END CONTENT -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
<?php endif; ?>
