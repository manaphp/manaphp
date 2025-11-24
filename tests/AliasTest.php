<?php

declare(strict_types=1);

namespace Tests;

use ManaPHP\Alias;
use ManaPHP\Alias\AliasNotExistException;
use ManaPHP\Alias\InvalidAliasNameException;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for ManaPHP\Alias class
 */
class AliasTest extends TestCase
{
    protected Alias $alias;

    /**
     * Set up test environment before each test
     * Clear all aliases except @manaphp to ensure clean test state
     */
    protected function setUp(): void
    {
        $this->alias = new Alias();
        // Clear default aliases, use clean test environment
        $aliases = $this->alias->all();
        foreach (array_keys($aliases) as $name) {
            if ($name !== '@manaphp') {
                $this->alias->remove($name);
            }
        }
    }

    /**
     * Test getting all aliases
     */
    public function testAll(): void
    {
        $this->alias->set('@test', '/path/to/test');
        $all = $this->alias->all();
        
        $this->assertIsArray($all);
        $this->assertArrayHasKey('@test', $all);
        $this->assertEquals('/path/to/test', $all['@test']);
    }

    /**
     * Test setting alias with absolute path
     */
    public function testSetWithAbsolutePath(): void
    {
        $result = $this->alias->set('@root', '/path/to/root');
        $this->assertEquals('/path/to/root', $result);
        $this->assertEquals('/path/to/root', $this->alias->get('@root'));
    }

    /**
     * Test setting alias with relative path
     */
    public function testSetWithRelativePath(): void
    {
        $result = $this->alias->set('@app', 'app');
        $this->assertEquals('app', $result);
    }

    /**
     * Test setting alias with another alias path
     */
    public function testSetWithAliasPath(): void
    {
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->set('@app', '@root/app');
        $this->assertEquals('/path/to/app', $result);
    }

    /**
     * Test setting alias with empty path
     */
    public function testSetWithEmptyPath(): void
    {
        $result = $this->alias->set('@empty', '');
        $this->assertEquals('', $result);
        $this->assertEquals('', $this->alias->get('@empty'));
    }

    /**
     * Test setting alias with trailing slash (should be trimmed)
     */
    public function testSetWithTrailingSlash(): void
    {
        $result = $this->alias->set('@root', '/path/to/');
        $this->assertEquals('/path/to', $result); // rtrim removes trailing slash
    }

