<?php
require_once __DIR__ . '/../config/api.php';

class CatsyPIMClient {
    public function getProducts($params = []) {
        $url = CATSY_API_BASE_URL . '/products';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . CATSY_API_TOKEN,
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($status !== 200) {
            throw new Exception("Catsy PIM API request failed: HTTP $status - " . $response);
        }
        
        return json_decode($response, true);
    }
    
    public function getProductBySKU($sku) {
        $url = CATSY_API_BASE_URL . '/products/' . urlencode($sku);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . CATSY_API_TOKEN,
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($status === 404) {
            return null; // Product not found
        }
        
        if ($status !== 200) {
            throw new Exception("Catsy PIM product request failed: HTTP $status - " . $response);
        }
        
        return json_decode($response, true);
    }
}

class FocusPointClient {
    private $accessToken;
    private $tokenExpires;
    
    public function __construct() {
        $this->loadCachedToken();
    }
    
    private function authenticate() {
        try {
            $authData = [
                'email' => FOCUSPOINT_API_EMAIL,
                'password' => FOCUSPOINT_API_PASSWORD
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => FOCUSPOINT_AUTH_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($authData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($status !== 200) {
                throw new Exception("FocusPoint authentication failed: HTTP $status");
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['token'])) {
                throw new Exception("Invalid token response");
            }
            
            $this->accessToken = $data['token'];
            $this->tokenExpires = time() + 3600 - API_TOKEN_EXPIRY_BUFFER;
            $this->cacheToken();
            
        } catch (Exception $e) {
            error_log("FocusPoint Auth Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateProduct($productData) {
        if ($this->isTokenExpired()) {
            $this->authenticate();
        }
        
        $url = FOCUSPOINT_API_BASE_URL . '/Products/Update';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($productData),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($status === 401) {
            // Token expired, try once more
            $this->authenticate();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        
        if ($status !== 200) {
            throw new Exception("FocusPoint update failed: HTTP $status - " . $response);
        }
        
        return json_decode($response, true);
    }
    
    private function isTokenExpired() {
        return empty($this->accessToken) || time() >= $this->tokenExpires;
    }
    
    private function loadCachedToken() {
        if (file_exists(API_TOKEN_CACHE_FILE)) {
            $data = json_decode(file_get_contents(API_TOKEN_CACHE_FILE), true);
            if ($data && $data['expires'] > time()) {
                $this->accessToken = $data['access_token'];
                $this->tokenExpires = $data['expires'];
            }
        }
    }
    
    private function cacheToken() {
        file_put_contents(API_TOKEN_CACHE_FILE, json_encode([
            'access_token' => $this->accessToken,
            'expires' => $this->tokenExpires,
            'created' => time()
        ]));
    }
}