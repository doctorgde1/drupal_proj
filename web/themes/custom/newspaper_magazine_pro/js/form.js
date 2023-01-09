(function (_ref, $) {
  Drupal.behaviors.Forms = {
    attach: function (context, settings) {
      this.ChangeText();
    },ChangeText: function () {
      jQuery("document").ready(function($){
        console.log('aaa');
        let button = $('.comment-form .button');
        let title = jQuery('.comment-wrapper .title');
        let url = jQuery('.js-form-type-url input');
        let email = jQuery('.form-type-email input');
        let name = jQuery('.form-type-textfield input');
        let comment = jQuery('.form-type-textarea textarea');

        button.val('Post Comment');
        title.text('LEAVE A REPLY');

        url.attr('placeholder', 'Website');
        email.attr('placeholder', 'Email*');
        name.attr('placeholder', 'Name*');
        comment.attr('placeholder', 'Comment');
      });
    }
  };
})(Drupal, jQuery);
