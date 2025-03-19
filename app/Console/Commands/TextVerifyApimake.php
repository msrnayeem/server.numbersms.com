<?php

namespace App\Console\Commands;

use App\Models\Services;
use Illuminate\Console\Command;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Http;


class TextVerifyApimake extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:text-verify-apimake';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commad get all of services from textverify api';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get all services from api
        try {
            $response = Http::post('https://server.sms.numbersms.com/api/service');
            $jsonData = json_decode($response->body(), true);
            // check data has or not
            if ($jsonData) {
                $services = $jsonData['services'];
                foreach ($services as $item) {
                    $bdExit = Services::where('service', $item['serviceName'])->first();
                    if ($bdExit) {
                        continue;
                    } else {
                        Services::create([
                            'service' => $item['serviceName'],
                            'capacity' => $item['capability']
                        ]);
                    }
                }
            } else {
                Logger()->info('Job errro No services data found');
            }
        } catch (\Exception $th) {
            Logger()->info('Job error ' . $th->getMessage());
        }
    }
}
