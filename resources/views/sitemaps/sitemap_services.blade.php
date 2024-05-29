<?php echo '<?xml version="1.0" encoding="UTF-8"?>' ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    @foreach($services as $category)

        <url>
            <loc>{{route('directory_uslugi_request', [$category->type->alias,  $category->city->region->alias, $category->city->alias])}}</loc>
            <lastmod>{{ gmdate(DateTime::W3C, strtotime($category->updated_at)) }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>1.0</priority>
        </url>
    @endforeach

</urlset>