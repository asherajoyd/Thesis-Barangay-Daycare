$(document).ready(function () {
    $('#editAcademicYearModal').on('show.bs.modal', function (event) {

        const button = $(event.relatedTarget);

        $('#edit_id').val(button.data('id'));
        $('#edit_academic_year').val(button.data('academic-year'));
        $('#edit_start_date').val(button.data('start-date'));
        $('#edit_end_date').val(button.data('end-date'));

    });
});