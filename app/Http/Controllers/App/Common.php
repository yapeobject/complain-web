<?php
/**
 * Created by PhpStorm.
 * User: Infelicitas
 * Date: 7/3/2018
 * Time: 4:25 PM
 */

namespace App\Http\Controllers\App;


use App\LocalBank;
use App\LocalTopUpOperator;
use App\MbCharge;
use App\PaypalRate;
use App\PointTransferStat;
use App\Rate;
use App\Service;
use App\ServiceCountry;
use App\ServiceTransferMode;
use App\Setting;
use App\User;
use Carbon\Carbon;

trait Common
{
    protected $currencyMap = [
        'my' => 'MYR',
        'bangladesh_reload' => 'BDT',
        'bd_mb_reload' => 'BDT',
        'nepal_reload' => 'NPR',
        'indo_pulsa' => 'IDR',
        'sg' => 'SGD',
        'point_transfer' => 'AUD',
        'recipient_wallet' => 'BDT'
    ];
    public function getChargeInformation($user,$serviceId,$serviceAmount,$isSelfNumber = false,$receiverMobile = null,$localOperatorId = null,$operator = null,$package = null,$receiverGroupId = null,$countryId = null,$type = null,$remiCurrency = null){

        $service = Service::find($serviceId);
        if ($localOperatorId != null) {
            $localOperator = LocalTopUpOperator::where('keyword', $localOperatorId)->where('service_id', $serviceId)->first();
            if ($localOperator) {
                $localOperatorId = $localOperator->id;
            }
        }
        $minimum_balance = Setting::where('variable', 'minimum_balance')->first()->value;
        if ($countryId != null) {
            $country = ServiceCountry::where('keyword', $countryId)->where('service_id', $serviceId)->first();
            $rate_per_aud = $country->rate;
            $currency = $country->currency;
        } else {
            $currencyCode = $this->currencyMap[$service->short_code];
            $rate = Rate::where('currency', $currencyCode)->first();
            $rate_per_aud = $rate->rate_per_sgd;
            $currency = $rate->currency;
        }
        $country_id = null;
        if ($countryId != null && $service->type == 'recipient') {
            $countryofSlab = ServiceCountry::where('keyword', $countryId)->where('service_id', $serviceId)->first();
            if ($countryofSlab) {
                $country_id = $countryofSlab->id;
            }
        }
        $serviceType_id = null;
        if ($type != null && $countryId == null) {
            $serviceMode = ServiceTransferMode::where('keyword', $type)->where('service_id',$serviceId)->first();
            if ($serviceMode) {
                $serviceType_id = $serviceMode->id;
            }
        }
        $point_transfer_stat = null;
        if ($service->short_code === 'point_transfer') {
            if ($receiverGroupId == null) {
                $receiver = User::with('groups')->where('mobile_number', $receiverMobile)->first();
                $receiverGroupId = $receiver->groups()->first()->id;
            }

            $point_transfer_stat = PointTransferStat::where([
                'sender_group_id' => $user->groups()->first()->id,
                'receiver_group_id' => $receiverGroupId,
                'send' => 1,
            ])->first();
        }

        if ($service->short_code === 'bd_mb_reload') {
            $package = MbCharge::where('id', $package)->first();
            $package['mycash_points'] = $package['charging_amount'] / $rate['rate_per_sgd'];
            $package['mycash_points'] = number_format((double) $package['mycash_points'], 3, '.', ',');
            $result = $user->mbCharges($package, $minimum_balance, $rate['rate_per_sgd']);
            $data['package'] = $package;
        }
        else{
            if($remiCurrency != null){
                if($remiCurrency == 'AUD'){
                    $amount = $serviceAmount;
                    $receiveAmt = $serviceAmount * $rate_per_aud;
                }else{
                    $amount = $serviceAmount / $rate_per_aud;
                    $receiveAmt = $serviceAmount;
                }
            }else{
                $amount = $serviceAmount / $rate_per_aud;
            }

            $result = $user->charges($amount, $minimum_balance, $serviceId, $currency, $localOperatorId, $point_transfer_stat,$isSelfNumber,$country_id,$serviceType_id);
        }
        $result['currency'] = $currency;
        if($remiCurrency != null){
            $result['conversion-amount'] = number_format($receiveAmt,3);
            $result['current-rate'] = $rate_per_aud;
        }

        return $result;

    }
    public function getPaypalChargeInformation($user,$serviceId,$amount,$bankId){
        $service = Service::find($serviceId);
        if(!$service){
            $result = [];
            return $result;
        }
        $minimum_balance = Setting::where('variable', 'minimum_balance')->first()->value;
        $bankInformation = LocalBank::where('id',$bankId)->first();
        $rate = PaypalRate::where('currency', $bankInformation->currency)->first();
        if(!$rate){
            $result = [];
            return $result;
        }
        $rate_per_sgd = $rate->rate_per_currency;
        $currency = $rate->currency;
        $amount = $amount / $rate_per_sgd;
        $result = $user->charges($amount, $minimum_balance, $serviceId, $currency);

        return $result;
    }
    public function getTransferModes($serviceId = null)
    {
            if ($serviceId != null) {
                $service = Service::find($serviceId);
                if ($service) {
                    $modes = $service->transferModes()->where('enable', 1)->get();
                    $data['transfer_modes'] = $modes;
                } else {
                    $data['message'] = "failed";
                    $data['reason'] = "service not found";
                }
            } else {
                $data['message'] = "failed";
                $data['reason'] = "service id needed";
            }
            return $data;
    }

