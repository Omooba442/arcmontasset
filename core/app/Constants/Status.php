<?php

namespace App\Constants;

class Status
{

    const ENABLE  = 1;
    const DISABLE = 0;

    const YES = 1;
    const NO  = 0;

    const VERIFIED   = 1;
    const UNVERIFIED = 0;

    const PAYMENT_INITIATE = 0;
    const PAYMENT_SUCCESS  = 1;
    const PAYMENT_PENDING  = 2;
    const PAYMENT_REJECT   = 3;

    const TICKET_OPEN   = 0;
    const TICKET_ANSWER = 1;
    const TICKET_REPLY  = 2;
    const TICKET_CLOSE  = 3;

    const PRIORITY_LOW    = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH   = 3;

    const USER_ACTIVE = 1;
    const USER_BAN    = 0;

    const KYC_UNVERIFIED = 0;
    const KYC_PENDING    = 2;
    const KYC_VERIFIED   = 1;

    const TRADE_WIN     = 1;
    const TRADE_LOSE    = 2;
    const TRADE_DRAW    = 3;
    const TRADE_PENDING = 0;

    const TRADE_COMPLETED = 1;
    const TRADE_RUNNING   = 0;

    const TRADE_HIGH = 1;
    const TRADE_LOW  = 2;
    
    const TRADE_RIG_NONE = 0;
    const TRADE_RIG_WIN = 1;
    const TRADE_RIG_DRAW = 2;
    const TRADE_RIG_LOSE = 3;

    const WALLET_USDT = 1;
    const WALLET_BTC  = 2;
    const WALLET_ETH  = 3;
}
