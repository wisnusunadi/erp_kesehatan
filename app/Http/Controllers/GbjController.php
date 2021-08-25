<?php

namespace App\Http\Controllers;

use App\GudangProduk;
use App\MutasiGudangProduk;
use App\HasilPerakitan;
use App\DetailProduk;
use App\Divisi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use PDF;
use Yajra\DataTables\Contracts\DataTable;

class GbjController extends Controller
{
    public function gudang_produk()
    {
        $p = DetailProduk::all();
        return view('page.gbj.gudang_produk_show', ['p' => $p]);
    }

    public function gudang_produk_show()
    {
        $s = GudangProduk::all();
        return DataTables::of($s)
            ->addIndexColumn()
            ->editColumn('tanggal', function ($s) {
                return Carbon::createFromFormat('Y-m-d', $s->tanggal)->format('d-m-Y');
            })

            ->addColumn('jumlah', function ($s) {
                $btn = HasilPerakitan::where('perakitan_id', $s->id)->count();
                return $btn . " " . $s->Bppb->DetailProduk->satuan;
            })
            ->addColumn('aksi', function ($s) {
                $btn = '<a href = "/perakitan/pemeriksaan/hasil/' . $s->id . '"><button class="btn btn-info btn-sm karyawan-img-small" style="border-radius:50%;"><i class="fas fa-eye"></i></button></a>';
                return $btn;
            })
            ->rawColumns(['operator', 'aksi'])
            ->make(true);
    }

    public function gudang_produk_produk($id)
    {
        $k = GudangProduk::where('detail_produk_id', $id)->with('DetailProduk')->first();
        return $k;
    }

    public function gudang_produk_produk_show($id)
    {
        $s = MutasiGudangProduk::whereHas('GudangProduk', function ($q) use ($id) {
            $q->where([
                ['detail_produk_id', '=', $id],
                ['divisi_id', '=', '13']
            ]);
        })->get();
        return DataTables::of($s)
            ->addIndexColumn()
            ->editColumn('tanggal', function ($s) {
                return Carbon::createFromFormat('Y-m-d', $s->tanggal)->format('d-m-Y');
            })
            ->make(true);
    }

    public function gudang_produk_tanggal_show($tanggal)
    {
        $s = MutasiGudangProduk::where('tanggal', $tanggal)->whereHas('GudangProduk', function ($q) {
            $q->where('divisi_id', '13');
        })->get();
        return DataTables::of($s)
            ->addIndexColumn()
            ->addColumn('produk', function ($s) {
                return $s->GudangProduk->DetailProduk->nama;
            })
            ->make(true);
    }

    public function gudang_produk_create($id)
    {
        $p = DetailProduk::find($id);
        $d = Divisi::all();
        return view('page.gbj.gudang_produk_create', ['id' => $id, 'p' => $p, 'd' => $d]);
    }

    public function gudang_produk_store(Request $request)
    {
        $v = Validator::make(
            $request->all(),
            [
                'nomor' => 'required',
                'tanggal' => 'required',
                'detail_produk_id' => 'required',
            ],
            [
                'nomor.required' => "No Kartu Stok harus diisi",
                'tanggal.required' => "Tanggal harus diisi",
                'detail_produk_id.required' => "Produk harus diisi",
            ]
        );

        if ($v->fails()) {
            return redirect()->back()->withErrors($v);
        } else {
            $s = GudangProduk::create([
                'detail_produk_id' => $request->detail_produk_id,
                'divisi_id' => $request->divisi_id
            ]);
            $bool = true;
            for ($i = 0; $i < count($request->tanggal); $i++) {
                $d = MutasiGudangProduk::create([
                    'gudang_produk_id' => $s->id,
                    'divisi_id' => $request->divisi_id[$i],
                    'tanggal' => $request->tanggal[$i],
                    'jumlah_masuk' => $request->jumlah_masuk[$i],
                    'jumlah_keluar' => $request->jumlah_keluar[$i],
                    'jumlah_saldo' => $request->jumlah_saldo[$i]
                ]);
                if (!$d) {
                    $bool = false;
                }
            }

            if ($bool == true) {
                return redirect()->back()->with('success', 'Selesai membuat Kartu Stock');
            } else if ($bool == true) {
                return redirect()->back()->with('error', 'Gagal membuat Kartu Stock');
            }
        }
    }
}