    public function getServiceDelayInformation($service,$token,$receiverNumber){
        $user = $token->user;
        if ($service->delay) {
            $transaction_report = $user->reports()->orderBy('created_at', 'desc')->where('service_id', $service->id)->where('receiver_mobile', 'like', '%'.$receiverNumber.'%')->where('refunded', 0)->first();
            if ($transaction_report) {
                $limit_time = $transaction_report->created_at->addMinutes(15)->toDateTimeString();
                $present_time = Carbon::now()->toDateTimeString();

                if ($present_time < $limit_time) {
                    return false;
                }else{
                    return true;
                }
            }else{
                return true;
            }
        }else{
            return true;
        }
    }
    public function getServiceOperatorInfo($serviceId,$isOnlyOperator = false,$operator = null,$isOnlyCountry = false,$isOnlyMode = false,$countrie = null,$mode = null)
    {

        if(!$this->token){
            return $this->respondWithError("Invalid Token.");
        }
        $data = [];
        $service = Service::find($serviceId);
        $packages = [];
        if ($service) {
            if(in_array($service->short_code,['bangladesh_reload','nepal_reload','bd_mb_reload','indo_pulsa'])){
                $operators = $service->operators()->with('mbPackages')->where('enable', 1);
                if($service->short_code =='nepal_reload'){
                    $packages = $service->amounts()->where('enable', 1)->get();
                }
                $countries = $service->countries()->where('enable', 1);
                $modes = $service->transferModes()->where('enable', 1);
            }
            elseif(in_array($service->short_code,['metro','city','recipient_wallet'])){
                $operators = $service->operators()->where('enable', 1);
                $countries = $service->countries()->where('enable', 1);
                $modes = $service->transferModes()->where('enable', 1);
            }else{
                $operators = $service->localOperators()->where('enable', 1);
                $countries = $service->countries()->where('enable', 1);
                $modes = $service->transferModes()->where('enable', 1);
            }
            $operators = $isOnlyOperator ? $operators->where('keyword',$operator)->first() : $operators->get();
            $countries = $isOnlyCountry ? $countries->where('keyword',$countrie)->first() : $countries->get();
            $modes = $isOnlyMode ? $modes->where('keyword',$mode)->first() : $modes->get();
            $type = $service->type;
            $data['service_name'] = $service->name;
            $data['service_code'] = $service->short_code;
            $data['type'] = $type;
            $data['operators'] = $operators;
            $data['packages'] = $packages;
            $data['countries'] = $countries;
            $data['transfer_modes'] = $modes;
        }
        return $data;
    }
    function removeDialingCode($number,$country){
        $dialingCode = $this->getCountryDialingCode($country);
        $dialingCodeLength = strlen($dialingCode);
        $dialingCodeNewLength = $country =='bangladesh' ? $dialingCodeLength-1 : $dialingCodeLength;
        $returnNumber = substr($number, $dialingCodeNewLength);
        return $returnNumber;
    }
    function getCountryDialingCode($country){
        $dialingCode = [
            'singapore' => '65',
            'malaysia' => '6',
            'nepal' => '977',
            'indonesia' => '62',
            'bangladesh' => '880'
        ];
        return $dialingCode = $dialingCode[$country];
    }

