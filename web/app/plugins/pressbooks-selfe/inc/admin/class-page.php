<?php
/**
 * Administration interface.
 */

namespace Pressbooks_Selfe\Admin;

use GuzzleHttp\Client;
use PressbooksMix\Assets;
use Pressbooks\Book;
use Pressbooks\Contributors;
use Pressbooks\Metadata;
use Pressbooks\Modules\Export\Export;

class Page {
	const OPTION = 'pressbooks_selfe_options';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	public function add() {
		$hook = add_submenu_page(
			'pb_publish',
			__( 'Submit to the Indie Author Project', 'pressbooks-selfe' ),
			__( 'Submit to the Indie Author Project', 'pressbooks-selfe' ),
			'manage_options',
			'pb_selfe',
			[ $this, 'display' ]
		);
	}

	public function assets( $hook ) {
		if ( $hook !== get_plugin_page_hookname( 'pb_selfe', 'pb_publish' ) ) {
			return;
		}

		$assets = new Assets( 'pressbooks-selfe', 'plugin' );
		$assets->setSrcDirectory( 'assets' )->setDistDirectory( 'dist' );

		wp_enqueue_style( 'selfe/css', $assets->getPath( 'styles/main.css' ), false, null );
		wp_enqueue_style( 'selfe/datepicker', PB_PLUGIN_URL . 'symbionts/custom-metadata/css/jquery-ui-smoothness.css', false, null );
		wp_enqueue_script( 'selfe/js', $assets->getPath( 'scripts/main.js' ), [ 'jquery', 'jquery-ui-datepicker' ], null );
	}

