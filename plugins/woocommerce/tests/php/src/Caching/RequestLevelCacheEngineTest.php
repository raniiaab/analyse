<?php
/**
 * RequestLevelCacheEngineTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\RequestLevelCacheEngine;

/**
 * Test class for RequestLevelCacheEngine
 */
class RequestLevelCacheEngineTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 * @var RequestLevelCacheEngine
	 */
	private $sut;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( RequestLevelCacheEngine::class );
	}

	/**
	 * @testDox Test that the cache engine can be used to cache and retrieve objects.
	 */
	public function test_caching_crud() {
		$object_to_cache        = new \stdClass();
		$object_to_cache->prop1 = 'dummy_value_1';
		$object_to_cache->prop2 = 'dummy_value_2';
		$key                    = 'dummy_object_key';
		$group                  = 'dummy_group';

		$this->assertFalse( $this->sut->is_cached( $key, $group ) );
		$this->sut->cache_object( $key, $object_to_cache, HOUR_IN_SECONDS, $group );
		$this->assertTrue( $this->sut->is_cached( $key, $group ) );

		$cached_obj = $this->sut->get_cached_object( $key, $group );
		$this->assertEquals( $object_to_cache->prop1, $cached_obj->prop1 );
		$this->assertEquals( $object_to_cache->prop2, $cached_obj->prop2 );

		$this->sut->delete_cached_object( $key, $group );
		$this->assertFalse( $this->sut->is_cached( $key, $group ) );
		$this->assertNull( ( $this->sut->get_cached_object( $key, $group ) ) );

		$this->sut->cache_object( $key, $object_to_cache, HOUR_IN_SECONDS, $group );
		$this->assertTrue( $this->sut->is_cached( $key, $group ) );
		$this->sut->delete_cache_group( $group );
		$this->assertFalse( $this->sut->is_cached( $key, $group ) );
		$this->assertNull( ( $this->sut->get_cached_object( $group ) ) );
	}
}
