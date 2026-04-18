<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link rel="icon" href="{{assets('backend/images/favicon-32x32.png')}}" type="image/png" />
    <link href="{{assets('backend/plugins/simplebar/css/simplebar.css')}}" rel="stylesheet" />
    <link href="{{assets('backend/plugins/perfect-scrollbar/css/perfect-scrollbar.css')}}" rel="stylesheet" />
    <link href="{{assets('backend/plugins/metismenu/css/metisMenu.min.css')}}" rel="stylesheet" />
    <link href="{{assets('backend/plugins/datatable/css/dataTables.bootstrap5.min.css')}}" rel="stylesheet" />
    <link href="{{assets('backend/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{assets('backend/css/bootstrap-extended.css')}}" rel="stylesheet">
    <link href="{{assets('backend/fonts.googleapis.com/css276c7.css')}}?family=Roboto:wght@400;500&amp;display=swap" rel="stylesheet">
    <link href="{{assets('backend/css/app.css')}}" rel="stylesheet">
    <link href="{{assets('backend/css/custom.css')}}" rel="stylesheet">
    <link href="{{assets('backend/css/icons.css')}}" rel="stylesheet">


    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="manifest" href="{{assets('manifest.json')}}">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script>window.baseUrl = '{{url('/')}}'</script>
    <script>window.uploadPath = '{{env('UPLOAD_PATH')}}'</script>
    <script>window.publicPath = '{{env('PUBLIC_PATH')}}'</script>
    <script>window.locale = '{{auth()->user()->locale ? auth()->user()->locale : 'en'}}'</script>
    <script>
        function dd(...args) {
            args.forEach(arg => {
                console.log(arg);
            });
        }
    </script>

    <title>AGRO IOT</title>
</head>

<body class="{{auth()->user()->theme}}">
    <div id="app">

    </div>
    <script src="{{assets('backend/js/bootstrap.bundle.min.js')}}"></script>
{{--<script src="{{assets('backend/js/jquery.min.js')}}"></script>--}}
<script src="{{assets('backend/plugins/simplebar/js/simplebar.min.js')}}"></script>
<script src="{{assets('backend/plugins/metismenu/js/metisMenu.min.js')}}"></script>
<script src="{{assets('backend/plugins/perfect-scrollbar/js/perfect-scrollbar.js')}}"></script>
<script src="{{assets('backend/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
<script src="{{assets('backend/plugins/datatable/js/dataTables.bootstrap5.min.js')}}"></script>
<script src="{{assets('backend/js/app.js')}}"></script>


    @env('local')
        @vite(['resources/js/app.js'])
        @else
            @php
                $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
                $appJs = $manifest['resources/js/app.js'];
            @endphp

            <script type="module" src="{{ assets('build/' . $appJs['file']) }}"></script>

            @if(isset($appJs['css']))
                @foreach($appJs['css'] as $css)
                    <link rel="stylesheet" href="{{ assets('build/' . $css) }}">
                @endforeach
            @endif

            @if(isset($manifest['resources/css/app.css']))
                <link rel="stylesheet" href="{{ assets('build/' . $manifest['resources/css/app.css']['file']) }}">
            @endif
            @endenv
</body>
</html>