	/**
	 * Display the Submit to Indie Author Project submenu page contents.
	 */
	public function display() {
		$meta = ( new Metadata() )->getMetaPost();
		$selfe_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin.php?page=pb_selfe' ), 'pb_selfe' );
		$latest_exports = \Pressbooks\Utility\latest_exports();

		if ( ! empty( $_POST ) && current_user_can( 'edit_posts' ) && check_admin_referer( 'pb_selfe' ) ) {
			$this->updateOption( $_POST );
			$ready = $this->validateSubmission( $_POST );
			if ( $ready ) {
				$this->postData( $latest_exports, $_POST['format'] );
			}
		}

		$options = get_option( self::OPTION, [] );
		$metadata = $this->overrideMetadataWithOptions( Book::getBookInformation( null, false ), $options );

		$book_info_url = admin_url( 'post.php?post=' . absint( $meta->ID ) . '&action=edit' );
		$formats = [];
		if ( isset( $latest_exports['epub'] ) ) {
			$formats['epub'] = __( 'EPUB', 'pressbooks-selfe' );
		}
		if ( isset( $latest_exports['epub3'] ) ) {
			$formats['epub3'] = __( 'EPUB 3', 'pressbooks-selfe' );
		}
		if ( isset( $latest_exports['pdf'] ) ) {
			$formats['pdf'] = __( 'PDF', 'pressbooks-selfe' );
		}
		$format_description = null;
		$format_disabled = false;
		if ( ! isset( $latest_exports['epub'] ) && ! isset( $latest_exports['epub3'] ) && ! isset( $latest_exports['pdf'] ) ) {
			$format_description = __( 'No export files were found in a compatible format. Please export your book as EPUB, EPUB3 or PDF ( digital distributon ) and return to this page to complete your submission.', 'pressbooks-selfe' );
			$format_disabled = true;
		}
		?>
		<div class="wrap">
			<h1><?php _e( 'Submit to the Indie Author Project', 'pressbooks-selfe' ); ?></h1>
			<p><?php _e( 'Pressbooks can submit your EPUB or PDF to the <a href="https://indieauthorproject.com/#moreinfo">Indie Author Project</a> self-publishing service. Please complete the information below before submitting.', 'pressbooks-selfe' ); ?></p>
			<h2><?php _e( 'Book Information', 'pressbooks-selfe' ); ?></h2>
			<p>
				<?php
				/* translators: %s: Book info url */
				printf( __( 'This information comes from your book&rsquo;s <a href="%s">Book Information</a> page. (<strong>IMPORTANT NOTE:</strong> Changes you make here will <strong>NOT</strong> be reflected on the Book Information page.)', 'pressbooks-selfe' ), $book_info_url );
				?>
			</p>
			<form id="selfe-form" action="<?php echo $selfe_form_url; ?>" method="POST">
				<table class="form-table" role="none">
					<?php
					$this->displayTextInput( 'pb_title', $metadata['pb_title'], __( 'Title*', 'pressbooks-selfe' ), null, true );
					$this->displayTextInput( 'pb_subtitle', ( isset( $metadata['pb_subtitle'] ) ) ? $metadata['pb_subtitle'] : '', __( 'Subtitle', 'pressbooks-selfe' ), null, false );

					// We mix deprecated contributor slugs with new contributor slugs when generating the HTML form, i.e.
					// { Left side: } Deprecated contributor slugs. Comes from 'pressbooks_selfe_options'
					// { Right side: } New contributor slugs. Comes from \Pressbooks\Book::getBookInformation()
					// Brave person from the future! You should probably rewrite this entire class. Enjoy?

					$this->displayTextInputRows( 'pb_author', ( isset( $metadata['pb_author'] ) ) ? $metadata['pb_author'] : '', __( 'Author(s)*', 'pressbooks-selfe' ), null, 'regular-text contributing-author' );
					$this->displayTextInputRows( 'pb_contributing_authors', ( isset( $metadata['pb_contributing_authors'] ) ) ? $metadata['pb_contributing_authors'] : '', __( 'Contributing Author(s)', 'pressbooks-selfe' ), null, 'regular-text contributing-author' );
					$this->displayTextInputRows( 'pb_editor', ( isset( $metadata['pb_editor'] ) ) ? $metadata['pb_editor'] : '', __( 'Editor(s)', 'pressbooks-selfe' ), null );
					$this->displayTextInputRows( 'pb_translator', ( isset( $metadata['pb_translator'] ) ) ? $metadata['pb_translator'] : '', __( 'Translator(s)', 'pressbooks-selfe' ), null );
					$this->displayTextInput( 'pb_series_number', ( isset( $metadata['pb_series_number'] ) ) ? $metadata['pb_series_number'] : '', __( 'Volume', 'pressbooks-selfe' ), null, false );
					$this->displayTextInput( 'pb_ebook_isbn', ( isset( $metadata['pb_ebook_isbn'] ) ) ? $metadata['pb_ebook_isbn'] : '', __( 'ISBN', 'pressbooks-selfe' ), null, false, 'regular-text code' );
					$this->displayTextInput( 'pb_publication_date', ( isset( $metadata['pb_publication_date'] ) ) ? strftime( '%m/%d/%Y', $metadata['pb_publication_date'] ) : '', __( 'Publication Date*', 'pressbooks-selfe' ), null, true );
					$this->displayTextInput( 'pb_publisher', ( isset( $metadata['pb_publisher'] ) ) ? $metadata['pb_publisher'] : '', __( 'Publisher', 'pressbooks-selfe' ), null, false );
					$this->displayTextArea( 'pb_about_50', ( isset( $metadata['pb_about_50'] ) ) ? $metadata['pb_about_50'] : '', __( 'Description*', 'pressbooks-selfe' ), null, true );
					$this->displaySelect(
						'pb_audience',
						[
							'' => __( 'Choose an audience&hellip;', 'pressbooks-selfe' ),
							'children' => __( 'Children', 'pressbooks-selfe' ),
							'young-adult' => __( 'Young Adult', 'pressbooks-selfe' ),
							'adult' => __( 'Adult', 'pressbooks-selfe' ),
						],
						( isset( $metadata['pb_audience'] ) ) ? $metadata['pb_audience'] : '',
						__( 'Target Audience*', 'pressbooks-selfe' ),
						null,
						true
					);
					$this->displaySelect( 'pb_bisac_subject', \Pressbooks_Selfe\Data\get_subjects(), ( isset( $metadata['pb_bisac_subject'] ) ) ? explode( ', ', $metadata['pb_bisac_subject'] ) : [], __( 'Categories*', 'pressbooks-selfe' ), null, true, true );
					$this->displaySelect( 'pb_language', \Pressbooks\L10n\supported_languages(), ( isset( $metadata['pb_language'] ) ) ? $metadata['pb_language'] : 'en', __( 'Language*', 'pressbooks-selfe' ), null, true );
					?>
				</table>

				<h2><?php _e( 'Submission Details', 'pressbooks-selfe' ); ?></h2>
				<p><?php _e( 'This information is required to complete your Indie Author Project submission and will be saved in case you need to resubmit your book at a later date.', 'pressbooks-selfe' ); ?>
					<?php $current_user = wp_get_current_user(); ?>
					<table class="form-table" role="none">
						<?php
						$this->displayTextInput( 'vendor', ( isset( $options['vendor'] ) ) ? $options['vendor'] : '', __( 'Vendor', 'pressbooks-selfe' ), __( 'Enter the full legal name of the book&rsquo;s vendor.', 'pressbooks-selfe' ), false );
						$this->displaySelect( 'format', $formats, ( isset( $options['format'] ) ) ? $options['format'] : '', __( 'Format*', 'pressbooks-selfe' ), $format_description, true, false, $format_disabled );
						$this->displayTextInput( 'submitter_first_name', ( isset( $options['submitter_first_name'] ) ) ? $options['submitter_first_name'] : $current_user->first_name, __( 'First Name*', 'pressbooks-selfe' ), null, true );
						$this->displayTextInput( 'submitter_last_name', ( isset( $options['submitter_last_name'] ) ) ? $options['submitter_last_name'] : $current_user->last_name, __( 'Last Name*', 'pressbooks-selfe' ), null, true );
						$this->displayTextInput( 'submitter_email', ( isset( $options['submitter_email'] ) ) ? $options['submitter_email'] : $current_user->user_email, __( 'Email*', 'pressbooks-selfe' ), null, true );
						$this->displayTextInput( 'submitter_library_other', ( isset( $options['submitter_library_other'] ) ) ? $options['submitter_library_other'] : get_blog_option( 1, 'blogname' ), __( 'Library*', 'pressbooks-selfe' ), null, true );
						?>
						<tr>
							<th scope="row"><label for="submitter_country"><?php _e( 'Country*', 'pressbooks-selfe' ); ?></label></th>
							<td><select id="submitter_country" name="submitter_country" class="crs-country" data-default-option="<?php _e( 'Select country&hellip;' ); ?>"
										data-region-id="submitter_region" data-value="shortcode"
										data-default-value="<?php echo ( isset( $options['submitter_country'] ) ) ? $options['submitter_country'] : ''; ?>" required
										aria-required="true"></select></td>
						</tr>
						<tr>
							<th scope="row"><label for="submitter_region"><?php _e( 'Region*', 'pressbooks-selfe' ); ?></label></th>
							<td><select id="submitter_region" name="submitter_region" class="crs-region" data-blank-option="--"
										data-default-option="<?php _e( 'Select region&hellip;' ); ?>" data-value="shortcode"
										data-default-value="<?php echo ( isset( $options['submitter_region'] ) ) ? $options['submitter_region'] : ''; ?>" required
										aria-required="true"></select></td>
						</tr>
				</table>
				<p>
					<?php
					// @codingStandardsIgnoreStart
					printf(
						__( 'By clicking "%1$s" below, you agree to the %2$s.', 'pressbooks-selfe' ),
						__( 'Submit to the Indie Author Project', 'pressbooks-selfe' ),
						sprintf(
							'<a href="%s">%s</a>',
							'https://indieauthorproject.librariesshare.com/indieauthor/terms.html',
							__( 'IAP Terms and Conditions', 'pressbooks-selfe' )
						)
					);
					// @codingStandardsIgnoreEnd
					?>
				</p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Submit to the Indie Author Project', 'pressbooks-selfe' ); ?>"></p>
			</form>
			<pre></pre>
		</div>
		<?php
	}

