(function (_ref, $) {
  Drupal.behaviors.Forms = {
    attach: function (context, settings) {
      this.ChangeText();
    },ChangeText: function () {
      jQuery("document").ready(function($){
        let button = $('.comment-form .button');
        let title = jQuery('.comment-wrapper .title');

        button.val('Post Comment');
        title.text('LEAVE A REPLY');
      });
    }
  };
})(Drupal, jQuery);
