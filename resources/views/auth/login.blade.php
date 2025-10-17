<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title> {{ Helper::title() }} - @yield('title') </title>

    <link href="{{ asset('assets/css/style-dark.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/intel-tel.css') }}" rel="stylesheet">

<style>
	div.iti--inline-dropdown {
		min-width: 100%!important;
	}
	.iti__selected-flag {
		height: 32px!important;
	}
	.iti--show-flags {
		width: 100%!important;
	}  
	label.error {
		color: red;
	}
	#phone_number{
		font-family: "Hind Vadodara",-apple-system,BlinkMacSystemFont,"Segoe UI","Helvetica Neue",Arial,sans-serif;
		font-size: 15px;
	}
</style>

</head>
<body class="theme-blue" id="login-svg">
	<div class="splash">
		<div class="splash-icon"></div>
	</div>

	<main class="main h-100 w-100">
		<div class="container h-100">
			<div class="row h-100">
				<div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">

					
						<div class="card" style="border-radius:32px;">
							<div class="card-body">
								<div class="m-sm-4">
                                    <form method="POST" action="{{ route('login') }}" id="login-form">
                                        @csrf

										<div class="mb-3">
											<h2 align="center"> {{ Helper::title() }} </h2>
										</div>

										<div class="mb-3">
											<label class="form-label mb-2">Phone Number</label> <br>
                                            <input type="hidden" name="dial_code" id="country_dial_code">
                                            {{-- <input type="text" name="fake_phone" id="fake_phone" style="display:none" autocomplete="off"> --}}
                                            <input id="phone_number"
                                                name="phone_number"
                                                type="text"
                                                class="form-control phone_number form-control-lg @error('phone_number') is-invalid @enderror"
                                                placeholder="Enter your phone number"
                                                autocomplete="new-phone">

											@if ($errors->has('phone_number'))
												<span class="text-danger d-block">{{ $errors->first('phone_number') }}</span>
											@endif

										</div>
										<div class="mb-3">
											<label class="form-label mb-2">Password</label>
                                            {{-- <input type="password" name="fake_pass" id="fake_pass" style="display:none" autocomplete="off">                                             --}}
											<input name="password"
                                            type="password"
                                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                                            placeholder="Enter your password"
                                            autocomplete="new-password">

											@if ($errors->has('password'))
												<span class="text-danger d-block mt-2">{{ $errors->first('password') }}</span>
											@elseif(session()->has('error'))
												<span class="text-danger d-block mt-2">{{ session()->get('error') }}</span>
											@endif
										</div>
										<div>
											<div class="form-check align-items-center">
												<input id="customControlInline" type="checkbox" class="form-check-input" value="remember-me" name="remember"
													{{ old('remember') ? 'checked' : '' }}>
												<label class="form-check-label text-small" for="customControlInline">Remember me next time</label>
											</div>
										</div>
										<div class="text-center mt-3">
											<button type="submit" class="btn btn-lg btn-primary">Sign in</button>
										</div>
									</form>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</main>

	<svg width="0" height="0" style="position:absolute">
		<defs>
			<symbol viewBox="0 0 512 512" id="ion-ios-pulse-strong">
				<path
					d="M448 273.001c-21.27 0-39.296 13.999-45.596 32.999h-38.857l-28.361-85.417a15.999 15.999 0 0 0-15.183-10.956c-.112 0-.224 0-.335.004a15.997 15.997 0 0 0-15.049 11.588l-44.484 155.262-52.353-314.108C206.535 54.893 200.333 48 192 48s-13.693 5.776-15.525 13.135L115.496 306H16v31.999h112c7.348 0 13.75-5.003 15.525-12.134l45.368-182.177 51.324 307.94c1.229 7.377 7.397 11.92 14.864 12.344.308.018.614.028.919.028 7.097 0 13.406-3.701 15.381-10.594l49.744-173.617 15.689 47.252A16.001 16.001 0 0 0 352 337.999h51.108C409.973 355.999 427.477 369 448 369c26.511 0 48-22.492 48-49 0-26.509-21.489-46.999-48-46.999z">
				</path>
			</symbol>
		</defs>
	</svg>

</body>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/intel-tel.js') }}"></script>
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>

<script src="{{ asset('assets/js/p5.min.js') }}"></script>
<script src="{{ asset('assets/js/vanta.topology.min.js') }}"></script>
<script>

	$(document).ready(function() {

		VANTA.TOPOLOGY({
			el: "#login-svg",
			mouseControls: true,
			touchControls: true,
			gyroControls: false,
			minHeight: 200.00,
			minWidth: 200.00,
			scale: 1.00,
			scaleMobile: 1.00,
			color: 0xffea00,
			backgroundColor: "{{ Helper::bgcolor() }}"
		})		

		const input = document.querySelector('#phone_number');
		const errorMap = ["Phone number is invalid.", "Invalid country code", "Too short", "Too long"];
		const form = input.closest("form");

		const iti = window.intlTelInput(input, {
			initialCountry: "in",
			separateDialCode:true,
			nationalMode:false,
			preferredCountries: @json(\App\Models\Country::select('iso2')->pluck('iso2')->toArray()),
			utilsScript: "{{ asset('assets/js/intel-tel-2.min.js') }}"
		});		

		$.validator.addMethod('inttel', function (value, element) {
			if (value.trim() != '') {
				return true;
			}
			
			return false;
		}, function (result, element) {
				return errorMap[iti.getValidationError()] || errorMap[0];
		});

		input.addEventListener("countrychange", function() {
			if (iti.isValidNumber()) {
				$('#country_dial_code').val(iti.s.dialCode);
			}
		});

		input.addEventListener('keyup', () => {
			if (iti.isValidNumber()) {
				$('#country_dial_code').val(iti.s.dialCode);
			}
		});

		$(document).ready(function () {
			$('#login-form').validate({
				rules: {
					'phone_number': {
						required: true,
						inttel: true
					},
					'password': {
						required: true
					}
				},
				messages: {
					'phone_number': {
						required: 'Phone number is required.'
					},
					'password': {
						required: 'Password is required.'
					}
				},
				errorPlacement: function(error, element) {
					if (element.hasClass('phone_number')) {
						error.insertAfter(element.parent("div"));
					} else {
						error.appendTo(element.parent("div"));					
					}
				},
				submitHandler: function (form) {
					$('#country_dial_code').val(iti.s.dialCode);
					form.submit();
				}
			});
		});

	});

</script>

</html>