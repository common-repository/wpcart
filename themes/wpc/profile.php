<?php
/*
Template Name: NO COMMENT
*/
?>

<?php 
global $post;
global $author;
get_header();
?>

<div id="left-major"><!-- start left-major -->
<!-- Start Site Nav --><?php include ('site-nav.php'); ?><!-- end Site Nav-->
<div id="left-minor"><!-- start left-minor-->
<!-- Start Sidebar Left --><?php include ('sidebar-left.php'); ?><!-- End Sidebar Left -->

<div id="content"><!-- START CONTENT -->

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	
<div class="post">
	 <h3 class="storytitle" id="post-<?php the_ID(); ?>"><?php the_title(); ?></h3>
	
	<?php if ($post->post_is_prod=='Yes') { ?>
<?php //if (isset($post[0]->post_is_prod=='Yes')) { ?>
	<div class="buy">
		
		<a href="<?php echo get_settings('siteurl') . '/?p='. $post->ID ?>&addcart=1">add to quote</a><br />
	</div>
	<div class="clearfix"></div>
<?php } ?>
	
	
	
	<div class="storycontent">
		<?php the_content(__('(more...)')); ?>
	</div>
	

</div>

<?php endwhile; else: ?>
<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif; ?>


</div><!-- END CONTENT -->
<div class="clearfix"></div>
</div><!-- end left-minor -->



</div><!-- end left-major -->

<?php require_once ('sidebar-right.php'); ?>

<div class="clearfix"></div>
</div> <!-- end wrap --><!-- Start Footer --><?php get_footer(); ?><!-- End Footer-->
</div> <!-- end IE5Center -->

</body>
</html>