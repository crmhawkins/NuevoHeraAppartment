<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })
    @if (session()->has('toast'))

        Toast.fire({
            icon: "{{session('toast')['icon']}}",
            title: "{{session('toast')['mensaje']}}"
        })
    @endif


</script>
