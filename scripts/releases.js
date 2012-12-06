// Inspired by http://windows.github.com/release-notes.html
// Slightly better through some basic markdown regex.
$(function () {
  $.ajax({
    url: 'https://raw.github.com/Nijikokun/fluf/master/changelog.jsonp',
    dataType: 'jsonp'
  })
});

/**
 * Markify is a slim-line markdown text processor that supports issue(s) links, links, strong, and italics.
 *
 * Options:
 *   - **options.issues.url** Gets parsed by issue(s) regex and replaces the `:id` tag with issue id.
 *   Allowing you to support more than just github issue url(s).
 *   - **options.skip** Tells `markify.process` to skip `this.regex[n].type` if `type` string is present
 *   in this array.
 *
 * Methods:
 *   - **add(type, expression, replace)** adds regex rule to this.regex for iteration when `process` is invoked.
 *   - **remove(type)** remove regex rule via `type` key
 *   - **skip(type)** adds `type` to `options.skip` array
 *   - **unskip(type)** remove `type` from `options.skip` if exists in element list
 *   - **process(string)** iterate through `this.regex` rules and apply them against `string`
 *
 * Built for fluf(php) release-notes as seen here:
 *   
 *   http://nijikokun.github.com/fluf/release-notes.html
 *
 * @param {Object} options Object containing, options and details
 * @return {Object}
 * @package utils
 * @author Nijiko Yonskai <nijikokun@gmail.com>
 * @year 2012
 * @license AOL <http://aol.nexua.org>
 */
var markify = function (options) {
  options = options || {};
  this.options = {
    issues: options.issues || {
      url: 'http://github.com/Nijikokun/fluf/issues/:id'
    },
    skip: options.skip || []
  };

  this.regex = [
    { // Tags
      type: 'tags',
      expr: /^([\w]+):/i,
      replace: '<em class="$1">$1</em>'
    },

    { // Strong
      type: 'strong',
      expr: /(\*\*|__)(?=\S)([^\*\_]+?[*_]*)(?!=\S)\1/g,
      replace: '<strong>$2</strong>'
    },

    { // Italics
      type: 'italics',
      expr: /(\*|_)(?=\S)([^\*\_]+?)(?!=\S)\1/g,
      replace: '<em>$2</em>'
    },

    { // Inline Code
      type: 'inline-code',
      expr: /(\`)(?=\S)([^\`]+?)(?!=\S)\1/g,
      replace: '<code>$2</code>'
    },

    { // Links
      type: 'links',
      expr: /\[([^\]]+)\]\(([^)]+)\)/g,
      replace: '<a href="$2">$1</a>'
    },

    { // Issue (Single)
      type: 'issue',
      expr: /issue\:\#?([\d]+)/gi,
      replace: function (match, id) {
        return 'issue #<a href="' + this.options.issues.url.replace(':id', id) + '">' + id + '</a>';
      }
    },

    { // Issues (Multiple), a little more complex.
      type: 'issues',
      expr: /issues\:([\d\#\,]+)/gi,
      replace: function (match, stringent) {
        var arr = stringent.match(/([\d]+)/gi), i = 0, result = '';
        function replacer (id) { return '#<a href="' + this.options.issues.url.replace(':id', id) + '">' + id + '</a>, '; }
        if (arr.length > 1) for (i; i < arr.length; i++) result += replacer(arr[i]);
        return result.length > 1 ? 'issues ' + result.substr(0, result.length-2) : '';
      }
    }
  ];

  this.add = function (type, expr, replace) {
    this.regex.push({ type: type, expr: expr, replace: replace });
  };

  this.skip = function (type) {
    this.options.skip.push(type);
  };

  this.unskip = function (type) {
    return this.remove(type, this.options.skip);
  };

  this.remove = function (type, arr) {
    if (!arr) arr = this.regex;
    if (arr.length < 1) return false;
    for (var i = 0; i < arr.length; i++)
      if (arr[i].type === type)
        return arr.splice(i, 1);
  };

  this.process = function (string) {
    for (var i = 0; i < this.regex.length; i++)
      if (this.options.skip.indexOf(this.regex[i].type) === -1)
        string = string.replace(regex[i].expr, regex[i].replace);
    return string;
  };

  return this;
};

function jsonParse (data) {
  var parser = markify();

  var releases = "";
  $.each(data.releases, function (i, r) {
    releases += "<li>";
    releases += "<span>" + r.version + "</span>";
    releases += "<h2" + (
      r.date ? ' datetime="' + new Date(r.date).toString() + '"' : ''
    ) + ">" + r.description + "</h2>";
    releases += "<ul class='changes'>";

    $.each(r.changes, function (j, change) {
      change = parser.process(change);
      releases += "<li>" + change + "</li>";
    });

    releases += "</ul></li>";
  });

  $("#changelog").html("<ul class='releases'>" + releases + "</ul>");
};