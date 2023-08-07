<?php

namespace App\Lib;

use Carbon\Carbon;
use App\Models\TradeLog;
use App\Constants\Status;
use App\Models\PracticeLog;
use App\Models\Transaction;
use App\Models\CryptoCurrency;
use App\Models\TradeSetting;
use Illuminate\Support\Facades\Validator;

class Trade
{

    protected $isPracticeTrade = false;
    protected $modelName       = TradeLog::class;
    protected $columnName      = 'balance';

    public function __construct($isPractice = false)
    {
        if ($isPractice) {
            $this->modelName       = PracticeLog::class;
            $this->columnName      = 'demo_balance';
            $this->isPracticeTrade = true;
        }
    }
    public function store($request)
    {
        $validator = Validator::make($request->all(), [
            'amount'        => 'required|numeric|gt:0',
            'coin_id'       => 'required|string|exists:crypto_currencies,symbol',
            'high_low_type' => 'required|in:1,2',
            'wallet'        => 'required|in:1,2,3',
            'duration'      => 'required|exists:trade_settings,time',
            'unit'          => 'required|in:seconds,minutes,hours,days'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all());
        }

        if (!in_array($request->wallet, [Status::WALLET_USDT, Status::WALLET_BTC, Status::WALLET_ETH])) {
            return $this->errorResponse(['Invalid wallet.']);
        }

        $crypto = CryptoCurrency::active()->where('symbol', $request->coin_id)->first();
        if (!$crypto) {
            return $this->errorResponse(['Crypto currency not found']);
        }
        $tradeSetting = TradeSetting::where('time', $request->duration)->where('unit', $request->unit)->first();
        if(!is_null($tradeSetting)){
            $profit = $tradeSetting->profit;
        } else {
            return $this->errorResponse(['Invalid open time.']);
        }
        
        $user      = auth()->user();
        $columName = $this->columnName;
        $wallet_map = [
            1 => 'USDT',
            2 => 'BTC',
            3 => 'ETH',
        ];
        if ($crypto->symbol == $wallet_map[$request->wallet]) {
            return $this->errorResponse(['You can\'t trade a coin against itself.']);
        }
        $balances = json_decode($user->$columName, true);
        $balance = $balances[$wallet_map[$request->wallet]] ?? 0;
        
        if($request->amount < json_decode($tradeSetting->minimum, true)[$wallet_map[$request->wallet]]){
            return $this->errorResponse(['You can\'t trade less than '.json_decode($tradeSetting->minimum, true)[$wallet_map[$request->wallet]].' '.$wallet_map[$request->wallet].' for this time setting.']);
        }

        if ($request->amount > $balance) {
            if ($this->isPracticeTrade) {
                $message = "You don\'t have sufficient practice balance. Please add practice balance";
            } else {
                $message = "You don\'t have sufficient balance. Please deposit money";
            }
            return $this->errorResponse([$message]);
        }

        $unit = "add" . ucfirst($request->unit);
        $now = Carbon::now();
        $then = Carbon::parse($now->toString());
        $time = $now->$unit($request->duration);
        
        $coinRate = getCoinRate($crypto->symbol, intval($request->wallet));

        $tradeLog                     = new $this->modelName();
        $tradeLog->user_id            = $user->id;
        $tradeLog->crypto_currency_id = $crypto->id;
        $tradeLog->amount             = $request->amount;
        $tradeLog->in_time            = $time;
        $tradeLog->high_low           = $request->high_low_type;
        $tradeLog->price_was          = $coinRate;
        $tradeLog->duration           = $time->diff($then)->format('%H:%I:%S');
        $tradeLog->wallet             = $request->wallet;
        $tradeLog->profit             = $profit ?? 5;
        $tradeLog->save();
        
        $balances[$wallet_map[$request->wallet]] -= $request->amount;
        $user->$columName = json_encode($balances);
        $user->save();

        if (!$this->isPracticeTrade) {
            $highLow = $request->high_low_type == Status::TRADE_HIGH ? 'High' : "Low";
            $details = 'Trade to ' . $crypto->name . ' ' . $highLow;
            $this->createTransaction($tradeLog->amount, $details, '-', $balances[$wallet_map[$tradeLog->wallet]], $wallet_map[$tradeLog->wallet]);
        }
        return response()->json([
            'success'     => true,
            'game_log_id' => $tradeLog->id,
            'trade'       => $tradeLog->price_was
        ]);
    }

