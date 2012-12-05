// Inspired by http://windows.github.com/release-notes.html
// Slightly better through some basic markdown regex.
$(function () {
  $.ajax({
    url: 'https://raw.github.com/Nijikokun/fluf/master/changelog.jsonp',
    dataType: 'jsonp'
  })
});

function jsonParse (data) {
  var regex = [
    { // Tags
      expr: /^([\w]+):/i,
      replace: '<em class="$1">$1</em>'
    },

    { // Strong
      expr: /(\*\*|__)(?=\S)([^\*\_]+?[*_]*)(?!=\S)\1/g,
      replace: '<strong>$2</strong>'
    },

    { // Italics
      expr: /(\*|_)(?=\S)([^\*\_]+?)(?!=\S)\1/g,
      replace: '<em>$2</em>'
    },

    { // Inline Code
      expr: /(\`)(?=\S)([^\`]+?)(?!=\S)\1/g,
      replace: '<code>$2</code>'
    },

    { // Links
      expr: /\[([^\]]+)\]\(([^)]+)\)/g,
      replace: '<a href="$2">$1</a>'
    }
  ];

  var releases = "";
  $.each(data.releases, function (i, r) {
    releases += "<li>";
    releases += "<span>" + r.version + "</span>";
    releases += "<h2" + (
      r.date ? ' datetime="' + new Date(r.date).toString() + '"' : ''
    ) + ">" + r.description + "</h2>";
    releases += "<ul class='changes'>";

    $.each(r.changes, function (j, change) {
      for (var k = 0; k < regex.length; k++) {
        change = change.replace(regex[k].expr, regex[k].replace);
      }

      releases += "<li>" + change + "</li>"
    });

    releases += "</ul></li>";
  });

  $("#changelog").html("<ul class='releases'>" + releases + "</ul>");
};