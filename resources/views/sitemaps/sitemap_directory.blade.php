<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    @foreach($seo as $item)

        <url>
            <loc>{{route('directory_request', [$item->type->alias,  $item->city->region->alias, $item->city->alias])}}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime($item->updated_at)) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>

    @endforeach

</urlset>