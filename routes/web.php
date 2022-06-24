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

Route::get('/process-call', function(Request $request) {
    //log the request
    Log::info("API Request - processCall", $request->all());
    //Verify the accessToken
    $accessToken = $request->get('accessToken');
    if (!in_array($accessToken, config('auth')['accessTokens'])) {
        //log the request
        Log::info("Invalid access token.", [$accessToken]);
        return response()->json([
            'error' =>  'Invalid access token.',
            'accessToken' => $accessToken
        ]);
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
    $zipCode = $request->get('zipcode');
    $validator = Validator::make(['zip_code' => $request->get('zipcode')], ['zip_code' => 'required|regex:/\b\d{5}\b/']);
    if ($validator->fails()) {
        Log::info("Invalid or empty zipcode.", [$request->get('zipcode')]);
        return response()->json([
            'error' =>  'Invalid or empty zipcode.',
            'accessToken' => $accessToken
        ]);
    }
    try {
        //fetch date
        $result = DB::select(
            DB::raw("SET NOCOUNT ON; exec dbo.DTP_ProcessCall @zipcode = :zip" , [':zip', $zipCode])
        );

        //log the request
        Log::info("API Response - processCall", [$result]);
        //return the response
        return response()->json([
            'response' => $result
        ]);
    } catch (\Throwable $th) {
        //log the request
        Log::info("API Response Error - processCall", [$th->getMessage()]);
        //return the response
        return response()->json([
            'response' => ""
        ]);
    }
});

Route::post('/confirm-lead', function(Request $request) {
    //log the request
    Log::info("API Request - confirmLead", $request->all());
    //Verify the accessToken
    $accessToken = $request->get('accessToken');
    if (!in_array($accessToken, config('auth')['accessTokens'])) {
        //log the request
        Log::info("Invalid access token.", [$accessToken]);
        return response()->json([
            'error' =>  'Invalid access token.',
            'accessToken' => $accessToken
        ]);
    }

    //validate callId
    $callId = $request->get('callId');
    if (empty($callId)) {
        Log::info("Invalid or empty callId.", [$request->get('callId')]);
        return response()->json([
            'error' =>  'Empty callId.',
            'accessToken' => $accessToken
        ]);
    }
    try {
        //fetch date
        $result = DB::select(
            DB::raw("SET NOCOUNT ON; exec dbo.DTP_ConfirmLead @callid = :callId, @success = 1" , [':callId', $callId])
        );

        //log the request
        Log::info("API Response - confirmLead", [$result]);
        //return the response
        return response()->json([
            'response' => $result
        ]); 
    } catch (\Throwable $th) {
        //log the request
        Log::info("API Response Error - confirmLead", [$th->getMessage()]);
        //return the response
        return response()->json([
            'response' => ""
        ]); 
    }
});