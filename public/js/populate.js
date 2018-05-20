var apiUrl = "http://localhost:9000/src/";
var insertedRecords = 0;

$(function () {

  $('#populateBtn').click(function() {
    console.log("Populate Button clicked");
    $('#populateBtn').prop('disabled', true);
    populate();
  });

  function populate()
  {
    $('#insertedRowsMsg').html("Processing data file and creating seed files for DB. Patience this will take time...");
    $.post(
      apiUrl+"app.php?action=createCSVFiles"
    ).done(function(results) {

      console.log("success");
      console.log(results);
      var seedCount = results.data.seedCount;

      $('#insertedRowsMsg').html(seedCount+ "file created for seeding. Preparing for insert into DB...");

      for(var i=0; i < seedCount; i++) {
        $.post(
          apiUrl+"app.php?action=populateDB",
          { "seedNumber" : i }
        ).done(function(results) {

          console.log("success");
          console.log(results);
          if(results.success) {
            var recordsProcessed = results.data.timeTakenInfo.length - results.error.length;
            insertedRecords += recordsProcessed;
            $('#insertedRowsMsg').html(insertedRecords + " rows inserted into DB...");
          } else {
            console.log("Error: " + results.error);
          }
        });
      }
    });
  }
});