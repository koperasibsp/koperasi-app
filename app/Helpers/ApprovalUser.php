<?php
namespace App\Helpers;
use App\Exceptions\ChangeConnectionException;

use App\GeneralSetting;
use App\Member;
use App\Position;
use App\Region;
use App\Resign;
use App\TsDeposits;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use function Psy\sh;

class ApprovalUser
{
    public static function getApproval($user){
        $approvals = [];
        if($user->hasRole('MEMBER')){
            $approvals = User::MemberApproval()->get();
        }

        if($user->hasRole('MEMBER') && $user->hasRole('KARYAWAN_PENGELOLA')){
            $approvals = User::MemberApproval($user)->get();
        }

        if($user->hasRole('DIREKTUR_UTAMA')){
            $approvals = User::MemberApproval($user)->get();
        }

        if($user->hasRole('KARYAWAN_KOPERASI')){
            $approvals = User::MemberApproval($user)->get();
        }

        return $approvals;
    }

    public static function getPenjamin($user){
        $penjamin = [];
        if($user->hasRole('MEMBER')){
            $penjamin = User::MemberPenjamin()->get();
        }

        if($user->hasRole('DANSEK')){
            $penjamin = User::DansekPenjamin()->get();
        }

        if($user->hasRole('MEMBER') && $user->hasRole('KARYAWAN_PENGELOLA')){
            $penjamin = User::KaryawanPengelolaPenjamin($user)->get();
        }

        if($user->hasRole('DIREKTUR_UTAMA')){
            $penjamin = User::DirekturPenjamin($user)->get();
        }

        if($user->hasRole('DIREKTUR')){
            $penjamin = User::DirekturPenjamin($user)->get();
        }

        if($user->hasRole('KARYAWAN_KOPERASI')){
            $penjamin = User::KaryawanKoperasiPenjamin($user)->get();
        }

        if($user->hasRole('DANSEK') && $user->hasRole('PENGELOLA_AREA')){
            $penjamin = User::DansekPenjamin($user)->get();
        }

        if($user->hasRole('GENERAL_MANAGER')){
            $penjamin = User::GeneralManagerPenjamin($user)->get();
        }

        if($user->hasRole('KOMISARIS')){
            $penjamin = User::GeneralManagerPenjamin($user)->get();
        }

        if($user->hasRole('PENGURUS')){
            $penjamin = User::PengurusPenjamin($user)->get();
        }

        return $penjamin;
    }
}
