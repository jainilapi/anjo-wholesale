<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  	<link rel="icon" type="image/x-icon" href="{{ Helper::favicon() }}">

	<title> {{ Helper::title() }} - {{ isset($title) ? $title : 'Home' }} </title>

	<style>
		:root {
			--customizable-bg: {{ Helper::bgcolor() }};
		}		
	</style>

    <link href="{{ asset('assets/css/font-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style-dark.min.css') }}?time={{ time() }}" rel="stylesheet">
	<link href="{{ asset('assets/css/custom.css') }}?time={{ time() }}" rel="stylesheet">

	@if(isset($datatable))
		<link rel="stylesheet" href="{{ asset('assets/css/datatable.min.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/css/dataTables.bootstrap5.min.css') }}">
	@endif

	@if(isset($select2))
		<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">
	@endif
		
	@if(isset($datepicker))
		<link rel="stylesheet" href="{{ asset('assets/css/daterangepicker.min.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/css/jquery-ui.min.css') }}">
	@endif

	@if(isset($editor))
		<link rel="stylesheet" href="{{ asset('assets/css/ckeditor.min.css') }}">
	@endif

	@if(isset($dropzone))
		<link rel="stylesheet" href="{{ asset('assets/css/dropzone.min.css') }}">
	@endif

	<link rel="stylesheet" href="{{ asset('assets/css/swal.min.css') }}">
	@stack('css')	
</head>

<body>
	<div class="splash">
		<div class="splash-icon"></div>
	</div>

	<div class="wrapper">
		@include('layouts.sidebar')
		<div class="main">
			<nav class="navbar navbar-expand navbar-theme">
				<a class="sidebar-toggle d-flex me-2">
					<i class="hamburger align-self-center"></i>
				</a>

				<form class="d-none d-sm-inline-block">
					<input class="form-control form-control-lite" type="text" placeholder="Search here...">
				</form>

				<div class="navbar-collapse collapse">
					<ul class="navbar-nav ms-auto">
						<li class="nav-item dropdown ms-lg-2">
							<a class="nav-link dropdown-toggle position-relative" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
								<i class="align-middle fas fa-bell"></i>
								<!-- <span class="indicator"></span> -->
							</a>
							<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
								<div class="dropdown-menu-header">
									No New Notifications
								</div>
							</div>
						</li>
						<li class="nav-item dropdown ms-lg-2">
							<a class="nav-link dropdown-toggle position-relative" href="#" id="userDropdown" data-bs-toggle="dropdown">
								<i class="align-middle fas fa-cog"></i>
							</a>
							<div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
								<a class="dropdown-item" href="{{ route('settings.index') }}"><i class="align-middle me-1 fas fa-fw fa-cogs"></i> Settings</a>
								<div class="dropdown-divider"></div>
								<form action="{{ route('logout') }}" method="POST"> @csrf
									<button class="dropdown-item" type="submit">
										<i class="align-middle me-1 fas fa-fw fa-arrow-alt-circle-right"></i> Sign out
									</button>
								</form>
							</div>
						</li>
					</ul>
				</div>
			</nav>
			<main class="content">
				<div class="container-fluid">

					<div class="header">
						<h1 class="header-title">
							{{ $title ?? '' }}
						</h1>
						<p class="header-subtitle">{{ $subTitle ?? '' }}</p>
					</div>

					@yield('content')

				</div>
			</main>
			@include('layouts.footer')
		</div>
	</div>

	<svg width="0" height="0" style="position:absolute">
		<defs>
			<symbol viewBox="0 0 512 512" id="ion-ios-pulse-strong">
				<path
					d="M448 273.001c-21.27 0-39.296 13.999-45.596 32.999h-38.857l-28.361-85.417a15.999 15.999 0 0 0-15.183-10.956c-.112 0-.224 0-.335.004a15.997 15.997 0 0 0-15.049 11.588l-44.484 155.262-52.353-314.108C206.535 54.893 200.333 48 192 48s-13.693 5.776-15.525 13.135L115.496 306H16v31.999h112c7.348 0 13.75-5.003 15.525-12.134l45.368-182.177 51.324 307.94c1.229 7.377 7.397 11.92 14.864 12.344.308.018.614.028.919.028 7.097 0 13.406-3.701 15.381-10.594l49.744-173.617 15.689 47.252A16.001 16.001 0 0 0 352 337.999h51.108C409.973 355.999 427.477 369 448 369c26.511 0 48-22.492 48-49 0-26.509-21.489-46.999-48-46.999z">
				</path>
			</symbol>
		</defs>
	</svg>
	<script src="{{ asset('assets/js/app.js') }}"></script>


</body>

@if(isset($datatable))
	<script src="{{ asset('assets/js/datatable.min.js') }}"></script>
	<script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
	<script src="{{ asset('assets/js/dataTables.bootstrap5.min.js') }}"></script>
@endif
	
@if(isset($select2))
	<script src="{{ asset('assets/js/select2.min.js') }}"></script>
@endif
	
@if(isset($datepicker))
	<script src="{{ asset('assets/js/daterangepicker.min.js') }}"></script>
	<script src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
@endif

@if(isset($editor))
	<script src="{{ asset('assets/js/ckeditor.min.js') }}"></script>
@endif

@if(isset($dropzone))
	<script src="{{ asset('assets/js/dropzone.min.js') }}"></script>
@endif
	
<script src="{{ asset('assets/js/swal.min.js') }}"></script>
@include('layouts.script')

@stack('js')
</html>