<?php

namespace App\Services;

use GuzzleHttp\Client;

class OpenAIService
{
    protected $client;
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = env('OPENAI_API_URL');
        $this->apiKey = env('OPENAI_API_KEY');
    }

    public function sendMessage($message, $functions = [])
    {
        $response = $this->client->post($this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4', // o el modelo que estés utilizando
                'messages' => [
                    ['role' => 'user', 'content' => $message]
                ],
                'functions' => $functions, // funciones API opcionales
                'function_call' => 'auto' // permite que el modelo decida cuándo llamar a la función
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
