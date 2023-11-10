<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;

class Output implements OutputInterface
{
    #[Autowired] protected ResponseInterface $response;

    public function json(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        $response = $this->response->json($data, $status);

        foreach ($headers as $key => $value) {
            $response->setHeader($key, $value);
        }

        return $response;
    }

    public function fail(string $message = '', mixed $data = null): ResponseInterface
    {
        return $this->response->json(['code' => -1, 'msg' => $message, 'data' => $data]);
    }

    public function success(string $message = '', mixed $data = null): ResponseInterface
    {
        return $this->response->json(['code' => 0, 'msg' => $message, 'data' => $data]);
    }
}