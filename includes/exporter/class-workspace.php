<?php
namespace Exporter;

use \ZipArchive as ZipArchive;
use \RecursiveIteratorIterator as RecursiveIteratorIterator;
use \RecursiveDirectoryIterator as RecursiveDirectoryIterator;

/**
 * Manage the exporter's workspace. This class is able to write to the
 * workspace as well as zipping it.
 *
 * @author  Federico Ramirez
 * @since   0.0.0
 */
class Workspace {

	/**
	 * Base path of the workspace directory. This should have 775 permissions,
	 * assuming the folder is owned by the webserver group (which is recommended
	 * but could also be the user). If not existant, the plugin will try to
	 * create it.
	 */
	private $base_path;

	/**
	 * Inside the workspace directory there's a tmp directory, it's used as a
	 * place to generate files into so they can then be zipped and stored in the
	 * workspace. The tmp directory gets cleaned up after each zipfile
	 * generation.
	 */
	private $tmp_path;

	function __construct() {
		$this->base_path = realpath( plugin_dir_path( __FILE__ ) . '../../workspace' ) . '/';
		$this->tmp_path  = $this->base_path . 'tmp/';

		if ( ! file_exists( $this->base_path ) ) {
			mkdir( $this->base_path, 0775, true );
		}

		if ( ! file_exists( $this->tmp_path ) ) {
			mkdir( $this->tmp_path, 0775, true );
		}
	}

	/**
	 * Delete all files from the workspace directory.
	 *
	 * @since   0.0.0
	 */
	private function clean_up() {
		$files = glob( $this->tmp_path . '*', GLOB_BRACE );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
	}

	/**
	 * Write a file to the workspace.
	 *
	 * @since   0.0.0
	 */
	public function write_tmp_file( $file, $contents ) {
		file_put_contents( $this->tmp_path . $file, $contents );
	}

	/**
	 * Compresses the workspace directory recursively into a ZIP.
	 *
	 * @since   0.0.0
	 * @return  The full path to the generated zipfile
	 */
	public function zip( $filename ) {
		$zipfile_path = $this->base_path . $filename;

		$zip = new ZipArchive();
		$zip->open( $zipfile_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $this->tmp_path ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $name => $file ) {
			if ( ! $file->isDir() ) {
				$file_path     = $file->getRealPath();
				$relative_path = substr( $file_path, strlen( $this->tmp_path ) );

				$zip->addFile( $file_path, $relative_path );
			}
		}

		// Write the zipfile
		$zip->close();
		// Clean up tmp dir and return generated zipfile path
		$this->clean_up();
		return $zipfile_path;
	}

}