import Swal from 'sweetalert2';

export function showAlert(title, text, icon = 'info') {
    Swal.fire({ title, text, icon });
}
export function showLoader(title = "Loading...", html = "Please wait...", minDuration = 2000) {
    const startTime = Date.now();

    Swal.fire({
        title,
        html,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // Return a function that closes the loader after minDuration
    return async () => {
        const elapsed = Date.now() - startTime;
        const remaining = minDuration - elapsed;
        if (remaining > 0) {
            await new Promise(resolve => setTimeout(resolve, remaining));
        }
        Swal.close();
    };
}