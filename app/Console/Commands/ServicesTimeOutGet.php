<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsHistory;
use Carbon\Carbon;

class ServicesTimeOutGet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:services-time-out-get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commad verify service time out';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $servicesData = SmsHistory::where('status', 'pending')->get();
        
        foreach ($servicesData as $data) {
            $smsData = json_decode($data->sms_data, true);
            
            if ($smsData && isset($smsData['createdAt'], $smsData['endsAt'])) {
                $createdAt = Carbon::parse($smsData['createdAt']);
                $endAt = Carbon::parse($smsData['endsAt']);
                
                if (Carbon::now()->greaterThanOrEqualTo($endAt)) {
                    $data->status = 'timeout';
                    $data->save();
                }
            }
        }
    }
}