    /**
     * Test setting alias with Windows path (should convert backslashes)
     */
    public function testSetWithWindowsPath(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $result = $this->alias->set('@root', 'C:\\path\\to');
            $this->assertEquals('C:/path/to', $result);
        } else {
            $this->markTestSkipped('Windows path test only runs on Windows');
        }
    }

    /**
     * Test setting alias with namespace prefix (@ns.)
     */
    public function testSetWithNsPrefix(): void
    {
        $result = $this->alias->set('@ns.test', 'Namespace\\Test');
        $this->assertEquals('Namespace\\Test', $result); // @ns. prefix doesn't convert backslashes
    }

    /**
     * Test setting alias with invalid name (should throw exception)
     */
    public function testSetInvalidName(): void
    {
        $this->expectException(InvalidAliasNameException::class);
        $this->alias->set('invalid', '/path');
    }

    /**
     * Test getting existing alias
     */
    public function testGet(): void
    {
        $this->alias->set('@test', '/path/to/test');
        $this->assertEquals('/path/to/test', $this->alias->get('@test'));
    }

    /**
     * Test getting non-existent alias (should return null)
     */
    public function testGetNonExistent(): void
    {
        $this->assertNull($this->alias->get('@nonexistent'));
    }

    /**
     * Test getting alias with invalid name (should throw exception)
     */
    public function testGetInvalidName(): void
    {
        $this->expectException(InvalidAliasNameException::class);
        $this->alias->get('invalid');
    }

    /**
     * Test checking if alias exists
     */
    public function testHas(): void
    {
        $this->alias->set('@test', '/path/to/test');
        $this->assertTrue($this->alias->has('@test'));
        $this->assertFalse($this->alias->has('@nonexistent'));
    }

    /**
     * Test checking alias with invalid name (should throw exception)
     */
    public function testHasInvalidName(): void
    {
        $this->expectException(InvalidAliasNameException::class);
        $this->alias->has('invalid');
    }

    /**
     * Test resolving simple alias
     */
    public function testResolveSimpleAlias(): void
    {
        $this->alias->set('@root', '/path/to/root');
        $result = $this->alias->resolve('@root');
        $this->assertEquals('/path/to/root', $result);
    }

    /**
     * Test resolving alias with sub-path
     */
    public function testResolveAliasWithSubPath(): void
    {
        $this->alias->set('@root', '/path/to/root');
        $result = $this->alias->resolve('@root/sub/file.txt');
        $this->assertEquals('/path/to/root/sub/file.txt', $result);
    }

    /**
     * Test resolving non-alias absolute path
     */
    public function testResolveNonAliasPath(): void
    {
        $result = $this->alias->resolve('/absolute/path');
        $this->assertEquals('/absolute/path', $result);
    }

    /**
     * Test resolving non-alias Windows path
     */
    public function testResolveNonAliasPathWindows(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $result = $this->alias->resolve('C:\\path\\to');
            $this->assertEquals('C:/path/to', $result);
        } else {
            $this->markTestSkipped('Windows path test only runs on Windows');
        }
    }

    /**
     * Test resolving path with context placeholder
     */
    public function testResolveWithContext(): void
    {
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{app_id}/log.txt', ['app_id' => 'myapp']);
        $this->assertEquals('/path/to/myapp/log.txt', $result);
    }

    /**
     * Test resolving path with multiple placeholders
     */
    public function testResolveWithMultiplePlaceholders(): void
    {
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{dir}/{file}.log', [
            'dir' => 'logs',
            'file' => 'app'
        ]);
        $this->assertEquals('/path/to/logs/app.log', $result);
    }

    /**
     * Test resolving path with context where placeholder key doesn't exist
     */
    public function testResolveWithContextNonExistentKey(): void
    {
        // If context doesn't have corresponding key, placeholder is preserved
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{app_id}/log.txt', ['other_key' => 'value']);
        $this->assertEquals('/path/to/{app_id}/log.txt', $result);
    }

    /**
     * Test resolving path with context having empty value
     */
    public function testResolveWithContextEmptyValue(): void
    {
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{key}/log.txt', ['key' => '']);
        $this->assertEquals('/path/to//log.txt', $result);
    }

    /**
     * Test resolving path with context having numeric value
     */
    public function testResolveWithContextNumericValue(): void
    {
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{id}/log.txt', ['id' => 123]);
        $this->assertEquals('/path/to/123/log.txt', $result);
    }

    /**
     * Test resolving non-existent alias (should throw exception)
     */
    public function testResolveNonExistentAlias(): void
    {
        $this->expectException(AliasNotExistException::class);
        $this->alias->resolve('@nonexistent');
    }

    /**
     * Test resolving non-existent alias with sub-path (should throw exception)
     */
    public function testResolveNonExistentAliasWithSubPath(): void
    {
        $this->expectException(AliasNotExistException::class);
        $this->alias->resolve('@nonexistent/sub/path');
    }

    /**
     * Test resolving nested alias (alias that references another alias)
     * Note: set() resolves the alias path once during set, but resolve() does not recursively resolve stored values
     */
    public function testResolveNestedAlias(): void
    {
        $this->alias->set('@root', '/path/to');
        // When setting @app with '@root/app', set() calls resolve('@root/app') which resolves to '/path/to/app'
        // So @app stores the resolved value '/path/to/app', not '@root/app'
        $this->alias->set('@app', '@root/app');
        $this->assertEquals('/path/to/app', $this->alias->get('@app'));
        
        // Resolve works with the stored resolved value
        $result = $this->alias->resolve('@app/controllers');
        $this->assertEquals('/path/to/app/controllers', $result);
    }

    /**
     * Test removing alias
     */
    public function testRemove(): void
    {
        $this->alias->set('@test', '/path/to/test');
        $this->assertTrue($this->alias->has('@test'));
        
        $this->alias->remove('@test');
        $this->assertFalse($this->alias->has('@test'));
        $this->assertNull($this->alias->get('@test'));
    }

    /**
     * Test removing alias with invalid name (should throw exception)
     */
    public function testRemoveInvalidName(): void
    {
        $this->expectException(InvalidAliasNameException::class);
        $this->alias->remove('invalid');
    }

    /**
     * Test JSON serialization
     */
    public function testJsonSerialize(): void
    {
        $this->alias->set('@test1', '/path/to/test1');
        $this->alias->set('@test2', '/path/to/test2');
        
        $json = $this->alias->jsonSerialize();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('@test1', $json);
        $this->assertArrayHasKey('@test2', $json);
    }

    /**
     * Test path concatenation with trailing slash
     */
    public function testPathConcatenationWithTrailingSlash(): void
    {
        // Test path concatenation: if alias path ends with /, subsequent path also starts with /
        $this->alias->set('@root', '/path/to'); // set will rtrim, so this won't end with /
        $result = $this->alias->resolve('@root/sub');
        $this->assertEquals('/path/to/sub', $result);
    }

    /**
     * Test path concatenation edge case with double slashes
     */
    public function testPathConcatenationEdgeCase(): void
    {
        // Even if alias is set with trailing slash through other means, test concatenation
        // Note: Since set method does rtrim, this test mainly verifies resolve concatenation logic
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root//sub'); // Double slash in path
        $this->assertEquals('/path/to//sub', $result); // Current implementation preserves double slash
    }

    /**
     * Test context key with special characters
     */
    public function testContextKeyWithSpecialCharacters(): void
    {
        // Test context key containing special characters (but not {})
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{key}.log', ['key' => 'value']);
        $this->assertEquals('/path/to/value.log', $result);
        
        // Note: Context key does NOT support containing {} characters
        // If context key contains {}, it won't match placeholder in path
        // This is because resolve creates replacement pattern as "{{$k}}" where $k is the context key
        // So if key is "{key}", it creates "{{key}}" which doesn't match "{key}" in path
        $result2 = $this->alias->resolve('@root/{key}.log', ['{key}' => 'value']);
        $this->assertEquals('/path/to/{key}.log', $result2); // Placeholder not replaced
    }

    /**
     * Test empty string alias path
     */
    public function testEmptyStringAliasPath(): void
    {
        // Test empty string alias path
        $this->alias->set('@empty', '');
        $result = $this->alias->resolve('@empty');
        $this->assertEquals('', $result);
        
        // Empty string path + sub-path
        $result2 = $this->alias->resolve('@empty/sub');
        $this->assertEquals('/sub', $result2);
    }

    /**
     * Test circular dependency (recursive resolution not supported)
     */
    public function testCircularDependency(): void
    {
        // Note: Recursive resolution is NOT supported
        // When setting @a with '@b', set() calls resolve('@b') which will fail if @b doesn't exist yet
        // So circular dependencies are prevented because set() requires the referenced alias to exist first
        
        // This will fail because @b doesn't exist when setting @a
        $this->expectException(AliasNotExistException::class);
        $this->alias->set('@a', '@b');
    }

    /**
     * Test context with dot notation in key
     */
    public function testContextWithDotNotation(): void
    {
        // Test dot-separated key (current implementation doesn't support, but test edge case)
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{app.id}/log.txt', ['app.id' => 'myapp']);
        $this->assertEquals('/path/to/myapp/log.txt', $result);
    }

    /**
     * Test multiple resolve calls consistency
     */
    public function testMultipleResolveCalls(): void
    {
        // Test consistency of multiple resolve calls
        $this->alias->set('@root', '/path/to');
        
        $result1 = $this->alias->resolve('@root/file.txt');
        $result2 = $this->alias->resolve('@root/file.txt');
        
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test resolve with context multiple calls
     */
    public function testResolveWithContextMultipleCalls(): void
    {
        // Test multiple calls with context
        $this->alias->set('@root', '/path/to');
        
        $result1 = $this->alias->resolve('@root/{app_id}/log.txt', ['app_id' => 'myapp']);
        $result2 = $this->alias->resolve('@root/{app_id}/log.txt', ['app_id' => 'myapp']);
        
        $this->assertEquals($result1, $result2);
        $this->assertEquals('/path/to/myapp/log.txt', $result1);
    }

    /**
     * Test resolve with empty context
     */
    public function testResolveWithEmptyContext(): void
    {
        // Test empty context
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{app_id}/log.txt', []);
        $this->assertEquals('/path/to/{app_id}/log.txt', $result);
    }

    /**
     * Test resolve without context parameter (using default)
     */
    public function testResolveWithoutContext(): void
    {
        // Test without passing context parameter (using default value)
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{app_id}/log.txt');
        $this->assertEquals('/path/to/{app_id}/log.txt', $result);
    }

    /**
     * Test set with placeholder in path (placeholder is stored but not processed in resolve)
     */
    public function testSetWithPlaceholderInPath(): void
    {
        // Note: set() does not support placeholder in path
        // If placeholder is included, it will be stored as-is and won't be processed during resolve
        $this->alias->set('@root', '/path/to');
        $this->alias->set('@log', '@root/{app_id}'); // Placeholder in alias path
        
        // The placeholder is stored in the alias value
        $stored = $this->alias->get('@log');
        $this->assertEquals('/path/to/{app_id}', $stored);
        
        // When resolving, placeholder in stored value is NOT replaced (because resolve only processes placeholders in the input path, not in stored alias values)
        $result = $this->alias->resolve('@log');
        $this->assertEquals('/path/to/{app_id}', $result);
        
        // Even with context, placeholder in stored value is not replaced
        $result2 = $this->alias->resolve('@log', ['app_id' => 'myapp']);
        $this->assertEquals('/path/to/{app_id}', $result2);
    }

    /**
     * Test resolve placeholder in sub-path
     */
    public function testResolvePlaceholderInSubPath(): void
    {
        // Test placeholder in sub-path
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{dir}/{file}.log', [
            'dir' => 'logs',
            'file' => 'app'
        ]);
        $this->assertEquals('/path/to/logs/app.log', $result);
    }

    /**
     * Test resolve with context partial replacement
     */
    public function testResolveWithContextPartialReplacement(): void
    {
        // Test partial placeholder replacement
        $this->alias->set('@root', '/path/to');
        $result = $this->alias->resolve('@root/{dir}/{file}.log', [
            'dir' => 'logs'
            // file not provided
        ]);
        $this->assertEquals('/path/to/logs/{file}.log', $result);
    }
}

