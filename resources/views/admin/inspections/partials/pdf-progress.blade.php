<div class="modal fade" id="inspection-pdf-modal" tabindex="-1" aria-labelledby="inspection-pdf-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="inspection-pdf-title">Preparing inspection report</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <p id="inspection-pdf-message" class="mb-2">Starting…</p>
            <div class="progress" style="height: 22px"><div id="inspection-pdf-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 1%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="100">1%</div></div>
            <p class="small text-muted mt-2 mb-0">You can leave this window open while photos are optimized in the background.</p>
        </div>
    </div></div>
</div>
@once
@push('scripts')
<script>
document.addEventListener('click', async event => {
    const button = event.target.closest('[data-inspection-pdf-generate]');
    if (!button) return;
    event.preventDefault();
    const modalElement = document.getElementById('inspection-pdf-modal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const bar = document.getElementById('inspection-pdf-bar'), message = document.getElementById('inspection-pdf-message');
    modal.show(); button.disabled = true;
    const update = state => {
        const percent = Number(state.percent || 0);
        bar.style.width = percent + '%'; bar.textContent = percent + '%'; bar.setAttribute('aria-valuenow', percent);
        message.textContent = state.message || 'Working…';
        bar.classList.toggle('bg-danger', state.status === 'failed');
    };
    try {
        const response = await fetch(button.dataset.generateUrl, {method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}});
        if (!response.ok) throw new Error('Could not start report generation.');
        const poll = async () => {
            const statusResponse = await fetch(button.dataset.statusUrl, {headers:{'Accept':'application/json'}});
            if (!statusResponse.ok) throw new Error('Could not read report progress.');
            const state = await statusResponse.json(); update(state);
            if (state.status === 'ready') { bar.classList.remove('progress-bar-animated'); button.disabled = false; window.location.assign(state.download_url); return; }
            if (state.status === 'failed') { button.disabled = false; return; }
            window.setTimeout(poll, 1200);
        };
        poll();
    } catch (error) { update({status:'failed',percent:100,message:error.message}); button.disabled = false; }
});
</script>
@endpush
@endonce
