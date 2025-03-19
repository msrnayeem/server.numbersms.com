<?php

namespace App\Console\Commands;

use App\Models\Services;
use App\Models\DiscountModel;
use Illuminate\Console\Command;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Http;

class TextVerifyPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:text-verify-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commad get service price from textverify api';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $discount = DiscountModel::first();
            
            $query = Services::where('price', 0)
                ->orWhere('selling_price', 0);
            
            if ($discount) {
                $query->orWhere('discount', '!=', $discount->discount);
            }
            
            $servicesList = $query->first();
            
            if ($servicesList) {
                $response = Http::post('https://server.sms.numbersms.com/api/verification_pricing', [
                    "serviceName" => $servicesList['service'],
                    "areaCode" => true,
                    "carrier" => true,
                    "numberType" => "mobile",
                    "capability" => $servicesList['capacity']
                ]);
            
                $jsonPriceData = json_decode($response->body(), true);
            
                if ($jsonPriceData) {
                    $newPrice = $jsonPriceData['pricing']['price'];
                    $newDiscountRate = $discount ? (1 - ($discount->discount / 100)) : 1;
                    $newSellingPrice = round($newPrice * $newDiscountRate, 2);
            
                    $servicesList->price = $newPrice;
                    $servicesList->discount = $discount->discount;
                    $servicesList->selling_price = $newSellingPrice;
                    $servicesList->save();
                } else {
                    logger()->info('Job error: API error');
                }
            } else {
                logger()->info('Job error: Database error');
            }
        } catch (\Exception $th) {
            Logger()->info('Job erro ' . $th->getMessage());
        }
    }
}
