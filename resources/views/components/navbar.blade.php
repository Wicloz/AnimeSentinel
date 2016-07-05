<nav class="navbar navbar-custom navbar-fixed-top" id="navbar">
  <div class="container-fluid">
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
        <li {{ "$_SERVER[REQUEST_URI]" === '/anime/recent' ? "class=active" : "" }}><a href="{{ url('/anime/recent') }}">Recently Uploaded</a></li>
        <li {{ "$_SERVER[REQUEST_URI]" === '/anime/search' ? "class=active" : "" }}><a href="{{ url('/anime/search') }}">Search Anime</a></li>
        <li {{ "$_SERVER[REQUEST_URI]" === '/streamers/list' ? "class=active" : "" }}><a href="{{ url('/streamers/list') }}">Browse Streaming Sites</a></li>
        <li {{ "$_SERVER[REQUEST_URI]" === '/about' ? "class=active" : "" }}><a href="{{ url('/about') }}">About</a></li>
        <li {{ "$_SERVER[REQUEST_URI]" === '/news' ? "class=active" : "" }}><a href="{{ url('/news') }}">News</a></li>
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
