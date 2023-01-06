(function (_ref, $) {
  Drupal.behaviors.Forms = {
    attach: function (context, settings) {
      this.ChangeText();
    },ChangeText: function () {
      jQuery("document").ready(function($){
        let button = $('.comment-form .button');
        console.log(button);
        button.val('Post Comment');
      });
    }
  };
})(Drupal, jQuery);
