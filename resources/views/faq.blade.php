@extends('layouts.main')

@section('content')
    <div class="faq-wrap">
        <h1>{{$article->h1}}</h1>
            @foreach($faq_content as $content)
            <div class="item-faq">
                <div class="question-wrapper">
                    <h3>{{$content->question}}</h3>
                </div>
                <div class="answer-wrapper">
                    {!! $content->answer !!}
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('after-scripts')
<script>
    $(document).ready(function () {
        $('.question-wrapper').click(function () {
            $(this).toggleClass('active');
            $(this).siblings('.answer-wrapper').toggleClass('active')
        })
    })
</script>
@endpush
