@extends('layouts.default')

@section('title', 'Import')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Import Bitfinex log files</div>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
                <form method="post" action="{{route('upload')}}" enctype="multipart/form-data">
				{{csrf_field()}}
				<div class="form-group">
				  <h4>Files</h4>
				  <input type="file" id="files" name="files[]" multiple>
				  <br />
				  <br />
				  <input type="submit" class="btn btn-primary" value="Import files">
				</div>
				</form>
            </div>
        </div>
    </div>
</div>

@endsection