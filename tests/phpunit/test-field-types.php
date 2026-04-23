<?php
/**
 * Tests for core field type sanitize/save/load cycles.
 *
 * @package FieldForge
 */

use PHPUnit\Framework\TestCase;

class FieldForge_Test_Field_Types extends WP_UnitTestCase {

	/** @var int */
	protected static int $post_id;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$post_id = self::factory()->post->create( array( 'post_title' => 'Test Post' ) );
	}

	// ------------------------------------------------------------------
	// Text
	// ------------------------------------------------------------------

	public function test_text_sanitize_strips_html(): void {
		$field = new FieldForge_Field_Text( array( 'key' => 'field_text', 'name' => 'my_text', 'type' => 'text' ) );
		$this->assertSame( 'hello world', $field->sanitize( '<b>hello world</b>' ) );
	}

	public function test_text_save_and_load(): void {
		$field = new FieldForge_Field_Text( array( 'key' => 'field_text', 'name' => 'ff_text', 'type' => 'text' ) );
		$field->save( self::$post_id, 'stored value' );
		$this->assertSame( 'stored value', $field->load( self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// Textarea
	// ------------------------------------------------------------------

	public function test_textarea_sanitize(): void {
		$field = new FieldForge_Field_Textarea( array( 'key' => 'field_ta', 'name' => 'ff_ta', 'type' => 'textarea' ) );
		$value = "line one\nline two";
		$this->assertSame( $value, $field->sanitize( $value ) );
	}

	// ------------------------------------------------------------------
	// Number
	// ------------------------------------------------------------------

	public function test_number_sanitize(): void {
		$field = new FieldForge_Field_Number( array( 'key' => 'field_num', 'name' => 'ff_num', 'type' => 'number' ) );
		$this->assertSame( '3.14', $field->sanitize( '3.14xyz' ) );
		$this->assertSame( '', $field->sanitize( '' ) );
	}

	// ------------------------------------------------------------------
	// Select
	// ------------------------------------------------------------------

	public function test_select_sanitize_single(): void {
		$field = new FieldForge_Field_Select( array( 'key' => 'field_sel', 'name' => 'ff_sel', 'type' => 'select' ) );
		$this->assertSame( 'red', $field->sanitize( 'red' ) );
	}

	public function test_select_sanitize_multiple(): void {
		$field = new FieldForge_Field_Select( array( 'key' => 'field_sel2', 'name' => 'ff_sel2', 'type' => 'select', 'multiple' => true ) );
		$this->assertSame( array( 'red', 'blue' ), $field->sanitize( array( 'red', 'blue' ) ) );
	}

	// ------------------------------------------------------------------
	// Checkbox
	// ------------------------------------------------------------------

	public function test_checkbox_sanitize_returns_array(): void {
		$field = new FieldForge_Field_Checkbox( array( 'key' => 'field_chk', 'name' => 'ff_chk', 'type' => 'checkbox' ) );
		$this->assertSame( array( 'a', 'b' ), $field->sanitize( array( 'a', 'b' ) ) );
	}

	public function test_checkbox_empty_value_is_array(): void {
		$field = new FieldForge_Field_Checkbox( array( 'key' => 'field_chk2', 'name' => 'ff_chk2', 'type' => 'checkbox' ) );
		$this->assertSame( array(), $field->get_empty_value() );
	}

	// ------------------------------------------------------------------
	// True/False
	// ------------------------------------------------------------------

	public function test_true_false_sanitize(): void {
		$field = new FieldForge_Field_True_False( array( 'key' => 'field_tf', 'name' => 'ff_tf', 'type' => 'true_false' ) );
		$this->assertSame( 1, $field->sanitize( '1' ) );
		$this->assertSame( 0, $field->sanitize( '0' ) );
		$this->assertSame( 0, $field->sanitize( '' ) );
	}

	public function test_true_false_save_load(): void {
		$field = new FieldForge_Field_True_False( array( 'key' => 'field_tf2', 'name' => 'ff_tf2', 'type' => 'true_false' ) );
		$field->save( self::$post_id, 1 );
		$this->assertTrue( $field->load( self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// Email
	// ------------------------------------------------------------------

	public function test_email_sanitize(): void {
		$field = new FieldForge_Field_Email( array( 'key' => 'field_em', 'name' => 'ff_em', 'type' => 'email' ) );
		$this->assertSame( 'user@example.com', $field->sanitize( 'user@example.com' ) );
		$this->assertSame( '', $field->sanitize( 'not-an-email' ) );
	}

	// ------------------------------------------------------------------
	// URL
	// ------------------------------------------------------------------

	public function test_url_sanitize(): void {
		$field = new FieldForge_Field_Url( array( 'key' => 'field_url', 'name' => 'ff_url', 'type' => 'url' ) );
		$this->assertSame( 'https://example.com', $field->sanitize( 'https://example.com' ) );
	}

	// ------------------------------------------------------------------
	// Color
	// ------------------------------------------------------------------

	public function test_color_sanitize(): void {
		$field = new FieldForge_Field_Color( array( 'key' => 'field_col', 'name' => 'ff_col', 'type' => 'color_picker' ) );
		$this->assertSame( '#ff0000', $field->sanitize( '#ff0000' ) );
		$this->assertSame( '', $field->sanitize( 'notacolor' ) );
	}

	// ------------------------------------------------------------------
	// Date
	// ------------------------------------------------------------------

	public function test_date_sanitize_yyyymmdd(): void {
		$field = new FieldForge_Field_Date( array( 'key' => 'field_dt', 'name' => 'ff_dt', 'type' => 'date_picker' ) );
		$this->assertSame( '20240115', $field->sanitize( '2024-01-15' ) );
		$this->assertSame( '20240115', $field->sanitize( '20240115' ) );
	}

	// ------------------------------------------------------------------
	// Image
	// ------------------------------------------------------------------

	public function test_image_sanitize(): void {
		$field = new FieldForge_Field_Image( array( 'key' => 'field_img', 'name' => 'ff_img', 'type' => 'image' ) );
		$this->assertSame( 42, $field->sanitize( '42' ) );
		$this->assertSame( 0, $field->sanitize( 'bad' ) );
	}

	// ------------------------------------------------------------------
	// Gallery
	// ------------------------------------------------------------------

	public function test_gallery_sanitize(): void {
		$field = new FieldForge_Field_Gallery( array( 'key' => 'field_gal', 'name' => 'ff_gal', 'type' => 'gallery' ) );
		$this->assertSame( array( 1, 2, 3 ), $field->sanitize( array( '1', '2', '3' ) ) );
		$this->assertSame( array(), $field->sanitize( 'not-array' ) );
	}

	// ------------------------------------------------------------------
	// Link
	// ------------------------------------------------------------------

	public function test_link_sanitize(): void {
		$field = new FieldForge_Field_Link( array( 'key' => 'field_lnk', 'name' => 'ff_lnk', 'type' => 'link' ) );
		$result = $field->sanitize( array( 'url' => 'https://example.com', 'title' => 'Example', 'target' => '_blank' ) );
		$this->assertSame( 'https://example.com', $result['url'] );
		$this->assertSame( 'Example', $result['title'] );
		$this->assertSame( '_blank', $result['target'] );
	}

	// ------------------------------------------------------------------
	// Repeater
	// ------------------------------------------------------------------

	public function test_repeater_load_returns_rows(): void {
		$post_id    = self::$post_id;
		$sub_fields = array(
			array( 'key' => 'field_sub1', 'name' => 'sub_title', 'type' => 'text' ),
			array( 'key' => 'field_sub2', 'name' => 'sub_count', 'type' => 'number' ),
		);

		$field = new FieldForge_Field_Repeater( array(
			'key'        => 'field_rep',
			'name'       => 'ff_repeater',
			'type'       => 'repeater',
			'sub_fields' => $sub_fields,
		) );

		// Manually write ACF-compatible meta.
		update_post_meta( $post_id, 'ff_repeater', 2 );
		update_post_meta( $post_id, 'ff_repeater_0_sub_title', 'Row One' );
		update_post_meta( $post_id, 'ff_repeater_0_sub_count', '10' );
		update_post_meta( $post_id, 'ff_repeater_1_sub_title', 'Row Two' );
		update_post_meta( $post_id, 'ff_repeater_1_sub_count', '20' );

		$rows = $field->load( $post_id );

		$this->assertCount( 2, $rows );
		$this->assertSame( 'Row One', $rows[0]['sub_title'] );
		$this->assertSame( '10', $rows[0]['sub_count'] );
		$this->assertSame( 'Row Two', $rows[1]['sub_title'] );
	}

	// ------------------------------------------------------------------
	// ACF meta key reference (_field_name)
	// ------------------------------------------------------------------

	public function test_save_stores_acf_key_reference(): void {
		$field = new FieldForge_Field_Text( array( 'key' => 'field_abc123', 'name' => 'ff_ref_test', 'type' => 'text' ) );
		$field->save( self::$post_id, 'hello' );
		$this->assertSame( 'field_abc123', get_post_meta( self::$post_id, '_ff_ref_test', true ) );
	}
}
