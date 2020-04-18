<div class="row">
<nav style="background-color: #1b262d"; class="navbar navbar-dark navbar-laravel">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('app.name', 'Laravel') }}
        </a>
        <!-- <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button> -->

        <div class="navbar">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav mr-auto" style="-webkit-box-direction: horizontal !important; flex-direction: row !important;">
                <li class="nav-item float-left">
                  <a class="nav-link" style="margin-right: 20px;" href="{{route('dashboard')}}">Dashboard <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" style="margin-right: 20px;" href="/alerts">Alerts <span class="sr-only">(current)</span></a>
                </li>

                <li class="nav-item">
                  <a class="nav-link" style="margin-right: 0px;" href="/coins">Coins <span class="sr-only">(current)</span></a>
                </li>
            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ml-auto">
            </ul>
        </div>
    </div>
</nav>
</div>