    public function getStatCheck($serviceId = null,$serviceAmount = null,$country = null)
    {
        if(!$this->token){
            return $this->respondWithError("Invalid Token.");
        }
        $tokenUser =  $this->token->user ? $this->token->user : null;
        $group = $tokenUser->groups()->first();
        if($country != null){
            if(is_numeric($country)){
                $countryId = $country;
            }else{
                $country = ServiceCountry::where('keyword',$country)->where('service_id', $serviceId)->first();
                $countryId = $country->id;
            }
            $countryCurrencyRate = ServiceCountry::where('id', $countryId)->where('service_id', $serviceId)->first();
            $stat = $group->stat()->where('service_id', $serviceId)->where('country_id', $countryId)->get();

        }else{
            $countryCurrencyRate = ServiceCountry::where('keyword', 'bangladesh')->where('service_id', $serviceId)->first();

            $stat = $group->stat()->where('service_id', $serviceId)->get();

        }


        $rate = $countryCurrencyRate ->rate;

        $amount = $serviceAmount / $rate;


        $statFlag = 0;
        if($stat->count() > 0){
        $lowerLimit = array_column($stat->toArray(), 'lower_limit');
        $higherLimit = array_column($stat->toArray(), 'higher_limit');
        $min = min($lowerLimit);
        $max = max($higherLimit);
        $message = 'Accepted';
        foreach ($stat as $state) {
            if ($this->nmberBetween($amount, $state->higher_limit, $state->lower_limit)) {
                $statFlag = 1;

                break;
            }else{
                if ($amount < $min) {
                    $message = 'Sorry, You can not place order less than AUD ' . $min . '';
                }
                if ($amount > $max) {
                    $message = 'Sorry, You can not place order more than AUD ' . $max . '';
                }
            }
        }
        }else{
            $message = 'Sorry, You can not place order. Please Contact with Admin.';
        }
        $data = [
            'message' => $message,
            'status'   =>  $statFlag
        ];

        return $data;
    }

    function nmberBetween($varToCheck, $high, $low) {
        if($varToCheck < $low) return false;
        if($varToCheck > $high) return false;
        return true;

    }

    public function getUser($userId = null)
    {
            $user = User::where('mobile_number',$userId)->where('active', 1)->first();
            if ($user) {
                $data['user_name'] = $user->name;
                $data['passport_no'] = $user->profile->id_no;
                $data['expire_date'] = $user->profile->id_expire_date->toFormattedDateString();
                $data['mobile_number'] = $user->mobile_number;
                $data['group'] = $user->groups()->first();
                $data['found'] = true;
                $data['id'] = $user->id;
            } else {
                $data['user_name'] = "User Not Found";
                $data['found'] = false;
            }

        return $data;
    }

    public function getCountries($serviceId = null)
    {
           if ($serviceId != null) {
                $service = Service::find($serviceId);
                if ($service) {
                    $countries = $service->countries()->where('enable', 1)->get();
                    $data['countries'] = $countries;
                } else {
                    $data['message'] = "failed";
                    $data['reason'] = "service not found";
                }
            } else {
                $data['message'] = "failed";
                $data['reason'] = "service id needed";
            }


        return $data;
    }

    public function getUserRecipients($senderMobile = null, $type = null)
    {

            if ($senderMobile != null) {
                $user = User::where('mobile_number', $senderMobile)->first();
                if ($user) {
                    if ($type != null) {
                        $recipients = $user->recipients()->where('transfer_type', $type)->with('recipientBankBranch')->get();
                    } else {
                        $recipients = $user->recipients;
                    }
                    $data['recipients'] = $recipients;
                } else {
                    $data['message'] = "failed";
                    $data['reason'] = "user not found";
                }
            } else {
                $data['message'] = "failed";
                $data['reason'] = "user number needed";
            }

        return response()->json($data);
    }
}