<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($articles as $article)
        @php
            if($article->type === 'news'){
            $route = route('get_news_article', $article->alias);
          }elseif($article->type === 'article') {
          $route = route('get_article', $article->alias);
          }else {
               $route = route('article_index', $article->alias);
          }
        @endphp
        <url>
            <loc>{{ $route }}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime($article->updated_at)) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>
    @endforeach
{{--    @foreach($contractors as $contractor)
        <url>
            <loc>{{ route('contractor_public_page', $contractor->contractor_alias) }}</loc>
            <lastmod>{{gmdate(DateTime::W3C, strtotime($contractor->updated_at))}}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>
    @endforeach--}}

    @foreach($machines as $machine)
        <url>
            <loc>{{$machine->rent_url}}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime($machine->updated_at)) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>
    @endforeach

    {{--<url>
        <loc>{{ route('directory_main') }}</loc>
        <lastmod></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>--}}

        <url>
            <loc>{{ url('for-contractors') }}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime('20-04-2020 01:00')) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>
        <url>
            <loc>{{ url('policy') }}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime( '20-04-2020 01:00')) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>
        <url>
            <loc>{{ url('about') }}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime( '20-04-2020 01:00')) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>

        <url>
            <loc>{{ route('directory_main') }}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime( \App\Machinery::query()->first()->updated_at)) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>

    @foreach($categories as $category)
        <url>
            <loc>{{ route('directory_main_category', $category->alias) }}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime( $category->machines->first()->updated_at)) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>
    @endforeach
    @foreach($cities as $city)
        @foreach($city->machines->groupBy('type') as $type => $machines)
            @foreach($machines as $machine)
                <url>
                    <loc>{{route('directory_main_result',  [$machine->_type->alias, $machine->region->alias, $machine->city->alias])}}</loc>
                    <lastmod>{{ gmdate(DateTime::W3C, strtotime($machine->updated_at)) }}</lastmod>
                    <changefreq>daily</changefreq>
                    <priority>1.0</priority>
                </url>
                @break
            @endforeach
        @endforeach
    @endforeach

</urlset>