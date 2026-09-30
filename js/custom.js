/*------------------------------------------------------------------
    File Name: custom.js
    Template Name: Pluto - Responsive HTML5 Template
    Created By: html.design
    Envato Profile: https://themeforest.net/user/htmldotdesign
    Website: https://html.design
    Version: 1.0
-------------------------------------------------------------------*/

/*--------------------------------------
	sidebar
--------------------------------------*/

"use strict";

$(document).ready(function () {
  /*-- sidebar js --*/
  $('#sidebarCollapse').on('click', function () {
    $('#sidebar').toggleClass('active');
    $('#content').toggleClass('active');
  });

  /*-- calendar js --*/
  if ($('#example14').length > 0) {
    $('#example14').calendar({
      inline: true
    });
  }
  if ($('#example15').length > 0) {
    $('#example15').calendar();
  }

  /*-- tooltip js --*/
  $('[data-toggle="tooltip"]').tooltip();
});

/*--------------------------------------
    scrollbar js
--------------------------------------*/

$(window).on('load', function() {
  if ($('#sidebar').length > 0 && typeof PerfectScrollbar !== 'undefined') {
    var ps = new PerfectScrollbar('#sidebar');
  }
});