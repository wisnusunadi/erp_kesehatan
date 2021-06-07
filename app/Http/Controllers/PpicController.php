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
use App\BillOfMaterial;
use App\DetailProduk;
use App\Produk;
use App\Part;
use App\Bppb;
use App\DetailPenyerahanBarangJadi;
use App\KelompokProduk;
use App\Event;
use App\PartEng;

use App\Events\RealTimeMessage;
use App\PenyerahanBarangJadi;
use Carbon\Carbon;
use App\HasilPengemasan;
use App\PermintaanBahanBaku;
use App\DetailPermintaanBahanBaku;
use App\Bom_Version;
use App\PengembalianBarangGudang;

class PPICController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function schedule_show(Request $request)
    {
        $date = Event::toBase()->orderBy('tanggal_mulai', 'asc')->get();
        if (isset($request->month) && isset($request->year)) {
            $month = $request->month;
            $year = $request->year;
        } else {
            $month = date('m');
            $year = date('Y');
        }
        $event = [];
        foreach ($date as $d) {
            $temp_date = strtotime($d->tanggal_mulai);

            if (date('m', $temp_date) == $month && date('Y', $temp_date) == $year)
                array_push($event, ['id' => $d->id, 'title' => $d->nama_produk, 'start' => $d->tanggal_mulai, 'end' => $d->tanggal_selesai, 'color' => $d->warna]);
        }
        $event = json_encode($event);

        if (isset($request->month) && isset($request->year)) {
            return $event;
        }

        $produk = DetailProduk::select('nama', 'id')->get();
        return view('page.ppic.jadwal_produksi', compact('event', 'produk', 'date'));
    }

    public function schedule_create(Request $request)
    {
        $data = [
            'nama_produk' => $request->title,
            'tanggal_mulai' => $request->start,
            'tanggal_selesai' => $request->end,
            'status' => $request->status,
            'jumlah_produksi' => $request->jumlah,
            'warna' => $request->color,
            'id_produk' => $request->id_produk,
        ];

        if ($request->bom != null) {
            $data = Event::where('id_produk', $request->id_produk)->update(['bom' => $request->bom]);
            // $data->bom = $request->bom;
            // $data->save();
            return $request->id_produk;
        }

        Event::create($data);
        $result = Event::latest()->first();
        return $result;
    }

    public function schedule_delete(Request $request)
    {
        if ($request->id != "") Event::destroy($request->id);
    }

    public function schedule_notif(Request $request)
    {
        event(new RealTimeMessage(Auth::user(), $request->message, $request->status));

        // $date = Event::toBase()->orderBy('start', 'asc')->get();
        // $today = date('m');
        // foreach ($date as $d) {
        //     $temp = strtotime($d->start);
        //     if ($today == date('m', $temp)) {
        //         $temp = Event::find($d->id);
        //         $temp->status = $request->status;
        //         $temp->save();
        //     }
        // }
    }

    public function bom()
    {
        $list = DetailProduk::all();
        return view('page.ppic.bom', compact('list'));
    }

    public function get_bom($id)
    {
        $bom = BillOfMaterial::where('produk_bill_of_material_id', $id)->get();
        $result = [];

        $min = INF;
        foreach ($bom as $d) {
            $part_eng = PartEng::where('kode_part', $d->part_eng_id)->first();
            if (!isset($part_eng['nama'])) continue;
            $part_gbmb = Part::where('kode', $part_eng['part_id'])->first();

            if (isset($part_gbmb['jumlah'])) {
                $count = (int)($part_gbmb['jumlah'] / $d->jumlah);
                if ($count < $min) $min = $count;
            }
            array_push($result, ['nama' => $part_eng['nama'], 'jumlah' => $d->jumlah, 'stok' => $part_gbmb['jumlah']]);
        }

        array_push($result, $min);
        return $result;
    }

    public function get_bom_version($id)
    {
        $bom_id = Bom_Version::where('detail_produk_id', $id)->get();
        $bom_id = json_encode($bom_id);

        return $bom_id;
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
                'model' => 'required',
            ],
            [
                'detail_produk_id.required' => "Silahkan Pilih Produk",
                'divisi_id.reqired' => "Silahkan Pilih Divisi",
                'jumlah.required' => "Jumlah Harus Diisi",
                'tanggal_bppb.required' => "Tanggal Harus Diisi",
                'model.required' => 'Model harus dipilih'
            ]
        );

        if ($v->fails()) {
            return redirect()->back()->withErrors($v);
        } else {
            $bool = true;
            $no_bppb = $request->no_bppb_urutan . '/' . $request->no_bppb_kode . '/' . $request->no_bppb_bulan . '/' . $request->no_bppb_tahun;
            $c = Bppb::create([
                'no_bppb' => $no_bppb,
                'detail_produk_id' => $request->detail_produk_id,
                'divisi_id' => $request->divisi_id,
                'tanggal_bppb' => $request->tanggal_bppb,
                'jumlah' => $request->jumlah
            ]);

            if ($c) {

                $u = PermintaanBahanBaku::create([
                    'bppb_id' => $c->id,
                    'divisi_id' => '11',
                    'tanggal' => Carbon::now()->toDateString(),
                    'jumlah' => $request->jumlah,
                    'status' => 'dibuat'
                ]);

                if ($u) {
                    for ($i = 0; $i < count($request->part_id); $i++) {
                        $k = DetailPermintaanBahanBaku::create([
                            'bill_of_material_id' => $request->part_id[$i],
                            'permintaan_bahan_baku_id' => $u->id,
                            'jumlah_diminta' => $request->part_jumlah_diminta[$i],
                            'jumlah_diterima' => 0
                        ]);
                        if (!$k) {
                            $bool = false;
                        }
                    }
                    if ($bool == true) {
                        return redirect()->back()->with('success', "Berhasil menambahkan BPPB");
                    } else if ($bool == false) {
                        return redirect()->back()->with('error', "Gagal menambahkan BPPB");
                    }
                }
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
                        $btn = '<a href="/bppb/permintaan_bahan_baku/status/' . $s->id . '/req_permintaan">
                                <button type="button" class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fas fa-paper-plane"></i></button>
                                <div><small>Permintaan</small></div></a>';
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
                $btn = '<a class="detailmodal" data-toggle="modal" data-target="#detailmodal" data-attr="/bppb/permintaan_bahan_baku/detail/show/' . $s->id . '" data-id="' . $s->id . '"><button class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fas fa-eye"></i></button></a>';
                $btn .= '<a href = "/bppb/permintaan_bahan_baku/edit/' . $s->id . '"><button class="btn btn-warning btn-sm m-1" style="border-radius:50%;"><i class="fas fa-edit"></i></button></a>';
                $btn .= '<a class="deletemodal" data-toggle="modal" data-target="#deletemodal" data-attr="/perakitan/laporan/delete/' . $s->id . '"><button class="btn btn-danger btn-sm m-1" style="border-radius:50%;"><i class="fas fa-trash"></i></button></a>';
                return $btn;
            })
            ->rawColumns(['no_seri', 'aksi', 'status'])
            ->make(true);
    }

    public function bppb_permintaan_bahan_baku_status($id, $status)
    {
        $s = PermintaanBahanBaku::find($id);
        $s->status = $status;
        $s->save();

        if ($status == 'acc_permintaan') {
            return redirect()->back();
        } else {
            return redirect()->back();
        }
    }

    public function bppb_permintaan_bahan_baku_detail()
    {
        return view('page.ppic.bppb_permintaan_bahan_baku_detail_show');
    }

    public function bppb_permintaan_bahan_baku_detail_show($id)
    {
        $s = DetailPermintaanBahanBaku::where('permintaan_bahan_baku_id', $id)->get();
        return DataTables::of($s)
            ->addIndexColumn()
            ->addColumn('part_eng', function ($s) {
                return $s->BillOfMaterial->PartEng->nama;
            })
            ->rawColumns(['part_eng'])
            ->make(true);
    }

    public function bppb_permintaan_bahan_baku_edit($id)
    {
        $s = PermintaanBahanBaku::find($id);
        return view('page.ppic.bppb_permintaan_bahan_baku_edit', ['id' => $id, 's' => $s]);
    }

    public function bppb_permintaan_bahan_baku_update($id, Request $request)
    {
        if (!empty($request->detail_permintaan_bahan_baku_id)) {
            for ($i = 0; $i < count($request->detail_permintaan_bahan_baku_id); $i++) {
                $s = DetailPermintaanBahanBaku::find($request->detail_permintaan_bahan_baku_id[$i]);
                $s->jumlah_diterima = $request->jumlah_diterima[$i];
                $u = $s->save();

                if ($u) {
                    return redirect()->back()->with('success', "Berhasil menambahkan Data");
                } else {
                    return redirect()->back()->with('error', "Gagal menambahkan Data");
                }
            }
        }
    }

    public function bppb_pengembalian_barang_gudang($id)
    {
        $s = Bppb::find($id);
        return view('page.ppic.bppb_pengembalian_barang_gudang_show', ['id' => $id, 's' => $s]);
    }

    public function bppb_pengembalian_barang_gudang_show($id)
    {
        $s = PengembalianBarangGudang::where('bppb_id', $id)->get();
        return DataTables::of($s)
            ->addIndexColumn()
            ->addColumn('', function ($s) {
                return $s->BillOfMaterial->PartEng->nama;
            })
            ->rawColumns([''])
            ->make(true);
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

                $btn = '<a href = "/bppb/penyerahan_barang_jadi/detail/' . $s->id . '"><button class="btn btn-info btn-sm m-1" style="border-radius:50%;"><i class="fas fa-eye"></i></button></a>';
                $btn .= '<a href = "/bppb/penyerahan_barang_jadi/edit/' . $s->id . '"><button class="btn btn-warning btn-sm m-1" style="border-radius:50%;"><i class="fas fa-edit"></i></button></a>';
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
