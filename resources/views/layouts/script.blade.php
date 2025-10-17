<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on("select2:open", () => {
        document.querySelector(".select2-container--open .select2-search__field").focus()
    })

    $.validator.addMethod("maxFiles", function(value, element, param) {
        return element.files.length <= param;
    }, "Maximum 10 files can be uploaded.");

    $.validator.addMethod("fileSizeLimit", function(value, element, param) {
        var totalSize = 0;
        var files = element.files;
        for (var i = 0; i < files.length; i++) {
            totalSize += files[i].size;
        }
        return totalSize <= param;
    }, "Total file size must not exceed 20 MB");
	
	function isNumeric(arg) {
        try {
            if (typeof arg !== 'undefined' && arg !== '' && arg !== null && !isNaN(arg)) {
                return true;
            }
            return false;
        } catch (err) {
            return false;
        }
    }

    function isNotEmpty(arg) {
        try {
            if (typeof arg !== 'undefined' && typeof arg == 'string' && arg !== '' && arg !== null) {
                return true;
            }
            return false;
        } catch (err) {
            return false;
        }
    }

    var hasSessionMessage = "{{session()->has('message') ? true : false}}";
    var hasSessionError = "{{session()->has('error') ? true : false}}";
    var hasSessionWarning = "{{session()->has('warning') ? true : false}}";
    var hasSessionSuccess = "{{session()->has('success') ? true : false}}";

    if (hasSessionMessage) {
        fireInfoMessage("{{ session('message') }}");
    }

    if (hasSessionError) {
        fireErrorMessage("{!! session('error') !!}");
    }

    if (hasSessionWarning) {
        fireWarningMessage("{!! session('warning') !!}");
    }

    if (hasSessionSuccess) {
        fireSuccessMessage("{{ session('success') }}");
    }
    
    function fireInfoMessage(message) {
        Swal.fire('Success', message, 'info');
    }

    function fireSuccessMessage(message) {
        Swal.fire('Success', message, 'success');
    }

    function fireErrorMessage(message) {
        Swal.fire('Error', message, 'error');
    }

    function fireWarningMessage(message) {
        Swal.fire('Warning', message, 'warning');
    }    

</script>