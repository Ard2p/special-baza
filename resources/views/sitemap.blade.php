<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    @foreach($articles as $article)
        @php
            if($article->is_news){
              $route = route('get_news_article', $article->alias);
            }elseif($article->is_article) {
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
            <loc>{{ route('contractor_public_page', $contractor) }}</loc>
            <lastmod></lastmod>
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

    <url>
        <loc>{{ route('directory_main') }}</loc>
        <lastmod></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    @foreach($categories as $category)
        <url>
            <loc>{{ route('directory_main_category', $category->alias) }}</loc>
            <lastmod></lastmod>
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

    <url>
        <loc>{{ route('directory') }}</loc>
        <lastmod></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    @foreach($seo as $item)

        <url>
            <loc>{{route('directory_request', [$item->type->alias,  $item->city->region->alias, $item->city->alias])}}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime($item->updated_at)) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>

    @endforeach
 {{--   @foreach($service_city as $city)
        @foreach($service_category as $category)
            <url>
                <loc>{{route('directory_uslugi_request', [$category->alias,  $city->region->alias, $city->alias])}}</loc>
                <lastmod>{{ gmdate(DateTime::W3C, strtotime($category->updated_at)) }}</lastmod>
                <changefreq>daily</changefreq>
                <priority>1.0</priority>
            </url>
        @endforeach
    @endforeach--}}
</urlset>