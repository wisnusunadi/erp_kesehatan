@extends('adminlte.page')

@section('title', 'Beta Version')

@section('content_header')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Perakitan</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/">Home</a></li>
          <li class="breadcrumb-item"><a href="/perakitan">Perakitan</a></li>
          <li class="breadcrumb-item"><a href="/perakitan/hasil/{{$id}}">Laporan Perakitan</a></li>
          <li class="breadcrumb-item active">Ubah Hasil Perakitan</li>
        </ol>
      </div>
    </div>
  </div>
</section>
@stop

@section('content')
<section class="content">
  <div class="row">
    <div class="col-3">
      <div class="card">
        <div class="card-header bg-info">
          <div class="card-title"><i class="fas fa-info-circle"></i>&nbsp;Info BPPB</div>
        </div>
        <div class="card-body">

          <div class="card-body box-profile">
            <div class="text-center">
              <img class="product-img-small img-fluid" @if(empty($sh->Bppb->DetailProduk->foto))
              src="{{url('assets/image/produk')}}/noimage.png"
              @elseif(!empty($sh->Bppb->DetailProduk->foto))
              src="{{url('assets/image/produk')}}/{{$sh->Bppb->DetailProduk->foto}}"
              @endif
              title="{{$sh->Bppb->DetailProduk->nama}}"
              >
            </div>
            <div style="text-align:center;vertical-align:center;padding-top:10px">
              <h5 class="card-heading">{{$sh->Bppb->DetailProduk->nama}}</h5>
              <h6 class="card-subheading text-muted">{{$sh->Bppb->DetailProduk->Produk->nama}}</h6>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-6" style="vertical-align: middle;">
              <hgroup>
                <!-- hgroup is deprecated, just defiantly using it anyway -->
                <h6 class="card-subheading text-muted">No BPPB</h6>
                <h5 class="card-heading">{{$sh->Bppb->no_bppb}}</h5>
              </hgroup>

            </div>
            <div class="col-lg-6" style="vertical-align: middle;">
              <hgroup>
                <!-- hgroup is deprecated, just defiantly using it anyway -->
                <h6 class="card-subheading text-muted ">Jumlah</h6>
                <h5 class="card-heading">{{$sh->Bppb->jumlah}}</h5>
              </hgroup>
            </div>
          </div>


        </div>
      </div>
    </div>
    <div class="col-9">
      @if(session()->has('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-check"></i></strong> {{session()->get('success')}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      @elseif(session()->has('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-times"></i></strong> {{session()->get('error')}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      @elseif(count($errors) > 0)
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-times"></i></strong> Lengkapi data terlebih dahulu
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      @endif
      <div class="card">
        <div class="card-header bg-warning">
          <div class="card-title"><i class="fas fa-edit"></i>&nbsp;Ubah Laporan Perakitan</div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <form action="{{route('perakitan.laporan.update',['id' => $sh->id])}}" method="post">
            {{ csrf_field() }}
            {{ method_field('PUT') }}
            <div class="form-group row">
              <label for="tanggal_laporan" class="col-sm-4 col-form-label" style="text-align:right;">Tanggal Laporan</label>
              <div class="col-sm-8">
                <input type="date" class="form-control" name="tanggal_laporan" id="tanggal_laporan" value="{{$sh->tanggal}}" style="width: 25%;">
                @if ($errors->has('tanggal_laporan'))
                <span class="invalid-feedback" role="alert">{{$errors->first('tanggal_laporan')}}</span>
                @endif
              </div>
            </div>

            <div class="form-group row">
              <label for="karyawan_id" class="col-sm-4 col-form-label" style="text-align:right;">Karyawan</label>
              <div class="col-sm-5">
                <div class="select2-info">
                  <select class="select2 form-control @error('karyawan_id') is-invalid @enderror karyawan_id" multiple="multiple" data-placeholder="Pilih Operator" data-dropdown-css-class="select2-info" style="width: 100%;" name="karyawan_id[]" id="karyawan_id">
                    @foreach($kry as $i)
                    <option value="{{$i->id}}" @if($sh->Karyawan->contains('id', $i->id))
                      selected
                      @endif
                      >{{$i->nama}}</option>
                    @endforeach
                  </select>
                  @if ($errors->has('karyawan_id'))
                  <span class="invalid-feedback" role="alert">{{$errors->first('karyawan_id.*')}}</span>
                  @endif
                </div>
              </div>
            </div>

            <div class="form-group row">
              <table id="tableitem" class="table table-hover styled-table">
                <thead style="text-align: center;">
                  <tr>
                    <th>No</th>
                    <th hidden></th>
                    <th>Tanggal</th>
                    <th>No Seri</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody style="text-align:center;">
                  @php ($first = true) @endphp
                  @foreach($sh->HasilPerakitan as $i)
                  <tr>
                    <td>{{$loop->iteration}}</td>
                    <td hidden><input type="text" id="id" name="id[]" value="{{$i->id}}"></td>
                    <td>
                      <div class="input-group">
                        <input type="date" class="form-control" name="tanggals[]" id="tanggals" value="{{$i->tanggal}}">
                      </div>
                    </td>
                    <td>
                      <div class="form-group">
                        <div class="input-group">
                          <input type="text" class="form-control @error('hasil_perakitans.*.no_seri') is-invalid @enderror" name="no_seri[{{$loop->iteration - 1}}]" id="no_seri" value="{{$i->no_seri}}">
                        </div>
                        @if ($errors->has('hasil_perakitans.*.no_seri'))
                        <span class="invalid-feedback" role="alert">{{$errors->first('hasil_perakitans.*.no_seri')}}</span>
                        @endif
                        <span id="no_seri-message[]" role="alert"></span>
                      </div>
                    </td>
                    <td>@if($first == false)
                      <button type="button" class="btn btn-danger btn-sm m-1" style="border-radius:50%;" id="closetable"><i class="fas fa-times-circle"></i></button>
                      @elseif($first == true)
                      @php ($first = false) @endphp
                      <button type="button" class="btn btn-success btn-sm m-1" style="border-radius:50%;" id="tambahitem"><i class="fas fa-plus-circle"></i></button>
                      @endif
                    </td>
                  </tr>

                  @endforeach
                </tbody>

              </table>
            </div>
        </div>
        <div class="card-footer">
          <span>
            <a class="cancelmodal" data-toggle="modal" data-target="#cancelmodal"><button type="button" class="btn btn-block btn-danger btn-rounded" style="width:200px;float:left;"><i class="fas fa-times"></i>&nbsp;Batal</button></a>
          </span>
          <span>
            <button type="submit" class="btn btn-block btn-warning btn-rounded" style="width:200px;float:right;"><i class="fas fa-edit"></i>&nbsp;Simpan Perubahan</button>
          </span>
        </div>
        </form>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->

      <!-- /.card -->
    </div>


    <!-- /.col -->
  </div>
  <!-- /.row -->
  <div class="modal fade" id="cancelmodal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background-color:	#778899;">
          <h4 class="modal-title" id="myModalLabel" style="color:white;">Keluar Halaman</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body" id="cancel">
          <div class="row">
            <div class="col-lg-12">
              <div class="card">
                <div class="card-body" style="text-align:center;">
                  <h6>Apakah anda yakin meninggalkan halaman ini?</h6>
                </div>
                <div class="card-footer col-12" style="margin-bottom: 2%;">
                  <span>
                    <button type="button" class="btn btn-block btn-secondary" data-dismiss="modal" id="batalhapussk" style="width:30%;float:left;">Batal</button>
                  </span>
                  <span>
                    <a href="/perakitan/hasil/{{$id}}" id="cancelform"><button type="submit" class="btn btn-block btn-danger" id="hapussk" style="width:30%;float:right;">Keluar</button></a>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
@section('adminlte_js')
<script>
  $(function() {
    var bppb = "{{$sh->Bppb->id}}";
    $('.karyawan_id').select2();

    function numberRows($t) {
      var c = 0 - 1;
      $t.find("tr").each(function(ind, el) {
        $(el).find("td:eq(0)").html(++c);
        var j = c - 1;
        $(el).find('input[id="tanggals"]').attr('name', 'tanggals[' + j + ']');
        $(el).find('input[id="no_seri"]').attr('name', 'no_seri[' + j + ']');
      });
    }

    $('#tambahitem').click(function(e) {
      $('#tableitem tr:last').after(`<tr>
          <td></td>
          <td hidden><input type="text" id="id" name="id[]"></td>
          <td>
            <div class="input-group">
              <input type="date" class="form-control" name="tanggals[]" id="tanggals" value="">
            </div>
          </td>
          <td>
            <div class="form-group">
              <div class="input-group">
                <input type="text" class="form-control @error('hasil_perakitans.*.no_seri') is-invalid @enderror" name="no_seri[]" id="no_seri" value="">
              </div>
              @if ($errors->has('hasil_perakitans.*.no_seri'))
              <span class="invalid-feedback" role="alert">{{$errors->first('hasil_perakitans.*.no_seri')}}</span>
              @endif
              <span id="no_seri-message[]" role="alert"></span>
            </div>
          </td>
          <td>
            <button type="button" class="btn btn-danger btn-sm m-1" style="border-radius:50%;" id="closetable"><i class="fas fa-times-circle"></i></button>
          </td>
        </tr>`);
      numberRows($("#tableitem"));
    });

    $('#tableitem').on('click', '#closetable', function(e) {
      $(this).closest('tr').remove();
      numberRows($("#tableitem"));
    });

    $('#tableitem').on("change keyup", 'input[id="no_seri"]', function() {
      var id = $(this).closest('tr').find('input[id="id"]').val();
      var no_seri_val = $(this).closest('tr').find('input[id="no_seri"]').val();
      var no_seri = $(this).closest('tr').find('input[id="no_seri"]');
      var message = $(this).closest('tr').find('span[id="no_seri-message[]"]');
      if (no_seri_val && id) {
        $.ajax({
          url: '/perakitan/laporan/edit/get_kode_perakitan_exist_not_in_id/' + bppb + '/' + id + '/' + no_seri_val,
          type: "GET",
          dataType: "json",
          success: function(data) {
            if (data > 0) {
              message.addClass("invalid-feedback");
              no_seri.addClass("is-invalid");
              message.html("No Seri Sudah Terpakai");
              console.log(message.val());
            } else {
              message.removeClass("invalid-feedback");
              no_seri.removeClass("is-invalid");
              message.empty();
            }
          }
        });
      } else if (no_seri_val && !(id)) {
        $.ajax({
          url: '/perakitan/laporan/edit/get_kode_perakitan_exist_not_in/' + bppb + '/' + no_seri_val,
          type: "GET",
          dataType: "json",
          success: function(data) {
            if (data > 0) {
              message.addClass("invalid-feedback");
              no_seri.addClass("is-invalid");
              message.html("No Seri Sudah Terpakai");
              console.log(message.val());
            } else {
              message.removeClass("invalid-feedback");
              no_seri.removeClass("is-invalid");
              message.empty();
            }
          }
        });
      } else {
        message.removeClass("invalid-feedback");
        no_seri.removeClass("is-invalid");
        message.empty();
      }
    });

  });
</script>
@stop