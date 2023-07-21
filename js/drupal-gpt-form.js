(function ($) {
  Drupal.behaviors.drupalGPTCustomBehavior = {
    attach: function (context, settings) {
      // Access the customVariable passed from PHP.
      $(document).ready(function(){
        $('.disable-on-click').on('click', function(){
          $('.disable-on-click').each(function(){
            // Disable the clicked button and any other buttons with the class 'disable-on-click'.
            $(this).prop('disabled', true);
          });
        });
      });
    }
  };
})(jQuery);
