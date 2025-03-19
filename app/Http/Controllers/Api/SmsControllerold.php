<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Services;
use App\Models\SmsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SmsController extends Controller
{
    // get services list
    public function getServices()
    {
        try {
            $data = Services::where('price', '!=', 0)->where('selling_price', '!=', 0)->get();
            if ($data) {
                return response()->json([
                    'status' => true,
                    'message' => 'All of services list',
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No data foudn!',
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // create verification
    public function createVerify(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
            ]);

            $servicesData = Services::find($request->id);

            // data has or not
            if (!$servicesData) {
                return response()->json([
                    'status' => false,
                    'message' => "Invalid services",
                    'data' => []
                ]);
            }

            // check balance
            $currentUser = auth()->user();
            if ($servicesData['selling_price'] > $currentUser->coin) {
                return response()->json([
                    'status' => false,
                    'message' => "Low balance. Please deposit first!",
                    'data' => []
                ]);
            }
            
            // check already running sms or not
            $pendingSms = SmsHistory::where('status', 'pending')
                ->where('user_id', $currentUser->id)
                ->first();
            if($pendingSms){
                 return response()->json([
                        'status' => false,
                        'message' => 'A service already runing, Please wait until completed😣',
                        'data' => [],
                    ]);
            }
            

            // create verification   
            $result = Http::post('https://server.sms.numbersms.com/api/create_verification', [
                "areaCodeSelectOption" => [],
                "carrierSelectOption" => [],
                "serviceName" => $servicesData['service'],
                "capability" => strtolower($servicesData['capacity']),
                "serviceNotListedName" => $servicesData['service']
            ]);

            if ($result) {  
                $pollingCount = 0; 
                $pollingData = null;
                $pollingData = $this->polling($result->json());

                // Check if $pollingData is valid JSON
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid JSON data received',
                        'data' => [],
                    ]);
                }
                
                // Check if $resData is not null
                if ($pollingData === null) {
                    if($pollingCount == 0){
                      $pollingData = $this->polling($result->json());
                      $pollingCount = 1;
                    }else{
                        return response()->json([
                            'status' => false,
                            'message' => 'No data received from polling',
                            'data' => [],
                        ]);
                    }
                }
                
                if($pollingData['status']){
                    // create usage history
                    $lastData = SmsHistory::create([
                        'user_id' => $currentUser->id,
                        'service' => $servicesData['service'],
                        'service_id' => $pollingData['data']['id'],
                        'price' => $servicesData['selling_price'],
                        'number' => $pollingData['data']['number'],
                        'status' => 'pending',
                        'sms_data'=> json_encode($pollingData['data'])
                    ]);
                        
                    // user coin update
                    $currentUser->coin = $currentUser->coin - $servicesData['selling_price'];
                    $currentUser->save();
                        
                    return response()->json([
                       'status' => true,
                       'message' => 'Verification created successfully',
                       'data' => [
                          'id'=>$lastData->id,
                          'data'=>[
                              'number'=> $pollingData['data']['number'],
                              'createdAt'=>$pollingData['data']['createdAt'],
                              'endsAt'=>$pollingData['data']['endsAt'],
                              'serviceName'=>$pollingData['data']['serviceName']
                          ]
                       ]
                    ]);  
                }else{
                    return response()->json([
                        'status' => false,
                        'message' => 'Verification created faild',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Verification created faild. try again!',
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
    
    // polling
    public function polling($data){
         try {
            if(!$data){
                return false;
            }
            
            $url = $data['data']['href'];
            
            $result = Http::post('https://server.sms.numbersms.com/api/startpolling', [
                "href"=> $url
            ]);
            $data = $result->json();
            
            if(!$result){
                return [
                'status' => false,
                'message' => 'Polling faild',
                'data' => []
            ];
            }else{
                return [
                'status' => true,
                'message' => 'Polling data',
                'data' => $data
            ];
            }
         } catch (\Exception $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ];
        }
        
    }

    // get account details
    public function accountDetails(){
         try {
            $result = Http::post('https://server.sms.numbersms.com/api/me');
            if($result){
                $data = $result->json();
                return response()->json([
                    'status'=>true, 
                    'message'=>'Account details',
                    'data'=> $data['data']
                ]);
            }else{
                return response()->json([
                    'status'=>false, 
                    'message'=>'No details found!',
                    'data'=>[]
                ]);
            }
         } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
    
    // getotop
    public function getOtp(Request $request){
        try {
            $request->validate([
                'id' => 'required',
            ]);
            
            $servicesData = SmsHistory::find($request->id);
            
            // this id valid or not 
            if(!$servicesData){
                return response()->json([
                    'status'=>false,
                    'message'=>'Invalid request',
                    'data'=>[]
                ]);
            }
            
            // make json
            $data = json_decode($servicesData->sms_data);
            
            // data has or not 
            if(!$data){
                return response()->json([
                    'status'=>false,
                    'message'=>'Server error',
                    'data'=>[]
                ]);
            }
            
            // request on api
            $result = Http::post('https://server.sms.numbersms.com/api/getotp', [
                "href"=>$data->sms->href,
                "methods"=>$data->sms->method
            ]);
            
            // validate data has or not
            if($result){
                // make data in json
                $data = $result->json();
                // data has or not
                if(!$data){
                    return response()->json([
                        'status' => true,
                        'message' => "Pending..",
                        'data' => []
                    ]);
                }
                
                $smsData = $data['data'][0];
                // $newData = json_decode($smsData);
                if($data['count'] == 1){
                    $servicesData->otp  = $smsData['parsedCode'];
                    $servicesData->status  = 'complete';
                    $servicesData -> save();
                }
                return response()->json([
                    'status' => true,
                    'message' => "Opt data",
                    'data' => $smsData['smsContent'],
                    'otp'=>$smsData['parsedCode']
                ]);
            }else{
                return response()->json([
                    'status' => true,
                    'message' => "Somthings else worng",
                    'data' => []
                ]);
            }
        }catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
    
    // cancel services
    public function cancelServices(Request $request){
        try {
            $request->validate([
                'id' => 'required',
            ]);
            
            $servicesData = SmsHistory::find($request->id);
            
            // this id valid or not 
            if(!$servicesData){
                return response()->json([
                    'status'=>false,
                    'message'=>'Invalid request',
                    'data'=>[]
                ]);
            }
            
            if($servicesData->status == 'complete'){
                return response()->json([
                    'status'=>false,
                    'message'=>'Services already complete',
                    'data'=>[]
                ]);
            }
            
            if($servicesData->status == 'timeout'){
                return response()->json([
                    'status'=>false,
                    'message'=>'Services already timeout',
                    'data'=>[]
                ]);
            }
            
            // make json
            $data = json_decode($servicesData->sms_data);
            
            // data has or not 
            if(!$data){
                return response()->json([
                    'status'=>false,
                    'message'=>'Server error',
                    'data'=>[]
                ]);
            }
            
            // cancel true or not
            if($data->cancel->canCancel != true){
                return response()->json([
                    'status'=>false,
                    'message'=>'Cancel not accept',
                    'data'=>[]
                ]);
            }
            
            // check already cancel
            if($servicesData->status == 'canceled'){
                return response()->json([
                    'status'=>false,
                    'message'=>'This services alreday canceled',
                    'data'=>[]
                ]);
            }
            
            // request on api
            $result = Http::post('https://server.sms.numbersms.com/api/getotp', [
                "href"=> $data->cancel->link->href,
                'methods'=>'POST'
            ]);
            
            // validate data has or not
            if($result){
                $data = $result->json();
                $servicesData->status = 'canceled';
                $servicesData->save();
                
                $user = auth()->user();
                $user->coin = $user->coin + $servicesData->price;
                $user->save();
                
                return response()->json([
                    'status' => true,
                    'message' => "Service cancel",
                    'data' => []
                ]);
            }else{
                return response()->json([
                    'status' => true,
                    'message' => "Somthings else worng",
                    'data' => []
                ]);
            }
        }catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
    
    // re active
    public function reactiveSMS(Request $request){
        try{
            $request->validate([
                'id' => 'required',
            ]);
            
            $servicesData = SmsHistory::find($request->id);
            
            if(!$servicesData){
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid request',
                    'data' => []
                ]);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'Verification created successfully',
                'data' => [
                    'id'=>$servicesData->id,
                    'data'=>$servicesData
                ]
            ]);
        }catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
}
