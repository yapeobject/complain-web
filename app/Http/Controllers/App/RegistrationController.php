<?php
/**
 * Created by PhpStorm.
 * User: Infelicitas
 * Date: 6/24/2018
 * Time: 2:38 PM
 */

namespace App\Http\Controllers\App;

use App\UserToken;
use Illuminate\Http\Request;
use App\Permission;
use App\User;
use App\ComplainCategory;
use App\ComplainGroup;
use App\UserType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends ApiController
{
    public function register(Request $request)
    {
        $inputs = $request->all();
        $rules = array (
            'user_id' => 'required|email|unique:user_account',
            'password' => 'required',
            'first_name' => 'required',
            'ic_number' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator-> fails()){
            return $this->respondValidationError('Fields Validation Failed.', $validator->errors()->all());
        }

        $user = User::where('user_id',$inputs['user_id'])->first();
        if($user){
            return $this->respondWithError("User already exists.");
        }

        $inputs['user_type'] = "user";
        $inputs['status'] = "active";
        $user = User::create($inputs);

        if(!$user){
            return $this->respondInternalError("An error occurred while performing an action!");
        }

        return $this->respond([
            'status' => 'success',
            'status_code' => $this->getStatusCode(),
            'message' => 'Registration successful!',
        ]);
    }

    public function getCombo()
    {
        $comboList = array();
        $complainCategory = ComplainCategory::orderBy('category', 'asc')->get();
        $comboList["complain-category"] = $complainCategory;

//        $complainGroup = ComplainGroup::orderBy('complain_group', 'asc')->get();
//        $comboList["complain-group"] = $complainGroup;
        return $this->respond([
            'status' => 'success',
            'status_code' => $this->getStatusCode(),
            'message' => 'successful!',
            'response' => $comboList
        ]);
    }

    public function newUser(Request $request, Permission $permission,UserToken $token)
    {
        $inputs = $request->all();
        $rules = array (
            'mobile_number' => 'required|numeric|unique:users',
            'fname' => 'required',
            'lname' => 'required',
            'id_type' =>'required',
            'id_no' => 'required',
            'id_expire_date' => 'required',
            'address' => 'required',
            'post_code' => 'required',
            'occupation' => 'required',
            'profile_photo' => 'required',
            'scan' => 'required',
            'gender' => 'required',
            'nationality' => 'required',
            'marital_status' => 'required',
            'user_dob' => 'required',
            'email' => 'required|email|unique:profiles'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator-> fails()){
            return $this->respondValidationError('Fields Validation Failed.', $validator->errors()->all());
        }
        $header = $request->header('token');
        $token = $token->where('token', $header)->first();
        if ($token) {
            $user = User::where('mobile_number', $inputs['mobile_number'])->first();
            if($user){
                return $this->respondWithError("User already exists.");
            }
            $default_password = '88'.strtolower(str_random(6));
            $default_pin = random_int(100000, 999999);
            $inputs['parent_id'] = $token->user->id;
            $inputs['password'] = Hash::make($default_password);
            $inputs['pin'] = Hash::make($default_pin);
            $inputs['active'] = 1;
            $inputs['name'] = $inputs['fname'].' '.$inputs['lname'];
            $user = User::create($inputs);
            $user->is_verified = 2;
            $user->save();

            if(!$user){
                return $this->respondInternalError("An error occurred while performing an action!");
            }

            $profile = $user->profile()->create($inputs);
            if ($request->hasFile('profile_photo')) {
                // Profile Photo
                $profile->profile_photo = uploadDocuments($request->file('profile_photo'),'user_photo_',$user->id);
            }
            // Scan 1
            if ($request->hasFile('scan')) {
                $profile->scan = uploadDocuments($request->file('scan'),'user_scan_1_',$user->id,'id_images');
            }
            // Scan 2
            if ($request->hasFile('scan_one')) {
                $profile->scan_one = uploadDocuments($request->file('scan_one'),'user_scan_2_',$user->id,'id_images');
            }
            // Scan 3
            if ($request->hasFile('scan_two')) {
                $profile->scan_two = uploadDocuments($request->file('scan_two'),'user_scan_3_',$user->id,'id_images');
            }
            $profile->present_address = $inputs['address'] ?? '';
            $profile->date_of_birth = $inputs['date_of_birth'] ?? '';
            $profile->id_no = $inputs['id_no'] ?? '';
            $profile->id_expire_date = $inputs['id_expire_date'] ?? '';
            $profile->post_code = $inputs['post_code'] ?? '';
            $profile->state = $inputs['state'] ?? '';
            $profile->occupation = $inputs['occupation'] ?? '';
            $profile->country = $inputs['nationality'] ?? '';
            $profile->gender = $inputs['gender'] ?? '';
            $profile->marrital_status = $inputs['marital_status'] ?? '';
            $profile->save();

            $user->point()->create(['available' => 0, 'total' => 0]);
            $permission = $permission->where('name', 'user.default')->get()->first();
            $group = $permission->groups()->first();
            if ($group) {
                $user->attachGroup($group);
            }

            try{
                $smsFormat = Setting::where('variable', 'sms_format_registration')->first()->value;
                $text = sprintf($smsFormat, $default_password, $default_pin);
                $user->sendSms($text, $user->mobile_number);
                $user->sendAccVerificationMail($user->mobile_number,$user->name);
            }catch(\Exception $e){
            }
            return $this->respond([
                'status' => 'success',
                'status_code' => $this->getStatusCode(),
                'message' => 'Registration successful!',
            ]);
        }else{
            return $this->respondWithError("Invalid Token.");
        }
    }

    public function passwordReset(Request $request, User $user)
    {
        $inputs = $request->all();
        $rules = array (
            'mobile_number' => 'required|numeric',
            'email' => 'required',
            'send_type' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator-> fails()){
            return $this->respondValidationError('Fields Validation Failed.', $validator->errors()->all());
        }
        $user = $user->where('mobile_number', trim($inputs['mobile_number']))->with('profile')->first();
        if(!$user){
            return $this->respondWithError("No user found with ".$inputs['mobile_number']." ID!");
        }
        if($inputs['email'] != $user['profile']['email']){
            return $this->respondWithError("Phone Number and Email Address did not match!");
        }
        $default_password = '88'.strtolower(str_random(6));
        $default_pin = mt_rand(100000, 999999);
        //$user = $user->find($inputs['id']);
        $isSuperAdmin = $user->groups()->first() ? $user->groups()->first()->name == "Super Admin" : false;
        if (!$isSuperAdmin) {
            $user->pin = Hash::make($default_pin);
            $user->password = Hash::make($default_password);
            $user->save();
            $data['message'] = 'Success';
            if($inputs['send_type'] == 2){
                try{
                    $smsFormat = Setting::where('variable', 'sms_format_reset_auth')->first()->value;
                    $text = sprintf($smsFormat, $default_password, $default_pin);
                    $user->sendSms($text, $user->mobile_number);
                    $user->adjustResetSmsCharge($user->id);
                    $msg = 'Please check your SMS inbox to get password!';
                }catch(\Exception $e){
                }
            }else{
                try{
                    $this->sendPassResetMail($inputs['mail-address'],$default_password,$default_pin);
                    $msg = 'Please check your Mail inbox to get password!';
                }catch(\Exception $e){}
            }
        }else{
            return $this->respondWithError("You are not authorised to reset password.");
        }

        return $this->respond([
            'status' => 'success',
            'status_code' => $this->getStatusCode(),
            'message' => $msg,
        ]);
    }

    public function postVerification(Request $request,UserToken $token,User $user) {
        $inputs = $request->all();
        $rules = array (
            'profile_photo' => 'required',
            'scan' => 'required',
            'date_of_birth' => 'required',
            'nationality' => 'required',
            'gender' => 'required',
            'marrital_status' => 'required',
            'present_address' => 'required',
            'occupation' => 'required',
            'post_code' => 'required|numeric',
            'id_no' => 'required',
            'id_type' => 'required',
            'id_expire_date' => 'required',
            'state' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator-> fails()){
            return $this->respondValidationError('Fields Validation Failed.', $validator->errors()->all());
        }
        $header = $request->header('token');
        $token = $token->where('token', $header)->first();
        $requested_user = $token->user;
        if ($token && $requested_user->groups()->first()->hasPermission('usersInformation.manage')) {
            $user = $token->user;
            $user->is_verified = 2;
            $user->save();
            $profile = $user->profile;
            if ($request->hasFile('profile_photo')) {
                // Profile Photo
                $inputs['profile_photo'] = uploadDocuments($request->file('profile_photo'),'user_photo_',$user->id);
            }
            // Scan 1
            if ($request->hasFile('scan')) {
                $inputs['scan'] = uploadDocuments($request->file('scan'),'user_scan_1_',$user->id);
            }

            // Scan 2
            if ($request->hasFile('scan_one')) {
                $inputs['scan_one'] = uploadDocuments($request->file('scan_one'),'user_scan_2_',$user->id);
            }

            $profile->update($inputs);
            $data['message'] = "Success";
            //email send
            try{
                $user->sendAccVerificationMail($user->mobile_number,$user->name);
            }
            catch(\Exception $e){
            }
            return $this->respond([
                'status' => 'success',
                'status_code' => $this->getStatusCode(),
                'message' => 'Congratulations, Your Verification request has been submitted. Please wait for Administrative approval. TQ',
            ]);
        } else {
            return $this->respondWithError("Invalid Token.");
        }

    }
}