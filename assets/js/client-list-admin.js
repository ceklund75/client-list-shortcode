(function ($) {
  $(document).ready(function () {
    $('#cls_select_file_button').on('click', function (e) {
      e.preventDefault();
      const frame = wp.media({
        multiple: false
      }).open();

      frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        $('#cls_client_list_file').val(attachment.url);
      });
    });
  });
})(jQuery);