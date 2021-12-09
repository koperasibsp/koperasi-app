<?php

namespace App\Http\Controllers;

use App\GenerateReportMembers;
use App\Helpers\DownloadReport;
use App\Helpers\reverseDataHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GenerateReportMembersController extends Controller
{
    /**
     * Display a listing of the resources
     */
    public function index()
    {

        $selected = GenerateReportMembers::all();
        if (request()->ajax()) {
            return \DataTables::of($selected)
                ->editColumn('name', function ($selected) {
                    return $selected->name;
                })
                ->editColumn('start', function ($selected) {
                    return $selected->start;
                })
                ->editColumn('end', function ($selected) {
                    return $selected->end;
                })
                ->editColumn('status', function ($selected) {
                    return $selected->status;
                })
                ->addColumn('action',function($selected){
                    $isCanEdit = auth()->user()->can('edit.generate.report.deposit');
                    $isCanDelete = auth()->user()->can('delete.generate.report.deposit');
                    $isCanDownload = auth()->user()->can('view.generate.report.deposit');
                    $btnDelete ='';
                    $btnEdit= '';
                    $btnDownload='';
                    if($isCanDownload)
                    {
                        $btnDownload ='<a class="btn btn-sm btn-warning" href="'.url("generate/member-report/".$selected->id).'/download"><i class="fa fa-download"></i></a>';
                    }
                    if($isCanEdit){
                        $btnEdit = '<a class="btn btn-primary btn-sm btnEdit" href="'.url("generate/member-report/".$selected->id).'/edit"><i class="fa fa-edit"></i></a>';
                    }
                    if($isCanDelete){
                        $btnDelete = '<button class="btn btn-sm btn-danger" href="javascript:void(0)" title="Hapus" onclick="destroyData('."'member-report'".','."'".$selected->id."'".','."'". csrf_token() ."'".','."'listLevel'".')"><i class="fa fa-trash" data-token="{{ csrf_token() }}"></i></button>';
                    }

                    return '<center>'.$btnDownload.$btnEdit.$btnDelete.'</center>';
                })
                ->make(true);
        }
        return view('report.generate.member.list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('report.generate.member.new');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $start = Carbon::createFromFormat('Y-m-d', $request['start']);
        $end = Carbon::createFromFormat('Y-m-d', $request['end']);
        if($start->greaterThan($end))
        {
            session()->flash('errors', collect(['Tanggal Awal dan Akhir Laporan tidak valid']));
            return redirect()->back()->withInput();
        }

        GenerateReportMembers::create($request->all());
        session()->flash('success', trans('response-message.success.create',['object'=>'Laporan Deposit']));
        return redirect('generate/member-report');
    }

    /**
     * Display the specified resource.
     *
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = GenerateReportMembers::findOrFail($id);
        return view('report.generate.member.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $start = Carbon::createFromFormat('Y-m-d', $request['start']);
        $end = Carbon::createFromFormat('Y-m-d', $request['end']);
        if($start->greaterThan($end))
        {
            session()->flash('errors', collect(['Tanggal Mulai dan Akhir Laporan tidak valid']));
            return redirect()->back()->withInput();
        }
        GenerateReportMembers::findOrFail($id)->update($request->all());
        session()->flash('success', trans('response-message.success.update',['object'=>'Laporan Deposit']));
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $genDeposit = GenerateReportMembers::findOrFail($id);
        $genDeposit->delete();
        session()->flash('success', trans('response-message.success.delete', ['object'=>'Laporan Deposit']));
        return redirect()->back();
    }

    public function download($id){
        $genMember = GenerateReportMembers::findOrFail($id);
        $dataMember = DownloadReport::generateMember($genMember->start, $genMember->end);
        $spreadsheet = ReverseDataHelper::downloadMember($dataMember, $genMember);
        $filename = $genMember->id.'_anggota.xlsx';
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        $path = \Storage::disk('deposit')->path($filename);
        $writer->save($path);
        if(\Storage::disk('deposit')->exists($filename)){
            return response()->download($path, $filename)->deleteFileAfterSend(true);
        }
//        return $genReportDeposit;
    }
}
