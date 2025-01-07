<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\CashRegister;
use App\Utils\CashRegisterUtil;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;

class AutoRegisterCloseController extends Controller{
    protected $cashRegisterUtil;
    protected $moduleUtil;

    public function __construct(CashRegisterUtil $cashRegisterUtil, ModuleUtil $moduleUtil)
    {
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
    }
    public function index(Request $request)
    {
        $openRegisters = CashRegister::where('status', 'open')->get();
        foreach($openRegisters as $openRegister){
            $register_id = $openRegister->id;
            $register_details = $this->cashRegisterUtil->getRegisterDetails($register_id);
            $user_id = $register_details->user_id;
            $open_time = $register_details['open_time'];
            $close_time = \Carbon::now()->toDateTimeString();
            $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');
            $data['closing_amount'] = number_format(round(($register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund - $register_details->total_cash_expense), 2),2,'.','');
            $data['total_card_slips'] = $register_details->total_card_slips;
            $data['total_cheques'] = $register_details->total_cheques;
            $data['closing_note'] = "Auto Close at ".date('Y-m-d H:i:s').".";
            $data['closed_at'] = \Carbon::now()->toDateTimeString();
            $data['status'] = 'close';
            CashRegister::where('user_id', $user_id)->where('status', 'open')->update($data);
        }
        exit;
    }
}
