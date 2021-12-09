<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Matrix\Builder;

class TsDeposits extends Model
{
	//

	protected $table = 'ts_deposits';

	public function detail()
    {
        return $this->hasMany(TsDepositsDetail::class, 'transaction_id', 'id');
	}

	public function member()
	{
		return $this->belongsTo(Member::class, 'member_id');
	}

	public function ms_deposit()
	{
		return $this->belongsTo(Deposit::class, 'ms_deposit_id');
	}

	public function totalDeposit($id){
		return $this->where(['status' => 'paid','member_id' => $id])->sum('total_deposit');
	}

	public static function getDepositArea($region){
        $selected = self::all();
        if(!empty($region)){
            $selected = self::whereHas('member', function ($query) {
                return $query->where('region_id', '=', auth()->user()->region['id']);
            })->get();
        }


        return $selected;
    }

    public static function getDepositTypeArea($region, $type){
        $selected = self::where('ms_deposit_id', $type)->get();
        if(!empty($region)){
            $selected = self::where('ms_deposit_id', $type)->whereHas('member', function ($query) {
                return $query->where('region_id', '=', auth()->user()->region['id']);
            })->get();
        }


        return $selected;
    }

    public static function totalDepositPokok($id){
        $debit =  self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 1,
            'type' => 'debit'
        ])->sum('total_deposit');

        $credit =  self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 1,
            'type' => 'credit'
        ])->sum('total_deposit');

        return $debit - $credit;
    }

    public static function totalDepositWajib($id){
        $debit =  self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 2,
            'type' => 'debit'
        ])->sum('total_deposit');

        $credit =  self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 2,
            'type' => 'credit'
        ])->sum('total_deposit');
        return $debit - $credit;
    }

    public static function totalDepositSukarela($id){
        $debit = self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 3,
            'type' => 'debit'
        ])->sum('total_deposit');

        $credit = self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 3,
            'type' => 'credit'
        ])->sum('total_deposit');

        return $debit - $credit;
    }

    public static function totalDepositBerjangka($id){
        return self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 4
        ])->sum('total_deposit');
    }

    public static function totalDepositShu($id){
        $debit = self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 5,
            'type' => 'debit'
        ])->sum('total_deposit');

        $credit = self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 5,
            'type' => 'credit'
        ])->sum('total_deposit');

        return $debit - $credit;
    }

    public static function totalDepositLainnya($id){
        $debit = self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 6,
            'type' => 'debit'
        ])->sum('total_deposit');

        $credit = self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 6,
            'type' => 'credit'
        ])->sum('total_deposit');

        return $debit - $credit;
    }

    public static function totalDepositPokokDate($id,$month){
        return self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 1
        ])->whereMonth('post_date','>=', $month->format('m'))
            ->whereYear('post_date','>=', $month->format('Y'))
            ->whereMonth('post_date','<=',$month->format('m'))
            ->whereYear('post_date','<=',$month->format('Y'))
            ->sum('total_deposit');
    }

    public static function totalDepositWajibDate($id,$month){
        return self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 2
        ])->whereMonth('post_date','>=', $month->format('m'))
            ->whereYear('post_date','>=', $month->format('Y'))
            ->whereMonth('post_date','<=',$month->format('m'))
            ->whereYear('post_date','<=',$month->format('Y'))
            ->sum('total_deposit');
    }

    public static function totalDepositSukarelaDate($id,$month){
        $debit = self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 3,
            'type' => 'debit'
        ])->whereMonth('post_date','>=', $month->format('m'))
            ->whereYear('post_date','>=', $month->format('Y'))
            ->whereMonth('post_date','<=',$month->format('m'))
            ->whereYear('post_date','<=',$month->format('Y'))
            ->sum('total_deposit');

        $credit = self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 3,
            'type' => 'credit'
        ])->whereMonth('post_date','>=', $month->format('m'))
            ->whereYear('post_date','>=', $month->format('Y'))
            ->whereMonth('post_date','<=',$month->format('m'))
            ->whereYear('post_date','<=',$month->format('Y'))
            ->sum('total_deposit');

        return $debit - $credit;
    }

    public static function totalDepositBerjangkaDate($id,$month){
        return self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 4
        ])->whereMonth('post_date','>=', $month->format('m'))
            ->whereYear('post_date','>=', $month->format('Y'))
            ->whereMonth('post_date','<=',$month->format('m'))
            ->whereYear('post_date','<=',$month->format('Y'))
            ->sum('total_deposit');
    }

    public static function totalDepositShuDate($id,$month){
        return self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 5
        ])->whereMonth('post_date','>=', $month->format('m'))
            ->whereYear('post_date','>=', $month->format('Y'))
            ->whereMonth('post_date','<=',$month->format('m'))
            ->whereYear('post_date','<=',$month->format('Y'))
            ->sum('total_deposit');
    }

    public static function totalDepositLainnyaDate($id){
        return self::where([
            'status' => 'paid',
            'member_id' => $id,
            'ms_deposit_id' => 6
        ])->sum('total_deposit');
    }

    public function scopefTypeDeposit($query, $id_deposit){
	    return $query->where('ms_deposit_id', $id_deposit);
    }

    public static function getYearlyDeposit($month, $region){
        $selected = self::whereYear('post_date', $month->format('Y'))
            ->whereMonth('post_date', $month->format('m'))
            ->where('status', 'paid')->select(DB::raw('YEAR(post_date) year, MONTH(post_date) month'), DB::raw('sum(total_deposit) as total'))
            ->groupBy('year', 'month')->orderBy('total', 'DESC')
            ->first();

        if(!empty($region)){
            $selected = self::whereYear('post_date', $month->format('Y'))
                ->whereMonth('post_date', $month->format('m'))
                ->where('status', 'paid')->whereHas('member', function ($query) {
                    $query->where('region_id', '=', auth()->user()->region['id']);
                })
                ->where('status', 'paid')->select(DB::raw('YEAR(post_date) year, MONTH(post_date) month'), DB::raw('sum(total_deposit) as total'))
                ->groupBy('year', 'month')->orderBy('total', 'DESC')
                ->first();
        }
        return $selected;
    }

    public static function getYearlyDepositType($month, $type, $deposit_type){
        return self::whereYear('post_date', $month->format('Y'))
            ->whereMonth('post_date', $month->format('m'))
            ->where('type', $type)
            ->where('ms_deposit_id', $deposit_type)
            ->where('status', 'paid')->select(DB::raw('YEAR(post_date) year, MONTH(post_date) month'), DB::raw('sum(total_deposit) as total'))
            ->groupBy('year', 'month')->orderBy('total', 'DESC')
            ->first();
    }
}
