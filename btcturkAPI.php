<?php

include 'exchange.php';

class BTCTurk extends Exchange
{
    private $endpoint = 'https://www.btcturk.com/api';
    private $publicKey = '';
    private $privateKey = '';
    
    
    public function __construct($priv, $pub)
    {
        $this->publicKey = $pub;
        $this->privateKey = $priv;
        
        //check if keys are correct
        // .....
        
    }
    
    public function setSymbols()
    {
        //this exchange just trades TRY/BTC
        $this->symbols[] = 'btctry';
    }
    
    public function updatePrices()
    {
        $url = $this->endpoint . '/ticker';
        $json = json_decode(file_get_contents($url));
        
        $this->prices['btctry']['high'] = $json->high;
        $this->prices['btctry']['low'] = $json->low;
        $this->prices['btctry']['bid'] = $json->bid;
        $this->prices['btctry']['ask'] = $json->ask;
        $this->prices['btctry']['last'] = $json->last;
        $this->prices['btctry']['time'] = $json->timestamp;
    }
    
    public function apiCall($payload, $url)
    {
        
        $time = time();
        $msg = $this->publicKey . $time;
        $signature = hash_hmac("sha256", $msg, base64_decode($this->privateKey), true);
        $signature = base64_encode($signature);

        $headers = array(
            "X-PCK: " . $this->publicKey,
            'X-Stamp: ' . $time,
            'X-Signature: ' . $signature
        );

        
        $curl = curl_init($url);
    	curl_setopt($curl, CURLOPT_POST, true);
    	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload));
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	$response = curl_exec($curl);
    	curl_close($curl);
    	return $response;        
    }
    
    public function GETcall($url)
    {
        //auth
        $time = time();
        $msg = $this->publicKey . $time;
        $signature = hash_hmac("sha256", $msg, base64_decode($this->privateKey), true);
        $signature = base64_encode($signature);

        $headers = array(
            "X-PCK: " . $this->publicKey,
            'X-Stamp: ' . $time,
            'X-Signature: ' . $signature
        );
        
        $curl = curl_init($url);
    	curl_setopt($curl, CURLOPT_POST, false);
    	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	$response = curl_exec($curl);
    	curl_close($curl);
    	return $response;  
    }
    
    public function cancelOrder($id)
    {
        $url = $this->endpoint . '/cancelOrder';
        $request = array(
            'id'=>$id    
        );
        return $this->apiCall($request, $url);
    }
    
    public function activeOrders()
    {
        $url = $this->endpoint . '/openOrders';
        return $this->GETcall($url);
    }
    
    public function transactions($limit ='', $sort = '')
    {
        $url = $this->endpoint . '/userTransactions';
        if(!empty($limit) || !empty($sort))
        {
            $url .= '?';
            
            if(!empty($limit))
            {
                $url .= 'limit=' . $limit;
            }
            if(!empty($sort))
            {
                if(!empty($limit))
                {
                    $url .= "&sort=" . $sort;
                }
                else
                {
                    $url .= "sort=" . $sort;
                }
            }
        }
        
        return $this->GETcall($url);
    }
    
    public function buy($amount, $price, $market = false)
    {
        $url = $this->endpoint . '/buy';
        if($market)
        {
            $request = array(
                'IsMarketOrder'=>1,
                'Type'=>'BuyBtc',
                'Price'=>0,
                'Amount'=>$amount,
                'Total'=>$amount*$price
            );            
        }
        else
        {
            $request = array(
                'IsMarketOrder'=>0,
                'Type'=>'BuyBtc',
                'Price'=>"$price",
                'Amount'=>"$amount",
                'Total'=>'' 
            );
        }
        
        return $this->apiCall($request, $url);
    }
    
    public function sell($amount, $price, $market = false)
    {
        $url = $this->endpoint . '/sell';
        if($market)
        {
            $request = array(
                'IsMarketOrder'=>1,
                'Type'=>'SelBtc',
                'Price'=>0,
                'Amount'=>$amount,
                'Total'=>$amount*$price
            );            
        }
        else
        {
            $request = array(
                'IsMarketOrder'=>0,
                'Type'=>'SelBtc',
                'Price'=>$price,
                'Amount'=>$amount,
                'Total'=>'' 
            );
        } 
        
        return $this->apiCall($request, $url);
    }
    
}
