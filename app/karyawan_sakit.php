<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class karyawan_sakit extends Model
{
    //
    protected $fillable = ['tgl_cek', 'karyawan_id', 'pemeriksa_id', 'analisa', 'diagnosa', 'tindakan', 'terapi', 'obat_id', 'jumlah', 'aturan', 'konsumsi', 'keputusan'];

    public function karyawan()
    {
        return $this->belongsTo('App\karyawan', 'karyawan_id');
    }

    public function pemeriksa()
    {
        return $this->belongsTo('App\karyawan', 'pemeriksa_id');
    }
    public function obat()
    {
        return $this->belongsTo('App\obat', 'obat_id');
    }
}
