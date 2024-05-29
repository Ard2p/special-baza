<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($articles as $article)
        @php
            if($article->type === 'news'){
              $route = route('get_news_article_kinosk', ['country' => $domain->alias, 'locale' => $domain->options['default_locale'], 'alias' => $article->alias]);
            }elseif($article->type === 'article') {
            $route = route('get_article_kinosk', ['country' => $domain->alias, 'locale' => $domain->options['default_locale'], 'alias' => $article->alias]);
            }else {
                 $route = route('article_index_kinosk', ['country' => $domain->alias, 'locale' => $domain->options['default_locale'], 'alias' => $article->alias]);
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
    @foreach($categories as $category)
        <url>
            <loc>{{ route('australia_directory', ['category_alias' => $category->alias, 'country' => $domain->alias, 'locale' => $domain->options['default_locale']]) }}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime( $category->machines->first()->updated_at)) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>
    @endforeach
    @foreach($cities as $city)
        @foreach($city->machines->groupBy('type') as $type => $machines)
            @foreach($machines as $machine)
                <url>
                    <loc>{{route('australia_directory',  ['category_alias' => $machine->_type->alias, 'country' => $domain->alias, 'locale' => $domain->options['default_locale'], 'region' =>  $machine->region->alias, 'city' => $machine->city->alias])}}</loc>
                    <lastmod>{{ gmdate(DateTime::W3C, strtotime($machine->updated_at)) }}</lastmod>
                    <changefreq>daily</changefreq>
                    <priority>1.0</priority>
                </url>
                @break
            @endforeach
        @endforeach
    @endforeach

</urlset>