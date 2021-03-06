<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use App\Activity;
use App\Perusahaan;
use App\Precheck;
use App\Postcheck;
use Auth;
use Session;
use Input;
use Validator;  
use Response;
use PDF;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Carbon\Carbon;
include_once(app_path().'/phpmailer/class.phpmailer.php');

class ActivityController extends Controller
{   
    public function index($status)
    {   
        $status = studly_case($status);
        $activities = Activity::where('status',$status)->get();
        $data = [
            'page' => 'activity',
            'activities' => $activities
        ];
        return view('activity.index',$data);
    }

    public function process($id)
    {
        $waktu_mulai = Carbon::now(7);
        $waktu_mulaiUpdate = Activity::where('id', $id)->update([
            'waktu_mulai' => $waktu_mulai->toTimeString()
            ]);
        $activity = Activity::where('id',$id)->first();
        
        $perusahaan = Perusahaan::where('nama_perusahaan',$activity->nama_perusahaan)->first();

        $data = [
            'page' => 'activity',
            'activity' => $activity,
            'perusahaan' => $perusahaan
        ];
        return view('activity.process',$data);
    }

    public function postProcess(Request $request) 
    {
        
        $waktu_selesai = Carbon::now(7);
        if (
            $request->input('id_activity') == '' ||
            $request->input('fisik') == '' ||
            $request->input('os') == '' ||
            $request->input('modul_terpasang') == '' ||
            $request->input('num_dial_iplus') == '' ||
            $request->input('ip_extreme') == '' ||
            $request->input('directory_modul') == '' ||
            $request->input('ip_extreme') == '' ||
            $request->input('lainlain') == '' ||
            $request->input('pengembalian_fungsi_awal') == '' ||
            $request->input('fungsi_model_edii') == '' ||
            $request->input('fungsi_pembentukan_edifact') == '' ||
            $request->input('fungsi_komunikasi') == '' ||
            $request->input('penyelesaian_semua_keluhan') == '' ||
            $request->input('serah_terima') == '' ||
            $request->input('nama_perusahaan') == '' ||
            $request->input('alamat') == '' ||
            $request->input('fax') == '' ||
            $request->input('nomor_telepon') == '' ||
            $request->input('no_hp') == '' ||
            $request->input('email') == '' ||
            $request->input('kontak_person') == '' ||
            $request->input('npwp') == '' ||
            $request->input('edi_number') == '' ||
            $request->input('enabler') == '' ||
            $request->input('jenis_perusahaan') == []
            )
        {
            return redirect()->route('admin.artikel.post.create', $request->input('id_activity'));
            Session::put('alert-danger', 'Pastikan semua field sudah terisi');
        }

        $modul_terpasang = implode(',', $request['modul_terpasang']);
        $precheckInput = Precheck::create([
            'id_activity' => $request->input('id_activity'),
            'fisik' => $request->input('fisik'),
            'os' => $request->input('os'),
            'modul_terpasang' => $request->input('modul_terpasang'),
            'modem' => $request->input('modem'),
            'accessories' => $request->input('accessories'),
            'num_dial_iplus' => $request->input('num_dial_iplus'),
            'ip_extreme' => $request->input('ip_extreme'),
            'directory_modul' => $request->input('directory_modul'),
            'lainlain' => $request->input('lain-lainPre'),
            'modul_terpasang' => $modul_terpasang
            ]);


        $postcheckInput = Postcheck::create([
            'id_activity' => $request->input('id_activity'),
            'pengembalian_fungsi_awal' => $request->input('pengembalian_fungsi_awal'),
            'fungsi_model_edii' => $request->input('fungsi_model_edii'),
            'fungsi_pembentukan_edifact' => $request->input('fungsi_pembentukan_edifact'),
            'fungsi_komunikasi' => $request->input('fungsi_komunikasi'),
            'penyelesaian_semua_keluhan' => $request->input('penyelesaian_semua_keluhan'),
            'serah_terima' => $request->input('serah_terima'),
            'lainlain' => $request->input('lain-lainPost'),
            ]);

        $jenis_perusahaan = implode(',', $request['jenis_perusahaan']);
          $perusahaan = Perusahaan::where('nama_perusahaan',$request->input('nama_perusahaan'))->
            update([
            'nama_perusahaan'   => $request->input('nama_perusahaan'),
            'alamat'            => $request->input('alamat'),
            'fax'               => $request->input('fax'),
            'nomor_telepon'     => $request->input('nomor_telepon'),
            'no_hp'             => $request->input('no_hp'),
            'email'             => $request->input('email'),
            'kontak_person'     => $request->input('kontak_person'),
            'npwp'              => $request->input('npwp'),
            'edi_number'        => $request->input('edi_number'),
            'enabler'           => $request->input('enabler'),
            'jenis_perusahaan'  => $jenis_perusahaan
            ]);

        $activityUpdate = Activity::where('id', $request->input('id_activity'))->update([
            'tindakan_perbaikan' => $request->input('tindakan_perbaikan'),
            'nama_admin' => Auth::User()->name,
            'status' => 'Sudah',
            'waktu_selesai' => $waktu_selesai->toTimeString()
            ]);

        $activity = Activity::where('id',$request->input('id_activity'))->first();
        $perusahaan = Perusahaan::where('nama_perusahaan',$request->input('nama_perusahaan'))->first();
        $precheck = Precheck::where('id_activity',$request->input('id_activity'))->first();
        $postcheck = Postcheck::where('id_activity',$request->input('id_activity'))->first();

        $createdAt = Carbon::parse($activity->created_at);

    
        $data = [
            'activity' => $activity,
            'perusahaan' => $perusahaan,
            'precheck' => $precheck,
            'postcheck' => $postcheck,
            'tanggal' => $createdAt->format('d-m-Y')
        ];    
        
        $tanggal = $createdAt->format('d-m-Y');
        //return view('pdf',$data);
         $pdf = PDF::loadView('pdf', $data);
         $pdf->save(public_path().'/Report_'.$perusahaan->nama_perusahaan.'_'.$tanggal.'_'.$activity->id.'.pdf' );
        Session::put('alert-success', 'Proses berhasil tersimpan');
        return Redirect::to('/activity/sudah');  

                
//    $mail = new \PHPMailer(true);
//         $body = 'An email test!';
 
// $mail->AddReplyTo('hilmiweq321@gmail.com', 'Clark Kent');
// $mail->SetFrom('hilmiweq321@gmail.com', 'Clark Kent');
// $mail->AddAddress('hilmiweq321@gmail.com', 'Code Chewing');
// $mail->Subject = 'Test email';
// $mail->MsgHTML( "weq");
 
// if( ! $mail->Send() ) {
//   echo "Mailer Error: " . $mail->ErrorInfo;

}}
