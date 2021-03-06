<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BillOfMaterial extends Model
{
    protected $fillable = ['produk_bill_of_material_id', 'part_eng_id', 'jumlah', 'satuan', 'status'];

    public function DetailProduk()
    {
        return $this->belongsTo(DetailProduk::class);
    }

    public function DetailPermintaanBahanBaku()
    {
        return $this->hasMany(PermintaanBahanBaku::class);
    }

    public function PartEng()
    {
        return $this->belongsTo(PartEng::class, 'part_eng_id');
    }

    public function ProdukBillOfMaterial()
    {
        return $this->belongsTo(ProdukBillOfMaterial::class);
    }

    public function PerbaikanProduksi()
    {
        return $this->belongsToMany(PerbaikanProduksi::class, 'perbaikan_produksi_parts', 'bill_of_material_id', 'perbaikan_produksi_id')->withTimestamps();
    }
}