	public function displayTextInput( $name, $value, $label, $description = null, $required = false, $class = 'regular-text' ) {
		if ( is_array( $value ) ) {
			$contributors_handler = new Contributors();
			$contributors = [];
			foreach ( $value as $contributor ) {
				$contributors[] = $contributors_handler->personalName( $contributor['slug'] );
			}
			$value = \Pressbooks\Utility\implode_add_and( ';', $contributors );
		}
		printf(
			'<tr><th scope="row"><label for="%s">%s</label></th><td><input type="text" name="%s" id="%s" value="%s" class="%s"%s aria-required="%s" />%s</td></tr>',
			$name,
			$label,
			$name,
			$name,
			$value,
			$class,
			( $required ) ? ' required' : '',
			$required,
			( $description ) ? sprintf( '<p class="description">%s</p>', $description ) : ''
		);
	}

	public function displayTextInputRows( $name, $contributors, $label, $description = null, $class = 'regular-text' ) {
		$rows = '';

		if ( $contributors ) {
			$i = 0;
			$contributors_handler = new Contributors();
			foreach ( $contributors as $contributor ) {
				$rows .= sprintf(
					'<div class="row"><input type="text" readonly name="%s[ ]" value="%s" class="%s" /></div>',
					$name,
					isset( $contributor['slug'] ) ? $contributors_handler->personalName( $contributor['slug'] ) : $contributor,
					$class
				);
				$i++;
			}
		} else {
			$rows = sprintf(
				'<div class="row"><input type="text" name="%s[ ]" value="" class="%s" /></div>',
				$name,
				$class
			);
		}

		printf(
			'<tr><th scope="row"><label for="%s">%s</label></th><td id="%s">%s%s</td></tr>',
			$name,
			$label,
			$name,
			$rows,
			( $description ) ? sprintf( '<p class="description">%s</p>', $description ) : ''
		);
	}

