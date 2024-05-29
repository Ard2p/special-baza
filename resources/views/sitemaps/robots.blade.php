@if($work)
User-agent: *
Clean-param: region_id
Clean-param: page
Clean-param: _escaped_fragment_
Disallow: /login
Disallow: /register
Disallow: /password
Disallow: /order
Host: {{Request::getSchemeAndHttpHost()}}
Sitemap: https://trans-baza.ru/sitemap.xml
@else
User-agent: *
Disallow: /
@endif
