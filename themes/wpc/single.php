<?php get_header(); ?>

	<div id="content" class="widecolumn">
<?php
if ($user_level>=9) {
   //Someone will also have to logged into the system as a admin to have a user_level >=9  THAT is not passed by the GET var. The actingas ID is passed through the DB the user should not have any access to that.
 //  $user_table='dj_users';
   //echo "---> {$userext_table}<---";

   $usertest = $wpdb->get_var("SELECT actingas FROM  {$userext_table} WHERE ID=$user_ID");
   if (isset($usertest)) {
      $admin_user_ID=$user_ID;
      $user_ID=$usertest;
      $actingas=true;
      $sql = "SELECT * FROM $user_table u LEFT JOIN $userext_table x ON u.ID=x.ID WHERE u.ID=$user_ID";
      $userres = $wpdb->get_row($sql);

   }
}
?>

<?php
//THIS IS THE ACTING AS INDICATOR
if ($actingas) {
  print "<div class=\"cart-actingas\"><h2>Acting AS: {$userres->user_firstname} {$userres->user_lastname} </h2><a href=\"./wp-admin/admin.php?page=shoppingcart.php\">Back to Admin</a>
</div>";
} 
?>
				
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	
		<div class="navigation">
			<div class="alignleft"><?php previous_post_link('&laquo; %link') ?></div>
			<div class="alignright"><?php next_post_link('%link &raquo;') ?></div>
		</div>
	
		<div class="post" id="post-<?php the_ID(); ?>">
			<h2><a href="<?php echo get_permalink() ?>" rel="bookmark" title="Permanent Link: <?php the_title(); ?>"><?php the_title(); ?></a></h2>
	
			<div class="entrytext">
				<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>
	
				<?php link_pages('<p><strong>Pages:</strong> ', '</p>', 'number'); ?>
	
				<?php if ($post->post_is_prod=='Yes') { ?>
<div class="buy">
<h4>Price: $<?php echo $post->post_map; ?></h4>
<h3><a href="<?php echo get_settings('siteurl') . '/?p='. $post->ID ?>&amp;addcart=1">add to my stuff</a></h3>
</div>
<?php } ?>
		

	
				<p class="postmetadata alt">
					<small>
						This entry was posted
						<?php /* This is commented, because it requires a little adjusting sometimes.
							You'll need to download this plugin, and follow the instructions:
							http://binarybonsai.com/archives/2004/08/17/time-since-plugin/ */
							/* $entry_datetime = abs(strtotime($post->post_date) - (60*120)); echo time_since($entry_datetime); echo ' ago'; */ ?> 
						on <?php the_time('l, F jS, Y') ?> at <?php the_time() ?>
						and is filed under <?php the_category(', ') ?>.
						You can follow any responses to this entry through the <?php comments_rss_link('RSS 2.0'); ?> feed. 
						
						<?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Both Comments and Pings are open ?>
							You can <a href="#respond">leave a response</a>, or <a href="<?php trackback_url(true); ?>" rel="trackback">trackback</a> from your own site.
						
						<?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Only Pings are Open ?>
							Responses are currently closed, but you can <a href="<?php trackback_url(true); ?> " rel="trackback">trackback</a> from your own site.
						
						<?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Comments are open, Pings are not ?>
							You can skip to the end and leave a response. Pinging is currently not allowed.
			
						<?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Neither Comments, nor Pings are open ?>
							Both comments and pings are currently closed.			
						
						<?php } edit_post_link('Edit this entry.','',''); ?>
						
					</small>
				</p>
	
			</div>
		</div>
		
	<?php comments_template(); ?>
	
	<?php endwhile; else: ?>
	
		<p>Sorry, no posts matched your criteria.</p>
	
<?php endif; ?>
	
	</div> <!-- END CONTENT -->

<?php get_footer(); ?>
