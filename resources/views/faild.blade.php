@extends('base')
@section('page_title', 'Payment faild')
@section('container')
    <div class='container'>
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="message-box _success _failed">
                    <i class="fa fa-times-circle" aria-hidden="true"></i>
                    <h2> Your payment failed </h2>
                    <p> Try again later </p>
                    <a href="{{env('UI_URL')}}{{'/user/credit'}}" class="btn text-success" style="margin-top: 20px">Go to dashboard</a>
                </div>
            </div>
        </div>
    </div>
@endsection