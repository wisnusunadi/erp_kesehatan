<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Collection;
use App\Events\Notification;
use Yajra\DataTables\Facades\DataTables;

use App\User;
use App\Bill_of_material;
use App\DetailProduk;
use App\Produk;
use App\Part;
use App\Bppb;
use App\DetailPenyerahanBarangJadi;
use App\KelompokProduk;
use App\Event;
use App\PenyerahanBarangJadi;
use Carbon\Carbon;
use App\HasilPengemasan;
use App\PermintaanBahanBaku;

class PPICController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $date = Event::toBase()->get();
        $date = json_encode($date);
        return view('page.ppic.jadwal_produksi', ['date' => $date]);
    }

    public function ppic()
    {
        $list = Produk::toBase()->get();
        return view("ppic.form_ppic", compact('list'));
    }

    public function calendar_create(Request $request)
    {
        $data = [
            'title' => $request->title,
            'start' => $request->start,
            'end' => $request->end,
        ];

        // return json_encode($data);
        Event::create($data);
    }

    public function calendar_delete(Request $request)
    {
        if ($request->id != "") Event::destroy($request->id);
    }

    public function bom()
    {
        $list = Produk::all();
        return view('page.ppic.bom', compact('list'));
    }

    public function get_bom()
    {
    }

    public function bppb()
    {
        return view('page.ppic.bppb_show');
    }

    public function bppb_show()
    {
        $b = Bppb::all();
        return DataTables::of($b)
            ->addIndexColumn()
            ->addColumn('gambar', function ($s) {
                $gambar = '<div class="text-center">';
                $gambar .= '<img class="product-img-small img-fluid"';
                if (empty($s->DetailProduk->foto)) {
                    $gambar .= 'src="{{url(\'assets/image/produk\')}}/noimage.png"';
                } else if (!empty($s->DetailProduk->foto)) {
                    $gambar = 'src="{{asset(\'image/produk/\')}}/' . $s->DetailProduk->foto . '"';
                }

                $gambar .= 'title="' . $s->DetailProduk->nama . '">';
                return $gambar;
            })
            ->addColumn('produk', function ($s) {
                $btn = '<hgroup><h6 class="heading">' . $s->DetailProduk->nama . '</h6><div class="subheading text-muted">' . $s->DetailProduk->Produk->Kelompokproduk->nama . '</div></hgroup>';
                return $btn;
            })
            ->addColumn('laporan', function ($s) {
                $btn = '<a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"  title="Klik untuk melihat detail BPPB">';
                $btn .= '<i class="fas fa-eye" aria-hidden="true"></i> </a>';

                $btn .= '<div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
                $btn .= '<a class="dropdown-item" href="/bppb/permintaan_bahan_baku/' . $s->id . '"><span style="color: black;"><i class="fas fa-box-open" aria-hidden="true"></i>&nbsp;Permintaan Bahan Baku</span></a>';
                $btn .= '<a class="dropdown-item" href="/bppb/pengembalian_barang_gudang/' . $s->id . '"><span style="color: black;"><i class="fas fa-dolly" aria-hidden="true"></i>&nbsp;Pengembalian Barang Gudang</span></a>';
                $btn .= '<a class="dropdown-item" href="/bppb/penyerahan_barang_jadi/' . $s->id . '"><span style="color: black;"><i class="fas fa-pallet" aria-hidden="true"></i>&nbsp;Penyerahan Barang Jadi</span></a>';
                return $btn;
            })
            ->addColumn('aksi', function ($s) {
                $btn = '<a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"  title="Klik untuk melihat detail BPPB">';
                $btn .= '<i class="fa fa-ellipsis-v" aria-hidden="true"></i> </a>';

                $btn .= '<div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
                $btn .= '<a class="dropdown-item" href="/bppb/edit/' . $s->id . '"><span style="color: black;"><i class="fa fa-edit" aria-hidden="true"></i>&nbsp;Ubah</span></a>';
                $btn .= '<a class="dropdown-item deletemodal" data-toggle="modal" data-target="#deletemodal" data-url="/bppb/delete/' . $s->id . '"><span style="color: black;"><i class="fa fa-trash" aria-hidden="true"></i>&nbsp;Hapus</span></a></div>';
                return $btn;
            })
            ->editColumn('divisi_id', function ($s) {
                $btn = $s->Divisi->nama;
                return $btn;
            })
            ->rawColumns(['gambar', 'produk', 'aksi', 'laporan'])
            ->make(true);
    }

    public function bppb_create()
    {
        $k = KelompokProduk::all();
        return view('page.ppic.bppb_create', ['k' => $k]);
    }

    public function bppb_store(Request $request)
    {
        $v = Validator::make(
            $request->all(),
            [
                'detail_produk_id' => 'required',
                'divisi_id' => 'required',
                'no_bppb_urutan' => 'required',
                'no_bppb_kode' => 'required',
                'no_bppb_tahun' => 'required',
                'no_bppb_bulan' => 'required',
                'jumlah' => 'required',
                'tanggal_bppb' => 'required',
            ],
            [
                'detail_produk_id.required' => "Silahkan Pilih Produk",
                'divisi_id.reqired' => "Silahkan Pilih Divisi",
                'jumlah.required' => "Jumlah Harus Diisi",
                'tanggal_bppb.required' => "Tanggal Harus Diisi",
            ]
        );

        if ($v->fails()) {
            return redirect()->back()->withErrors($v);
        } else {
            $no_bppb = $request->no_bppb_urutan . '/' . $request->no_bppb_kode . '/' . $request->no_bppb_bulan . '/' . $request->no_bppb_tahun;
            $c = Bppb::create([
                'no_bppb' => $no_bppb,
                'detail_produk_id' => $request->detail_produk_id,
                'divisi_id' => $request->divisi_id,
                'tanggal_bppb' => $request->tanggal_bppb,
                'jumlah' => $request->jumlah
            ]);

            if ($c) {
                // $u = User::where('divisi_id', $request->divisi_id)->get();
                // foreach ($u as $i) {
                //     $cs = $this->NotifikasiController->create("Penambahan BPPB", "telah menambahkan BPPB", Auth::user()->id, $i->id, "/bppb");
                // }
                return redirect()->back()->with('success', "Berhasil menambahkan BPPB");
            } else {
                return redirect()->back()->with('error', "Gagal menambahkan BPPB");
            }
        }
    }

    public function bppb_edit($id)
    {
        $b = Bppb::find($id);
        $k = KelompokProduk::all();
        $dp = DetailProduk::all();

        $no_bppb = explode("/", $b->no_bppb);
        return view('page.ppic.bppb_edit', ['id' => $id, 'i' => $b, 'no_bppb' => $no_bppb, 'k' => $k, 'dp' => $dp]);
    }

    public function bppb_update($id, Request $request)
    {
        $v = Validator::make(
            $request->all(),
            [
                'detail_produk_id' => 'required',
                'divisi_id' => 'required',
                'no_bppb_urutan' => 'required',
                'no_bppb_kode' => 'required',
                'no_bppb_tahun' => 'required',
                'no_bppb_bulan' => 'required',
                'jumlah' => 'required',
                'tanggal_bppb' => 'required',
            ],
            [
                'detail_produk_id.required' => "Silahkan Pilih Produk",
                'divisi_id.required' => "Silahkan Pilih Divisi",
                'jumlah.required' => "Jumlah Harus Diisi",
                'tanggal_bppb.required' => "Tanggal Harus Diisi",
            ]
        );

        if ($v->fails()) {
            return redirect()->back()->withErrors($v);
        } else {
            $no_bppb = $request->no_bppb_urutan . '/' . $request->no_bppb_kode . '/' . $request->no_bppb_bulan . '/' . $request->no_bppb_tahun;

            $u = Bppb::find($id);
            $u->no_bppb = $no_bppb;
            $u->detail_produk_id = $request->detail_produk_id;
            $u->divisi_id = $request->divisi_id;
            $u->tanggal_bppb = $request->tanggal_bppb;
            $u->jumlah = $request->jumlah;
            $up = $u->save();

            if ($up) {
                return redirect()->back()->with('success', "Berhasil mengubah BPPB");
            } else {
                return redirect()->back()->with('error', "Gagal mengubah BPPB");
            }
        }
    }

    public function bppb_delete($id, Request $request)
    {
        $bppb = Bppb::find($id);
        $this->UserLogController->create(Auth::user()->id, $bppb->no_bppb, 'BPPB', 'Hapus', $request->keterangan_log);
        $d = $bppb->delete();

        return redirect()->back();
    }

    public function bppb_permintaan_bahan_baku($id)
    {
        $s = Bppb::find($id);
        return view('page.ppic.bppb_permintaan_bahan_baku_show', ['id' => $id, 's' => $s]);
    }

    public function bppb_permintaan_bahan_baku_show($id)
    {
        $s = "";
        if (Auth::user()->Divisi->nama == "Produksi" || Auth::user()->Divisi->nama == "PPIC") {
            $s = PermintaanBahanBaku::where('bppb_id', $id)->get();
        } else if (Auth::user()->Divisi->nama == "Gudang Bahan Material") {
            $s = PermintaanBahanBaku::where([
                ['bppb_id', '=', $id],
                ['divisi_id', '=', Auth::user()->divisi_id]
            ])->whereIn('status', ['req_permintaan', 'acc_permintaan', 'rej_permintaan'])->get();
        }

        return DataTables::of($s)
            ->addIndexColumn()
            ->editColumn('tanggal', function ($s) {
                return Carbon::createFromFormat('Y-m-d', $s->tanggal)->format('d-m-Y');
            })
            ->editColumn('divisi_id', function ($s) {
                return $s->Divisi->nama;
            })
            ->editColumn('status', function ($s) {
                $btn = "";
                if (Auth::user()->divisi->nama == "Produksi") {
                    if ($s->status == "dibuat") {
                        $btn = '<a href="/bppb/penyerahan_barang_jadi/status/' . $s->id . '/req_permintaan">
                                <button type="button" class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fas fa-paper-plane"></i></button>
                                <div><small>Penyerahan</small></div></a>';
                    } else if ($s->status == "req_permintaan") {
                        $btn = '<div><small class="warning-text">Menunggu</small></div>';
                    } else if ($s->status == "acc_permintaan") {
                        $btn = '<div><small class="success-text">Diterima</small></div>';
                    } else if ($s->status == "rej_permintaan") {
                        $btn = '<div><small class="danger-text">Ditolak</small></div>';
                    }
                } else if (Auth::user()->divisi->nama == "PPIC") {
                    if ($s->status == "dibuat") {
                        $btn = '<div><small class="info-text">Sedang Dibuat</small></div>';
                    } else if ($s->status == "req_permintaan") {
                        $btn = '<div><small class="warning-text">Menunggu</small></div>';
                    } else if ($s->status == "acc_permintaan") {
                        $btn = '<div><small class="success-text">Diterima</small></div>';
                    } else if ($s->status == "rej_permintaan") {
                        $btn = '<div><small class="danger-text">Ditolak</small></div>';
                    }
                } else if (Auth::user()->Divisi->nama == "Gudang Bahan Material") {
                    if ($s->status == "req_permintaan") {
                        $btn = '<a href="/bppb/permintaan_bahan_baku/status/' . $s->id . '/terima"><button type="button" class="btn btn-success btn-sm m-1" style="border-radius:50%;"><i class="fas fa-check"></i></button></a>
                                <a href="/bppb/permintaan_bahan_baku/status/' . $s->id . '/tolak"><button type="button" class="btn btn-danger btn-sm m-1" style="border-radius:50%;"><i class="fas fa-times"></i></button></a>';
                    } else if ($s->status == "acc_permintaan") {
                        $btn = '<div><small class="success-text">Diterima</small></div>';
                    } else if ($s->status == "rej_permintaan") {
                        $btn = '<div><small class="danger-text">Ditolak</small></div>';
                    }
                }

                return $btn;
            })
            ->addColumn('aksi', function ($s) {
                $btn = '<a href = "/bppb/penyerahan_barang_jadi/' . $s->id . '"><button class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fas fa-eye"></i></button></a>';
                $btn .= '<a href = "/perakitan/laporan/edit/' . $s->id . '"><button class="btn btn-warning btn-sm m-1" style="border-radius:50%;"><i class="fas fa-edit"></i></button></a>';
                $btn .= '<a class="deletemodal" data-toggle="modal" data-target="#deletemodal" data-attr="/perakitan/laporan/delete/' . $s->id . '"><button class="btn btn-danger btn-sm m-1" style="border-radius:50%;"><i class="fas fa-trash"></i></button></a>';
                return $btn;
            })
            ->rawColumns(['no_seri', 'aksi', 'status'])
            ->make(true);
    }

    public function bppb_pengembalian_barang_gudang($id)
    {
        $s = Bppb::find($id);
        return view('page.ppic.bppb_pengembalian_barang_gudang_show', ['id' => $id, 's' => $s]);
    }

    public function bppb_penyerahan_barang_jadi($id)
    {
        $s = Bppb::find($id);
        return view('page.ppic.bppb_penyerahan_barang_jadi_show', ['id' => $id, 's' => $s]);
    }

    public function bppb_penyerahan_barang_jadi_show($id)
    {
        $s = "";
        if (Auth::user()->Divisi->nama == "Produksi" || Auth::user()->Divisi->nama == "PPIC") {
            $s = PenyerahanBarangJadi::where('bppb_id', $id)->get();
        } else if (Auth::user()->Divisi->nama == "Gudang Barang Jadi" || Auth::user()->Divisi->nama == "Gudang Karantina") {
            $s = PenyerahanBarangJadi::where([
                ['bppb_id', '=', $id],
                ['divisi_id', '=', Auth::user()->divisi_id]
            ])->whereIn('status', ['req_penyerahan', 'penyerahan'])->get();
        }

        return DataTables::of($s)
            ->addIndexColumn()
            ->editColumn('tanggal', function ($s) {
                return Carbon::createFromFormat('Y-m-d', $s->tanggal)->format('d-m-Y');
            })
            ->addColumn('no_seri', function ($s) {
                $arr = [];
                foreach ($s->DetailPenyerahanBarangJadi as $i) {
                    array_push($arr, $i->HasilPerakitan->no_seri);
                }
                return implode("<br>", $arr);
            })
            ->editColumn('divisi_id', function ($s) {
                return $s->Divisi->nama;
            })
            ->editColumn('status', function ($s) {
                $btn = "";
                if (Auth::user()->divisi->nama == "Produksi") {
                    if ($s->status == "dibuat") {
                        $btn = '<a href="/bppb/penyerahan_barang_jadi/status/' . $s->id . '/req_penyerahan">
                                <button type="button" class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fas fa-paper-plane"></i></button>
                                <div><small>Penyerahan</small></div></a>';
                    } else if ($s->status == "req_penyerahan") {
                        $btn = '<div><small class="warning-text">Menunggu</small></div>';
                    } else if ($s->status == "penyerahan") {
                        $btn = '<div><small class="success-text">Selesai</small></div>';
                    }
                } else if (Auth::user()->divisi->nama == "PPIC") {
                    if ($s->status == "dibuat") {
                        $btn = '<div><small class="info-text">Sedang Dibuat</small></div>';
                    } else if ($s->status == "req_penyerahan") {
                        $btn = '<div><small class="warning-text">Menunggu</small></div>';
                    } else if ($s->status == "penyerahan") {
                        $btn = '<div><small class="success-text">Selesai</small></div>';
                    }
                } else if (Auth::user()->Divisi->nama == "Gudang Barang Jadi" || Auth::user()->Divisi->nama == "Gudang Karantina") {
                    if ($s->status == "req_penyerahan") {
                        $btn = '<a href="/bppb/penyerahan_barang_jadi/status/' . $s->id . '/penyerahan">
                                <button type="button" class="btn btn-success btn-sm m-1" style="border-radius:50%;"><i class="fas fa-check"></i></button>
                                <div><small>Terima</small></div></a>';
                    } else if ($s->status == "penyerahan") {
                        $btn = '<div><small class="success-text">Selesai</small></div>';
                    }
                }

                return $btn;
            })
            ->addColumn('aksi', function ($s) {
                $btn = '<a href = "/bppb/penyerahan_barang_jadi/' . $s->id . '"><button class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fas fa-eye"></i></button></a>';
                $btn .= '<a href = "/perakitan/laporan/edit/' . $s->id . '"><button class="btn btn-warning btn-sm m-1" style="border-radius:50%;"><i class="fas fa-edit"></i></button></a>';
                $btn .= '<a class="deletemodal" data-toggle="modal" data-target="#deletemodal" data-attr="/perakitan/laporan/delete/' . $s->id . '"><button class="btn btn-danger btn-sm m-1" style="border-radius:50%;"><i class="fas fa-trash"></i></button></a>';
                return $btn;
            })
            ->rawColumns(['no_seri', 'aksi', 'status'])
            ->make(true);
    }

    public function bppb_penyerahan_barang_jadi_status($id, $status)
    {
        $s = PenyerahanBarangJadi::find($id);
        $s->status = $status;
        $u = $s->save();

        if ($u) {
            return redirect()->back();
        }
    }
}
