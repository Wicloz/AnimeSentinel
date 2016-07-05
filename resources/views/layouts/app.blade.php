<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <meta name="author" content="Wilco de Boer | Wicloz">
  <title>AnimeSentinel - @yield('title', 'define this')</title>

  <!-- Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css" integrity="sha384-XdYbMnZ/QjLh6iI4ogqCTaIjrFk87ip+ekIjefZch0Y+PvJ8CDYtEs1ipDmPorQ+" crossorigin="anonymous">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:100,300,400,700">

  <!-- Styles -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/app.min.css">

  <!-- Favicons -->

  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <link href="css/ie10-viewport-bug-workaround.css" rel="stylesheet">
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
  <nav class="navbar navbar-inverse navbar-fixed-top" id="navbar">
    <div class="container">
      <div class="navbar-header">
        <!-- Collapsed Hamburger -->
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
          <span class="sr-only">Toggle Navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <!-- Branding Image -->
        <a class="navbar-brand {{ "$_SERVER[REQUEST_URI]" === '/' ? "active" : "" }}" href="{{ url('/') }}">
          AnimeSentinel
        </a>
      </div>

      <div class="collapse navbar-collapse" id="navbar-collapse">
        <!-- Left Side Of Navbar -->
        <ul class="nav navbar-nav">
          <li {{ "$_SERVER[REQUEST_URI]" === '/services' ? "class=active" : "" }}><a href="{{ url('/services') }}">Services</a></li>
          <li {{ "$_SERVER[REQUEST_URI]" === '/social' ? "class=active" : "" }}><a href="{{ url('/social') }}">Online Presence</a></li>
          <li {{ "$_SERVER[REQUEST_URI]" === '/about' ? "class=active" : "" }}><a href="{{ url('/about') }}">My CV</a></li>
          <li {{ "$_SERVER[REQUEST_URI]" === '/contact' ? "class=active" : "" }}><a href="{{ url('/contact') }}">Contact Me</a></li>
        </ul>

        <!-- Right Side Of Navbar -->
        <ul class="nav navbar-nav navbar-right">
          @if (Auth::guest())
            <li {{ "$_SERVER[REQUEST_URI]" === '/login' ? "class=active" : "" }}><a href="{{ url('/login') }}">Login</a></li>
            <li {{ "$_SERVER[REQUEST_URI]" === '/register' ? "class=active" : "" }}><a href="{{ url('/register') }}">Register</a></li>
          @else
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                {{ Auth::user()->name }} <span class="caret"></span>
              </a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="{{ url('/logout') }}"><i class="fa fa-btn fa-sign-out"></i>Logout</a></li>
              </ul>
            </li>
          @endif
        </ul>
      </div>
    </div>
  </nav>
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
        <h2><a href="{{ url('/contact') }}">Contact</a></h2>
        <p>Name: Wilco de Boer</p>
        <p>Email: <a href="mailto:deboer.wilco@gmail.com">deboer.wilco@gmail.com</a></p>
        <p>Mobile Number: +31-637338259</p>
      </div>
      <div class="col-sm-2">
      </div>
    </div>
  </div>

  <!-- JavaScripts -->
  <script src="js/jquery.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/expandables.js"></script>
  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <script src="js/ie10-viewport-bug-workaround.js"></script>
  <!-- YieldFoot -->
  @yield('foot')
</body>
</html>
