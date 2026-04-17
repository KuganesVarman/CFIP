<h1>Welcome, {{ session('name') }}</h1>
<p>Your role: {{ session('role') }}</p>
<a href="{{ route('logout') }}">Logout</a>
