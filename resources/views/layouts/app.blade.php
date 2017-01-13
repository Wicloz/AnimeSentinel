<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <meta name="description" content="AnimeSentinel is a new, open source, anime streaming site with MAL integration and dynamically obtained content.">
  <meta name="author" content="Wilco de Boer (Wicloz)">
  <title>AnimeSentinel - @yield('title', 'DEFINE')</title>

  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css" integrity="sha384-XdYbMnZ/QjLh6iI4ogqCTaIjrFk87ip+ekIjefZch0Y+PvJ8CDYtEs1ipDmPorQ+" crossorigin="anonymous">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:100,300,400,700">

  <!-- Styles -->
  <link rel="stylesheet" href="{{ fullUrl('/') }}/css/bootstrap.min.css">
  <link rel="stylesheet" href="{{ elixir('css/app.css') }}">

  <!-- Favicons -->
  <!--TODO-->

  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <link rel="stylesheet" href="{{ fullUrl('/') }}/css/ie10-viewport-bug-workaround.css">

  <!-- Scripts -->
  <script>
    window.Laravel = {!! json_encode(['csrfToken' => csrf_token()]) !!};
  </script>
  <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
    ga('create', 'UA-87274962-1', 'auto');
    ga('send', 'pageview');
  </script>

  <!-- YieldHead -->
  @yield('head')
</head>

<body>
  <!-- Navigation Bar -->
  @include('components.navbar')
  <div class="navbar"></div>

  <!-- Main Content -->
  @yield('content-top')
  <div class="container-fluid" id="content">
    <div class="row">
      <div id="content-left" class="col-md-2">
        @yield('content-left')
      </div>
      <div id="content-center" class="col-md-8">
        @yield('content-center')
      </div>
      <div id="content-right" class="col-md-2">
        @yield('content-right')
        @include('components.alerts')
      </div>
    </div>
  </div>
  @yield('content-bottom')

  <!-- Footer -->
  <div class="container-fluid" id="footer">
    <div class="row">
      <div class="col-md-2">
      </div>
      <div class="col-md-8">
      </div>
      <div class="col-md-2">
      </div>
    </div>
  </div>

  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <script src="{{ fullUrl('/') }}/js/ie10-viewport-bug-workaround.js"></script>
  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
  <!-- Scripts -->
  <script src="{{ fullUrl('/') }}/js/jquery.min.js"></script>
  <script src="{{ fullUrl('/') }}/js/bootstrap.min.js"></script>
  <script>
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
  </script>
  <!-- YieldFoot -->
  @yield('foot')
</body>
</html>
