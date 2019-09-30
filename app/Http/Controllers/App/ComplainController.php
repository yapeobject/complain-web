<?php
/**
 * Created by PhpStorm.
 * User: Infelicitas
 * Date: 6/24/2018
 * Time: 2:38 PM
 */

namespace App\Http\Controllers\App;

use Illuminate\Http\Request;
use App\UserToken;
use App\Complain;
use App\ComplainFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ComplainController extends ApiController
{
    use Common;
    protected $header;
    protected $token;

    public function __construct(UserToken $token, Request $request)
    {
        $this->header = $request->header('token');
        $token = $token->where('token', $this->header)->first();
        if (isset($token)) {
            $this->token = $token;
        } else {
            $this->token = false;

        }
    }

    public function getComplain(Request $request)
    {
        if (!$this->token) {
            return $this->respondWithError("Invalid Token.");
        }
        $complains = Complain::with('complainFile')->where('user_id', $this->token->user_id)->orderBy('date', 'desc')->skip($request->offset * $request->limit)->take($request->limit)->get();
        return $this->respond([
            'status' => 'success',
            'status_code' => $this->getStatusCode(),
            'message' => 'successful!',
            'response' => $complains
        ]);
    }

    public function addComplain(Request $request)
    {
        if (!$this->token) {
            return $this->respondWithError("Invalid Token.");
        }

        $inputs = $request->all();
        $rules = array (
            'complain_group' => 'required',
            'user_id' => 'required|email',
            'district_name' => 'required',
            'category' =>'required',
            'longitude' => 'required',
            'latitude' => 'required',
            'date' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator-> fails()){
            return $this->respondValidationError('Fields Validation Failed.', $validator->errors()->all());
        }

        $inputs['urgency_level'] = "Low";
        $inputs['mode'] = "";
        $inputs['status'] = "New";
        $complain = Complain::create($inputs);

        if(!$complain){
            return $this->respondInternalError("An error occurred while performing an action!");
        }

        if ($request->hasFile('file_1')) {
            $complainFile = array();
            $complainFile["complain_id"] = $complain->complain_id;
            $complainFile["complain_file_type"] = "image";
            $complainFile["file_location"] = $this->uploadDocuments($request->file('file_1'),'complain_photo_1_', $complain->complain_id, 'complain-files');
            $complainFile1 = ComplainFile::create($complainFile);
            if(!$complainFile1){
                return $this->respondInternalError("An error occurred while performing an action!");
            }
        }

        if ($request->hasFile('file_2')) {
            $complainFile = array();
            $complainFile["complain_id"] = $complain->complain_id;
            $complainFile["complain_file_type"] = "image";
            $complainFile["file_location"] = $this->uploadDocuments($request->file('file_2'),'complain_photo_2_', $complain->complain_id, 'complain-files');
            $complainFile2 = ComplainFile::create($complainFile);

            if(!$complainFile2){
                return $this->respondInternalError("An error occurred while performing an action!");
            }
        }

        if ($request->hasFile('file_3')) {
            $complainFile = array();
            $complainFile["complain_id"] = $complain->complain_id;
            $complainFile["complain_file_type"] = "image";
            $complainFile["file_location"] = $this->uploadDocuments($request->file('file_3'),'complain_photo_3_', $complain->complain_id, 'complain-files');
            $complainFile3 = ComplainFile::create($complainFile);

            if(!$complainFile3){
                return $this->respondInternalError("An error occurred while performing an action!");
            }
        }

        $complains = Complain::with('complainFile')->where('complain_id', $complain->complain_id)->orderBy('date', 'desc')->get();
        return $this->respond([
            'status' => 'success',
            'status_code' => $this->getStatusCode(),
            'message' => 'successful!',
            'response' => $complains
        ]);
    }

    public function uploadDocuments($file, $fileName, $id, $directory=null){
        $imageName = $fileName.$id.'.'.$file->getClientOriginalExtension();
        if(empty($directory)){
            $directory = 'complain-files';
        }
        $destination = base_path().'/public/files/'.$directory.'/';
        $file->move($destination, $imageName);
        return $img = '/files/'.$directory.'/'.$imageName;
    }

}