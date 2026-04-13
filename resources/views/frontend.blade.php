<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Better Go ERP</title>
    <meta name="robots" content="noindex, follow"/>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="{{assets('frontend/images/favicon.png')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/vendor/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/vendor/font-awesome.min.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/vendor/ionicons.min.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/vendor/pe-icon-7-stroke.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/vendor/tm-lawyers-icon.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/plugins/animate.min.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/plugins/ion.rangeSlider.min.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/plugins/magnific-popup.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/plugins/slick.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/helper.css')}}">
    <link rel="stylesheet" href="{{assets('frontend/css/style.css')}}">

    <script>window.baseUrl = '{{url('/')}}'</script>
    <script>window.uploadPath = '{{env('UPLOAD_PATH')}}'</script>
    <script>window.publicPath = '{{env('PUBLIC_PATH')}}'</script>
    <script>window.locale = '{{auth()->check() ? auth()->user()->locale : 'en'}}'</script>
    <script>
        function dd(...args) {
            args.forEach(arg => {
                console.log(arg);
            });
        }
    </script>
</head>

<body class="{{auth()->check() ? auth()->user()->theme : ''}}">
<div id="app"></div>

<script src="{{assets('frontend/js/vendor/modernizr-3.6.0.min.js')}}"></script>
<script src="{{assets('frontend/js/vendor/jquery-3.3.1.min.js')}}"></script>
<script src="{{assets('frontend/js/vendor/bootstrap.bundle.min.js')}}"></script>
<script src="https://maps.google.com/maps/api/js?sensor=false&amp;language=en"></script>
<script src="{{assets('frontend/js/plugins/wow.min.js')}}"></script>
<script src="{{assets('frontend/js/plugins/slick.min.js')}}"></script>
<script src="{{assets('frontend/js/plugins/jquery.waypoints.min.js')}}"></script>
<script src="{{assets('frontend/js/plugins/jquery.counterup.min.js')}}"></script>
<script src="{{assets('frontend/js/plugins/jquery.magnific-popup.min.js')}}"></script>
<script src="{{assets('frontend/js/plugins/ion.rangeSlider.min.js')}}"></script>
<script src="{{assets('frontend/js/plugins/jquery.zoom.min.js')}}"></script>
<script src="{{assets('frontend/js/plugins/jquery.ajaxchimp.min.js')}}"></script>
<!-- Main JS -->
<script src="{{assets('frontend/js/main.js')}}"></script>

{{--@vite('resources/js/web.js')--}}

@env('local')
    @vite(['resources/js/web.js'])
@else
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        @endphp
        <script type="module" src="{{assets("build/".$manifest['resources/js/web.js']['file'])}}"></script>
        @if(isset($manifest['resources/js/web.js']['css']))
            @foreach($manifest['resources/js/web.js']['css'] as $css)
                <link rel="stylesheet" href="{{assets("build/$css")}}">
            @endforeach
        @endif
@endenv

</body>

</html>
