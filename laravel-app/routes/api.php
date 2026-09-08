<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('payments/pawapay/deposits/callback', 'PawaPayCallbackController@handle');
Route::post('payments/pawapay/checkouts/callback', 'PawaPayCallbackController@handle');
Route::post('payments/pawapay/payouts/callback', 'PawaPayCallbackController@handle');
Route::post('payments/pawapay/refunds/callback', 'PawaPayCallbackController@handle');
Route::post('payments/pawapay/callback', 'PawaPayCallbackController@handle');
Route::post('payments/stripe/webhook', 'StripePaymentController@webhook');
