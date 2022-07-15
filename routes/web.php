<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function(Request $request) {
    //Verify the accessToken
    $accessToken = $request->get('accessToken');
    if (!in_array($accessToken, config('auth')['accessTokens'])) {
        return response()->json([
            'error' =>  'Invalid access token.',
            'accessToken' => $accessToken
        ]);
    }

    return response()->json([
        'futuredontics-api'
    ]);
});

Route::any('/process-call', function(Request $request) {
    $params = null;
    if (!empty($request->getContent())) {
        $params = json_decode($request->getContent(), true);
    }

    //log the request
    Log::info("API Request - processCall", $request->all());
    //Verify the accessToken
    $accessToken = $request->get('accessToken');
    if (!in_array($accessToken, config('auth')['accessTokens'])) {
        //log the request
        Log::error("Invalid access token.", [$accessToken]);
        return response()->json(
            [
                'error' =>  'Invalid access token.',
                'accessToken' => $accessToken
            ],
            400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
        );
    }

    /*
        Target Sub ID (CID)
        Billable Duration
        Revenue Amount
        Publisher Payout Bid
        Publisher Duration Bid
        PostbackURL
    */
    //validate zip code
    if (!empty($params)) {
        $zipCode = $params["zipcode"] ?? null;
        $language = $params["language"] ?? null;
        $ringbaCallId = $params["ringbaCallId"] ?? null;
    } else {
        $zipCode = $request->get('zipcode');
        $language = $request->get('language');
        $ringbaCallId = $request->get('ringbaCallId');
    }
    
    if (!in_array($language, ['EN', 'ES'])) {
        $language = 'EN';
    }

    $validator = Validator::make(['zip_code' => $request->get('zipcode')], ['zip_code' => 'required|regex:/\b\d{5}\b/']);
    if ($validator->fails()) {
        Log::info("Invalid or empty zipcode.", [$request->get('zipcode')]);
        return response()->json(
            [
                'error' =>  'Invalid or empty param : zipcode',
                'accessToken' => $accessToken
            ],
            400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
        );
    }
    try {
        //fetch date
        $result = DB::select(
            DB::raw("SET NOCOUNT ON; exec dbo.DTP_ProcessCall @zipcode = :zip, @language = :language, @ringba_call_id = :ringba_call_id"), [':zip' => $zipCode, ':language' => $language, ':ringba_call_id' => $ringbaCallId]
        );

        if (!empty($result[0])) {
            $result = (array) $result[0];
            if (!empty($result['CallId'])) {
                $result['PostbackURL'] = env('APP_URL') . '/confirm-lead?accessToken=' . $accessToken . '&callId=' . $result['CallId'] . '&success=&recordingUrl=';
            }
        } else {
            $result = null;
        }
        //log the request
        Log::info("API Response - processCall", [$result]);
        //return the response
        return response()->json(
            ['response' => $result], 200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
        );
    } catch (\Throwable $th) {
        //log the request
        $hash = md5(time());
        Log::error("API Response Error - processCall - $hash", [$th->getMessage()]);
        //return the response
        return response()->json(
            [
                'error' => "API unknown error, please contact support",
                'errorId' => $hash
            ],
            400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
        ); 
    }
});

Route::any('/confirm-lead', function(Request $request) {
    $params = null;
    if (!empty($request->getContent())) {
        $params = json_decode($request->getContent(), true);
    }
    //log the request
    Log::info("API Request - confirmLead", $request->all());
    //Verify the accessToken
    $accessToken = $request->get('accessToken');
    if (!in_array($accessToken, config('auth')['accessTokens'])) {
        //log the request
        Log::error("Invalid access token.", [$accessToken]);
        return response()->json(
            [
                'error' =>  'Invalid access token.',
                'accessToken' => $accessToken
            ],
            400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
        );
    }

    //validate callId
    if (!empty($params)) {
        //$callId = $params["callId"] ?? null;
        $success = $params["success"] ?? null;
        $recordingUrl = $params["recordingUrl"] ?? null;
        $callerId = $params["callerId"] ?? null;
        $callLength = $params["callLength"] ?? null;
        $ringbaCallId = $params["ringbaCallId"] ?? null;
    } else {
        //$callId = $request->get('callId');
        $success = $request->get('success');
        $recordingUrl = $request->get('recordingUrl');
        $callerId = $request->get('callerId');
        $callLength = $request->get('callLength');
        $ringbaCallId = $request->get('ringbaCallId');
    }

    if (empty($ringbaCallId) ||  (int) $success > 1) {
        Log::error("Invalid or empty callId.", [$request->get('ringbaCallId')]);
        return response()->json(
            [
                'error' =>  'Missing params, ringbaCallId or success',
                'accessToken' => $accessToken
            ],
            400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
        );
    }

    try {
        //fetch date
        $result = DB::select(
            DB::raw("SET NOCOUNT ON; exec dbo.DTP_ConfirmLead @callid = :callId, @success = :success, @recording_url = :recordingUrl, @caller_id = :callerId, @call_length = :callLength"), 
            [
                ':callId' => $ringbaCallId, 
                ':success' => intval($success),
                ':recordingUrl' => $recordingUrl,
                ':callLength' => $callLength,
                ':callerId' => $callerId
            ]);

        if (!empty($result[0])) {
            $result = (array) $result[0];
        } else {
            $result = null;
        }

        //log the request
        Log::info("API Response - confirmLead", [$result]);
        //return the response
         //return the response
        return response()->json(
            ['response' => $result], 200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
        );
    } catch (\Throwable $th) {
        //log the request
        $hash = md5(time());
        Log::error("API Response Error - confirmLead - $hash", [$th->getMessage()]);
        //return the response
        return response()->json(
            [
                'error' => "API unknown error, please contact support",
                'errorId' => $hash
            ],
            400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
        );  
    }
});