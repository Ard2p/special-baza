<meta itemprop="name" content="Аренда {{$machine->_type->name}} {{$machine->brand->name ?? ''}} {{$machine->city->name ?? ''}}, {{$machine->region->name ?? ''}}"/>
<link itemprop="url"
      href="{{$machine->rent_url}}"/>
<link itemprop="image" href="{{url($machine->photo)}}"/>
<meta itemprop="brand" content="{{$machine->brand->name ?? ''}}"/>
<meta itemprop="manufacturer" content="{{$machine->brand->name ?? ''}}"/>
<meta itemprop="productID" content="{{$machine->id}}"/>
<meta itemprop="category" content="{{$machine->_type->name}}"/>
<meta itemprop="description" content="{{$machine->_type->name}} {{$machine->brand->name ?? ''}} {{$machine->city->name ?? ''}}, {{$machine->region->name ?? ''}} характеристики, фото, предложения по аренде"/>
<div itemprop="offers" itemscope itemtype="https://schema.org/Offer">
    <meta itemprop="price" content="{{$machine->sum_day / 100}}"/>
    <meta itemprop="priceCurrency" content="RUB"/>
    <link itemprop="availability" href="https://schema.org/InStock" />
</div>Ьф