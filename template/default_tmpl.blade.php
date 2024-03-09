server {
    listen @{{ .Env.LISTEN }} default_server;
@if ($version === 'proxy')
    server_name  ~^(?<subdomain>.+)\.@{{ .Env.DOMAIN }}$;
@else
    server_name @{{ .Env.DOMAIN }};
    root @{{ .Env.ROOT }};
    index @{{ .Env.INDEX }};
@endif
    charset utf-8;

    location = /favicon.ico { log_not_found off; access_log off; }
    location = /robots.txt  { log_not_found off; access_log off; }

    client_max_body_size @{{ .Env.CLIENT_MAX_BODY_SIZE }};

@if ($version === 'static')
    error_page 404 /@{{ .Env.INDEX }};
@endif

@if ($version === 'proxy')
    location / {
        resolver 127.0.0.11;

        proxy_pass http://$subdomain;
    }
@else
    location / {
        try_files $uri $uri/ /@{{ .Env.INDEX }}{{ $version === 'php' ? '?$query_string' : '' }};

        add_header X-Served-By kool.dev;
    }
@endif

@if ($version === 'php')
    location ~ \.php$ {
        fastcgi_buffers @{{ .Env.FASTCGI_BUFFERS }};
        fastcgi_buffer_size @{{ .Env.FASTCGI_BUFFER_SIZE }};
        fastcgi_pass @{{ .Env.PHP_FPM }};
        fastcgi_read_timeout @{{ .Env.FASTCGI_READ_TIMEOUT }};
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
@endif

    location ~ /\.ht {
        deny all;
    }

    # basic H5BP suggestions
    include h5bp/internet_explorer/x-ua-compatible.conf;
    include h5bp/security/referrer-policy.conf;
    include h5bp/security/x-content-type-options.conf;
    include h5bp/security/x-frame-options.conf;
    include h5bp/security/x-xss-protection.conf;

    # performance enhancements (mostly for caching static data)
    include h5bp/web_performance/cache-file-descriptors.conf;
    include h5bp/web_performance/pre-compressed_content_gzip.conf;
}
