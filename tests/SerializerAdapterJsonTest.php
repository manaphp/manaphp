<?php
namespace Tests;

use ManaPHP\Serializer\Adapter\Json;
use PHPUnit\Framework\TestCase;

class SerializerAdapterJsonTest extends TestCase
{
    public function test_serialize()
    {
        $serializer = new Json();

        $data = true;
        $this->assertSame($data, $serializer->deserialize($serializer->serialize($data)));

        $data = false;
        $this->assertSame($data, $serializer->deserialize($serializer->serialize($data)));

        $data = 1;
        $this->assertSame($data, $serializer->deserialize($serializer->serialize($data)));

        $data = '1';
        $this->assertSame($data, $serializer->deserialize($serializer->serialize($data)));

        $data = 'abc';
        $this->assertSame($data, $serializer->deserialize($serializer->serialize($data)));

        $data = [];
        $this->assertSame($data, $serializer->deserialize($serializer->serialize($data)));

        $data = ['ab' => 'abc'];
        $this->assertSame($data, $serializer->deserialize($serializer->serialize($data)));

        $data = new \stdClass();
        $data->a = 1;
        $data->b = 2;
        $this->assertSame((array)$data, $serializer->deserialize($serializer->serialize($data)));
    }
}