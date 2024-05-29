@extends('layouts.main')

@section('content')
    <div class="row">
        <div class="col-md-offset-4 col-md-4">
            @if(Session::has('email_confirm'))
                <div class="alert alert-success">
                    {{Session::get('email_confirm')}}
                </div>
            @endif
                @if(isset($errors) && $errors->all())
                    <div class="alert alert-danger">
                      {{$errors->first('no_email')}}
                    </div>
                @endif
            <div class="auth">
                @include('includes.auth_fields')
            </div>
        </div>
    </div>
@endsection
