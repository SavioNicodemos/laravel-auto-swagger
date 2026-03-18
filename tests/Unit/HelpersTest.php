<?php

namespace AutoSwagger\Docs\Tests\Unit;

use AutoSwagger\Docs\Tests\TestCase;

class HelpersTest extends TestCase
{
    // --- at_least_one_in_array ---

    public function test_at_least_one_in_array_returns_true_when_one_matches(): void
    {
        $this->assertTrue(at_least_one_in_array(['a', 'x'], ['a', 'b', 'c']));
    }

    public function test_at_least_one_in_array_returns_false_when_none_match(): void
    {
        $this->assertFalse(at_least_one_in_array(['x', 'y'], ['a', 'b', 'c']));
    }

    public function test_at_least_one_in_array_returns_false_for_empty_items(): void
    {
        $this->assertFalse(at_least_one_in_array([], ['a', 'b', 'c']));
    }

    // --- all_in_array ---

    public function test_all_in_array_returns_true_when_all_items_present(): void
    {
        $this->assertTrue(all_in_array(['a', 'b'], ['a', 'b', 'c']));
    }

    public function test_all_in_array_returns_false_when_one_is_missing(): void
    {
        $this->assertFalse(all_in_array(['a', 'z'], ['a', 'b', 'c']));
    }

    public function test_all_in_array_returns_true_for_empty_items(): void
    {
        $this->assertTrue(all_in_array([], ['a', 'b', 'c']));
    }

    // --- strip_optional_char ---

    public function test_strip_optional_char_removes_question_mark(): void
    {
        $this->assertSame('/users/{id}', strip_optional_char('/users/{id?}'));
    }

    public function test_strip_optional_char_leaves_normal_uri_unchanged(): void
    {
        $this->assertSame('/users/{id}', strip_optional_char('/users/{id}'));
    }

    public function test_strip_optional_char_removes_all_question_marks(): void
    {
        $this->assertSame('/users/{id}/posts/{postId}', strip_optional_char('/users/{id?}/posts/{postId?}'));
    }

    // --- swagger_is_connection_secure ---

    public function test_swagger_is_connection_secure_returns_false_by_default(): void
    {
        // Without HTTPS server headers, should return false
        $this->assertFalse(swagger_is_connection_secure());
    }

    // --- swagger_resolve_documentation_file_path ---

    public function test_swagger_resolve_documentation_file_path_returns_empty_when_file_absent(): void
    {
        config(['swagger.storage' => sys_get_temp_dir() . '/swagger-test-' . uniqid()]);

        $result = swagger_resolve_documentation_file_path('nonexistent_page');

        $this->assertSame('', $result);
    }

    public function test_swagger_resolve_documentation_file_path_returns_json_path_when_exists(): void
    {
        $dir = sys_get_temp_dir() . '/swagger-test-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/mypage.json', '{}');

        config(['swagger.storage' => $dir]);

        $result = swagger_resolve_documentation_file_path('mypage');

        $this->assertSame($dir . '/mypage.json', $result);

        // Cleanup
        unlink($dir . '/mypage.json');
        rmdir($dir);
    }
}