    public function result($request)
    {
        $validator = Validator::make($request->all(), [
            'game_log_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all());
        }
        $tradeLog = $this->modelName::where('id', $request->game_log_id)->where('user_id', auth()->id())->where('result', Status::TRADE_PENDING)->where('status', Status::TRADE_RUNNING)->first();

        if (!$tradeLog) {
            return $this->errorResponse(['Trade not found']);
        }

        if ($tradeLog->high_low == Status::TRADE_HIGH) {
            return $this->tradeHigh($tradeLog);
        } else if ($tradeLog->high_low == Status::TRADE_LOW) {
            return $this->tradeLow($tradeLog);
        } else {
            return $this->errorResponse(['Trade must be High/Low']);
        }
    }

    public function tradeHigh($tradeLog)
    {
        $cryptoRate = getCoinRate($tradeLog->crypto->symbol, $tradeLog->wallet);
        $tradeLog->price_is = $cryptoRate;
        $tradeLog->save();
        if ($tradeLog->price_was < $cryptoRate) {
            return $this->tradeWin($tradeLog);
        } else if ($tradeLog->price_was > $cryptoRate) {
            return $this->tradeLoss($tradeLog);
        } else {
            return $this->tradeDraw($tradeLog);
        }
    }

    public function tradeLow($tradeLog)
    {
        $cryptoRate = getCoinRate($tradeLog->crypto->symbol, $tradeLog->wallet);
        $tradeLog->price_is = $cryptoRate;
        $tradeLog->save();

        if ($tradeLog->price_was > $cryptoRate) {
            return $this->tradeWin($tradeLog);
        } else if ($tradeLog->price_was < $cryptoRate) {
            return $this->tradeLoss($tradeLog);
        } else {
            return $this->tradeDraw($tradeLog);
        }
    }

    public function tradeWin($tradeLog)
    {
        $columName = $this->columnName;
        $user      = auth()->user();
        $gnl       = gs();


        $balances = json_decode($user->$columName, true);
        $wallet_map = [
            1 => 'USDT',
            2 => 'BTC',
            3 => 'ETH',
        ];
        $balances[$wallet_map[$tradeLog->wallet]] += $tradeLog->amount + (($tradeLog->amount / 100) * $tradeLog->profit);
        $user->$columName = json_encode($balances);
        $user->save();

        if (!$this->isPracticeTrade) {
            $details = "Trade to " . $tradeLog->crypto->name . ' ' . "WIN";
            $this->createTransaction($tradeLog->amount, $details, '+', $balances[$wallet_map[$tradeLog->wallet]], $wallet_map[$tradeLog->wallet]);
        }

        $tradeLog->result = Status::TRADE_WIN;
        $tradeLog->status = Status::TRADE_COMPLETED;
        $tradeLog->save();

        return $this->successResponse("Trade Win");
    }
    public function tradeLoss($tradeLog)
    {
        $tradeLog->result = Status::TRADE_LOSE;
        $tradeLog->status = Status::TRADE_COMPLETED;
        $tradeLog->save();

        return $this->successResponse("Trade Lose");
    }
    public function tradeDraw($tradeLog)
    {
        $columName = $this->columnName;
        $user      = auth()->user();

        $balances = json_decode($user->$columName, true);
        $wallet_map = [
            1 => 'USDT',
            2 => 'BTC',
            3 => 'ETH',
        ];
        $balances[$wallet_map[$tradeLog->wallet]] += $tradeLog->amount;
        $user->save();

        if (!$this->isPracticeTrade) {
            $details = "Trade " . $tradeLog->crypto->name . ' ' . "DRAW";
            $this->createTransaction($tradeLog->amount, $details, '+', $balances[$wallet_map[$tradeLog->wallet]], $wallet_map[$tradeLog->wallet]);
        }

        $tradeLog->result = Status::TRADE_DRAW;
        $tradeLog->status = Status::TRADE_COMPLETED;
        $tradeLog->save();

        return $this->successResponse("Trade Draw");
    }

    public function createTransaction($amount, $details, $trxType = "+", $balance, $wallet)
    {
        $user = auth()->user();

        $transaction               = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = $amount;
        $transaction->post_balance = $balance;
        $transaction->trx_type     = $trxType;
        $transaction->details      = $details;
        $transaction->wallet      = $wallet;
        $transaction->trx          = getTrx();
        $transaction->save();
    }
    public function errorResponse($errors)
    {
        return response()->json([
            'success' => false,
            'errors'  => $errors
        ]);
    }

    public function successResponse($message)
    {
        return response()->json([
            'success' => true,
            "message" => $message
        ]);
    }
}
