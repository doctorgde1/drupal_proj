(function (_ref, $) {
  Drupal.behaviors.Scroll = {
    attach: function (context, settings) {
      this.ScrollSidebar();
    },ScrollSidebar: function () {
      jQuery("document").ready(function($){
        let element = jQuery(".view-id-latest_articles_sidebar");
        let sidebar = jQuery(".row-1");
        let sidebar_right = jQuery(".region-sidebar-first");

        let sidebar_bottom = sidebar.offset().top + sidebar.outerHeight();

        $(window).scroll(function () {
          if ($(this).scrollTop() >= element.offset().top) {
            element.addClass("active");
          }
          if($(this).scrollTop() >= sidebar.outerHeight() - element.outerHeight()) {
            element.removeClass("active");
            sidebar_right.addClass("active-bottom");
          }
          if ($(this).scrollTop() <= sidebar.offset().top + element.outerHeight()) {
            element.removeClass("active");
          }
        });
      });
    }
  };
})(Drupal, jQuery);
