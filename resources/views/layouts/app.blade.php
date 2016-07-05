<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <meta name="description" content="">
  <meta name="keywords" content="">
  <meta name="author" content="Wilco de Boer | Wicloz">
  <title>AnimeSentinel - @yield('title', 'this should not appear')</title>

  <!-- Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css" integrity="sha384-XdYbMnZ/QjLh6iI4ogqCTaIjrFk87ip+ekIjefZch0Y+PvJ8CDYtEs1ipDmPorQ+" crossorigin="anonymous">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:100,300,400,700">

  <!-- Styles -->
  <link rel="stylesheet" href="{{ url('/') }}/css/bootstrap.min.css">
  <link rel="stylesheet" href="{{ elixir('css/app.css') }}">

  <!-- Favicons -->
  <!--TODO-->

  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <link href="{{ url('/') }}/css/ie10-viewport-bug-workaround.css" rel="stylesheet">
  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- JavaScripts -->

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
      <div class="col-md-2">
        @yield('content-left')
      </div>
      <div class="col-md-8">
        @yield('content-center')
      </div>
      <div class="col-md-2">
        @yield('content-right')
      </div>
    </div>
  </div>
  @yield('content-bottom')

  <!-- Footer -->
  <div class="container-fluid" id="footer">
    <div class="row">
      <div class="col-sm-2">
      </div>
      <div class="col-sm-8">
      </div>
      <div class="col-sm-2">
      </div>
    </div>
  </div>

  <!-- JavaScripts -->
  <script src="{{ url('/') }}/js/jquery.min.js"></script>
  <script src="{{ url('/') }}/js/bootstrap.min.js"></script>
  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <script src="{{ url('/') }}/js/ie10-viewport-bug-workaround.js"></script>
  <!-- YieldFoot -->
  @yield('foot')
</body>
</html>
