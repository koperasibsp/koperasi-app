<?php

namespace App\Http\Controllers;

use App\Policy;
use App\Shu;
use App\TsDeposits;
use App\TsDepositsDetail;
use App\User;
use Auth;
use Excel;
use Illuminate\Support\Facades\Input;
use NotificationChannels\OneSignal\OneSignalChannel;
use Redirect;
use App\Resign;
use App\Member;
use Illuminate\Http\Request;
use Storage;
use Yajra\DataTables\Facades\DataTables;

class ResignController extends GlobalController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // {data: 'no' , name : 'no' },
        //     {data: 'date' , name : 'date' },
        //     {data: 'reason' , name : 'reason' },
        //     {data: 'status' , name : 'status' },
        //     {data: 'action' , name : 'action' },
        $this->i  = 1;
        $selected = Resign::get();
        if(Auth::user()->isMember()) {
            $selected = Resign::get()->where('member_id', Auth::user()->member->id);
        }
        if (request()->ajax()) {
            return DataTables::of($selected)
            ->editColumn('no', function ($selected) {
                return $this->i++;
            })
            ->addColumn('approval', function($selected){
                 if($selected->approval == 'canceled'){
                    $status = 'Dibatalkan';
                 } else if($selected->approval == 'rejected'){
                    $status = 'Ditolak';
                 } else if($selected->approval == 'waiting'){
                    $status = 'Menunggu persetujuan';
                 } else if($selected->approval == 'waiting'){
                    $status = 'Menunggu persetujuan admin area';
                 }else if($selected->approval == 'approved1'){
                    $status = 'Menunggu persetujuan admin pusat';
                 }else if($selected->approval == 'approved2'){
                    $status = 'Pengunduran diri disetujui';
                 }
                 return $status;
            })
            ->addColumn('action',function($selected){
                $idRecord              = \Crypt::encrypt($selected->id);
                if($selected->approval == 'waiting') {
                $action ='<a  class="btn btn-primary btn-sm btnEdit" onclick="showRecord('."'".$idRecord."'".','."'". csrf_token() ."'".')"><i class="fa fa-edit"></i></a>
                <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="cancel" onclick="patchData('."'resign'".','."'".$idRecord."'".','."'". csrf_token() ."'".','."'subResign'".','."'canceled'".')">
                <i class="fa fa-undo" data-token="{{ csrf_token() }}"></i></a>';
                } else{
                    $action ='<a  class="btn btn-primary btn-sm btnEdit" onclick="showRecord('."'".$idRecord."'".','."'". csrf_token() ."'".')"><i class="fa fa-edit"></i></a>';
                }
                 return
                '<center>'.$action.'</center>';
            })
            ->make(true);
        }
        return view('master.resign');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['policy'] = Policy::where('id', 3)->first();
        return view('master.form-resign', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//    	return Auth::user()->id;
        $spcMember   = Member::where('user_id', Auth::user()->id)->first();
//        dd($spcMember);
        // cek kalau sudah mengajukan
        $checkRsn       = $this->checkRsn($spcMember->id);
        if ($checkRsn) {
            \Session::flash('error', '* Anda telah melakukan pengajuan pengunduran diri. Mohon hubungi bagian administrasi untuk info lebih lanjut.');
            return Redirect::back();
        }
        // cek simpanan cukup untuk menutup hutang
        $close       = $this->close($spcMember->id);
        if ($close) {
            \Session::flash('error', '* Pengunduran diri tidak bisa dilakukan. Karena, simpanan anda tidak cukup untuk menutup pinjaman yang belum lunas');
            return Redirect::back();
        }
        // jika validasi terlewati
        $newRsn = new Resign();
        $newRsn->member_id = $spcMember->id;
        $newRsn->date = $request['date'];
        $newRsn->reason = $request['reason'];
        $newRsn->approval = 'waiting';
        $newRsn->save();

        $approvals = User::FUserApproval()->get();
        $newRsn->newResignBlastTo($approvals, ['database', OneSignalChannel::class]);

        \Session::flash('message', '* Pengunduran diri berhasil diajukan. Silahkan menunggu informasi persetujuan lebih lanjut');
        return Redirect::back();


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $idRecord            = $this->decrypter($id);
        $finder              = Resign::findOrFail($idRecord);
        if($finder){
            $data      = array(
                            'error' => 0,
                            'json'   => $finder,
                        );
        } else{
            $data      = array(
                            'error' => 1,
                            'msg'   => 'Gagal memperbaharui status data.',
                        );
        }
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $idRecord            = $this->decrypter($id);
        $finder              = Resign::findOrFail($idRecord);
        if ($finder) {
            if($request['action'] == 'canceled'){
            $finder->approval  = $request['action'];
            $finder->note    = $finder->note.' canceled by member';
            }
            $finder->save();
            $data      = array(
                            'error' => 0,
                            'msg'   => 'Data berhasil diperbaharui.',
                        );
        } else{
            $data      = array(
                            'error' => 1,
                            'msg'   => 'Gagal memperbaharui status data.',
                        );
        }
        return response()->json($data);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

	public function list_resign(){
//		$query = Resign::with('member', 'member.project')->get();
//		return $query;
		return view('members.resign.list-resign');
	}

	public function listResign($query){
		if($query == 'all')
		{
			$query = Resign::with('member')->get();
		}
		return \DataTables::of($query)
			->editColumn('nik', function($resign){
				return $resign->member['nik_koperasi'];
			})
			->editColumn('name', function($resign){
				return $resign->member['first_name'];
			})
			->editColumn('proyek', function($resign){
				return $resign->member->project['project_name'];
			})
			->editColumn('tanggal', function($resign){

				return $resign->date;
			})
			->editColumn('status', function($resign){

				return $resign->approval;
			})
			->addColumn('action', function($resign){
				$downloadShu = url('list-resign').'/download/'.$resign->member['id'].'';
				$btnDownload = '<a class="btn btn-sm btn-primary" href="'.$downloadShu.'" data-toggle="tooltip" title="Download"><i class="fa fa-file"></i></a>';

				$btnResign = '<a  class="btn btn-primary btn-sm btnEdit" onclick="showRecord('."'".$resign->id."'".','."'". csrf_token() ."'".')"><i class="fa fa-edit"></i></a>';

				return $btnDownload.' '.$btnResign;

			})->make(true);
	}

	public function download($member_id){
		$excel_path = Storage::disk('template')->getDriver()->getAdapter()->applyPathPrefix('clearance.xlsx');
		$members = Member::where('id', $member_id)->FActive()->first();
		Excel::load($excel_path, function($reader) use ($members){

			$swajib = collect($members->depositWajib);
			$sukarela = collect($members->depositSukarela);
			$slain = collect($members->depositLainlain);
			$pokok = $members->pokok->value;
			$jasapinjaman = collect($members->loanBelumLunas);
			$jumlahpinjaman = $jasapinjaman->sum('value');
			$jumlahjasa = $jasapinjaman->sum('rate_of_interest') / 100;
			$totaljasa = $jumlahpinjaman * $jumlahjasa;

			$sheet = $reader->sheet(0);
			$sheet->setCellValue('E7', $members->fullname);
			$sheet->setCellValue('E8', $members->nik_koperasi);
			$sheet->setCellValue('E9', '-');
			$sheet->setCellValue('E10', $members->project->project_name);
			$sheet->setCellValue('E14', ($pokok ? $pokok : 0));

			$sheet->setCellValue('E15', (count($swajib) > 0 ? $swajib->sum('total') : 0));
			$sheet->setCellValue('E16', (count($sukarela) > 0 ? $sukarela->sum('total')  : 0));
			$sheet->setCellValue('E17', (count($slain) > 0 ? $slain->sum('total')  : 0));
			$sheet->setCellValue('E18', 15000);
			$sheet->setCellValue('E23', $jumlahpinjaman);
			$sheet->setCellValue('E24', $totaljasa);


		})->export('xlsx');
	}

	public function getStatusResign(Request $request){


		$input           = Input::all();
		$idRecord        = $input['id'];
		$selected        = Resign::findOrFail($idRecord);
		$approve = 'approve';
		$reject = 'reject';
		if($selected){
			$data        = array(
				'error'    => 0,
				'msg'      => 'Berhasil.',
				'json'     => $selected,
				'action'   => '<button type="button" class="btn btn-primary" onclick="onRejected('.$idRecord.','.$approve.')">Approve</button>
					<button type="button" class="btn btn-danger" onclick="onRejected('.$idRecord.','.$reject.')">Rejected</button>'
			);

//			$users = User::fMemberOnly()->whereNotNull('os_token')->get();
//			$selected->blastTo($users,['mail',OneSignalChannel::class]);
		} else{
			$data        = array(
				'error' => 1,
				'msg'   => 'Data resign tidak ditemukan.',
			);
		}
		return response()->json($data);

	}

	public function approve(Request $request){
    	$user = auth()->user();
    	$status = 'approved1';
    	if($user->position->id == 1){
    		$status = 'approved2';
		}elseif($user->position->id == 2){
			$status = 'approved2';
		}elseif($user->position->id == 6){
			$status = 'approved1';
		}

    	$approval = $request->status;
    	$note = $request->note;
    	$id = $request->id;

    	$resign = Resign::find($id);
    	if(!empty($resign)) {

			if ($approval == 'approved') {
				$resign->approval = $status;
			} elseif ($approval == 'rejected') {
				$resign->approval = $approval;
			} else {
				$resign->approval = $approval;
			}
			$resign->note = $note;
			$resign->update();

			$data = array(
				'error' => 0,
				'msg'   => 'Berhasil diupdate.',
			);

		}else{
			$data = array(
				'error' => 0,
				'msg'   => 'Gagal diupdate.',
			);
		}
		return response()->json($data);
	}

    public function updateStatusMember(){
        ini_set("memory_limit", "10056M");
        $Csv = new \App\Helpers\CsvToArray();
        $file = base_path() . '/database/seeds/csv/update_status_member.csv';
        $csv = utf8_encode(file_get_contents($file));
        $array = explode("\r", $csv);
        $data = array_map('str_getcsv', $array);

        $csv_data = array_slice($data, 0, 5000);
        foreach ($csv_data as $key => $val) {
            $iProject = str_replace("ï»¿", '', $val);
            $user = User::where('email', $iProject[2].'@gmail.com')->first();
            $member = $user->member;
            $member->is_active = 0;
            $member->is_permanent = 0;
            $member->keterangan = $val[3];
            $member->save();

            $resign = new Resign();
            $resign->member_id = $member->id;
            $resign->date = now()->format('Y-m-d');
            $resign->note = $val[3];
            $resign->reason = $val[3];
            $resign->is_resign = 1;
            $resign->approval = 'approve';
            $resign->save();

        }


        return 'updated';
    }

}
