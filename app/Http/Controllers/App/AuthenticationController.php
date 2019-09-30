<?php
namespace App\Http\Controllers\App;
use App\Repository\Transformers\authTransformer;
use App\User;
use App\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthenticationController extends ApiController
{
    protected $authTransformer;
    public function __construct(authTransformer $authTransformer)
    {
        $this->authTransformer = $authTransformer;
    }
    public function login(Request $request, User $user)
    {
        $rules = array (
            'user_id' => 'required|email',
            'password' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator-> fails()){
            return $this->respondValidationError('Fields Validation Failed.', $validator->errors()->all());
        }
//        $ip = $request->server('HTTP_USER_AGENT');
        $user = User::where('user_id', $request->user_id)->orderBy('first_name', 'asc')->first();
        if ($user) {
            if ($user->password == $request->password) {
                if ($user->status == "active" && $user->user_type != null) {
                    $token = uniqid('', true).'.'.$request->user_id.'.'.uniqid('', true);
                    $userToken = UserToken::where('user_id', $request->user_id)->first();
                    if ($userToken) {
                        $input = array();
                        $input["user_id"] = $request->user_id;
                        $input["token"] = $token;
                        $userToken->update($input);
                    } else {
                        $input = array();
                        $input["user_id"] = $request->user_id;
                        $input["token"] = $token;
                        UserToken::create($input);
                    }
                    $user->token = $token;
                    return $this->respond([
                        'status' => 'success',
                        'status_code' => $this->getStatusCode(),
                        'message' => 'login successful!',
                        'response' => $user
                    ]);
                } else {
                    return $this->respondWithError('User is not active');
                }
            } else {
                return $this->respondWithError("Wrong Password.");
            }
        } else {
            return $this->respondWithError("User doesn't exist.");
        }
    }

    public function authUpdate(Request $request, UserToken $token){
        $header = $request->header('token');
        $token = $token->where('token', $header)->first();
        if ($token) {
            $rules = array (
                'password' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator-> fails()){
                return $this->respondValidationError('Fields Validation Failed.', $validator->errors()->all());
            }

            if (Hash::check($request->old_pin, $token->user->pin)) {
                $user = $token->user;
                if ($request->has('password')) {
                    $user->password = bcrypt($request->password);
                }
                if ($request->has('pin')) {
                    $user->pin = bcrypt($request->pin);
                }
                $user->save();
                return $this->respond([
                    'status' => 'success',
                    'status_code' => $this->getStatusCode(),
                    'message' => 'Update successful!'
                ]);
            }else{
                $token->user->increment('wrong_pin');

                if ($token->user->wrong_pin > 3) {
                    $token->user->update([
                        'active' => 0,
                        'wrong_pin' => 0,
                    ]);
                }
                return $this->respondWithError("Old pin doesn't match.");
            }
        }else{
            return $this->respondWithError("Invalid Token.");
        }
    }

}



