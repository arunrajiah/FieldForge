<?php
/**
 * Tests for format_value() on field types that support return_format.
 *
 * @package FieldForge
 */

class FieldForge_Test_Format_Value extends WP_UnitTestCase {

	/** @var int */
	protected static int $post_id;

	/** @var int */
	protected static int $attachment_id;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$post_id = self::factory()->post->create( array( 'post_title' => 'Format Value Test Post' ) );

		// Create a minimal attachment.
		self::$attachment_id = self::factory()->attachment->create(
			array(
				'post_title'     => 'Test Image',
				'post_mime_type' => 'image/jpeg',
				'post_status'    => 'inherit',
			)
		);
	}

	// ------------------------------------------------------------------
	// Image
	// ------------------------------------------------------------------

	public function test_image_format_value_id(): void {
		$field = new FieldForge_Field_Image( array( 'key' => 'f', 'name' => 'img', 'type' => 'image', 'return_format' => 'id' ) );
		$this->assertSame( self::$attachment_id, $field->format_value( self::$attachment_id, self::$post_id ) );
	}

	public function test_image_format_value_url(): void {
		$field = new FieldForge_Field_Image( array( 'key' => 'f', 'name' => 'img', 'type' => 'image', 'return_format' => 'url' ) );
		$url   = $field->format_value( self::$attachment_id, self::$post_id );
		$this->assertIsString( $url );
	}

	public function test_image_format_value_array(): void {
		$field  = new FieldForge_Field_Image( array( 'key' => 'f', 'name' => 'img', 'type' => 'image', 'return_format' => 'array' ) );
		$result = $field->format_value( self::$attachment_id, self::$post_id );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'title', $result );
	}

	public function test_image_format_value_zero_returns_empty(): void {
		$field = new FieldForge_Field_Image( array( 'key' => 'f', 'name' => 'img', 'type' => 'image', 'return_format' => 'array' ) );
		$this->assertSame( array(), $field->format_value( 0, self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// File
	// ------------------------------------------------------------------

	public function test_file_format_value_id(): void {
		$field = new FieldForge_Field_File( array( 'key' => 'f', 'name' => 'file', 'type' => 'file', 'return_format' => 'id' ) );
		$this->assertSame( self::$attachment_id, $field->format_value( self::$attachment_id, self::$post_id ) );
	}

	public function test_file_format_value_array(): void {
		$field  = new FieldForge_Field_File( array( 'key' => 'f', 'name' => 'file', 'type' => 'file', 'return_format' => 'array' ) );
		$result = $field->format_value( self::$attachment_id, self::$post_id );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'filename', $result );
	}

	// ------------------------------------------------------------------
	// Gallery
	// ------------------------------------------------------------------

	public function test_gallery_format_value_ids(): void {
		$field = new FieldForge_Field_Gallery( array( 'key' => 'f', 'name' => 'gal', 'type' => 'gallery', 'return_format' => 'id' ) );
		$ids   = array( self::$attachment_id );
		$this->assertSame( $ids, $field->format_value( $ids, self::$post_id ) );
	}

	public function test_gallery_format_value_urls(): void {
		$field  = new FieldForge_Field_Gallery( array( 'key' => 'f', 'name' => 'gal', 'type' => 'gallery', 'return_format' => 'url' ) );
		$result = $field->format_value( array( self::$attachment_id ), self::$post_id );
		$this->assertIsArray( $result );
		$this->assertIsString( $result[0] );
	}

	// ------------------------------------------------------------------
	// Post Object
	// ------------------------------------------------------------------

	public function test_post_object_format_value_id(): void {
		$field  = new FieldForge_Field_Post_Object( array( 'key' => 'f', 'name' => 'po', 'type' => 'post_object', 'return_format' => 'id', 'multiple' => false ) );
		$result = $field->format_value( self::$post_id, self::$post_id );
		$this->assertSame( self::$post_id, $result );
	}

	public function test_post_object_format_value_object(): void {
		$field  = new FieldForge_Field_Post_Object( array( 'key' => 'f', 'name' => 'po', 'type' => 'post_object', 'return_format' => 'object', 'multiple' => false ) );
		$result = $field->format_value( self::$post_id, self::$post_id );
		$this->assertInstanceOf( WP_Post::class, $result );
	}

	public function test_post_object_format_value_zero_returns_null(): void {
		$field  = new FieldForge_Field_Post_Object( array( 'key' => 'f', 'name' => 'po', 'type' => 'post_object', 'return_format' => 'object', 'multiple' => false ) );
		$result = $field->format_value( 0, self::$post_id );
		$this->assertNull( $result );
	}

	// ------------------------------------------------------------------
	// Base class passthrough
	// ------------------------------------------------------------------

	public function test_text_format_value_passthrough(): void {
		$field = new FieldForge_Field_Text( array( 'key' => 'f', 'name' => 'txt', 'type' => 'text' ) );
		$this->assertSame( 'hello', $field->format_value( 'hello', self::$post_id ) );
	}
}
