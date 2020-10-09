server {
    listen @{{ .Env.LISTEN }} default_server;
    server_name _;
    root @{{ .Env.ROOT }};
    index @{{ .Env.INDEX }};
    charset utf-8;

    location = /favicon.ico { log_not_found off; access_log off; }
    location = /robots.txt  { log_not_found off; access_log off; }

    client_max_body_size @{{ .Env.CLIENT_MAX_BODY_SIZE }};

@if ($version === 'static')
    error_page 404 /@{{ .Env.INDEX }};
@endif

    location / {
        try_files $uri $uri/ /@{{ .Env.INDEX }}{{ $version === 'php' ? 'index.php?$query_string' : '' }};

        add_header X-Served-By kool.dev;
    }

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
}
