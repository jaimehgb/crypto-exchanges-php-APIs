<?php

include 'exchange.php';

class CEX_IO extends Exchange
{
    
    private $privateKey = '';
    private $publicKey = '';
    private $user_id = '';
    
    
    public function __construct($priv, $pub, $user_id)
    {
        $this->privateKey = $priv;
        $this->publicKey = $pub;
        $this->user_id = $user_id;
        
        // check keys permissions
        // .......
    }
    
    public function setSymbols()
    {
        $url = 'https://cex.io/api/currency_limits';
        $json = json_decode(file_get_contents($url));
        $pairs = $json->data->pairs;
        
        foreach($pairs as $pair)
        {
            $p = $pair->symbol1 . "_" . $pair->symbol2;
            $this->symbols[] = $p;
        }
        
        return $this->symbols;
    }
    
    public function updatePrices()
    {
        $url = 'https://cex.io/api/tickers/USD/EUR/RUB/BTC';
        $json = json_decode(file_get_contents($url));
        if($json->ok == 'ok')
        {
            foreach($json->data as $pair)
            {
                $pair_name = explode(':',$pair->pair)[0] . "_" . explode(':',$pair->pair)[1];
                $this->prices[$pair_name]['high'] = $pair->high;
                $this->prices[$pair_name]['low'] = $pair->low;
                $this->prices[$pair_name]['bid'] = $pair->bid;
                $this->prices[$pair_name]['ask'] = $pair->ask;
                $this->prices[$pair_name]['last'] = $pair->last;
                $this->prices[$pair_name]['time'] = $pair->timestamp;
            }
        }
        
        return $this->prices;
    }
    
    public function apiCall($request, $url)
    {
        $time = time();
        $msg = $time . $this->user_id . $this->publicKey;
        $signature = hash_hmac('sha256', $msg, $this->privateKey);
        
        $req = $request;
        $req['key'] = $this->publicKey;
        $req['signature'] = $signature;
        $req['nonce'] = $time;
        
        $curl = curl_init($url);
    	curl_setopt($curl, CURLOPT_POST, true);
    	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($req));
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	$response = curl_exec($curl);
    	curl_close($curl);
    	//echo curl_error($curl);
    	return $response;
    }
    
    public function placeOrder($symbol, $amount, $price, $side)
    {
        $url = "https://cex.io/api/place_order/";
        $url .= explode('_', $symbol)[0] . '/' . explode('_', $symbol)[1];
       
        $req = array(
            'type'=>$side,
            'amount'=>$amount,
            'price'=>$price
        );
        
        $res = $this->apiCall($req, $url);
        return $res;
    }
    
    public function buy($symbol, $amount, $price)
    {
        return $this->placeOrder($symbol, $amount, $price, 'buy');
    }
    
    public function sell($symbol, $amount, $price)
    {
        return $this->placeOrder($symbol, $amount, $price, 'sell');
    }
    
    public function instant($symbol, $amount, $side)
    {
        $url = "https://cex.io/api/place_order/";
        $url .= explode('_', $symbol)[0] . '/' . explode('_', $symbol)[1];
       
        $req = array(
            'type'=>$side,
            'amount'=>$amount,
            'price'=>$price,
            'order_type'=>'market'
        );
        
        $res = $this->apiCall($req, $url);
        return $res;
    }
    
    public function cancelOrder($id)
    {
        $url = 'https://cex.io/api/cancel_order/';
        $req = array(
            'id'=>$id    
        );
        return $this->apiCall($req, $url);
    }
    
    public function orderStatus($list = array())
    {
        if(count($list) > 20) return false;
        $url = 'https://cex.io/api/active_orders_status';
        $req['orders_list'] = $list;
        $res = $this->apiCall($req, $url);
        if(json_decode($res)->ok == 'ok')
        {
            return $res;
        }
        else
        {
            return false;
        }
    }
    
    public function orderDetails($id)
    {
        $url = 'https://cex.io/api/get_order/';
        $req = array(
            'id'=>$id    
        );
        
        return $this->apiCall($req, $url);
    }
    
    public function activeOrders()
    {
        $url = 'https://cex.io/api/open_orders/';
        $res = $this->apiCall('', $url);
        return $res;
    }
    
    public function getBalance()
    {
        $url = 'https://cex.io/api/balance/';
        $json = json_decode($this->apiCall('', $url));
        $balances['BTC'] = $json->BTC;
        $balances['USD'] = $json->USD;
        $balances['EUR'] = $json->EUR;
        $balances['GHS'] = $json->GHS;
        $balances['LTC'] = $json->LTC;
        $balances['ETH'] = $json->ETH;
        $balances['RUB'] = $json->RUB;
        
        return $balances;
    }
}
