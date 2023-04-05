<?php
/**
 * Plugin Name: HEIC to JPEG
 * Description: Convert HEIC images to JPEG format when upload to the Media Library.
 * Author:      Crissy Field GmbH
 * Author URI:  https://www.crissyfield.de/
 * Version:     1.0.1
 * Text Domain: heic-to-jpeg
 */

defined( 'ABSPATH' ) || exit;

// Add HEIC mime type to mimes allowed for upload.
function add_heic_upload_mime( $mimes, $user ) {
	$mimes['heic'] = 'image/heic';
	return $mimes;
}

add_filter( 'upload_mimes', 'add_heic_upload_mime', 10, 2 );

// Disable the client-side error message for HEIC media.
function allow_heic_plupload_init( $setts ) {
	$setts['heic_upload_error'] = false;
	return $setts;
}

add_filter( 'plupload_default_settings', 'allow_heic_plupload_init', 10, 1 );

// Convert HEIC to JPEG on upload.
function resize_heic_upload_prefilter( $file ) {
	// Bail if no HEIC file
	if ( $file['type'] !== 'image/heic' ) {
		return $file;
	}

	// Bail if no ImageMagick available
	if ( ! class_exists( 'Imagick' ) ) {
		return $file;
	}

	// Resize HEIC image
	$imagick = new Imagick();

	if ( ! $imagick->readImage( $file['tmp_name'] ) ) {
		return $file;
	}

	$imagick->setImageFormat( 'jpg' );
	$imagick->writeImage( $file['tmp_name'] . '_jpg' );

	// Overwrite original file
	rename( $file['tmp_name'] . '_jpg', $file['tmp_name'] );

	// Fix up upload data
	$file['type'] = 'image/jpeg';
	$file['name'] = basename( $file['name'], '.heic' ) . '.jpg';
	$file['size'] = filesize( $file['tmp_name'] );

	return $file;
}

add_filter( 'wp_handle_upload_prefilter', 'resize_heic_upload_prefilter', 10, 1 );
