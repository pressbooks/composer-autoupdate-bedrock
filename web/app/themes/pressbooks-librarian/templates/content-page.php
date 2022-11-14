<div class="page-content col-xs-12 col-md-8 col-md-offset-2">
  <?php the_content(); ?>
  <?php if ( class_exists( '\PressbooksBiblioBoardOAuth\OAuth' ) ) {
    do_action( 'pressbooks_oauth_buttons' );
  } ?>
</div>
