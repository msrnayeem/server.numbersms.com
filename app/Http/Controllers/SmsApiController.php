<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsApiController extends Controller
{
    private $baseUrl;
    private $apiKey;
    private $email;

    public function __construct()
    {
        $this->baseUrl = env('TEXTVERIFIED_BASEURL');
        $this->apiKey = env('TEXTVERIFIED_APIKEY');
        $this->email = env('TEXTVERIFIED_EMAIL');
    }

    public function generateBearerToken()
    {
        try {
            $headers = [
                'X-API-KEY' => env('TEXTVERIFIED_APIKEY'),
                'X-API-USERNAME' => env('TEXTVERIFIED_EMAIL')
            ];

            $response = Http::withHeaders($headers)->post(env('TEXTVERIFIED_BASEURL') . '/api/pub/v2/auth');

            // if ($response->failed()) {
            //     return response()->json(['error' => 'Failed to generate token', 'details' => $response->body()], 400);
            // }

            return $response;
        } catch (\Exception $e) {
            return response()->json(['error' => 'Exception: ' . $e->getMessage()], 500);
        }
    }


    private function makeApiRequest($endpoint, $method = 'GET', $data = [], $params = [], $urltype = false)
    {
        $token = $this->generateBearerToken();
        if (!$token) {
            return response()->json(['error' => 'Failed to generate token'], 500);
        }

        $url = $urltype ? $endpoint : "{$this->baseUrl}{$endpoint}";

        try {
            $response = Http::withToken($token)->$method($url, $method === 'GET' ? $params : $data);

            if ($response->successful()) {
                return $response->json();
            }
            return ['error' => 'API request failed', 'details' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Error making API request: ' . $e->getMessage());
            return ['error' => 'API request failed: ' . $e->getMessage()];
        }
    }

    public function service()
    {
        $result = $this->makeApiRequest('/api/pub/v2/services', 'GET', [], ['numberType' => 'mobile', 'reservationType' => 'verification']);

        if (isset($result['error'])) {
            return response()->json(['status' => 'error', 'error' => $result['error']]);
        }

        $services = array_filter($result, fn($service) => strtolower($service['capability'] ?? '') === 'sms');
        return response()->json(['status' => 'success', 'services' => $services]);
    }

    public function verificationPricing(Request $request)
    {
        $data = $request->only(['serviceName', 'areaCode', 'carrier', 'numberType', 'capability']);
        $data['numberType'] = $data['numberType'] ?? 'mobile';
        $data['capability'] = $data['capability'] ?? 'sms';

        $result = $this->makeApiRequest('/api/pub/v2/pricing/verifications', 'POST', $data);

        return isset($result['error'])
            ? response()->json(['status' => 'error', 'error' => $result['error']])
            : response()->json(['status' => 'success', 'pricing' => $result]);
    }

    public function createVerification(Request $request)
    {
        $body = $request->only(['areaCodeSelectOption', 'carrierSelectOption', 'serviceName', 'capability', 'serviceNotListedName']);

        $result = $this->makeApiRequest('/api/pub/v2/verifications', 'POST', $body);
        return response()->json(['status' => 'success', 'data' => $result]);
    }

    public function startPolling(Request $request)
    {
        $href = $request->input('href');
        if (!$href) {
            return response()->json(['error' => 'Missing verification_href in the request'], 400);
        }

        $lastSegment = basename($href);
        $result = $this->makeApiRequest("/api/pub/v2/verifications/{$lastSegment}", 'GET');
        return response()->json($result);
    }

    public function getOtp(Request $request)
    {
        $href = $request->input('href');
        $method = strtoupper($request->input('methods', 'GET'));
        if (!$href) {
            return response()->json(['error' => 'Missing href in the request'], 400);
        }

        $result = $this->makeApiRequest($href, $method, [], [], true);
        return response()->json($result);
    }

    public function me()
    {
        // $result = $this->makeApiRequest('/api/pub/v2/account/me', 'GET');
        // return isset($result['error'])
        //     ? response()->json(['status' => 'error', 'error' => $result['error']])
        //     : response()->json(['status' => 'success', 'data' => $result]);
        $token = $this->generateBearerToken();
        return response()->json($token);
    }
}
