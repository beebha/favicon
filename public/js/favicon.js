var apiUrl = "http://localhost:9000/src/";

$(function () {

  $('#faviconResults').hide();

  $('#websiteUrl').keyup(function(e) {
    if ($.trim($(this).val()) == '') {
      $('#submitBtn').prop('disabled', true);
    } else {
      $('#submitBtn').prop('disabled', false);
    }

    if (e.which === 13) {
      findFavicon();
      return false;
    }
  });

  $('#submitBtn').click(function() {
    console.log("Favicon Find Button clicked");
    findFavicon();
  });

  function findFavicon()
  {
    $('#submitBtn').prop('disabled', true);
    $.post(
      apiUrl+"app.php?action=findFavicon",
      {
        "websiteUrl": $.trim($('#websiteUrl').val())
      }
    ).done(function(results) {
      console.log("success finding favicon icon");
      console.log(results);
      if(results.success) {
        $('#validWebsiteUrl').html(results.data.websiteUrl);
        $('#faviconImg').attr("src", results.data.faviconUrl);
        $('#faviconImgSuccess').show();
        $('#faviconImgError').hide();
      } else {
        $('#faviconImgError').html(results.error);
        $('#faviconImgSuccess').hide();
        $('#faviconImgError').show();
      }
    }).always(function() {
      $('#websiteUrl').val("");
      $('#submitBtn').prop('disabled', true);
      $('#faviconResults').show();
    });
  }

});