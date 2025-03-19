@extends('base')
@section('page_title', 'Payment success')
@section('container')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="message-box _success">
                <i class="fa fa-check-circle" aria-hidden="true"></i>
                <h2> Your payment was successful </h2>
                <p>Thank you for your payment. We will be in contact with more details shortly.</p>
                <a href="{{env('UI_URL')}}{{'/user/credit'}}" class="btn text-success" style="margin-top: 20px">Go to dashboard</a>
            </div>
        </div>
    </div>
</div>
@endsection