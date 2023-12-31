<?php

use Illuminate\Support\Facades\Route;

Route::namespace('User\Auth')->name('user.')->group(function () {

    Route::controller('LoginController')->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login');
        Route::get('logout', 'logout')->name('logout');
    });

    Route::controller('RegisterController')->group(function () {
        Route::get('register', 'showRegistrationForm')->name('register');
        Route::post('register', 'register')->middleware('registration.status');
        Route::post('check-mail', 'checkUser')->name('checkUser');
    });

    Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
        Route::get('reset', 'showLinkRequestForm')->name('request');
        Route::post('email', 'sendResetCodeEmail')->name('email');
        Route::get('code-verify', 'codeVerify')->name('code.verify');
        Route::post('verify-code', 'verifyCode')->name('verify.code');
    });
    Route::controller('ResetPasswordController')->group(function () {
        Route::post('password/reset', 'reset')->name('password.update');
        Route::get('password/reset/{token}', 'showResetForm')->name('password.reset');
    });
});

Route::middleware('auth')->name('user.')->group(function () {
    //authorization
    Route::namespace('User')->controller('AuthorizationController')->group(function () {
        Route::get('authorization', 'authorizeForm')->name('authorization');
        Route::get('resend-verify/{type}', 'sendVerifyCode')->name('send.verify.code');
        Route::post('verify-email', 'emailVerification')->name('verify.email');
        Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile');
        Route::post('verify-g2fa', 'g2faVerification')->name('go2fa.verify');
    });

    Route::middleware(['check.status'])->group(function () {

        Route::get('user-data', 'User\UserController@userData')->name('data');
        Route::post('user-data-submit', 'User\UserController@userDataSubmit')->name('data.submit');

        Route::middleware('registration.complete')->namespace('User')->group(function () {

            Route::controller('UserController')->group(function () {
                Route::get('dashboard', function () {
                    return redirect()->route('user.markets.index');
                })->name('home');

                //2FA
                Route::get('twofactor', 'show2faForm')->name('twofactor');
                Route::post('twofactor/enable', 'create2fa')->name('twofactor.enable');
                Route::post('twofactor/disable', 'disable2fa')->name('twofactor.disable');

                //KYC
                Route::get('kyc-form', 'kycForm')->name('kyc.form');
                Route::get('kyc-data', 'kycData')->name('kyc.data');
                Route::post('kyc-submit', 'kycSubmit')->name('kyc.submit');

                //Report
                Route::any('deposit/history', 'depositHistory')->name('deposit.history');
                Route::get('transactions', 'transactions')->name('transactions');

                Route::get('attachment-download/{fil_hash}', 'attachmentDownload')->name('attachment.download');
            });

            //Profile setting
            Route::controller('ProfileController')->group(function () {
                Route::get('profile-setting', 'profile')->name('profile.setting');
                Route::post('profile-setting', 'submitProfile');
                Route::get('change-password', 'changePassword')->name('change.password');
                Route::post('change-password', 'submitPassword');
            });

            // Withdraw
            Route::controller('WithdrawController')->prefix('withdraw')->name('withdraw')->group(function () {
                Route::middleware('kyc')->group(function () {
                    Route::get('/', 'withdrawMoney')->name('.index');
                    Route::post('/', 'withdrawStore')->name('.money');
                    Route::get('preview', 'withdrawPreview')->name('.preview');
                    Route::post('preview', 'withdrawSubmit')->name('.submit');
                });
                Route::get('history', 'withdrawLog')->name('.history');
            });
            
            Route::controller("FiatController")->name('fiat.')->prefix('fiat')->group(function () {     
                Route::get('/', 'index')->name('index');
                Route::post('store', 'store')->name('store');
                Route::post('result', 'tradeResult')->name('result');
            });
            
            Route::controller("TradeController")->name('trade.')->prefix('trade')->group(function () {     
                Route::get('/', 'index')->name('index');
                Route::post('store', 'store')->name('store');
                Route::post('result', 'tradeResult')->name('result');
            });
            
            Route::controller("LockupController")->name('lockup.')->prefix('lockup')->group(function () {     
                Route::get('/', 'index')->name('index');
                Route::post('store', 'store')->name('store');
                Route::post('result', 'tradeResult')->name('result');
            });
            
            Route::controller("EarnController")->name('earn.')->prefix('earn')->group(function () {     
                Route::get('/', 'index')->name('index');
                Route::get('/log', 'log')->name('log');
                Route::post('store', 'store')->name('store');
            });
            
            Route::controller("LeverageController")->name('leverage.')->prefix('leverage')->group(function () {     
                Route::get('/', 'index')->name('index');
                Route::middleware('throttle: 15, 1')->post('/historicalData', 'historicalData')->name('historicalData');
                Route::post('store', 'store')->name('store');
                Route::post('result', 'tradeResult')->name('result');
            });
            
            Route::controller("ExchangeController")->name('exchange.')->prefix('exchange')->group(function () {     
                Route::get('/', 'index')->name('index');
                Route::post('achieve', 'achieve')->name('achieve');
            });
            
            Route::controller("AssetsController")->name('assets.')->prefix('assets')->group(function () {     
                Route::get('/', 'index')->name('index');
                Route::get('log/{wallet}', 'log')->name('log')->whereIn('wallet', ['USDT', 'BTC', 'ETH']);
            });
            
            Route::controller("MarketsController")->name('markets.')->prefix('markets')->group(function () {     
                Route::get('/', 'index')->name('index');
            });

            Route::controller("ReferralController")->group(function(){
                Route::get('commissions/history', 'commissions')->name('commissions.log');
                Route::get('referral/log', 'referralsLog')->name('referral.log');
            });
        });

        // Payment
        Route::middleware('registration.complete')->prefix('deposit')->name('deposit.')->controller('Gateway\PaymentController')->group(function () {
            Route::any('/', 'deposit')->name('index');
            Route::post('insert', 'depositInsert')->name('insert');
            Route::get('confirm', 'depositConfirm')->name('confirm');
            Route::get('manual', 'manualDepositConfirm')->name('manual.confirm');
            Route::post('manual', 'manualDepositUpdate')->name('manual.update');
        });
    });
});

// Route::get('test', [App\Http\Controllers\Gets\UserController::class, 'index']);
