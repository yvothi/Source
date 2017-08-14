<?php
/*
Plugin Name: 	Noo Jobs Import
Plugin URI: 	https://www.nootheme.com
Description: 	Export any post type to a XML file. Edit the exported data WPJobManager, and then re-import it later using Noo Jobs Import.
Version: 		1.1.0
Author: 		Nootheme
Author URI: 	https://www.nootheme.com
*/

class Noo_Import {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'create_menu_sidebar' ) );

		if ( isset($_GET['page']) &&  ( $_GET['page'] == 'noo-import' || $_GET['page'] == 'noo-wpjobmanager' || $_GET['page'] == 'noo-indeed' ) ) : 
			// -- Load style
				add_action( 'admin_enqueue_scripts', array( $this, 'load_enqueue_style' ) );

			// -- Load script
				add_action( 'admin_enqueue_scripts', array( $this, 'load_enqueue_script' ) );
		endif;
		
		// -- Load event ajax
			add_action( 'wp_ajax_load_xml', array( $this, 'load_xml' ) );
		
		// -- WP All Import add-on
			if( is_plugin_active( 'wp-all-import/plugin.php' ) ) {
				require 'includes/wp-all-import-jobmonster.php';
			}
	}

	public function create_menu_sidebar() {
		// -- Create parent menu
			add_menu_page( __( 'Noo Jobs Import', 'noo' ), __( 'Noo Jobs Import', 'noo' ), 'manage_options', 'noo-import', array( $this, 'main_content' ), 'dashicons-migrate', 76 );

		// -- Create child menu
			// -- Import WPJobManager
				add_submenu_page( 'noo-import', 'Import jobs from WP Job Manager', 'from WPJobManager', 'manage_options', 'noo-wpjobmanager', array( $this, 'main_wpjobmanager' ) );

			// -- Import Indeed
				add_submenu_page( 'noo-import', 'Import from Indeed.com', 'from Indeed.com', 'manage_options', 'noo-indeed', array( $this, 'main_indeed' ) );

	}

	public function load_enqueue_style() {

		wp_register_style( 'noo-css', plugin_dir_url( __FILE__ ) . 'assets/css/noo-import.css' );
		wp_enqueue_style( 'noo-css' );

	}

	public function load_enqueue_script() {

		wp_register_script( 'noo-script', plugin_dir_url( __FILE__ ) . 'assets/js/noo-script.js', array( 'jquery'), null, true );
		
		wp_enqueue_script( 'noo-script' );

		// -- Load ajax
			wp_localize_script( 'noo-script', 'noo_ajax',
	            array( 
	            	'ajax_url' => admin_url( 'admin-ajax.php' )
	            )
	        );

	}

	public function main_content() {
		?>
		<div class="container">
			<h2 class="page-title"><?php _e( 'Import Jobs', 'noo' ); ?></h2>
			<div class="row">
				<div class="col-md-6">
				    <a href="<?php echo admin_url( 'admin.php' ).'?page=noo-wpjobmanager'; ?>" class="btn btn-primary" ><?php _e( 'From WP Job Manager' , 'noo' ); ?></a>
				    <p class="help-block"><?php _e( 'Import from your old website with WP Job Manager plugin', 'noo' ); ?></p>
				</div>
				<div class="col-md-6">
				    <a href="<?php echo admin_url( 'admin.php' ).'?page=noo-indeed'; ?>" class="btn btn-warning" ><?php _e( 'From Indeed.com' , 'noo' ); ?></a>
				    <p class="help-block"><?php _e( 'Download jobs from Indeed.com site', 'noo' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	public function main_wpjobmanager() {
		$user = get_users( 'orderby=nicename' );
		error_reporting(0);
		?>
		<div class="container">
			<h2 class="page-title"><?php _e( 'Import jobs from WP Job Manager', 'noo' ); ?></h2>
			<?php $this->processing_data(); ?>
			<form method="POST" id="noo_form" enctype="multipart/form-data" class="row">
				<div class="col-md-4">
					<div class="form-group">
					    <label for="upload_xml"><?php _e( 'Upload .XML file' , 'noo' ); ?></label>
					    <input type="file" name="file" id="upload_xml">
					    <p class="help-block"><?php _e( 'If you don\'t have any .xml file please use the Export function on your old WP Job Manager site and export a XML file for Jobs data', 'noo' ); ?></p>
					</div>
				</div>
				<div style="border-left: 1px solid #ddd" class="col-md-8 container">
					<div class="form-group row">
						<label class="col-sm-4 control-label" for="noo_job_author" ><?php _e( 'Job Author' , 'noo' ); ?></label>
						<div class="col-sm-8">
							<select name="author" id="noo_job_author" class="form-control">
								<option value="auto"><?php _e( 'Automatically create new Authors', 'noo' ); ?></option>
								<?php
									foreach ($user as $info) {
										echo "<option value='{$info->ID}'>{$info->display_name}</option>";
									}
								?>
							</select>
							<p class="help-block"><?php _e('You can choose one author to import jobs to or create new authors base on the data from your old site', 'noo'); ?></p>
						</div>
					</div>
					<script type="text/javascript">
					jQuery(document).ready(function($) {
						// Checking value option
							if( $('#noo_job_author').val() != 'auto' ) $('.noo_hide').hide();
							else $('.noo_hide').show();

						// Event
							$('#noo_job_author').change(function(event) {
								if( $(this).val() != 'auto' ) $('.noo_hide').hide();
								else $('.noo_hide').show();
							});

					});
					</script>
					<div class="form-group row noo_hide">
						<label class="col-sm-4 control-label" for="noo_pws"><?php _e( 'Author Password' , 'noo' ); ?></label>
						<div class="col-sm-8">
							<input type="password" style="width: 100%" class="form-control" id="noo_pws" name="pws" value="<?php isset($_POST['pws']) ? $_POST['pws'] : '' ?>" placeholder="<?php _e( 'The password for the new Authors, default is 123456', 'noo' ); ?>" />
						</div>
					</div>
					<div class="form-group row">
					    <div class="col-sm-offset-4 col-sm-8">
					      	<button style="margin-top: 10px;" name="noo_import" type="submit" class="btn btn-primary noo_import"><?php echo __('Submit', 'noo'); ?></button>
					    </div>
					</div>
				</div>
				
			</form>
		</div>
		<?php
	}

	/**
	 * Display main page import
	 *
	 * @package 	Noo Jobs Import
	 * @author 		KENT <tuanlv@vietbrain.com>
	 * @version 	1.1
	 */
	public function main_indeed() {
		$user = get_users( 'orderby=nicename' );

		if ( isset( $_POST['import_indeed'] ) ) {
			$options = array(
				"publisher"             => esc_attr( $_POST['publisher'] ),
				"q"         		=> esc_attr( $_POST['q'] ),
				"l"         		=> esc_attr( $_POST['l'] ),
				"co"        		=> esc_attr( $_POST['co'] ),
				"jt"        		=> esc_attr( $_POST['jt'] ),
				"author"    		=> esc_attr( $_POST['author'] ),
				"job_category"    	=> esc_attr( $_POST['job_category'] ),
				"application_url"       => esc_attr( $_POST['application_url'] ),
				"start"     		=> esc_attr( $_POST['start'] ),
				"limit"     		=> esc_attr( $_POST['limit'] ),
			);
			update_option( 'noo_import_jobs_from_indeed', $options );
		} else {
			$options = get_option( 'noo_import_jobs_from_indeed' );
		}
		?>
		<div class="container">
			<h2 class="page-title">
				<?php _e( 'Import jobs from Indeed.com', 'noo' ); ?>
			</h2>

			<?php $this->processing_data(); ?>
				<form method="POST" style="max-width: 650px" class="main_import form-horizontal" enctype="multipart/form-data">
				
					<div class="form-group">
					    <label for="public_id" class="col-sm-3 control-label"><?php _e( 'Publisher ID', 'noo' ); ?></label>
					    <div class="col-sm-9">
					      	<input type="text" class="form-control" id="public_id" name="publisher" placeholder="<?php _e( 'Enter your ID', 'noo' ); ?>" value="<?php echo ( isset( $options['publisher'] ) ? $options['publisher'] : '' ); ?>"/>
					      	<p class="help-block"><?php _e( 'To import jobs from Indeed you will need a publisher account. Obtain it <a href="https://ads.indeed.com/jobroll/signup" title="" target="_blank">here</a>.', 'noo' ); ?></p>
					    </div>
					</div>

					<div class="form-group">
					    <label for="keyword" class="col-sm-3 control-label"><?php _e( 'Keyword', 'noo' ); ?></label>
					    <div class="col-sm-9">
					      	<input type="text" class="form-control" id="keyword" name="q" placeholder="<?php _e( 'Enter your keyword', 'noo' ); ?>" value="<?php echo ( isset( $options['q'] ) ? $options['q'] : '' ); ?>"/>
					      	<p class="help-block"><?php _e( 'The keyword to search Indeed jobs.', 'noo' ); ?></p>
					    </div>
					</div>

					<div class="form-group">
					    <label for="location" class="col-sm-3 control-label"><?php _e( 'Job Location', 'noo' ); ?></label>
					    <div class="col-sm-9">
					      	<input type="text" class="form-control" id="location" name="l" placeholder="<?php _e( 'Enter a location', 'noo' ); ?>" value="<?php echo ( isset( $options['l'] ) ? $options['l'] : '' ); ?>"/>
					      	<p class="help-block"><?php _e( 'The location to filter Indeed jobs.', 'noo' ); ?></p>
					    </div>
					</div>

					<div class="form-group">
					    <label for="country" class="col-sm-3 control-label"><?php _e( 'Job Country', 'noo' ); ?></label>
					    <div class="col-sm-9">
					    	<select name="co" id="country">
					    		<?php $co = isset( $options['co'] ) ? $options['co'] : ''; ?>
					      		<option value="" <?php selected( $co, '' ); ?>><?php _e( '- Select a country -', 'noo' ); ?></option>
								<option value="aq" <?php selected( $co, 'aq' ); ?>><?php _e('Antarctica', 'noo'); ?></option>
								<option value="ar" <?php selected( $co, 'ar' ); ?>><?php _e('Argentina', 'noo'); ?></option>
								<option value="au" <?php selected( $co, 'au' ); ?>><?php _e('Australia', 'noo'); ?></option>
								<option value="at" <?php selected( $co, 'at' ); ?>><?php _e('Austria', 'noo'); ?></option>
								<option value="bh" <?php selected( $co, 'bh' ); ?>><?php _e('Bahrain', 'noo'); ?></option>
								<option value="be" <?php selected( $co, 'be' ); ?>><?php _e('Belgium', 'noo'); ?></option>
								<option value="br" <?php selected( $co, 'br' ); ?>><?php _e('Brazil', 'noo'); ?></option>
								<option value="ca" <?php selected( $co, 'ca' ); ?>><?php _e('Canada', 'noo'); ?></option>
								<option value="cl" <?php selected( $co, 'cl' ); ?>><?php _e('Chile', 'noo'); ?></option>
								<option value="cn" <?php selected( $co, 'cn' ); ?>><?php _e('China', 'noo'); ?></option>
								<option value="co" <?php selected( $co, 'co' ); ?>><?php _e('Colombia', 'noo'); ?></option>
								<option value="cr" <?php selected( $co, 'cr' ); ?>><?php _e('Costa Rica', 'noo'); ?></option>
								<option value="cz" <?php selected( $co, 'cz' ); ?>><?php _e('Czech Republic', 'noo'); ?></option>
								<option value="dk" <?php selected( $co, 'dk' ); ?>><?php _e('Denmark', 'noo'); ?></option>
								<option value="ec" <?php selected( $co, 'ec' ); ?>><?php _e('Ecuador', 'noo'); ?></option>
								<option value="eg" <?php selected( $co, 'eg' ); ?>><?php _e('Egypt', 'noo'); ?></option>
								<option value="fi" <?php selected( $co, 'fi' ); ?>><?php _e('Finland', 'noo'); ?></option>
								<option value="fr" <?php selected( $co, 'fr' ); ?>><?php _e('France', 'noo'); ?></option>
								<option value="de" <?php selected( $co, 'de' ); ?>><?php _e('Germany', 'noo'); ?></option>
								<option value="gr" <?php selected( $co, 'gr' ); ?>><?php _e('Greece', 'noo'); ?></option>
								<option value="hk" <?php selected( $co, 'hk' ); ?>><?php _e('Hong Kong', 'noo'); ?></option>
								<option value="hu" <?php selected( $co, 'hu' ); ?>><?php _e('Hungary', 'noo'); ?></option>
								<option value="in" <?php selected( $co, 'in' ); ?>><?php _e('India', 'noo'); ?></option>
								<option value="id" <?php selected( $co, 'id' ); ?>><?php _e('Indonesia', 'noo'); ?></option>
								<option value="ie" <?php selected( $co, 'ie' ); ?>><?php _e('Ireland', 'noo'); ?></option>
								<option value="il" <?php selected( $co, 'il' ); ?>><?php _e('Israel', 'noo'); ?></option>
								<option value="it" <?php selected( $co, 'it' ); ?>><?php _e('Italy', 'noo'); ?></option>
								<option value="jp" <?php selected( $co, 'jp' ); ?>><?php _e('Japan', 'noo'); ?></option>
								<option value="kw" <?php selected( $co, 'kw' ); ?>><?php _e('Kuwait', 'noo'); ?></option>
								<option value="lu" <?php selected( $co, 'lu' ); ?>><?php _e('Luxembourg', 'noo'); ?></option>
								<option value="my" <?php selected( $co, 'my' ); ?>><?php _e('Malaysia', 'noo'); ?></option>
								<option value="mx" <?php selected( $co, 'mx' ); ?>><?php _e('Mexico', 'noo'); ?></option>
								<option value="ma" <?php selected( $co, 'ma' ); ?>><?php _e('Morocco', 'noo'); ?></option>
								<option value="nl" <?php selected( $co, 'nl' ); ?>><?php _e('Netherlands', 'noo'); ?></option>
								<option value="nz" <?php selected( $co, 'nz' ); ?>><?php _e('New Zealand', 'noo'); ?></option>
								<option value="ng" <?php selected( $co, 'ng' ); ?>><?php _e('Nigeria', 'noo'); ?></option>
								<option value="no" <?php selected( $co, 'no' ); ?>><?php _e('Norway', 'noo'); ?></option>
								<option value="om" <?php selected( $co, 'om' ); ?>><?php _e('Oman', 'noo'); ?></option>
								<option value="pk" <?php selected( $co, 'pk' ); ?>><?php _e('Pakistan', 'noo'); ?></option>
								<option value="pa" <?php selected( $co, 'pa' ); ?>><?php _e('Panama', 'noo'); ?></option>
								<option value="pe" <?php selected( $co, 'pe' ); ?>><?php _e('Peru', 'noo'); ?></option>
								<option value="ph" <?php selected( $co, 'ph' ); ?>><?php _e('Philippines', 'noo'); ?></option>
								<option value="pl" <?php selected( $co, 'pl' ); ?>><?php _e('Poland', 'noo'); ?></option>
								<option value="pt" <?php selected( $co, 'pt' ); ?>><?php _e('Portugal', 'noo'); ?></option>
								<option value="qa" <?php selected( $co, 'qa' ); ?>><?php _e('Qatar', 'noo'); ?></option>
								<option value="ro" <?php selected( $co, 'ro' ); ?>><?php _e('Romania', 'noo'); ?></option>
								<option value="ru" <?php selected( $co, 'ru' ); ?>><?php _e('Russia', 'noo'); ?></option>
								<option value="sa" <?php selected( $co, 'sa' ); ?>><?php _e('Saudi Arabia', 'noo'); ?></option>
								<option value="sg" <?php selected( $co, 'sg' ); ?>><?php _e('Singapore', 'noo'); ?></option>
								<option value="za" <?php selected( $co, 'za' ); ?>><?php _e('South Africa', 'noo'); ?></option>
								<option value="kr" <?php selected( $co, 'kr' ); ?>><?php _e('South Korea', 'noo'); ?></option>
								<option value="es" <?php selected( $co, 'es' ); ?>><?php _e('Spain', 'noo'); ?></option>
								<option value="se" <?php selected( $co, 'se' ); ?>><?php _e('Sweden', 'noo'); ?></option>
								<option value="ch" <?php selected( $co, 'ch' ); ?>><?php _e('Switzerland', 'noo'); ?></option>
								<option value="tw" <?php selected( $co, 'tw' ); ?>><?php _e('Taiwan', 'noo'); ?></option>
								<option value="th" <?php selected( $co, 'th' ); ?>><?php _e('Thailand', 'noo'); ?></option>
								<option value="tr" <?php selected( $co, 'tr' ); ?>><?php _e('Turkey', 'noo'); ?></option>
								<option value="ua" <?php selected( $co, 'ua' ); ?>><?php _e('Ukraine', 'noo'); ?></option>
								<option value="ae" <?php selected( $co, 'ae' ); ?>><?php _e('United Arab Emirates', 'noo'); ?></option>
								<option value="gb" <?php selected( $co, 'gb' ); ?>><?php _e('United Kingdom', 'noo'); ?></option>
								<option value="us" <?php selected( $co, 'us' ); ?>><?php _e('United States', 'noo'); ?></option>
								<option value="uy" <?php selected( $co, 'uy' ); ?>><?php _e('Uruguay', 'noo'); ?></option>
								<option value="ve" <?php selected( $co, 've' ); ?>><?php _e('Venezuela', 'noo'); ?></option>
								<option value="vn" <?php selected( $co, 'vn' ); ?>><?php _e('Vietnam', 'noo'); ?></option>
					    	</select>
					      	<p class="help-block"><?php _e( 'The country to filter Indeed jobs.', 'noo' ); ?></p>
					    </div>
					</div>

					<div class="form-group">
						<label for="jt" class="col-sm-3 control-label"><?php _e( 'Job Type', 'noo' ); ?></label>
						<div class="col-sm-9">
							<select name="jt" id="jt" class="form-control">
					    		<?php $jt = isset( $options['jt'] ) ? $options['jt'] : ''; ?>
								<option value="" <?php selected( $jt, '' ); ?>><?php _e( '- All types -', 'noo' ); ?></option>
								<option value="internship|Internship" <?php selected( $jt, 'internship|Internship' ); ?>><?php _e( 'Internship', 'noo' ); ?></option>
								<option value="contract|Contract" <?php selected( $jt, 'contract|Contract' ); ?>><?php _e( 'Contract', 'noo' ); ?></option>
								<option value="parttime|Part time" <?php selected( $jt, 'parttime|Part time' ); ?>><?php _e( 'Part time', 'noo' ); ?></option>
								<option value="temporary|Temporary" <?php selected( $jt, 'temporary|Temporary' ); ?>><?php _e( 'Temporary', 'noo' ); ?></option>
								<option value="permanent|Permanent" <?php selected( $jt, 'permanent|Permanent' ); ?>><?php _e( 'Permanent', 'noo' ); ?></option>
								<option value="commission|Commission" <?php selected( $jt, 'commission|Commission' ); ?>><?php _e( 'Commission', 'noo' ); ?></option>
								<option value="new_grad|New-Grad" <?php selected( $jt, 'new_grad|New-Grad' ); ?>><?php _e( 'New-Grad', 'noo' ); ?></option>
								<option value="fulltime|Full time" <?php selected( $jt, 'fulltime|Full time' ); ?>><?php _e( 'Full time', 'noo' ); ?></option>
							</select>
							<p class="help-block"><?php _e( 'The type of jobs to import.', 'noo' ); ?></p>
						</div>
					</div>
					<hr/><br/>

					<div class="form-group">
						<label for="author" class="col-sm-3 control-label"><?php _e( 'Link Application to Indeed', 'noo' ); ?></label>
						<div class="col-sm-9">
							<input type="checkbox" name="application_url" id="application_url" class="form-control" <?php checked( isset( $options['application_url'] ) && $options['application_url'] ); ?>/>
							<p class="help-block"><?php _e( 'Use original Indeed link as the application URL for imported jobs.', 'noo' ); ?></p>
						</div>
					</div>
					<div class="form-group">
						<label for="author" class="col-sm-3 control-label"><?php _e( 'Jobs author', 'noo' ); ?></label>
						<div class="col-sm-9">
							<select name="author" id="author" class="form-control">
					    		<?php $author = isset( $options['author'] ) ? $options['author'] : ''; ?>
								<?php foreach ($user as $info) : ?>
									<option value="<?php echo $info->ID; ?>" <?php selected( $author, $info->ID ); ?> ><?php echo $info->display_name; ?></option>
								<?php endforeach; ?>
							</select>
							<p class="help-block"><?php _e( 'Select an user for Job Author.', 'noo' ); ?></p>
						</div>
					</div>
					<div class="form-group">
						<label for="job_category" class="col-sm-3 control-label"><?php _e( 'Job Category', 'noo' ); ?></label>
						<div class="col-sm-9">
							<select name="job_category" id="job_category" class="form-control">
					    		<?php $job_category = isset( $options['job_category'] ) ? $options['job_category'] : ''; ?>
								<option value="none"><?php echo __('- None -', 'noo' ); ?></option>
								<?php 
									$terms = get_terms( 'job_category', array( 'hide_empty' => false ) );
									if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
									    foreach ( $terms as $term ) : ?>
									       	<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $job_category, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
									    <?php endforeach;
									endif;
								?>
							</select>
							<p class="help-block"><?php _e( 'Select a category for imported jobs.', 'noo' ); ?></p>
						</div>
					</div>

					<div class="form-group">
					    <label for="start" class="col-sm-3 control-label"><?php _e( 'Start job index', 'noo' ); ?></label>
					    <div class="col-sm-9">
					      	<input type="text" class="form-control" id="start" name="start" placeholder="" value="<?php echo ( isset( $options['start'] ) ? $options['start'] : '' ); ?>"/>
					      	<p class="help-block"><?php _e( 'The index of job to start import from.', 'noo' ); ?></p>
					    </div>
					</div>

					<div class="form-group">
					    <label for="limit" class="col-sm-3 control-label"><?php _e( 'Max Number of job', 'noo' ); ?></label>
					    <div class="col-sm-9">
					      	<input type="text" class="form-control" id="limit" name="limit" placeholder="" value="<?php echo ( isset( $options['limit'] ) ? $options['limit'] : '' ); ?>"/>
					      	<p class="help-block"><?php _e( 'The maximum number of jobs to import.', 'noo' ); ?></p>
					    </div>
					</div>
					<div class="form-group hidden">
						<input type="hidden" class="form-control" id="user_agent" name="useragent" value="<?php echo $_SERVER['HTTP_USER_AGENT'] ?>" />
						<input type="hidden" class="form-control" id="ip" name="userip" value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" />
					</div>
					<button style="margin-left: 133px;" type="submit" name="import_indeed" class="col-md-offset-3 btn btn-success"><?php _e( 'Import Job', 'noo' ); ?></button>

				</form>
			<!-- </div> -->
		</div>
		<?php

	}

	/**
	 * Function process data when submit
	 *
	 * @package 	Noo Jobs Import
	 * @author 		KENT <tuanlv@vietbrain.com>
	 * @version 	1.1
	 */
	public function processing_data() {
		$i = 0;
		if ( isset( $_POST['noo_import'] ) ) :
			$upload_dir = wp_upload_dir( );
			$file = $upload_dir['path'] . '/' . basename($_FILES['file']['name']);
			if( move_uploaded_file($_FILES['file']['tmp_name'], $file) ) :

				$xml = simplexml_load_file( $file, 'SimpleXMLElement', LIBXML_NOCDATA) or die("Error: Cannot read file");
				
				foreach($xml->channel->item as $item) :

					$post_item['post_title']   = (string)$item->title; 
					$post_item['post_type']    = esc_attr( 'noo_job' ); 
					$post_item['post_status']  = esc_attr( 'publish' ); 
					$post_item['post_content'] = (string)$item->children( 'content', true )->encoded;
					$post_item['job_category'] = array();
					$post_item['job_location'] = array();
					$post_item['job_type']     = array();

				  	// --- Process Category data
				  		if ( !empty( $item->category ) ) :
				  			$i_cat = 0;
				  			$item_category = esc_attr( $item->category );
					  		foreach ($item_category as $category) :
					  			if ( $category->attributes()->domain == 'job_listing_category' ) :

					  				$cat = (string)$item->category[$i_cat];
						  			$job_cat = get_term_by( 'name', $cat, 'job_category' );
						  			
						  			if ( $job_cat ) :

						  				$post_item['job_category'] = array_merge( $post_item['job_category'], (array)$job_cat->term_id);
						  			
						  			else :

						  				$cat_id = wp_insert_term( $cat, 'job_category', array() );
						  				$post_item['job_category'] = array_merge( $post_item['job_category'], (array)$cat_id);

						  			endif;
						  		elseif ( $category->attributes()->domain == 'job_listing_type' ) :

					  				$cat = (string)$item->category[$i_cat];
						  			$job_cat = get_term_by( 'name', $cat, 'job_type' );
						  			
						  			if ( $job_cat ) :

						  				$post_item['job_type'] = array_merge( $post_item['job_type'], (array)$job_cat->term_id);
						  			
						  			else :

						  				$cat_id = wp_insert_term( $cat, 'job_type', array() );
						  				$post_item['job_type'] = array_merge( $post_item['job_type'], (array)$cat_id);

						  			endif;
					  			endif;
					  			$i_cat++;
					  		endforeach;

					  	endif;
			  		// -- process meta data
						$xml_meta = $item->children("wp", true)->postmeta;
						foreach ($xml_meta as $key => $value) {
							
							// === Set default

							if( (string)$value->meta_key == '_job_location' ) : // -- location
								$job_location = get_term_by( 'name', (string)$value->meta_value, 'job_location' );
								
								if ( $job_location ) :

				  					$post_item['job_location'] = array_merge( $post_item['job_location'], (array)$job_location->term_id);
					  			
					  			else :

					  				$location_id = wp_insert_term( (string)$value->meta_value, 'job_location', array() );
					  				$post_item['job_location'] = array_merge( $post_item['job_location'], (array)$location_id);
					  			
					  			endif;

							endif; 


							if( (string)$value->meta_key == '_company_name' ) : // -- company

								$job_company = post_exists( (string)$value->meta_value);
								if ( $job_company != 0 ) :

				  					$post_item['job_company'] = $job_company;
					  			
					  			else :

					  				$args_company = array(
										'post_title'  => (string)$value->meta_value,
										'post_type'   => esc_attr( 'noo_company' ),
										'post_status' => esc_attr( 'publish' )
					  				);
					  				$post_item['job_company'] = wp_insert_post( $args_company );
					  			
					  			endif;

							endif; 

							if( (string)$value->meta_key == '_job_expires' ) : // -- expires

								$date = strtotime( (string)$value->meta_value );
								$post_item['_expires'] = $date;
								$post_item['_closing'] = $date;
							endif;

							if( (string)$value->meta_key == '_application' ) : // -- application

								$post_item['_application_email'] = (string)$value->meta_value;

							endif;

						}

						// -- process user data
							if ( $_POST['author'] == 'auto' ) :

					  			$xml_user = $item->children("dc", true);

					  			$user_id = get_user_by( 'slug', $xml_user->creator )->ID;

					  			if ( $user_id ) :

					  				$post_item['post_author'] = absint( $user_id );

					  			else :
					  				// -- Create user new
					  					$user_id = wp_create_user( $xml_user->creator, esc_attr( !empty($_POST['pws']) ? $_POST['pws'] : '123456' ) );
					  				
					  				// -- Update info user new
					  					wp_update_user( array( 'ID' => $user_id, 'role' => 'employer' ) );
					  					update_user_meta( $user_id, 'employer_company', $post_item['job_company'] );
					  				
					  				$post_item['post_author'] = absint( $user_id );

					  			endif;

					  		else :

					  			$post_item['post_author'] = absint( $_POST['author'] );
							
					  		endif;
					if ( $this->create_post( $post_item ) ) $i++;
			  		
	  			endforeach;
	  			$this->notice( $i );
			else :

				$this->notice( false, __( 'Not upload file, please try again!', 'noo' ), 'error' );

			endif;

		endif;

		if ( isset( $_POST['import_indeed'] ) ) :
			require 'includes/indeed.php';
			$client = new Indeed($_POST['publisher']);
			$jt = explode('|', esc_attr( $_POST['jt'] ) );
			$params = array(
				"q"         => esc_attr( $_POST['q'] ),
				"l"         => esc_attr( $_POST['l'] ),
				"co"        => esc_attr( $_POST['co'] ),
				"userip"    => esc_attr( $_POST['userip'] ),
				"useragent" => esc_attr( $_POST['useragent'] ),
				"start"     => esc_attr( $_POST['start'] ),
				"limit"     => esc_attr( $_POST['limit'] ),
			);
			if( isset( $jt[0] ) )
				$params['jt'] = esc_attr( $jt[0] );

			$link_application = ( isset($_POST['application_url']) ? 1 : 0 );

			$results = $client->search($params);
			foreach ($results['results'] as $info_job) :
				$indeed_content = $this->get_content_indeed( $info_job['url'] );
				
				$post_item['post_title']   = esc_attr( $info_job['jobtitle'] ); 
				$post_item['post_type']    = esc_attr( 'noo_job' ); 
				$post_item['post_status']  = esc_attr( 'publish' ); 
				$post_item['post_content'] = isset( $indeed_content['content'] ) ? $indeed_content['content'] : '';
				$post_item['job_category'] = array($_POST['job_category']);
				$post_item['job_location'] = array();
				$date = esc_html( $info_job['date'] );
				$date = strtotime( $date );
				$date = date( 'Y-m-d H:i:s', $date );
				$post_item['post_date'] = $date;

			  	$job_type = isset( $jt[1] ) ? get_term_by( 'name', $jt[1], 'job_type' ) : '';

	  			if ( $job_type ) {

	  				$post_item['job_type'] = (array)$job_type->term_id;
	  			
	  			} else {

	  				$job_type_id = wp_insert_term( $jt[1], 'job_type', array() );
	  				$post_item['job_type'] = (array)$job_type_id;

	  			}

			  	$job_location = get_term_by( 'name', $info_job['formattedLocationFull'], 'job_location' );
				
				if ( $job_location ) {
  					$post_item['job_location'] = (array)$job_location->term_id;
				} else {
					$location_id               = wp_insert_term( $info_job['formattedLocationFull'], 'job_location', array() );
					$post_item['job_location'] = (array)$location_id;
	  			}

	  			$name_company = isset( $indeed_content['company'] ) ? $indeed_content['company'] : '';
	  			$job_company = !empty( $name_company ) ? post_exists( $name_company ) : 0;
				if ( $job_company != 0 && 'noo_company' == get_post_type( $job_company ) ) {

  					$post_item['_company_id'] = $job_company;
	  			
	  			} else {

	  				$args_company = array(
						'post_title'  => esc_attr( $name_company ),
						'post_type'   => esc_attr( 'noo_company' ),
						'post_status' => esc_attr( 'publish' )
	  				);
	  				$post_item['_company_id'] = wp_insert_post( $args_company );

	  				$company_url = isset( $indeed_content['company_url'] ) ? $indeed_content['company_url'] : '';;
	  				if( !empty( $company_url ) ) {
	  					update_post_meta( $post_item['_company_id'], '_website', $company_url );
	  				}
	  			}

	  			$post_item['post_author'] = absint( $_POST['author'] );

	  			if ( $link_application ) {
	  				$post_item['_custom_application_url'] = esc_html( $info_job['url'] );
	  			}

	  			// check reference
	  			$job_ref = esc_html( $info_job['jobkey'] );
	  			$args = array(
		            'post_type' => 'noo_job',
		            // 'post_status' => 'publish',
		            'meta_key' => '_indeed_jobkey',
		            'meta_value' => $job_ref,
		            'relation' => 'AND'
		        );
		        $check = get_posts( $args );
		        if( empty( $check ) || count( $check ) == 0 ) {
		        	if ( $post_id = $this->create_post( $post_item ) ) {
		  				update_post_meta( $post_id, '_indeed_jobkey', $job_ref );
		  				$i++;
		  			}
		        }

			endforeach;
			$this->notice( $i );

		endif;

	}

	public function notice( $i = false, $text = 'Saved', $status = 'success' ) {

		echo '<div id="message" class="updated notice notice-' . $status . ' is-dismissible below-h2"><p>' . $text . ' ' . $i . __( ' jobs', 'noo' ) . '. <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';

	}

	public function get_content_indeed( $url ) {

		$page = file_get_contents( $url );
		$return = array();
		
		preg_match('#<span id="job_summary" class="summary">(.*?)</span>#is', $page, $results);
		$return['content'] = trim($results[1]);

		preg_match('#<span class="company">(.*?)</span>#is', $page, $results);
		$return['company'] = trim($results[1]);

		preg_match( '#<div class="cmp_title">(.*?)href="(.*?)"#is', $page, $url_company );
		if ( isset($url_company[2]) )
			$return['company_url'] = "http://www.indeed.com{$url_company[2]}";

		return $return;

	}

	public function create_post( $args = array(), $update_meta = true ) {
		if ( !post_exists( $args['post_title'], $args['post_content'] ) ) :
			$id_job = wp_insert_post( $args );
			if ( $id_job && $update_meta ) :
				if ( @$args['_expires'] ) {
					update_post_meta( $id_job, '_expires', $args['_expires'] );
					update_post_meta( $id_job, '_closing', $args['_expires'] );
				}
				// if ( @$args['_featured'] ) update_post_meta( $id_job, '_featured', ( $args['_featured'] == 1 ) ? 'yes' : 'no' );
				if ( @$args['_company_id'] ) update_post_meta( $id_job, '_company_id', absint( $args['_company_id'] ) );
				if ( @$args['_application_email'] ) update_post_meta( $id_job, '_application_email', $args['_application_email'] );
				if ( @$args['_custom_application_url'] ) update_post_meta( $id_job, '_custom_application_url', esc_html( $args['_custom_application_url'] ) );
				wp_set_post_terms( $id_job, $args['job_location'], 'job_location' );
				if( !empty( $args['job_category'] ) ) {
					wp_set_post_terms( $id_job, $args['job_category'], 'job_category' );
				}
				if( !empty( $args['job_category'] ) ) {
					wp_set_post_terms( $id_job, $args['job_type'], 'job_type' );
				}
				// echo "<div><a target='_blank' href='" . get_post_permalink( $id_job ) . "'>{$args['post_title']}</a></div>";
				return $id_job;
			else :
				return false;
			endif;
		else :
			return false;
		endif;
	}

	public function load_xml() {
		error_reporting(0);
		$args_list = array(

			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'publish',
			'posts_per_page' => -1

		);

		if ( $_POST['post_type'] == 'noo_job' ) :

			$args_list['post_type'] = 'noo_job';

		elseif ( $_POST['post_type'] == 'noo_resume' ) :

			$args_list['post_type'] = 'noo_resume';

		endif;

		$info_post = get_posts( $args_list );

		$xml = new DOMDocument("1.0");
		$root = $xml->createElement("source");
		$xml->appendChild($root);

		// -- Publisher
			$publisher         = $xml->createElement("publisher");
			$publisher_content = $xml->createTextNode( get_bloginfo( 'name' ) );
			$publisher->appendChild( $publisher_content );
			$root->appendChild($publisher);

		// -- Publisherurl
			$publisherurl         = $xml->createElement("publisherurl");
			$publisherurl_content = $xml->createTextNode( get_bloginfo( 'url' ) );
			$publisherurl->appendChild( $publisherurl_content );
			$root->appendChild($publisherurl);
			

			foreach ($info_post as $item) :
				setup_postdata( $item ); 
				$job = $xml->createElement("job");
				$root->appendChild($job);

					// -- title
						$title         = $xml->createElement("title");
						$title_content = $xml->createCDATASection( get_the_title( $item->ID ) );
						$title->appendChild( $title_content );
						$job->appendChild($title);

					// -- date
						$date         = $xml->createElement("date");
						$date_content = $xml->createCDATASection( get_the_date( 'D, j M Y g:i:s', $item->ID ) .' GMT' );
						$date->appendChild( $date_content );
						$job->appendChild($date);

					// -- date
						$url         = $xml->createElement("url");
						$url_content = $xml->createCDATASection( get_the_permalink( $item->ID ) );
						$url->appendChild( $url_content );
						$job->appendChild($url);

					// -- company
						$company         = $xml->createElement("company");
						$id_company      = $this->get_info_author( $item->post_author, 'employer_company' );
						$company_content = $xml->createCDATASection( get_the_title($id_company) );
						$company->appendChild( $company_content );
						$job->appendChild($company);

					// -- city
						$city         = $xml->createElement("city");
						$city_content = $xml->createCDATASection( $_POST['city'] );
						$city->appendChild( $city_content );
						$job->appendChild($city);

					// -- state
						$state         = $xml->createElement("state");
						$state_content = $xml->createCDATASection( $_POST['state'] );
						$state->appendChild( $state_content );
						$job->appendChild($state);

					// -- country
						$country         = $xml->createElement("country");
						$country_content = $xml->createCDATASection( $_POST['country'] );
						$country->appendChild( $country_content );
						$job->appendChild($country);

					// -- postalcode
						$postalcode         = $xml->createElement("postalcode");
						$postalcode_content = $xml->createCDATASection( $_POST['postalcode'] );
						$postalcode->appendChild( $postalcode_content );
						$job->appendChild($postalcode);

					// -- description
						$description         = $xml->createElement("description");
						$description_content = $xml->createCDATASection( strip_tags(get_the_content()) );
						$description->appendChild( $description_content );
						$job->appendChild($description);

					// -- salary
						$salary         = $xml->createElement("salary");
						$salary_content = $xml->createCDATASection( $_POST['salary'] );
						$salary->appendChild( $salary_content );
						$job->appendChild($salary);

					// -- jobtype
						$jobtype         = $xml->createElement("jobtype");
						$jobtype_list    = wp_get_post_terms($item->ID, 'job_type');
						// print_r($jobtype_list);
						$jobtype_content = $xml->createCDATASection( str_replace( '-', '', $jobtype_list[0]->slug ) );
						$jobtype->appendChild( $jobtype_content );
						$job->appendChild($jobtype);

					// -- experience
						$experience         = $xml->createElement("experience");
						$experience_content = $xml->createCDATASection( $_POST['experience'] );
						$experience->appendChild( $experience_content );
						$job->appendChild($experience);

				// $content_xml .= "</job>\n";

			endforeach;
			// $root->appendChild($job);
			// print $xml->saveXML();
			$upload_dir = wp_upload_dir();
			$time = time();
			$file = $upload_dir['path'] . '/' . basename($_POST['post_type'] . "_{$time}.xml" );
			$file_redirect = $upload_dir['url'] . '/' . basename($_POST['post_type'] . "_{$time}.xml" );
			$xml->save($file) or die("Error");
			// header('Content-type: application/xml');
			// header('Content-Disposition: attachment; filename='.$file_redirect);
			echo $file_redirect;
		wp_reset_postdata();
		wp_die();

	}

	public function get_info_author( $user_id, $key ) {

		return get_user_meta( $user_id, $key, true ); 

	}

}

new Noo_Import();