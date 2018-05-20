var apiUrl = "http://localhost:9000/src/";
var insertedRecords = 0;
var errorCount = 0;
var allErrorMsg = "";

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

      var seedCount = results.data.seedCount;
      var processedCount = seedCount;

      $('#insertedRowsMsg').html(seedCount+ " files created for seeding. Preparing for insert into DB...");

      for(var i=0; i < seedCount; i++) {
        $.post(
          apiUrl+"app.php?action=populateDB",
          { "seedNumber" : i }
        ).done(function(results) {
          if(results.success) {
            var recordsProcessed = results.data.timeTakenInfo.length - results.error.length;
            insertedRecords += recordsProcessed;
            $('#insertedRowsMsg').html(insertedRecords + " rows inserted into DB...");
          } else {
            allErrorMsg += results.error[0]+"<br>";
            $('#errorRowsMsg').html(allErrorMsg);
            errorCount++;
          }

          processedCount -=1;

          if(processedCount === 0) {
            $('#insertedRowsMsg').html("");
            $('#completeMsg').html("A total of " + insertedRecords + " rows were inserted into DB. We. Are. Done!<br><br>");
            // $.post(
            //   apiUrl+"app.php?action=deleteAllCSV",
            //   { "seedCount" : seedCount }
            // ).done(function(results) {
            //   $('#completeMsg').html("A total of " + insertedRecords + " rows were inserted into DB. We. Are. Done!");
            // });
          }
        });
      }
    });
  }
});