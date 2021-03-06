<?php

namespace App\Http\Controllers;

use App\GudangProduk;
use App\MutasiGudangProduk;
use App\HasilPerakitan;
use App\DetailProduk;
use App\Divisi;
use App\HistoriMutasiGudangProduk;
use App\Podo_offline;
use App\Podo_online;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Exists;
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

    public function mutasi_gudang_produk($id)
    {
        $gp = MutasiGudangProduk::find($id);
        return view('page.gbj.mutasi_gudang_produk_show', ['gp' => $gp, 'id' => $id]);
    }

    public function mutasi_gudang_produk_show($id)
    {
        $s = HistoriMutasiGudangProduk::where('mutasi_gudang_produk_id', $id)->get();
        return DataTables::of($s)
            ->addIndexColumn()
            ->addColumn('barcode', function ($s) {
                if ($s->HasilPerakitan->HasilPengemasan->first()->no_barcode != "") {
                    return str_replace("/", "", $s->HasilPerakitan->HasilPengemasan->first()->Pengemasan->alias_barcode) . $s->HasilPerakitan->HasilPengemasan->first()->no_barcode;
                } else {
                    return str_replace("/", "", $s->HasilPerakitan->HasilMonitoringProses->first()->MonitoringProses->alias_barcode) . $s->HasilPerakitan->HasilMonitoringProses->first()->no_barcode;
                }
            })
            ->rawColumns(['barcode'])
            ->make(true);
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
        $k = MutasiGudangProduk::whereHas('GudangProduk', function ($q) use ($id) {
            $q->where('detail_produk_id', $id);
        })->orderBy('id', 'desc')->with('GudangProduk.DetailProduk.Produk')->first();
        return $k;
    }

    public function gudang_produk_produk_show($id)
    {
        $jumlah = 0;
        $bool = true;
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
            ->editColumn('divisi_id', function ($s) {
                $btn = "";
                if ($s->jumlah_masuk != "0") {
                    $btn .= '<i class="fas fa-arrow-circle-down" style="color:green;"></i>';
                } else if ($s->jumlah_keluar != "0") {
                    $btn .= '<i class="fas fa-arrow-circle-up" style="color:red;"></i>';
                }
                return $btn . " " . $s->Divisi->nama;
            })
            ->editColumn('keterangan', function ($s) {
                $btn = '<a href="#" class="btn pop limitchar larget" data-container="body" data-placement="bottom" data-html="true" data-toggle="popover" 
                    data-content="' . $s->keterangan . '">' . $s->keterangan . '</a>';
                return $btn;
            })
            ->editColumn('jumlah_saldo', function ($s) {
                global $bool;
                global $jumlah;
                if ($bool == true) {
                    $bool = false;
                    if ($s->jumlah_masuk != "0") {
                        $jumlah = (int)$s->jumlah_masuk;
                    } else if ($s->jumlah_keluar != "0") {
                        $jumlah = (int)$s->jumlah_keluar * -1;
                    }
                    return $jumlah;
                } else if ($bool == false) {
                    if ($s->jumlah_masuk != "0") {
                        $jumlah = $jumlah + (int)$s->jumlah_masuk;
                    } else if ($s->jumlah_keluar != "0") {
                        $jumlah = $jumlah - (int)$s->jumlah_keluar;
                    }
                    return $jumlah;
                }
            })
            ->addColumn('aksi', function ($s) {
                $btn = '<a class="historimutasimodal" data-toggle="modal" data-target="#historimutasimodal" data-attr="/gudang_produk_gbj/mutasi/show/' . $s->id . '" data-id="' . $s->id . '">';
                $btn .= '<div><button type="button" class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fa fa-search"></i></button></div>';
                $btn .= '<div><small>Lihat No Seri</small></div></a>';
                // $q = HistoriMutasiGudangProduk::where('mutasi_gudang_produk_id', $s->id)->get();
                // $btn = '<a href="#" class="hasTooltip"><i class="fas fa-ellipsis-h"></i><span>';
                // foreach ($q as $i) {
                //     $btn .= '<p>';
                //     if ($i->HasilPerakitan->HasilPengemasan->first()->no_barcode != "") {
                //         $btn .= str_replace("/", "", $i->HasilPerakitan->HasilPengemasan->first()->Pengemasan->alias_barcode) . $i->HasilPerakitan->HasilPengemasan->first()->no_barcode;
                //     } else {
                //         $btn .= str_replace("/", "", $i->HasilPerakitan->HasilMonitoringProses->first()->MonitoringProses->alias_barcode) . $i->HasilPerakitan->HasilMonitoringProses->first()->no_barcode;
                //     }
                //     $btn .= '</p>';
                // }
                // $btn .= '</span></a>';
                return $btn;
            })
            ->rawColumns(['aksi', 'divisi_id', 'keterangan'])
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
            ->editColumn('divisi_id', function ($s) {
                $btn = "";
                if ($s->jumlah_masuk != "0") {
                    $btn = '<i class="fas fa-arrow-circle-down" style="color:green;"></i>';
                } else if ($s->jumlah_keluar != "0") {
                    $btn = '<i class="fas fa-arrow-circle-up" style="color:red;"></i>';
                }
                return $btn . " " . $s->Divisi->nama;
            })
            ->editColumn('keterangan', function ($s) {
                $btn = '<div class="limitchar">' . $s->keterangan . '</div>';
                $btn = '<a href="#" class="btn pop limitchar medt" data-container="body" data-placement="bottom" data-html="true" data-toggle="popover" 
                    data-content="' . $s->keterangan . '">' . $s->keterangan . '</a>';
                return $btn;
            })
            ->addColumn('aksi', function ($s) {
                $btn = '<a class="historimutasimodal" data-toggle="modal" data-target="#historimutasimodal" data-attr="/gudang_produk_gbj/mutasi/show/' . $s->id . '" data-id="' . $s->id . '">';
                $btn .= '<div><button type="button" class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fa fa-search"></i></button></div>';
                $btn .= '<div><small>Lihat No Seri</small></div></a>';
                return $btn;
            })
            ->rawColumns(['aksi', 'divisi_id', 'keterangan'])
            ->make(true);
    }

    public function gudang_produk_create($id)
    {
        $p = DetailProduk::find($id);
        $d = Divisi::all();
        return view('page.gbj.gudang_produk_create', ['id' => $id, 'p' => $p, 'd' => $d]);
    }

    public function gudang_produk_store(Request $request, $id)
    {
        $v = Validator::make(
            $request->all(),
            [
                'divisi_id' => 'required',
                'keterangan' => 'required',
                'jumlah_keluar' => 'required',
                'jumlah_masuk' => 'required',
                'keterangan' => 'required',
                'tanggal' => 'required',
            ],
            [
                'divisi_id.required' => 'Asal / Tujuan Divisi harus dipilih',
                'keterangan.required' => 'Keterangan harus diisi',
                'jumlah_keluar.required' => 'Jumlah Keluar harus diisi',
                'jumlah_masuk.required' => 'Jumlah Masuk harus diisi',
                'keterangan.required' => 'Keterangan harus diisi',
                'tanggal.required' => "Tanggal harus diisi",
            ]
        );

        if ($v->fails()) {
            return redirect()->back()->withErrors($v);
        } else {
            $s = GudangProduk::create([
                'detail_produk_id' => $id,
                'divisi_id' => '13'
            ]);
            $bool = true;
            $saldo = 0;
            for ($i = 0; $i < count($request->tanggal); $i++) {
                if ($request->jumlah_masuk[$i] != "0") {
                    $saldo = $saldo + (int) $request->jumlah_masuk[$i];
                } else if ($request->jumlah_keluar[$i] != "0") {
                    $saldo = $saldo - (int) $request->jumlah_keluar[$i];
                }

                $d = MutasiGudangProduk::create([
                    'gudang_produk_id' => $s->id,
                    'divisi_id' => $request->divisi_id[$i],
                    'keterangan' => $request->keterangan[$i],
                    'tanggal' => $request->tanggal[$i],
                    'jumlah_masuk' => $request->jumlah_masuk[$i],
                    'jumlah_keluar' => $request->jumlah_keluar[$i],
                    'jumlah_saldo' => $saldo
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

    public function surat_jalan()
    {
        return view('page.gbj.surat_jalan_show');
    }

    public function purchase_order()
    {
        return view('page.gbj.purchase_order_show');
    }

    public function purchase_order_grid_show($status)
    {
        $po = "";
        if ($status == "online") {
            $po = Podo_online::leftJoin('ekatjuals', 'podo_onlines.ekatjual_id', '=', 'ekatjuals.id')
                ->leftJoin('distributors', 'ekatjuals.distributor_id', '=', 'distributors.id')->select(
                    'podo_onlines.id as id',
                    'podo_onlines.po as no_po',
                    'podo_onlines.tglpo as tgl_po',
                    'podo_onlines.keterangan as keterangan',
                    'distributors.nama as customer'
                )->get();
        } else if ($status == "offline") {
            $po = Podo_offline::leftJoin('offlines', 'podo_offlines.offline_id', '=', 'offlines.id')
                ->leftJoin('distributors', 'offlines.customer_id', '=', 'distributors.id')->select(
                    'podo_offlines.id as id',
                    'podo_offlines.po as no_po',
                    'podo_offlines.tglpo as tgl_po',
                    'podo_offlines.keterangan as keterangan',
                    'distributors.nama as distributor'
                )->get();
        } else if ($status == "semua" || $status == "") {
            $po_on = Podo_online::leftJoin('ekatjuals', 'podo_onlines.ekatjual_id', '=', 'ekatjuals.id')
                ->leftJoin('distributors', 'ekatjuals.distributor_id', '=', 'distributors.id')->select(
                    'podo_onlines.id as id',
                    'podo_onlines.po as no_po',
                    'podo_onlines.tglpo as tgl_po',
                    'podo_onlines.keterangan as keterangan',
                    'distributors.nama as customer'
                )->get();

            $po_off = Podo_offline::leftJoin('offlines', 'podo_offlines.offline_id', '=', 'offlines.id')
                ->leftJoin('distributors', 'offlines.customer_id', '=', 'distributors.id')->select(
                    'podo_offlines.id as id',
                    'podo_offlines.po as no_po',
                    'podo_offlines.tglpo as tgl_po',
                    'podo_offlines.keterangan as keterangan',
                    'distributors.nama as distributor'
                )->get();
            $po = $po_on->merge($po_off);
        }
        return $po;
    }

    public function purchase_order_table_show($status)
    {
        $po = "";
        if ($status == "online") {
            $po = Podo_online::leftJoin('ekatjuals', 'podo_onlines.ekatjual_id', '=', 'ekatjuals.id')
                ->leftJoin('distributors', 'ekatjuals.distributor_id', '=', 'distributors.id')->select(
                    'podo_onlines.id as id',
                    'podo_onlines.po as no_po',
                    'podo_onlines.tglpo as tgl_po',
                    'podo_onlines.keterangan as keterangan',
                    'distributors.nama as customer'
                )->get();
        } else if ($status == "offline") {
            $po = Podo_offline::leftJoin('offlines', 'podo_offlines.offline_id', '=', 'offlines.id')
                ->leftJoin('distributors', 'offlines.customer_id', '=', 'distributors.id')->select(
                    'podo_offlines.id as id',
                    'podo_offlines.po as no_po',
                    'podo_offlines.tglpo as tgl_po',
                    'podo_offlines.keterangan as keterangan',
                    'distributors.nama as distributor'
                )->get();
        } else if ($status == "semua" || $status == "") {
            $po_on = Podo_online::leftJoin('ekatjuals', 'podo_onlines.ekatjual_id', '=', 'ekatjuals.id')
                ->leftJoin('distributors', 'ekatjuals.distributor_id', '=', 'distributors.id')->select(
                    'podo_onlines.id as id',
                    'podo_onlines.po as no_po',
                    'podo_onlines.tglpo as tgl_po',
                    'podo_onlines.keterangan as keterangan',
                    'distributors.nama as customer'
                )->get();

            $po_off = Podo_offline::leftJoin('offlines', 'podo_offlines.offline_id', '=', 'offlines.id')
                ->leftJoin('distributors', 'offlines.customer_id', '=', 'distributors.id')->select(
                    'podo_offlines.id as id',
                    'podo_offlines.po as no_po',
                    'podo_offlines.tglpo as tgl_po',
                    'podo_offlines.keterangan as keterangan',
                    'distributors.nama as distributor'
                )->get();
            $po = $po_on->merge($po_off);
        }


        return DataTables::of($po)
            ->addColumn('checkbox', function ($s) {
                return '<input type="checkbox" class="form-check" value="' . $s->id . '">';
            })
            ->addColumn('cust', function ($s) {
                if (isset($s->customer)) {
                    return $s->customer;
                } else if (isset($s->distributor)) {
                    return $s->distributor;
                }
            })
            ->addColumn('no_po', function ($s) {
                return $s->no_po;
            })
            ->addColumn('tgl_po', function ($s) {
                return Carbon::createFromFormat('Y-m-d', $s->tgl_po)->format('d-m-Y');
            })
            ->addColumn('jenis_po', function ($s) {
                if (isset($s->customer)) {
                    return '<small class="light-green-text">Online</small>';
                } else if (isset($s->distributor)) {
                    return '<small class="purple-text">Offline</small>';
                }
            })
            ->addColumn('aksi', function ($s) {
            })
            ->rawColumns(['checkbox', 'jenis_po'])
            ->make(true);
    }
}
