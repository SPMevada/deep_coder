{{-- Shared by allocations/create and allocations/edit --}}
<script>
$(function () {
    const availabilityUrl = "{{ route('api.projectallocations.availability', ':id') }}";
    const ignoreId        = {{ $ignoreId ?? 'null' }};
    const isCreate        = {{ $isCreate ? 'true' : 'false' }};

    $('.select2-field').select2({ width: '100%' });

    /* ---------- Percentage slider ---------- */
    function updateSlider() {
        const value = $('#allocation_percentage').val();
        $('#percentValue').text(value + '%');
        $('#percentBar').css('width', value + '%').attr('aria-valuenow', value);
    }
    $('#allocation_percentage').on('input', updateSlider);

    /* ---------- Project date limits ---------- */
    function selectedProject() { return $('#project_id option:selected'); }

    function applyProjectBounds() {
        const opt = selectedProject();
        const start = opt.attr('data-start');
        const end   = opt.attr('data-deadline');

        if (!start) {
            $('#projectHint').text('');
            $('#start_date, #end_date').removeAttr('min').removeAttr('max');
            return;
        }

        $('#projectHint').text('Project runs ' + opt.attr('data-start-label') + ' to ' + opt.attr('data-deadline-label'));
        $('#start_date').attr({ min: start, max: end });
        $('#end_date').attr({ min: $('#start_date').val() || start, max: end });
    }

    /* ---------- Availability (peak allocation of the employee) ---------- */
    function checkAvailability() {
        const userId = $('#user_id').val();
        const start  = $('#start_date').val();
        const end    = $('#end_date').val() || selectedProject().attr('data-deadline');
        const box    = $('#availabilityBox');

        if (!userId || !start || !end) {
            box.addClass('d-none');
            return;
        }

        $.get(availabilityUrl.replace(':id', userId), {
            start_date: start,
            end_date:   end,
            ignore:     ignoreId
        }).done(function (r) {
            const cls = r.available === 0 ? 'alert-danger' : (r.available < 50 ? 'alert-warning' : 'alert-success');

            box.removeClass('d-none alert-success alert-warning alert-danger').addClass(cls)
               .html('<i class="bi bi-info-circle me-1"></i>Already allocated <b>' + r.used +
                     '%</b> in this period &middot; Available <b>' + r.available + '%</b>' +
                     (r.available === 0 ? ' &mdash; this employee is fully allocated.' : ''));

            // Do not let the slider go above what is free
            const max = Math.max(1, r.available);
            $('#allocation_percentage').attr('max', max);
            if (parseInt($('#allocation_percentage').val()) > max) {
                $('#allocation_percentage').val(max);
            }
            updateSlider();
        }).fail(function () {
            box.addClass('d-none');
        });
    }

    /* ---------- Events ---------- */
    $('#project_id').on('change', function () {
        applyProjectBounds();

        // On create, suggest a start date (today, or the project start if it is later)
        const projectStart = selectedProject().attr('data-start');
        if (isCreate && projectStart && !$('#start_date').val()) {
            const today = new Date().toISOString().slice(0, 10);
            $('#start_date').val(today > projectStart ? today : projectStart);
            applyProjectBounds();
        }
        checkAvailability();
    });

    $('#user_id, #end_date').on('change', checkAvailability);

    $('#start_date').on('change', function () {
        $('#end_date').attr('min', $(this).val());
        if ($('#end_date').val() && $('#end_date').val() < $(this).val()) {
            $('#end_date').val('');
        }
        checkAvailability();
    });

    /* ---------- AJAX submit ---------- */
    $('#allocationForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const btn  = $('#submitBtn');

        btn.prop('disabled', true)
           .html('<span class="spinner-border spinner-border-sm me-1"></span> {{ $busyText }}');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',               // _method=PUT is inside the serialized form on edit
            data: form.serialize(),
            dataType: 'json',

            success: function (response) {
                if (response.success) {
                    successToast(response.message);
                    window.location.href = "{{ route('projectallocations.index') }}";
                } else {
                    errorToast(response.message || 'Unable to save allocation.');
                    resetButton();
                }
            },

            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        errorToast(messages[0]);
                    });
                } else {
                    errorToast(xhr.responseJSON?.message || 'Something went wrong.');
                }
                resetButton();
            }
        });

        function resetButton() {
            btn.prop('disabled', false)
               .html('<i class="bi bi-check-circle me-1"></i> {{ $submitText }}');
        }
    });

    /* ---------- Initial state ---------- */
    applyProjectBounds();
    updateSlider();
    checkAvailability();
});
</script>