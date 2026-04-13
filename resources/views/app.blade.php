<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

    <script>window.baseUrl = '{{url('/')}}'</script>

    @vite('resources/js/app.js')
    <title>Dashtrans - Bootstrap5 Admin Template</title>
</head>

<body class="{{auth()->user()->theme}}">
<div id="app">

</div>
<script src="{{assets('backend/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{assets('backend/js/jquery.min.js')}}"></script>
<script src="{{assets('backend/plugins/simplebar/js/simplebar.min.js')}}"></script>
<script src="{{assets('backend/plugins/metismenu/js/metisMenu.min.js')}}"></script>
<script src="{{assets('backend/plugins/perfect-scrollbar/js/perfect-scrollbar.js')}}"></script>
<script src="{{assets('backend/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
<script src="{{assets('backend/plugins/datatable/js/dataTables.bootstrap5.min.js')}}"></script>
<script src="{{assets('backend/js/app.js')}}"></script>
</body>
</html>