	public function displayTextArea( $name, $value, $label, $description = null, $required = false ) {
		printf(
			'<tr><th scope="row"><label for="%s">%s</label></th><td><textarea name="%s" id="%s" rows="5" cols="30"%s aria-required="%s" />%s</textarea>%s</td></tr>',
			$name,
			$label,
			$name,
			$name,
			( $required ) ? ' required' : '',
			$required,
			$value,
			( $description ) ? sprintf( '<p class="description">%s</p>', $description ) : ''
		);
	}

	public function displaySelect( $name, $options, $values, $label, $description = null, $required = false, $multiple = false, $disabled = false ) {
		$choices = '';

		foreach ( $options as $key => $value ) {
			if ( $multiple ) {
				$choices .= sprintf( '<option value="%s" %s>%s</option>', $key, ( in_array( $key, $values, true ) ) ? 'selected' : '', $value );
			} else {
				$choices .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $values, $key, false ), $value );
			}
		}
		printf(
			'<tr><th scope="row"><label for="%s">%s</label></th><td><select name="%s%s" id="%s"%s%s%s aria-required="%s">%s</select>%s</td></tr>',
			$name,
			$label,
			$name,
			( $multiple ) ? '[ ]' : '',
			$name,
			( $multiple ) ? ' multiple' : '',
			( $disabled ) ? ' disabled' : '',
			( $required ) ? ' required' : '',
			$required,
			$choices,
			( $description ) ? sprintf( '<p class="description">%s</p>', $description ) : ''
		);
	}

	/**
	 * Use options over metadata
	 *
	 * @param array $metadata
	 * @param array $options
	 *
	 * @return array
	 */
	public function overrideMetadataWithOptions( $metadata, $options ) {
		$contributors_options_map = [
			'pb_author' => 'pb_authors',
			'pb_contributing_authors' => 'pb_contributors',
			'pb_editor' => 'pb_editors',
			'pb_translator' => 'pb_translators',
		];
		foreach ( $contributors_options_map as $key_option => $key_metadata ) {
			if ( isset( $metadata[ $key_metadata ] ) ) {
				$metadata[ $key_option ] = $metadata[ $key_metadata ];
			}
		}

		foreach ( $options as $key => $val ) {
			if ( strpos( $key, 'pb_' ) !== 0 || array_key_exists( $key, $metadata ) ) {
				continue; // Don't use
			} elseif ( $key === 'pb_bisac_subject' ) {
				$metadata[ $key ] = implode( ', ', $val );
			} else {
				$metadata[ $key ] = $val;
			}
		}
		return $metadata;
	}

	/**
	 * @param array $data
	 */
	public function updateOption( $data ) {
		$option = [];
		foreach ( $data as $key => $value ) {
			if ( strpos( $key, 'pb_' ) === 0 ) {
				if (
				in_array(
					$key,
					[
						'pb_title',
						'pb_subtitle',
						'pb_author',
						'pb_series_number',
						'pb_ebook_isbn',
						'pb_publisher',
						'pb_about_50',
					],
					true
				)
				) {
					if ( $key === 'pb_ebook_isbn' ) {
						$isbn = new \Isbn\Isbn();
						if ( $isbn->validation->isbn( $value ) === true ) {
							try {
								$value = $isbn->hyphens->removeHyphens( $value );
							} catch ( \Exception $e ) {
								$value = '';
							}
						} else {
							$value = '';
						}
					}
					$option[ $key ] = sanitize_text_field( $value );
				} elseif (
				in_array(
					$key, [ 'pb_publication_date' ], true
				)
				) {
					$option[ $key ] = sanitize_text_field( strtotime( $value ) );
				} elseif (
				in_array(
					$key,
					[
						'pb_contributing_authors',
						'pb_editor',
						'pb_translator',
						'pb_bisac_subject',
					],
					true
				)
				) {
					$option[ $key ] = array_map( 'sanitize_text_field', $value );
				} elseif (
				in_array(
					$key,
					[
						'pb_language',
						'pb_audience',
					],
					true
				)
				) {
					$option[ $key ] = $value;
				}
			} else {
				if (
				in_array(
					$key,
					[
						'vendor',
						'format',
						'submitter_first_name',
						'submitter_last_name',
						'submitter_email',
						'submitter_library_other',
						'submitter_country',
						'submitter_region',
					],
					true
				)
				) {
					$option[ $key ] = sanitize_text_field( $value );
				}
			}
		}
		update_option( self::OPTION, $option );
	}

	public function validateSubmission( $data ) {
		$required = [
			'pb_title' => __( 'Title', 'pressbooks-selfe' ),
			'pb_publication_date' => __( 'Publication Date', 'pressbooks-selfe' ),
			'pb_audience' => __( 'Target Audience', 'pressbooks-selfe' ),
			'pb_about_50' => __( 'Description', 'pressbooks-selfe' ),
			'pb_language' => __( 'Language', 'pressbooks-selfe' ),
			'pb_bisac_subject' => __( 'Categories', 'pressbooks-selfe' ),
			'pb_author' => __( 'Author', 'pressbooks-selfe' ),
			'submitter_first_name' => __( 'First Name', 'pressbooks-selfe' ),
			'submitter_last_name' => __( 'Last Name', 'pressbooks-selfe' ),
			'submitter_email' => __( 'Email', 'pressbooks-selfe' ),
			'submitter_library_other' => __( 'Library', 'pressbooks-selfe' ),
			'submitter_country' => __( 'Country', 'pressbooks-selfe' ),
			'submitter_region' => __( 'Region', 'pressbooks-selfe' ),
			'format' => __( 'Format', 'pressbooks-selfe' ),
		];

		$missing = [];

		foreach ( $required as $key => $name ) {
			if ( ! isset( $data[ $key ] ) || empty( $data[ $key ] ) ) {
				$missing[] = $name;
			}
		}

		if ( ! empty( $missing ) ) {
			$list = '';
			foreach ( $missing as $field ) {
				$list .= sprintf( '<li>%s</li>', $field );
			}
			printf(
				'<div id="message" role="alert" class="error"><p>%s</p><ul>%s</ul></div>',
				__( 'You did not complete the following required field(s):', 'pressbooks-selfe' ),
				$list
			);
			return false;
		}
		return true;
	}

	public function postData( $exports, $format ) {
		$base_uri = apply_filters( 'pb_selfe_base_uri', 'https://stip.biblioboard.com' );
		$endpoint = '/projects/' . \Pressbooks_Selfe\Data\get_project_id() . '/submissions/legacy';

		$options = get_option( self::OPTION, [] );
		$metadata = $this->overrideMetadataWithOptions( Book::getBookInformation( null, false ), $options );
		$data = \Pressbooks_Selfe\Metadata\convert_to_selfe( $metadata, $options );
		if ( ! $data ) {
			printf(
				'<div id="message" role="alert" class="notice notice notice-error"><p>%s</p></div>',
				__( 'Error: Your submission failed! Please verify the authors, contributors, editors or translators are present in the book.', 'pressbooks-selfe' )
			);
			return false;
		}

		$file = Export::getExportFolder() . $exports[ $format ];
		$request = [
			'multipart' => [
				[
					'name' => 'submission-data',
					'contents' => wp_json_encode( $data ),
					'headers' => [
						'Content-Type' => 'application/json',
					],
				],
				[
					'name' => 'submission-file',
					'contents' => fopen( $file, 'r' ),
				],
			],
		];

		$client = new Client(
			[
				'base_uri' => $base_uri,
				'http_errors' => false,
			]
		);

		$response = $client->request( 'POST', $endpoint, $request );
		$status = $response->getStatusCode();
		if ( $status > 300 ) {
			printf(
				'<div id="message" role="alert" class="notice notice notice-error"><p>%s</p></div>',
				/* translators: %s: Submission status */
				sprintf( __( 'Error %s: Your submission failed! Please verify all fields and try again.', 'pressbooks-selfe' ), $status )
			);
		} else {
			printf(
				'<div id="message" role="status" class="notice notice notice-success"><p>%s</p></div>',
				__( 'Your submission was successful! You will receive a confirmation shortly.', 'pressbooks-selfe' )
			);
		}
	}
}
