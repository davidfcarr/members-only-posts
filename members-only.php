<?php
/*
Plugin Name: Members Only Posts
Plugin URI: https://github.com/davidfcarr/members-only-posts
Description: Create posts that will only be displayed (and listed in search) for logged in members. Just add the special category Members Only to any post not to be shared publicly. Includes Members Only sidebar widget and Site News widget (all posts not in Members Only category)
Author: David F. Carr
Tags: Members Only, Private Posts
Author URI: http://www.carrcommunications.com
Text Domain: members-only
Version: 1.0
*/

include 'members-only-widgets.php';

add_filter('the_content','carrcomm_member_only_content');
add_filter('the_excerpt','carrcomm_member_only_excerpt');
add_filter('get_the_excerpt','carrcomm_member_only_excerpt');
add_filter('jetpack_seo_meta_tags', 'carrcomm_members_only_jetpack');    
add_filter('bp_get_activity_content_body','carrcomm_members_only_bp');
add_shortcode('carrcomm_members_only','carrcomm_members_only');
add_shortcode('carrcomm_site_news','carrcomm_site_news');
add_action( 'pre_get_posts', 'carrcomm_members_only_modify_query' );
add_action('admin_init','carrcomm_members_only_init');

function carrcomm_members_only_init() {
    $members_category = get_category_by_slug('members-only');
    if(empty($members_category))
        wp_create_category('Members Only');    
}

function carrcomm_is_club_member() {
    return apply_filters('carrcomm_is_club_member',is_user_member_of_blog());	
}

function carrcomm_member_only_content($content) {
if( !in_category('members-only') && !has_term('members-only','rsvpmaker-type') )
	return $content;

if(!carrcomm_is_club_member() )
return '<div style="width: 100%; background-color: #ddd;">'.__('You must be logged in and a member of this blog to view this content','members-only').'</div>'. sprintf('<div id="member_only_login"><a href="%s">'.__('Login to View','members-only').'</a></div>',site_url('/wp-login.php?redirect_to='.urlencode(get_permalink()) ) );
else
return $content.'<div style="width: 100%; background-color: #ddd;">'.__('Note: This is member-only content (login required)','members-only').'</div>';
}

function carrcomm_members_only_jetpack ($tag_array) {
if( !in_category('members-only') && !has_term('members-only','rsvpmaker-type') )
	return $tag_array;
$tag_array['description'] = __('Members only content','members-only');
return $tag_array;
}

function carrcomm_member_only_excerpt($excerpt) {
if( !in_category('members-only') && !has_term('members-only','rsvpmaker-type') )
	return $excerpt;

if(!carrcomm_is_club_member() )
return __('You must be logged in and a member of this blog to view this content','members-only');
else
return $excerpt;
}


function carrcomm_members_only_bp($args) {
global $activities_template;

if($activities_template->activity->secondary_item_id)
{
$cat = wp_get_post_categories($activities_template->activity->secondary_item_id);
foreach($cat as $cat_id)
	{
	$category = get_category($cat_id);
	if($category->slug == 'members-only')
		return 'Members-only content (login required)';
	}
}

return $args;
}

add_action( 'pre_get_posts', 'carrcomm_members_only_modify_query' );

function carrcomm_members_only_modify_query( $query ) {
	if((get_post_type() == 'rsvpmaker') && ! carrcomm_is_club_member() )
		{
			$query->set('tax_query',array(array('taxonomy'  => 'rsvpmaker-type',
            'field'     => 'slug',
            'terms'     => 'members-only', 
            'operator'  => 'NOT IN')));
		}
    elseif ( ! is_admin() && $query->is_main_query() && ! carrcomm_is_club_member() )
		{
		$category = get_category_by_slug('members-only');
		if($category)
			$query->set( 'cat', '-'.$category->term_id );
		}
}

function carrcomm_members_only($args) {
ob_start();		
		$show_date = (!empty($args["show_date"])) ? 1 : 0;
		$show_excerpt = (!empty($args["show_excerpt"])) ? 1 : 0;
		$show_thumbnail = (!empty($args["show_thumbnail"])) ? 1 : 0;
		$number = (!empty($args["posts_per_page"])) ? $args["posts_per_page"] : 10;
		$title = (isset($args["title"]) ) ? $args["title"] : 'Members Only';
		if(!empty($title))
			echo '<h2 class="club_news">'.$title."</h2>\n";
		$qargs =  array(
		'posts_per_page'      => $number,
		'category_name' => 'members-only',
		'no_found_rows'       => true,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true);

		$r = new WP_Query( apply_filters( 'widget_posts_args', $qargs ) );

		if ($r->have_posts()) :
		 ?>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<h3>
				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $show_date ) : ?>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; ?>
			</h3>
			<?php
			
			if ( $show_thumbnail && has_post_thumbnail() ) : ?>
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
				<?php the_post_thumbnail('thumbnail'); ?>
				</a>
			<?php endif;			
			
			 if ( $show_excerpt ) : ?>
				<div class="post-excerpt"><?php the_excerpt(); ?></div>
			<?php endif; ?>
		<?php endwhile; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();
		endif;
return ob_get_clean();
}

function carrcomm_site_news($args) {
    ob_start();		
            $show_date = (!empty($args["show_date"])) ? 1 : 0;
            $show_excerpt = (!empty($args["show_excerpt"])) ? 1 : 0;
            $show_thumbnail = (!empty($args["show_thumbnail"])) ? 1 : 0;
            $number = (!empty($args["posts_per_page"])) ? $args["posts_per_page"] : 10;
            $title = (isset($args["title"]) ) ? $args["title"] : 'Members Only';
            if(!empty($title))
                echo '<h2 class="club_news">'.$title."</h2>\n";
                $category = get_category_by_slug('members-only');
                    $qargs =  array(
                    'posts_per_page'      => $number,
                    'cat' => '-'.$category->term_id,
                    'no_found_rows'       => true,
                    'post_status'         => 'publish',
                    'ignore_sticky_posts' => true);
    
            $r = new WP_Query( apply_filters( 'widget_posts_args', $qargs ) );
    
            if ($r->have_posts()) :
             ?>
            <?php while ( $r->have_posts() ) : $r->the_post(); ?>
                <h3>
                    <a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
                <?php if ( $show_date ) : ?>
                    <span class="post-date"><?php echo get_the_date(); ?></span>
                <?php endif; ?>
                </h3>
                <?php
                
                if ( $show_thumbnail && has_post_thumbnail() ) : ?>
                    <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                    <?php the_post_thumbnail('thumbnail'); ?>
                    </a>
                <?php endif;			
                
                 if ( $show_excerpt ) : ?>
                    <div class="post-excerpt"><?php the_excerpt(); ?></div>
                <?php endif; ?>
            <?php endwhile; ?>
    <?php
            // Reset the global $the_post as this query will have stomped on it
            wp_reset_postdata();
            endif;
    return ob_get_clean();
}