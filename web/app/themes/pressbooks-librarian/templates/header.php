<header class="banner">
  <div class="container">
    <div class="link-wrap">
          <?php if ( !class_exists( '\PressbooksBiblioBoardOAuth\OAuth' ) && !is_user_logged_in() ) : ?>
            <a href="<?= wp_login_url( get_option( 'home' ) ); ?>" class="btn btn-primary btn-sm"><?php _e('Sign In', 'pressbooks-librarian'); ?></a>
          <?php endif; ?>
          <?php if ( is_user_logged_in() ) : ?>
            <a href="<?= wp_logout_url(); ?>" class="btn btn-primary btn-sm"><?php _e('Sign Out', 'pressbooks-librarian'); ?></a>
            <?php $user_info = get_userdata( get_current_user_id() ); if ( $user_info->primary_blog ) : ?>
              <a href="<?php $user_info = get_userdata( get_current_user_id() ); ?><?= get_blogaddress_by_id( $user_info->primary_blog ); ?>wp-admin/index.php?page=pb_catalog" class="btn btn-primary btn-sm"><?php _e('My Books', 'pressbooks-librarian'); ?></a>
            <?php endif; ?>
          <?php if (is_super_admin() || is_user_member_of_blog()): ?>
            <a href="<?= get_option('home'); ?>/wp-admin" class="btn btn-primary btn-sm"><?php _e('Admin', 'pressbooks-librarian'); ?></a>
          <?php endif;
        endif; ?>
     </div>

    <div class="logo"><?php the_custom_logo(); ?></div>
    <a class="brand" href="<?= esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
    <h2><?php _e('Pressbooks Public Self-Publishing Platform', 'pressbooks-librarian'); ?></h2>
  </div>
</header>
