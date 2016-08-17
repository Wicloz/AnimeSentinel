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
      <a class="navbar-brand {{ request()->is('/') ? 'active' : '' }}" href="{{ fullUrl('/') }}">
        AnimeSentinel
      </a>
    </div>
    <div class="collapse navbar-collapse" id="navbar-collapse">

      <!-- Left Side Of Navbar -->
      <ul class="nav navbar-nav">
        <li {{ request()->is('anime/recent/*') ? 'class=active' : '' }}><a href="{{ fullUrl('/anime/recent') }}">Recently Uploaded</a></li>
        <li {{ request()->is('anime/search') ? 'class=active' : '' }}><a href="{{ fullUrl('/anime/search') }}">Search Anime</a></li>
        <li {{ request()->is('anime') || (request()->is('anime/*') && !request()->is('anime/recent/*') && !request()->is('anime/search')) ? 'class=active' : '' }}><a href="{{ fullUrl('/anime') }}">Browse Anime</a></li>
        <li {{ request()->is('streamers') || request()->is('streamers/*') ? 'class=active' : '' }}><a href="{{ fullUrl('/streamers') }}">Browse Streaming Sites</a></li>
        <li {{ request()->is('about') ? 'class=active' : '' }}><a href="{{ fullUrl('/about') }}">About</a></li>
        <li {{ request()->is('news') ? 'class=active' : '' }}><a href="{{ fullUrl('/news') }}">News</a></li>
      </ul>

      <!-- Right Side Of Navbar -->
      <ul class="nav navbar-nav navbar-right">
        @if (Auth::guest())
          <li {{ request()->is('login') ? 'class=active' : '' }}><a href="{{ fullUrl('/login') }}">Login</a></li>
          <li {{ request()->is('register') ? 'class=active' : '' }}><a href="{{ fullUrl('/register') }}">Register</a></li>
        @else
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
              {{ Auth::user()->name }} <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" role="menu">
              <li><a href="{{ fullUrl('/logout') }}"><i class="fa fa-btn fa-sign-out"></i>Logout</a></li>
            </ul>
          </li>
        @endif
      </ul>

    </div>
  </div>
</nav>
