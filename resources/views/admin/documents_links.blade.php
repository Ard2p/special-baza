<div class="form-group form-element-text "><label for="description" class="control-label">
        Ссылки на документы (скопировать необходимые и вставить в форму ниже) :
    </label>
@foreach(\App\Support\Document::where('billing_type', 'all')->get() as $document)

    <p>{{$document->body}} : {{url($document->url)}}</p>
@endforeach
</div>